<?php
// Start session early for CSRF and auth
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Create PDO connection using Database class
require_once __DIR__ . '/../includes/database.php';
$db = new Database();
$pdo = $db->getConnection();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error = null;

// Handle delete via POST (secure, not GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $id = (int) ($_POST['delete_id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Invalid recipe ID.');
        }

        // Fetch image path
        $stmt = $pdo->prepare("SELECT image FROM recipes WHERE id = ?");
        $stmt->execute([$id]);
        $recipe = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$recipe) {
            throw new RuntimeException('Recipe not found.');
        }

        // Delete related rows (if FK cascade not set)
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM recipe_instructions WHERE recipe_id = ?")->execute([$id]);

        // Delete recipe
        $pdo->prepare("DELETE FROM recipes WHERE id = ?")->execute([$id]);

        // Remove image file if exists
        if (!empty($recipe['image'])) {
            $imgPath = __DIR__ . '/../' . $recipe['image'];
            if (file_exists($imgPath)) {
                @unlink($imgPath);
            }
        }

        header("Location: " . basename(__FILE__) . "?deleted=1");
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Fetch recipes
$stmt = $pdo->query("SELECT r.*, c.name AS category, u.name AS author 
                     FROM recipes r
                     JOIN categories c ON r.category_id = c.id
                     JOIN users u ON r.user_id = u.id
                     ORDER BY r.created_at DESC");
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
:root {
  --primary-dark: #000000ff;
  --danger-dark: #bd1907ff;
  --danger: red;
  --primary: #030303ff;
  --secondary: #f59e0b;
  --secondary-light: #fcd34d;
  --accent: #b45309;
  --text-primary: #f8fafc;
  --text-secondary: #cbd5e1;
  --card-bg: rgba(0, 0, 0, 0.8);
  --hover-bg: rgba(245, 158, 11, 0.1);
}

  .card {
    background: var(--card-bg);
    border: 1px solid rgba(148, 163, 184, 0.15);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(2, 6, 23, 0.35);
    backdrop-filter: blur(6px);
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .card:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(2, 6, 23, 0.45);
  }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .6rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all .15s ease;
        border: 1px solid transparent;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-outline { background: none; color: var(--gray-700); border-color: var(--gray-200); }
    .btn-outline:hover { background: var(--gray-500); }
    .btn-danger { background: var(--danger); color: white; }
    .btn-danger:hover { background: var(--danger-dark); }

    .badge {
        display: inline-block;
        background: var(--gray-100);
        color: var(--gray-700);
        padding: .2rem .6rem;
        border-radius: 9999px;
        font-size: .75rem;
        font-weight: 600;
    }

    table { width: 100%; border-collapse: separate; border-spacing: 0; color: var(--text-primary); }
  thead th {
    background: rgba(15, 23, 42, 0.75);
    color: var(--text-secondary);
    font-weight: 800; text-align: left; padding: .9rem 1rem;
    border-bottom: 1px solid rgba(148,163,184,.2);
    white-space: nowrap; position: sticky; top: 0; backdrop-filter: blur(4px);
  }
  tbody td {
    padding: 1rem; border-bottom: 1px solid rgba(148,163,184,.12);
  }
  tbody tr:hover { background: var(--hover-bg); }

    .search-box {
        display: flex;
        align-items: center;
        gap: .5rem;
        background: white;
        border: 1px solid #E5E7EB;
        padding: .5rem .75rem;
        border-radius: 10px;
        width: 100%;
        max-width: 320px;
        color:black;
    }
    .search-box input {
        outline: none;
        border: none;
        width: 100%;

    }

    .modal {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center; justify-content: center;
        padding: 1rem;
    }
    .modal.show { display: flex; }
    .modal-content {
        background: white;
        border-radius: 12px;
        width: 95%;
        max-width: 520px;
        padding: 1.25rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
    .modal-title { font-weight: 800; color: var(--gray-900); }
    .icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2rem; height: 2rem; border-radius: 8px;
        border: 1px solid #E5E7EB; background: #F9FAFB; color: #374151;
    }
    .icon-btn:hover { background: #F3F4F6; }

    .alert {
        padding: 0.75rem 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .alert-success { background-color: #D1FAE5; color: #065F46; }
    .alert-danger { background-color: #FEE2E2; color: #991B1B; }
</style>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-300">Manage Recipes</h1>
            <p class="text-gray-500">View and manage all submitted recipes.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="recipes.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Recipes</a>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <div class="search-box">
            <i class="fas fa-search text-gray-500"></i>
            <input id="tableSearch" type="text" placeholder="Search by title, category, or author...">
        </div>

        <div class="flex flex-col gap-2 items-end">
        <?php foreach (['deleted'=>'Recipe deleted'] as $k=>$msg): ?>
          <?php if (isset($_GET[$k])): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?> successfully!
            </div>
          <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger">
            <i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="p-3">Title</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Author</th>
                        <th class="p-3">Created</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="recipesTableBody">
                    <?php if (empty($recipes)): ?>
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">No recipes found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recipes as $r): ?>
                            <tr class="hover:bg-gray-700 text"
                                data-title="<?= htmlspecialchars(mb_strtolower($r['title'])) ?>"
                                data-category="<?= htmlspecialchars(mb_strtolower($r['category'])) ?>"
                                data-author="<?= htmlspecialchars(mb_strtolower($r['author'])) ?>">
                                <td class="p-3 font-medium text-gray-300"><?= htmlspecialchars($r['title']) ?></td>
                                <td class="p-3 font-medium text-gray-300"><span class="badge"><?= htmlspecialchars($r['category']) ?></span></td>
                                <td class="p-3 font-medium text-gray-300"><?= htmlspecialchars($r['author']) ?></td>
                                <td class="p-3 font-medium text-gray-300"><?= htmlspecialchars($r['created_at']) ?></td>
                                <td class="p-3 font-medium text-gray-300">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            class="btn btn-danger text-sm"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-title="<?= htmlspecialchars($r['title'], ENT_QUOTES) ?>"
                                            onclick="openDeleteFromButton(this)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteRecipeModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title text-red-600"><i class="fas fa-trash mr-2"></i>Delete Recipe</div>
            <button class="icon-btn" onclick="closeModal('deleteRecipeModal')" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <p class="text-gray-700 mb-2">Are you sure you want to delete this recipe?</p>
        <p class="text-gray-900 font-semibold mb-4" id="delete_recipe_title"></p>
        <form id="deleteRecipeForm" method="POST" class="flex justify-end gap-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_id" id="delete_id">
            <button type="button" class="btn btn-outline" onclick="closeModal('deleteRecipeModal')">Cancel</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
        </form>
    </div>
</div>

<script>
    // Search filter
    const searchInput = document.getElementById('tableSearch');
    const tbody = document.getElementById('recipesTableBody');

    function filterRows() {
        const q = (searchInput.value || '').trim().toLowerCase();
        let visible = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            const title = tr.getAttribute('data-title') || '';
            const category = tr.getAttribute('data-category') || '';
            const author = tr.getAttribute('data-author') || '';
            const match = !q || title.includes(q) || category.includes(q) || author.includes(q);
            tr.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (visible === 0) {
            if (!document.getElementById('noResultsRow')) {
                const row = document.createElement('tr');
                row.id = 'noResultsRow';
                row.innerHTML = '<td colspan="5" class="p-6 text-center text-gray-500">No matching results.</td>';
                tbody.appendChild(row);
            }
        } else {
            const nr = document.getElementById('noResultsRow');
            if (nr) nr.remove();
        }
    }
    if (searchInput) {
        searchInput.addEventListener('input', filterRows);
    }

    // Alerts auto-hide
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .4s ease';
            setTimeout(() => alert.remove(), 400);
        });
    }, 4000);

    // Modal helpers
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.add('show');
            m.setAttribute('aria-hidden', 'false');
        }
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.remove('show');
            m.setAttribute('aria-hidden', 'true');
        }
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
        }
    });
    document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) closeModal(m.id);
        });
    });

    // Delete modal actions
    function openDeleteFromButton(btn) {
        const id = btn.getAttribute('data-id');
        const title = btn.getAttribute('data-title') || '';
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_recipe_title').textContent = title;
        openModal('deleteRecipeModal');
    }
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
