<?php
// category.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

// Logged in check (config.php manages session_start)
$isLoggedIn = isset($_SESSION['user_id']);

$db = new Database();
$pdo = $db->getConnection();

$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($catId <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Fetch category info
    $stmt = $pdo->prepare("SELECT id, name, description FROM categories WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $catId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        header('Location: index.php');
        exit;
    }

    // Pagination setup
    $perPage = 6;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $page = max($page, 1);
    $offset = ($page - 1) * $perPage;

    // Count total recipes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM recipes WHERE category_id = ?");
    $stmt->execute([$catId]);
    $totalRecipes = (int)$stmt->fetchColumn();
    $totalPages = max(ceil($totalRecipes / $perPage), 1);
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    // Fetch recipes under this category (join users to get author)
    $stmt = $pdo->prepare("
      SELECT r.*, u.name AS author 
      FROM recipes r 
      JOIN users u ON r.user_id = u.id
      WHERE r.category_id = ?
      ORDER BY r.created_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $catId, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Category page error: ' . $e->getMessage());
    $category = null;
    $recipes = [];
}
ob_start()
?>

  <section class="max-w-7xl mx-auto px-6 py-6 md:py-10">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-4" aria-label="Breadcrumb">
      <ol class="flex items-center gap-2 text-sm">
        <li><a href="index.php" class="nav-link px-2 py-1">Home</a></li>
        <li aria-hidden="true" class="text-gray-500">/</li>
        <li class="px-2 py-1 rounded" style="background: rgba(148,163,184,.08); color: var(--text-secondary);">
          <?= htmlspecialchars($category['name']) ?>
        </li>
      </ol>
    </nav>

    <!-- Category Header -->
    <header class="mb-6 md:mb-8">
      <h1 class="text-3xl md:text-4xl font-extrabold mb-2" style="color: var(--text-primary);">
        <?= htmlspecialchars($category['name']) ?>
      </h1>
      <?php if (!empty($category['description'])): ?>
        <p class="max-w-3xl" style="color: var(--text-secondary);">
          <?= nl2br(htmlspecialchars($category['description'])) ?>
        </p>
      <?php else: ?>
        <p class="max-w-3xl" style="color: var(--text-secondary);">
          Explore handpicked recipes curated under this category.
        </p>
      <?php endif; ?>
    </header>

    <?php if (empty($recipes)): ?>
      <div class="card p-8 text-center">
        <i class="fas fa-folder-open text-3xl mb-3" style="color: var(--text-secondary);"></i>
        <p style="color: var(--text-secondary);">No recipes found in this category yet.</p>
        <p class="mt-4">
          <a href="index.php" class="btn btn-outline">Back to Home</a>
        </p>
      </div>
    <?php else: ?>

      <!-- Recipes Grid -->
      <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($recipes as $r): ?>
          <?php
            $image = $r['image'] ?? '';
            $cooking = trim($r['cooking_time'] ?? '');
            $author = $r['author'] ?? 'Unknown';
            $desc = $r['description'] ?? '';
            $descShort = mb_substr($desc, 0, 220);
            $hasMore = mb_strlen($desc) > mb_strlen($descShort);
            ?>
          <article class="card recipe-card p-4">
            <div class="recipe-cover mb-4">
              <?php if (!empty($image)): ?>
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy">
              <?php else: ?>
                <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=1600&auto=format&fit=crop" alt="Delicious dish" loading="lazy">
              <?php endif; ?>
              <div class="recipe-overlay"></div>
            </div>

            <div class="flex items-center gap-2 mb-3">
              <span class="chip"><i class="far fa-user"></i> <?= htmlspecialchars($author) ?></span>
              <span class="chip"><i class="far fa-clock"></i> <?= htmlspecialchars($cooking !== '' ? $cooking : '—') ?></span>
            </div>

            <h2 class="text-xl font-extrabold mb-2">
              <a href="recipe-detail.php?id=<?= (int)$r['id'] ?>" class="title-link">
                <?= htmlspecialchars($r['title']) ?>
              </a>
            </h2>

            <p class="clamp-3 mb-4" style="color: var(--text-secondary);">
              <?= nl2br(htmlspecialchars($descShort)) ?><?= $hasMore ? '…' : '' ?>
            </p>

            <div class="mt-auto">
              <a href="recipe-detail.php?id=<?= (int)$r['id'] ?>" class="btn btn-primary">
                <i class="fas fa-book-open"></i> View Recipe
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="mt-10 pagination" role="navigation" aria-label="Pagination">
          <!-- Prev -->
          <a class="page-btn" href="?id=<?= $catId ?>&page=<?= max($page - 1, 1) ?>" aria-disabled="<?= $page <= 1 ? 'true' : 'false' ?>">
            <i class="fas fa-chevron-left"></i> Prev
          </a>

          <?php
              // Simple windowed pagination
              $window = 2;
          $start = max(1, $page - $window);
          $end = min($totalPages, $page + $window);
          if ($start > 1) {
              echo '<a class="page-btn" href="?id='.$catId.'&page=1">1</a>';
              if ($start > 2) {
                  echo '<span class="page-btn" aria-disabled="true">…</span>';
              }
          }
          for ($i = $start; $i <= $end; $i++):
              ?>
            <a class="page-btn <?= $i === $page ? 'active' : '' ?>" href="?id=<?= $catId ?>&page=<?= $i ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>>
              <?= $i ?>
            </a>
          <?php endfor; ?>
          <?php if ($end < $totalPages) {
              if ($end < $totalPages - 1) {
                  echo '<span class="page-btn" aria-disabled="true">…</span>';
              }
              echo '<a class="page-btn" href="?id='.$catId.'&page='.$totalPages.'">'.$totalPages.'</a>';
          } ?>

          <!-- Next -->
          <a class="page-btn" href="?id=<?= $catId ?>&page=<?= min($page + 1, $totalPages) ?>" aria-disabled="<?= $page >= $totalPages ? 'true' : 'false' ?>">
            Next <i class="fas fa-chevron-right"></i>
          </a>
        </nav>
      <?php endif; ?>

    <?php endif; ?>
  </section>


<script>
  // Mobile menu toggle
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });
  }
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
