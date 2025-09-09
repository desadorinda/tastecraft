<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

require_once __DIR__ . '/../includes/database.php';
$db = new Database();
$pdo = $db->getConnection();

$userId = $_SESSION['user_id'] ?? null;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error = null;
$removedSuccess = false;

// Handle remove saved recipe (POST + CSRF)
if (isset($_POST['remove_saved'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new RuntimeException('Invalid CSRF token.');
        }
        $recipeId = (int)($_POST['recipe_id'] ?? 0);
        if ($recipeId <= 0) {
            throw new RuntimeException('Invalid recipe.');
        }
        $stmt = $pdo->prepare("DELETE FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
        $stmt->execute([$userId, $recipeId]);
        $removedSuccess = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Fetch saved recipes with recipe + category info
$stmt = $pdo->prepare("
    SELECT r.id, r.title, r.description, r.image, r.cooking_time, c.name AS category
    FROM saved_recipes sr
    JOIN recipes r ON sr.recipe_id = r.id
    JOIN categories c ON r.category_id = c.id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$userId]);
$savedRecipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Dashboard - TasteCraft";
$current_page = "saved_recipes.php";

ob_start();
?>
<style>
  :root {
    --gold-primary: #FFD700;
    --gold-secondary: #D4AF37;
    --gold-dark: #B8860B;
    --gold-light: #F8DE7E;
    --black-primary: #000000;
    --black-secondary: #1A1A1A;
    --black-tertiary: #000000ff;
    --text-primary: #FFFFFF;
    --text-secondary: #E0E0E0;
    --text-muted: #AAAAAA;
    --danger: #DC3545;
    --danger-dark: #BD2130;
  }

  body {
  background: radial-gradient(circle at 50% 30%, var(--gold-primary) 0%, var(--black-secondary) 70%, var(--black-primary) 100%);
    color: var(--text-primary);
    min-height: 100vh;
  }

  .section-head { 
    color: var(--gold-primary);
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
  }
  
  .subtext { 
    color: var(--text-secondary); 
  }

  .card {
    background: linear-gradient(145deg, var(--black-tertiary) 0%, var(--black-secondary) 100%);
    border: 1px solid var(--gold-primary);
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
  }
  
  .card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--gold-dark), var(--gold-primary), var(--gold-dark));
    z-index: 1;
  }
  
  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(255, 215, 0, 0.2);
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
  }
  
  .btn-primary {
    background: linear-gradient(to right, var(--gold-dark), var(--gold-primary));
    color: var(--black-primary);
    border: 1px solid var(--gold-primary);
  }
  
  .btn-primary:hover {
    background: linear-gradient(to right, var(--gold-primary), var(--gold-light));
    box-shadow: 0 0 15px rgba(255, 215, 0, 0.4);
  }
  
  .btn-outline {
    background: transparent;
    color: var(--gold-primary);
    border: 1px solid var(--gold-primary);
  }
  
  .btn-outline:hover {
    background: rgba(255, 215, 0, 0.1);
    box-shadow: 0 0 15px rgba(255, 215, 0, 0.2);
  }
  
  .btn-danger {
    background: linear-gradient(to right, var(--danger-dark), var(--danger));
    color: white;
    border: 1px solid var(--danger);
  }
  
  .btn-danger:hover {
    background: linear-gradient(to right, var(--danger), #e4606d);
    box-shadow: 0 0 15px rgba(220, 53, 69, 0.4);
  }

  .chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    background: rgba(255, 215, 0, 0.15);
    border: 1px solid var(--gold-primary);
    color: var(--gold-light);
    font-size: 0.8rem;
    font-weight: 600;
  }

  .img-cover {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 215, 0, 0.3);
    aspect-ratio: 16 / 9;
  }
  
  .img-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
    display: block;
  }
  
  .card:hover .img-cover img {
    transform: scale(1.05);
  }

  .muted {
    color: var(--text-muted);
  }

  .search-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--gold-primary);
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    width: 100%;
    max-width: 360px;
    color: var(--text-primary);
  }
  
  .search-box input {
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-primary);
    width: 100%;
  }
  
  .search-box input::placeholder {
    color: var(--text-muted);
  }

  .select {
    background: rgba(0, 0, 0, 0.3);
    color: var(--text-primary);
    border: 1px solid var(--gold-primary);
    border-radius: 8px;
    padding: 0.55rem 0.75rem;
    outline: none;
  }
  
  .select option {
    background: var(--black-secondary);
    color: var(--text-primary);
  }

  .recipe-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .grid-wrap {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 1.5rem;
  }
  
  @media (min-width: 768px) {
    .grid-wrap {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  
  @media (min-width: 1024px) {
    .grid-wrap {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }

  .alert {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    border: 1px solid transparent;
  }
  
  .alert-success {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
    border-color: rgba(40, 167, 69, 0.3);
  }
  
  .alert-danger {
    background: rgba(220, 53, 69, 0.15);
    color: var(--danger);
    border-color: rgba(220, 53, 69, 0.3);
  }

  /* Modal */
  .modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000;
    align-items: center;
    justify-content: center;
  }
  
  .modal.show {
    display: flex;
  }
  
  .modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
  }
  
  .modal-content {
    position: relative;
    background: linear-gradient(145deg, var(--black-tertiary) 0%, var(--black-secondary) 100%);
    color: var(--text-primary);
    border: 1px solid var(--gold-primary);
    border-radius: 12px;
    width: 95%;
    max-width: 520px;
    padding: 1.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7);
  }
  
  .modal-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--gold-dark), var(--gold-primary), var(--gold-dark));
  }
  
  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
  }
  
  .modal-title {
    font-weight: 700;
    color: var(--gold-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
    border: 1px solid var(--gold-primary);
    color: var(--gold-primary);
    background: transparent;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .icon-btn:hover {
    background: rgba(255, 215, 0, 0.1);
  }
  
  /* Header styling */
  .dashboard-header {
    background: linear-gradient(90deg, var(--black-primary) 0%, var(--black-secondary) 100%);
    border-bottom: 2px solid var(--gold-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
  }
  
  /* Navigation styling */
  .nav-item.active {
    color: var(--gold-primary);
    border-bottom: 2px solid var(--gold-primary);
  }
  
  .nav-item:hover {
    color: var(--gold-light);
  }
  
  /* Footer styling */
  .site-footer {
    background: linear-gradient(90deg, var(--black-primary) 0%, var(--black-secondary) 100%);
    border-top: 1px solid var(--gold-primary);
  }
</style>

<div class="flex items-center justify-between mb-8">
  <div>
    <h1 class="text-2xl md:text-3xl font-extrabold section-head mb-2">My Saved Recipes</h1>
    <p class="subtext">Here's what you've been cooking lately.</p>
  </div>
  <div class="flex items-center gap-2">
    <a href="../index.php" class="btn btn-outline"><i class="fas fa-home"></i> Back to Site</a>
  </div>
</div>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
  <div class="search-box">
    <i class="fas fa-search" style="color: var(--gold-primary);"></i>
    <input id="searchSaved" type="text" placeholder="Search by title or category...">
  </div>
  <div class="flex items-center gap-2">
    <label for="sortSelect" class="subtext text-sm">Sort:</label>
    <select id="sortSelect" class="select">
      <option value="newest">Newest</option>
      <option value="oldest">Oldest</option>
      <option value="title_asc">Title A–Z</option>
      <option value="title_desc">Title Z–A</option>
      <option value="category_asc">Category A–Z</option>
      <option value="category_desc">Category Z–A</option>
    </select>
  </div>
</div>

<?php if ($removedSuccess): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> Recipe removed from saved.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($savedRecipes)): ?>
  <div id="savedGrid" class="grid-wrap">
    <?php foreach ($savedRecipes as $recipe): ?>
      <?php
        $title = $recipe['title'] ?? '';
        $category = $recipe['category'] ?? '';
        $desc = $recipe['description'] ?? '';
        $descShort = mb_substr($desc, 0, 140);
        $hasMore = mb_strlen($desc) > mb_strlen($descShort);
        $img = $recipe['image'] ? '../' . $recipe['image'] : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=1600&auto=format&fit=crop';
        $time = $recipe['cooking_time'] ?? '—';
      ?>
      <article class="card p-5"
               data-title="<?= htmlspecialchars(mb_strtolower($title), ENT_QUOTES) ?>"
               data-category="<?= htmlspecialchars(mb_strtolower($category), ENT_QUOTES) ?>">
        <div class="img-cover mb-4">
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($title) ?>" loading="lazy">
        </div>

        <div class="flex items-center gap-2 mb-3">
          <span class="chip"><i class="fas fa-tag"></i> <?= htmlspecialchars($category) ?></span>
          <span class="chip"><i class="far fa-clock"></i> <?= htmlspecialchars($time) ?></span>
        </div>

        <h3 class="text-lg font-extrabold section-head mb-2"><?= htmlspecialchars($title) ?></h3>
        <p class="muted mb-4"><?= nl2br(htmlspecialchars($descShort)) ?><?= $hasMore ? '…' : '' ?></p>

        <div class="recipe-actions">
          <a href="../recipe-detail.php?id=<?= (int)$recipe['id'] ?>" class="btn btn-primary">
            <i class="fas fa-book-open"></i> View Recipe
          </a>
          <button class="btn btn-danger"
                  data-recipe-id="<?= (int)$recipe['id'] ?>"
                  data-recipe-title="<?= htmlspecialchars($title, ENT_QUOTES) ?>"
                  onclick="openRemoveModal(this)">
            <i class="fas fa-times-circle"></i> Remove
          </button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card p-10 text-center">
    <i class="fas fa-heart-broken text-4xl mb-4" style="color: var(--gold-primary);"></i>
    <h3 class="text-xl font-bold section-head mb-2">No Saved Recipes Yet</h3>
    <p class="muted mb-6">You haven't saved any recipes to your collection.</p>
    <a href="../index.php" class="btn btn-primary"><i class="fas fa-compass"></i> Explore Recipes</a>
  </div>
<?php endif; ?>

<!-- Remove Confirmation Modal -->
<div id="removeModal" class="modal" aria-hidden="true">
  <div class="modal-backdrop" onclick="closeModal('removeModal')"></div>
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-trash"></i> Remove Saved Recipe</div>
      <button class="icon-btn" onclick="closeModal('removeModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <p class="muted mb-4">Are you sure you want to remove this recipe from your saved list?</p>
    <p class="font-bold mb-4" id="remove_recipe_title" style="color: var(--text-primary);"></p>
    <form method="POST" class="flex justify-end gap-2">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="recipe_id" id="remove_recipe_id">
      <button type="button" class="btn btn-outline" onclick="closeModal('removeModal')">Cancel</button>
      <button type="submit" name="remove_saved" class="btn btn-danger"><i class="fas fa-trash"></i> Remove</button>
    </form>
  </div>
</div>

<script>
  // Alerts auto-hide
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .35s ease';
      setTimeout(() => el.remove(), 350);
    });
  }, 4000);

  // Modal helpers
  function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  
  function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('show');
    document.body.style.overflow = 'auto';
  }
  
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
      document.body.style.overflow = 'auto';
    }
  });

  // Remove modal open
  function openRemoveModal(btn) {
    const id = btn.getAttribute('data-recipe-id');
    const title = btn.getAttribute('data-recipe-title') || '';
    document.getElementById('remove_recipe_id').value = id;
    document.getElementById('remove_recipe_title').textContent = title;
    openModal('removeModal');
  }

  // Search filter
  const searchInput = document.getElementById('searchSaved');
  const grid = document.getElementById('savedGrid');
  
  function filterCards() {
    if (!grid) return;
    const q = (searchInput.value || '').trim().toLowerCase();
    let visible = 0;
    
    grid.querySelectorAll('article.card').forEach(card => {
      const title = card.getAttribute('data-title') || '';
      const cat = card.getAttribute('data-category') || '';
      const match = !q || title.includes(q) || cat.includes(q);
      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    
    // Optional: show a quick empty message
    if (visible === 0) {
      if (!document.getElementById('noResultsCard')) {
        const div = document.createElement('div');
        div.id = 'noResultsCard';
        div.className = 'card p-8 text-center';
        div.innerHTML = `
          <i class="fas fa-search mb-3" style="font-size: 2rem; color: var(--gold-primary);"></i>
          <p class="muted">No recipes found matching your search.</p>
        `;
        grid.appendChild(div);
      }
    } else {
      const nr = document.getElementById('noResultsCard');
      if (nr) nr.remove();
    }
  }
  
  if (searchInput) searchInput.addEventListener('input', filterCards);

  // Sort
  const sortSelect = document.getElementById('sortSelect');
  
  function sortCards() {
    if (!grid) return;
    const val = sortSelect.value;
    const cards = Array.from(grid.querySelectorAll('article.card')).filter(c => c.id !== 'noResultsCard');

    if (val === 'newest') {
      // Default order (DOM order), assuming newest first by SQL
      cards.forEach(c => grid.appendChild(c));
      return;
    }
    
    if (val === 'oldest') {
      // Reverse DOM order
      cards.reverse().forEach(c => grid.appendChild(c));
      return;
    }

    const getTitle = c => (c.getAttribute('data-title') || '').toLowerCase();
    const getCat = c => (c.getAttribute('data-category') || '').toLowerCase();

    switch (val) {
      case 'title_asc':
        cards.sort((a,b) => getTitle(a).localeCompare(getTitle(b)));
        break;
      case 'title_desc':
        cards.sort((a,b) => getTitle(b).localeCompare(getTitle(a)));
        break;
      case 'category_asc':
        cards.sort((a,b) => getCat(a).localeCompare(getCat(b)));
        break;
      case 'category_desc':
        cards.sort((a,b) => getCat(b).localeCompare(getCat(a)));
        break;
    }
    
    cards.forEach(c => grid.appendChild(c));
  }
  
  if (sortSelect) sortSelect.addEventListener('change', sortCards);
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>