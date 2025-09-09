<?php
// Start session for CSRF/auth
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

function uploadCategoryImage(array $file, ?string $existingPath = null): ?string {
    if (empty($file['name'])) {
        return $existingPath;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload error.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];
    $maxBytes = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Image too large (max 5MB).');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Invalid image type. Use JPG, PNG, GIF, or WEBP.');
    }

    $dir = __DIR__ . '/../uploads/categories/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $targetPath = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Failed to save uploaded image.');
    }

    // Remove old image if replacing
    if ($existingPath && file_exists(__DIR__ . '/../' . $existingPath)) {
        @unlink(__DIR__ . '/../' . $existingPath);
    }

    return 'uploads/categories/' . $filename;
}

// Handle POST actions securely (create/update/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Category name is required.');
            }

            $imagePath = null;
            if (!empty($_FILES['image']['name'])) {
                $imagePath = uploadCategoryImage($_FILES['image'], null);
            }

            $stmt = $pdo->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $imagePath]);

            header("Location: categories.php?added=1");
            exit();
        }

        if ($action === 'update') {
            $id = (int) ($_POST['edit_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($id <= 0) {
                throw new RuntimeException('Invalid category ID.');
            }
            if ($name === '') {
                throw new RuntimeException('Category name is required.');
            }

            // Get current image
            $stmt = $pdo->prepare("SELECT image FROM categories WHERE id=?");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$category) {
                throw new RuntimeException('Category not found.');
            }
            $currentImage = $category['image'];

            // Replace image if new uploaded
            $imagePath = $currentImage;
            if (!empty($_FILES['image']['name'])) {
                $imagePath = uploadCategoryImage($_FILES['image'], $currentImage);
            }

            $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, image=? WHERE id=?");
            $stmt->execute([$name, $description, $imagePath, $id]);

            header("Location: categories.php?updated=1");
            exit();
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['delete_id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('Invalid category ID.');
            }

            // Fetch image
            $stmt = $pdo->prepare("SELECT image FROM categories WHERE id=?");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$category) {
                throw new RuntimeException('Category not found.');
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    throw new RuntimeException('Cannot delete category: it may be used by recipes.');
                }
                throw new RuntimeException('Delete failed.');
            }

            if (!empty($category['image']) && file_exists(__DIR__ . '/../' . $category['image'])) {
                @unlink(__DIR__ . '/../' . $category['image']);
            }

            header("Location: categories.php?deleted=1");
            exit();
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Fetch all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
  :root {
    --primary-dark: #000000ff;
    --danger-dark: #bd1907ff;
    --danger: red;
    --primary: #000000ff;
    --secondary: #f59e0b;
    --secondary-light: #fcd34d;
    --accent: #b45309;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --card-bg: rgba(0, 0, 0, 0.8);
    --hover-bg: rgba(245, 158, 11, 0.1);
  }

  .page-wrap {
    /* background: radial-gradient(1200px 600px at top left, rgba(245, 158, 11, 0.07), transparent),
                linear-gradient(180deg, var(--primary-dark), var(--primary)); */
    min-height: 100%;
    color: var(--text-primary);
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
    display: inline-flex; align-items: center; justify-content: center;
    gap: .5rem; padding: .6rem 1rem; border-radius: 10px;
    font-weight: 700; border: 1px solid transparent; cursor: pointer;
    transition: all .15s ease;
  }
  .btn-primary { background: var(--secondary); color: #111827; }
  .btn-primary:hover { background: var(--secondary-light); }
  .btn-outline { background: transparent; color: var(--text-secondary); border-color: rgba(148,163,184,.25); }
  .btn-outline:hover { background: rgba(148,163,184,.08); }
  .btn-danger { background: var(--danger); color: white; }
  .btn-danger:hover { background: var(--danger-dark); }

  .badge {
    display: inline-block; padding: .2rem .55rem; border-radius: 9999px;
    font-size: .75rem; font-weight: 700;
    background: rgba(245, 158, 11, 0.15); color: var(--secondary-light);
    border: 1px solid rgba(245, 158, 11, 0.35);
  }

  .table-wrap { overflow-x: auto; }
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
    display: flex; align-items: center; gap: .5rem;
    background: rgba(2, 6, 23, 0.35);
    border: 1px solid rgba(148,163,184,.2);
    padding: .6rem .75rem; border-radius: 10px; width: 100%; max-width: 360px;
    color: var(--text-primary);
  }
  .search-box input {
    background: transparent; border: none; outline: none; color: var(--text-primary); width: 100%;
  }
  .search-box input::placeholder { color: var(--text-secondary); }

  .form-input, .form-textarea, .form-file {
    width: 100%; padding: .75rem; border-radius: 10px;
    border: 1px solid rgba(148,163,184,.25);
    background: rgba(2, 6, 23, 0.35); color: var(--text-primary);
    transition: border-color .15s ease, box-shadow .15s ease;
  }
  .form-input:focus, .form-textarea:focus, .form-file:focus {
    outline: none; border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
  }
  label { color: var(--text-secondary); font-weight: 600; }

  .modal {
    display: none; position: fixed; inset: 0;
    background: rgba(2, 6, 23, 0.65); z-index: 1000;
    align-items: center; justify-content: center; padding: 1rem;
  }
  .modal.show { display: flex; }
  .modal-content {
    background: var(--card-bg);
    border: 1px solid rgba(148,163,184,.2);
    border-radius: 14px; width: 95%; max-width: 640px;
    padding: 1.25rem; box-shadow: 0 18px 40px rgba(2, 6, 23, 0.55);
    color: var(--text-primary);
  }
  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
  .modal-title { font-weight: 900; color: var(--secondary-light); display: flex; align-items: center; gap: .5rem; }
  .icon-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem; border-radius: 10px; border: 1px solid rgba(148,163,184,.25);
    color: var(--text-secondary); background: rgba(2, 6, 23, 0.35);
  }
  .icon-btn:hover { background: rgba(148,163,184,.12); }

  .thumb {
    width: 56px; height: 56px; border-radius: 10px; object-fit: cover;
    border: 1px solid rgba(148,163,184,.25);
    background: rgba(2, 6, 23, 0.35);
  }
  .img-preview {
    width: 100%; max-height: 220px; object-fit: cover; border-radius: 10px;
    border: 1px solid rgba(148,163,184,.25); display: none;
  }

  .alert {
    padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem;
    display: flex; align-items: center; gap: 0.5rem; font-weight: 600;
  }
  .alert-success { background: rgba(16,185,129,.12); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
  .alert-danger { background: rgba(239,68,68,.12); color: #f87171; border: 1px solid rgba(239,68,68,.35); }

  @media (max-width: 640px) {
    .mobile-hidden { display: none; }
  }
</style>

<div class="page-wrap">
  <div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-extrabold" style="color: var(--text-primary);">Manage Categories</h1>
        <p class="text-sm" style="color: var(--text-secondary);">Create, edit, and organize your recipe categories.</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn btn-primary" onclick="openModal('addCategoryModal')">
          <i class="fas fa-plus"></i> Add Category
        </button>
        <a href="../index.php" class="btn btn-outline"><i class="fas fa-home"></i> Back to Site</a>
      </div>
    </div>

    <div class="flex items-center justify-between mb-4">
      <div class="search-box">
        <i class="fas fa-search" style="color: var(--text-secondary);"></i>
        <input id="tableSearch" type="text" placeholder="Search by name or description...">
      </div>

      <div class="flex flex-col gap-2 items-end">
        <?php foreach (['added'=>'Category added','updated'=>'Category updated','deleted'=>'Category deleted'] as $k=>$msg): ?>
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

    <div class="card overflow-hidden">
      <div class="px-6 py-4 border-b" style="border-color: rgba(148,163,184,.15);">
        <h2 class="text-xl font-bold" style="color: var(--secondary-light);">
          <i class="fas fa-list mr-2"></i> All Categories
        </h2>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="p-3">Name</th>
              <th class="p-3">Description</th>
              <th class="p-3">Image</th>
              <th class="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody id="categoriesTableBody">
            <?php if (empty($categories)): ?>
              <tr>
                <td colspan="4" class="p-8 text-center" style="color: var(--text-secondary);">
                  <i class="fas fa-inbox text-3xl mb-2 opacity-60"></i><br>
                  No categories found. Click “Add Category” to create one.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($categories as $c): ?>
                <?php
                  $catData = [
                    'id'          => (int)$c['id'],
                    'name'        => $c['name'],
                    'description' => $c['description'],
                    'image'       => $c['image'],
                  ];
                  $dataAttr = htmlspecialchars(json_encode($catData), ENT_QUOTES, 'UTF-8');
                ?>
                <tr class="hover:bg-gray-700"
                    data-name="<?= htmlspecialchars(mb_strtolower($c['name'] ?? ''), ENT_QUOTES) ?>"
                    data-description="<?= htmlspecialchars(mb_strtolower($c['description'] ?? ''), ENT_QUOTES) ?>">
                  <td class="p-3 font-semibold" style="color: var(--text-primary);">
                    <?= htmlspecialchars($c['name']) ?>
                  </td>
                  <td class="p-3" style="color: var(--text-secondary);">
                    <?= htmlspecialchars($c['description']) ?>
                  </td>
                  <td class="p-3">
                    <?php if (!empty($c['image'])): ?>
                      <img src="../<?= htmlspecialchars($c['image']) ?>" alt="Category Image" class="thumb">
                    <?php else: ?>
                      <div class="thumb flex items-center justify-center">
                        <i class="fas fa-image" style="color: var(--text-secondary);"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="p-3">
                    <div class="flex justify-end gap-2">
                      <button class="btn btn-outline text-sm" data-cat='<?= $dataAttr ?>' onclick="openEditFromButton(this)">
                        <i class="fas fa-edit"></i> Edit
                      </button>
                      <button class="btn btn-danger text-sm"
                              onclick="openDeleteModal(<?= (int)$c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>')">
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
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-tags"></i> Add Category</div>
      <button class="icon-btn" onclick="closeModal('addCategoryModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <form id="addCategoryForm" method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="create">

      <div>
        <label class="block mb-1">Category Name</label>
        <input type="text" name="name" class="form-input" placeholder="e.g. Breakfast" required>
      </div>

      <div>
        <label class="block mb-1">Description</label>
        <textarea name="description" class="form-textarea" rows="3" placeholder="Short description"></textarea>
      </div>

      <div>
        <label class="block mb-1">Category Image (optional)</label>
        <input type="file" name="image" class="form-file" accept="image/*" onchange="previewImage(this, 'addImagePreview')">
        <img id="addImagePreview" class="img-preview" alt="">
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" class="btn btn-outline" onclick="closeModal('addCategoryModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-pen-to-square"></i> Edit Category</div>
      <button class="icon-btn" onclick="closeModal('editCategoryModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>

    <form id="editCategoryForm" method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="edit_id" id="edit_id">

      <div>
        <label class="block mb-1">Category Name</label>
        <input type="text" name="name" id="edit_name" class="form-input" required>
      </div>

      <div>
        <label class="block mb-1">Description</label>
        <textarea name="description" id="edit_description" class="form-textarea" rows="3"></textarea>
      </div>

      <div>
        <label class="block mb-1">New Image (optional)</label>
        <input type="file" name="image" class="form-file" accept="image/*" onchange="previewImage(this, 'editImagePreview')">
        <img id="editImagePreview" class="img-preview" alt="">
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" class="btn btn-outline" onclick="closeModal('editCategoryModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteCategoryModal" class="modal" aria-hidden="true">
  <div class="modal-content" style="max-width: 520px;">
    <div class="modal-header">
      <div class="modal-title" style="color: #fca5a5;"><i class="fas fa-trash"></i> Delete Category</div>
      <button class="icon-btn" onclick="closeModal('deleteCategoryModal')" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <p class="mb-2" style="color: var(--text-secondary);">Are you sure you want to delete this category?</p>
    <p class="font-bold mb-4" id="delete_category_title" style="color: var(--text-primary);"></p>
    <form id="deleteCategoryForm" method="POST" class="flex justify-end gap-2">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="delete_id" id="delete_id">
      <button type="button" class="btn btn-outline" onclick="closeModal('deleteCategoryModal')">Cancel</button>
      <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
    </form>
  </div>
</div>

<script>
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

  // Alerts auto-hide
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity .4s ease';
      setTimeout(() => alert.remove(), 400);
    });
  }, 4000);

  // Search filter
  const searchInput = document.getElementById('tableSearch');
  const tbody = document.getElementById('categoriesTableBody');
  function filterRows() {
    const q = (searchInput.value || '').trim().toLowerCase();
    let visible = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
      const name = tr.getAttribute('data-name') || '';
      const desc = tr.getAttribute('data-description') || '';
      const match = !q || name.includes(q) || desc.includes(q);
      tr.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    if (visible === 0) {
      if (!document.getElementById('noResultsRow')) {
        const row = document.createElement('tr');
        row.id = 'noResultsRow';
        row.innerHTML = '<td colspan="4" class="p-6 text-center" style="color: var(--text-secondary);">No matching results.</td>';
        tbody.appendChild(row);
      }
    } else {
      const nr = document.getElementById('noResultsRow');
      if (nr) nr.remove();
    }
  }
  if (searchInput) searchInput.addEventListener('input', filterRows);

  // Image preview
  function previewImage(input, previewId) {
    const file = input.files && input.files[0];
    const img = document.getElementById(previewId);
    if (file && img) {
      const reader = new FileReader();
      reader.onload = e => {
        img.src = e.target.result;
        img.style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else if (img) {
      img.src = '';
      img.style.display = 'none';
    }
  }

  // Open edit modal with pre-filled data
  function openEditFromButton(btn) {
    const data = btn.getAttribute('data-cat');
    if (!data) return;
    let c;
    try { c = JSON.parse(data); } catch(e) { return; }
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_name').value = c.name || '';
    document.getElementById('edit_description').value = c.description || '';

    const preview = document.getElementById('editImagePreview');
    if (c.image) {
      preview.src = '../' + c.image;
      preview.style.display = 'block';
    } else {
      preview.src = '';
      preview.style.display = 'none';
    }

    openModal('editCategoryModal');
  }

  // Open delete modal
  function openDeleteModal(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_category_title').textContent = name || '';
    openModal('deleteCategoryModal');
  }
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>