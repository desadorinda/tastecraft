<?php
session_start();
require_once __DIR__ . '/includes/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Auth checks
$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $isLoggedIn ? (int)$_SESSION['user_id'] : null;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle Save/Unsave via POST (with CSRF). Supports AJAX and non-AJAX.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    $response = ['ok' => false];

    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $recipeId = (int)($_POST['recipe_id'] ?? 0);
        if ($recipeId <= 0) {
            throw new RuntimeException('Invalid recipe.');
        }

        if (isset($_POST['save_recipe'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)");
            $stmt->execute([$userId, $recipeId]);
            $response = ['ok' => true, 'action' => 'saved'];
        } elseif (isset($_POST['unsave_recipe'])) {
            $stmt = $pdo->prepare("DELETE FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
            $stmt->execute([$userId, $recipeId]);
            $response = ['ok' => true, 'action' => 'unsaved'];
        }
    } catch (Throwable $e) {
        $response = ['ok' => false, 'error' => $e->getMessage()];
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Fallback: reload page with current filters
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        header("Location: recipes.php" . ($qs ? '?' . $qs : ''));
        exit;
    }
}

// Fetch categories
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Filters
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';
$searchTerm     = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$currentPage    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$recipesPerPage = 6;
$offset         = ($currentPage - 1) * $recipesPerPage;

// Base query
$sql = "SELECT r.id, r.title, r.description, r.cooking_time, r.image, r.rating,
               c.name AS category, c.id AS category_id, r.created_at
        FROM recipes r
        JOIN categories c ON r.category_id = c.id
        WHERE 1";
$params = [];

// Category filter
if ($categoryFilter !== 'all' && is_numeric($categoryFilter)) {
    $sql .= " AND c.id = ?";
    $params[] = (int)$categoryFilter;
}

// Search filter
if ($searchTerm !== '') {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

// Count total
$countSql = "SELECT COUNT(*) FROM ($sql) AS t";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRecipes = (int)$stmt->fetchColumn();
$totalPages   = max(ceil($totalRecipes / $recipesPerPage), 1);
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

// Final query with pagination
$sql .= " ORDER BY r.created_at DESC LIMIT $recipesPerPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$paginatedRecipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Saved status for current user
$savedIds = [];
if ($isLoggedIn) {
    $sstmt = $pdo->prepare("SELECT recipe_id FROM saved_recipes WHERE user_id = ?");
    $sstmt->execute([$userId]);
    $savedIds = array_map('intval', $sstmt->fetchAll(PDO::FETCH_COLUMN));
}

// Helper to keep query params while changing page
function buildUrl($paramsOverride = [])
{
    $params = $_GET;
    foreach ($paramsOverride as $k => $v) {
        if ($v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return 'recipes.php' . (empty($params) ? '' : '?' . http_build_query($params));
}
ob_start()
?>

  <style>
    :root {
      --primary-dark: #000000ff;
      --primary: #000000ff;
      --secondary: #f59e0b;
      --secondary-light: #fcd34d;
      --accent: #b45309;
      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --card-bg: rgba(0, 0, 0, 0.7);
      --hover-bg: rgba(245, 158, 11, 0.1);
      --border-soft: rgba(0, 0, 0, 0.25);
    }

    /* Animations */
    @keyframes gradientShift { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
    @keyframes shimmer { 0%{left:-100%} 100%{left:100%} }
    @keyframes float { 0%{transform:translateY(0)} 50%{transform:translateY(-8px)} 100%{transform:translateY(0)} }

    body {
      background: radial-gradient(1200px 600px at top left, rgba(245, 158, 11, 0.07), transparent),
                  linear-gradient(180deg, var(--primary-dark), var(--primary));
      color: var(--text-primary);
      min-height: 100vh;
    }

    /* Top Nav */
    .nav-gradient{
      background: linear-gradient(105deg, var(--primary-dark) 0%, var(--primary) 45%, var(--primary-dark) 60%, var(--accent) 80%, var(--secondary) 100%);
      background-size: 200% 200%;
      animation: gradientShift 10s ease infinite;
      box-shadow: 0 4px 30px rgba(180,83,9,0.25);
      position: sticky;
      top: 0; left: 0; width: 100%;
      z-index: 50;
      border-bottom: 1px solid var(--border-soft);
    }
    .nav-gradient::before{
      content:'';
      position:absolute;top:0;left:-100%;width:100%;height:100%;
      background:linear-gradient(90deg,transparent,rgba(245,158,11,0.12),transparent);
      animation:shimmer 3.5s infinite;
    }
    .nav-link{ padding:.5rem 1rem; border-radius: 8px; color: var(--text-secondary); transition: all .25s ease; }
    .nav-link:hover{ color: var(--secondary); background: rgba(245,158,11,.08); }

    /* Card */
    .card {
      background: var(--card-bg);
      border: 1px solid var(--border-soft);
      border-radius: 14px;
      box-shadow: 0 8px 24px rgba(2, 6, 23, 0.35);
      backdrop-filter: blur(6px);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(2, 6, 23, 0.45); }

    .chip {
      display: inline-flex; align-items: center; gap:.35rem;
      padding: .25rem .5rem; border-radius: 9999px;
      background: rgba(245, 158, 11, .15);
      border: 1px solid rgba(245, 158, 11, .35);
      color: var(--secondary-light);
      font-size: .75rem; font-weight: 700;
    }

    /* Filters */
    .filters { display:flex; gap:.5rem; flex-wrap: wrap; }
    .filter-chip {
      padding:.45rem .8rem; border-radius: 9999px; border:1px solid var(--border-soft);
      color: var(--text-secondary); background: rgba(2,6,23,.35); transition: all .15s ease; font-weight:800; white-space:nowrap;
    }
    .filter-chip:hover { background: rgba(148,163,184,.08); color: var(--text-primary); }
    .filter-chip.active { background: var(--secondary); color:#111827; border-color: rgba(245,158,11,.6); }

    /* Recipe Card */
    .recipe-card { overflow: hidden; position: relative; display:flex; flex-direction:column; height:100%; }
    .recipe-cover { position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 16 / 9; background: rgba(2,6,23,.4); border: 1px solid var(--border-soft); }
    .recipe-cover img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .35s ease; }
    .recipe-card:hover .recipe-cover img { transform: scale(1.03); }
    .recipe-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(2,6,23,0) 20%, rgba(2,6,23,0.65) 70%, rgba(2,6,23,0.9) 100%); }
    .title-link { color: var(--text-primary); }
    .title-link:hover { color: var(--secondary-light); }

    .save-btn {
      position:absolute; top:12px; right:12px; z-index:5;
      width:42px; height:42px; border-radius: 50%;
      display:flex; align-items:center; justify-content:center;
      background: rgba(2,6,23,.6); color: var(--secondary); border:1px solid var(--border-soft);
      transition: all .2s ease;
    }
    .save-btn:hover { background: var(--secondary); color:#111827; }
    .save-btn.saved { background: var(--secondary); color:#111827; }

    /* Search */
    .search-box {
      display: flex; align-items: center; gap: .5rem;
      background: rgba(2, 6, 23, 0.35);
      border: 1px solid rgba(148,163,184,.2);
      padding: .6rem .75rem; border-radius: 9999px; width: 100%; max-width: 420px;
      color: var(--text-primary);
    }
    .search-box input { background: transparent; border: none; outline: none; color: var(--text-primary); width: 100%; }
    .search-box input::placeholder { color: var(--text-secondary); }
    .search-btn {
      display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%;
      background: var(--secondary); color:#111827; border:none; cursor:pointer;
    }

    /* Pagination */
    .pagination { display:flex; gap:.4rem; align-items:center; justify-content:center; flex-wrap: wrap; }
    .page-btn {
      padding:.45rem .8rem; border-radius:10px; border:1px solid var(--border-soft);
      color: var(--text-secondary); background: rgba(2,6,23,.35); transition: all .15s ease; font-weight:800;
    }
    .page-btn:hover { background: rgba(148,163,184,.08); color: var(--text-primary); }
    .page-btn.active { background: var(--secondary); color:#111827; border-color: rgba(245,158,11,.6); }
    .page-btn[aria-disabled="true"] { opacity:.5; pointer-events:none; }

    /* Footer */
    .footer-gradient{
      background: linear-gradient(355deg, var(--primary-dark) 0%, var(--primary) 35%, var(--primary-dark) 60%, var(--accent) 80%, var(--secondary) 100%);
      background-size: 200% 200%;
      animation: gradientShift 10s ease infinite;
      border-top: 1px solid var(--border-soft);
    }

    .floating { animation: float 4.5s ease-in-out infinite; }
  </style>
</head>
<body>



  <!-- Header -->
  <section class="py-10 px-6 text-center">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-4xl md:text-5xl font-extrabold mb-3">Our <span style="color: var(--secondary-light);">Recipe Collection</span></h1>
      <p class="text-lg mb-8" style="color: var(--text-secondary);">Discover delicious recipes from around the world.</p>

      <div class="flex flex-col md:flex-row items-center justify-center gap-4 mb-8">
        <!-- Search -->
        <form method="GET" class="relative w-full md:w-[480px]">
          <div class="search-box">
            <i class="fas fa-search" style="color: var(--text-secondary);"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm); ?>" placeholder="Search recipes...">
            <button class="search-btn" aria-label="Search"><i class="fas fa-arrow-right"></i></button>
          </div>
          <?php if ($categoryFilter !== 'all'): ?>
            <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
          <?php endif; ?>
        </form>

        <!-- Category chips -->
        <div class="filters">
          <a href="<?= buildUrl(['category' => 'all', 'page' => null]) ?>" class="filter-chip <?= $categoryFilter === 'all' ? 'active' : '' ?>">All</a>
          <?php foreach ($categories as $cat): ?>
            <a href="<?= buildUrl(['category' => (int)$cat['id'], 'page' => null]) ?>"
               class="filter-chip <?= ($categoryFilter == $cat['id']) ? 'active' : '' ?>">
               <?= htmlspecialchars($cat['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Recipes Grid -->
  <section class="px-6 pb-12">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (!empty($paginatedRecipes)): ?>
        <?php foreach ($paginatedRecipes as $recipe): ?>
          <?php
            $img = !empty($recipe['image'])
                   ? htmlspecialchars($recipe['image'])
                   : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=1600&auto=format&fit=crop';
            $saved = $isLoggedIn && in_array((int)$recipe['id'], $savedIds, true);
            $rating = is_numeric($recipe['rating']) ? (float)$recipe['rating'] : 0;
            $time = $recipe['cooking_time'] ?: '—';
            $desc = trim($recipe['description'] ?? '');
            $descShort = mb_substr($desc, 0, 160) . (mb_strlen($desc) > 160 ? '…' : '');
            ?>
          <article class="card recipe-card p-4">
            <div class="recipe-cover mb-4">
              <img src="<?= $img ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" loading="lazy">
              <div class="recipe-overlay"></div>

              <?php if ($isLoggedIn): ?>
                <button class="save-btn <?= $saved ? 'saved' : '' ?>"
                        data-recipe-id="<?= (int)$recipe['id'] ?>"
                        data-saved="<?= $saved ? '1' : '0' ?>"
                        aria-label="<?= $saved ? 'Unsave recipe' : 'Save recipe' ?>">
                  <i class="<?= $saved ? 'fas' : 'far' ?> fa-bookmark"></i>
                </button>
              <?php endif; ?>
            </div>

            <div class="flex items-center gap-2 mb-3">
              <span class="chip"><i class="fas fa-tag"></i> <?= htmlspecialchars($recipe['category']) ?></span>
              <span class="chip"><i class="far fa-clock"></i> <?= htmlspecialchars($time) ?></span>
              <span class="chip"><i class="fas fa-star"></i> <?= number_format($rating, 1) ?></span>
            </div>

            <h2 class="text-xl font-extrabold mb-2">
              <a class="title-link" href="recipe-detail.php?id=<?= (int)$recipe['id'] ?>"><?= htmlspecialchars($recipe['title']) ?></a>
            </h2>

            <p class="mb-4" style="color: var(--text-secondary);"><?= nl2br(htmlspecialchars($descShort)) ?></p>

            <div class="mt-auto">
              <a href="recipe-detail.php?id=<?= (int)$recipe['id'] ?>" class="nav-link inline-block" style="background: var(--secondary); color:#111827; border-radius:10px;">
                <i class="fas fa-book-open"></i> View Recipe
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-span-3 card p-8 text-center">
          <i class="fas fa-folder-open text-3xl mb-3" style="color: var(--text-secondary);"></i>
          <p style="color: var(--text-secondary);">No recipes found. Try adjusting your filters or search.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="mt-10 pagination" role="navigation" aria-label="Pagination">
        <a class="page-btn" href="<?= buildUrl(['page' => max($currentPage - 1, 1)]) ?>" aria-disabled="<?= $currentPage <= 1 ? 'true' : 'false' ?>">
          <i class="fas fa-chevron-left"></i> Prev
        </a>

        <?php
            $window = 2;
        $start = max(1, $currentPage - $window);
        $end = min($totalPages, $currentPage + $window);
        if ($start > 1) {
            echo '<a class="page-btn" href="'.buildUrl(['page' => 1]).'">1</a>';
            if ($start > 2) {
                echo '<span class="page-btn" aria-disabled="true">…</span>';
            }
        }
        for ($i = $start; $i <= $end; $i++):
            ?>
          <a class="page-btn <?= $i === $currentPage ? 'active' : '' ?>" href="<?= buildUrl(['page' => $i]) ?>" <?= $i === $currentPage ? 'aria-current="page"' : '' ?>>
            <?= $i ?>
          </a>
        <?php endfor; ?>
        <?php if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                echo '<span class="page-btn" aria-disabled="true">…</span>';
            }
            echo '<a class="page-btn" href="'.buildUrl(['page' => $totalPages]).'">'.$totalPages.'</a>';
        } ?>

        <a class="page-btn" href="<?= buildUrl(['page' => min($currentPage + 1, $totalPages)]) ?>" aria-disabled="<?= $currentPage >= $totalPages ? 'true' : 'false' ?>">
          Next <i class="fas fa-chevron-right"></i>
        </a>
      </nav>
    <?php endif; ?>
  </section>


<script>
  // Mobile menu
  document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
    document.getElementById('mobileMenu')?.classList.toggle('hidden');
  });

  // Save/Unsave via AJAX
  const csrfToken = '<?= htmlspecialchars($csrf_token) ?>';
  document.querySelectorAll('.save-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const saved = btn.getAttribute('data-saved') === '1';
      const recipeId = btn.getAttribute('data-recipe-id');
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('recipe_id', recipeId);
      formData.append(saved ? 'unsave_recipe' : 'save_recipe', '1');

      try {
        const res = await fetch('recipes.php' + window.location.search, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const data = await res.json();
        if (data.ok) {
          // Toggle state
          if (saved) {
            btn.classList.remove('saved');
            btn.setAttribute('data-saved', '0');
            btn.innerHTML = '<i class="far fa-bookmark"></i>';
            btn.setAttribute('aria-label', 'Save recipe');
          } else {
            btn.classList.add('saved');
            btn.setAttribute('data-saved', '1');
            btn.innerHTML = '<i class="fas fa-bookmark"></i>';
            btn.setAttribute('aria-label', 'Unsave recipe');
          }
        } else {
          console.warn(data.error || 'Action failed');
        }
      } catch (err) {
        console.error(err);
      }
    });
  });
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>