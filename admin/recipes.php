<?php
// Ensure session starts at the top before any output
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Create PDO connection
require_once __DIR__ . '/../includes/database.php';
$db = new Database();
$pdo = $db->getConnection();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch categories for dropdown (once)
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Helpers
function uploadImage(array $file, ?string $existingPath = null): ?string {
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

    $targetDir = __DIR__ . '/../uploads/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $targetFile = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        throw new RuntimeException('Failed to save uploaded image.');
    }

    // Remove old image if present
    if ($existingPath && file_exists(__DIR__ . '/../' . $existingPath)) {
        @unlink(__DIR__ . '/../' . $existingPath);
    }

    return 'uploads/' . $filename;
}

function intOrNull($val) {
    if ($val === '' || $val === null) return null;
    return (int) $val;
}

function strOrNull($val) {
    if ($val === '' || $val === null) return null;
    return $val;
}

$error = null;

// Handle POST actions (create/update/delete) with CSRF check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(400);
        die('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    try {
        // Get logged-in user ID from session
        $user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
        if (!$user_id) {
            throw new RuntimeException('You must be logged in to perform this action.');
        }

        if ($action === 'create') {
            $title        = trim($_POST['title'] ?? '');
            $description  = trim($_POST['description'] ?? '');
            $category_id  = (int) ($_POST['category_id'] ?? 0);
            $cooking_time = trim($_POST['cooking_time'] ?? '');
            $servings     = strOrNull($_POST['servings'] ?? null);
            $calories     = strOrNull($_POST['calories'] ?? null);
            $protein      = strOrNull($_POST['protein'] ?? null);
            $carbs        = strOrNull($_POST['carbs'] ?? null);
            $fat          = strOrNull($_POST['fat'] ?? null);
            $rating       = $_POST['rating'] !== '' ? (float) $_POST['rating'] : 0;
            $notes        = strOrNull($_POST['notes'] ?? null);

            if ($title === '') {
                throw new RuntimeException('Title is required.');
            }

            $imagePath = null;
            if (!empty($_FILES['image']['name'])) {
                $imagePath = uploadImage($_FILES['image'], null);
            }

            $stmt = $pdo->prepare("INSERT INTO recipes 
                (user_id, category_id, title, description, cooking_time, image, servings, calories, protein, carbs, fat, rating, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id, $category_id, $title, $description, $cooking_time, $imagePath,
                $servings, $calories, $protein, $carbs, $fat, $rating, $notes
            ]);

            $recipe_id = (int) $pdo->lastInsertId();

            // Ingredients: expects array of "measure|ingredient" strings (kept compatible)
            if (!empty($_POST['ingredients']) && is_array($_POST['ingredients'])) {
                foreach ($_POST['ingredients'] as $ingredientRaw) {
                    $ingredientRaw = trim($ingredientRaw);
                    if ($ingredientRaw !== '') {
                        $parts  = explode('|', $ingredientRaw);
                        $measure = trim($parts[0] ?? '');
                        $name    = trim($parts[1] ?? $parts[0]);
                        if ($name !== '') {
                            $stmtIng = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, measure, ingredient) VALUES (?, ?, ?)");
                            $stmtIng->execute([$recipe_id, $measure, $name]);
                        }
                    }
                }
            }

            // Instructions
            if (!empty($_POST['instructions']) && is_array($_POST['instructions'])) {
                $order = 1;
                foreach ($_POST['instructions'] as $step) {
                    $step = trim($step);
                    if ($step !== '') {
                        $stmtInst = $pdo->prepare("INSERT INTO recipe_instructions (recipe_id, step, step_order) VALUES (?, ?, ?)");
                        $stmtInst->execute([$recipe_id, $step, $order]);
                        $order++;
                    }
                }
            }

            header("Location: recipes.php?added=1");
            exit();
        }

        if ($action === 'update') {
            $id           = (int) ($_POST['edit_id'] ?? 0);
            $title        = trim($_POST['title'] ?? '');
            $description  = trim($_POST['description'] ?? '');
            $category_id  = (int) ($_POST['category_id'] ?? 0);
            $cooking_time = trim($_POST['cooking_time'] ?? '');
            $servings     = strOrNull($_POST['servings'] ?? null);
            $calories     = strOrNull($_POST['calories'] ?? null);
            $protein      = strOrNull($_POST['protein'] ?? null);
            $carbs        = strOrNull($_POST['carbs'] ?? null);
            $fat          = strOrNull($_POST['fat'] ?? null);
            $rating       = $_POST['rating'] !== '' ? (float) $_POST['rating'] : 0;
            $notes        = strOrNull($_POST['notes'] ?? null);

            if ($id <= 0) {
                throw new RuntimeException('Invalid recipe ID.');
            }
            if ($title === '') {
                throw new RuntimeException('Title is required.');
            }

            // Fetch current recipe to check existing image
            $stmt = $pdo->prepare("SELECT image FROM recipes WHERE id=?");
            $stmt->execute([$id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                throw new RuntimeException('Recipe not found.');
            }
            $currentImage = $current['image'];

            // If new image uploaded
            $imagePath = $currentImage;
            if (!empty($_FILES['image']['name'])) {
                $imagePath = uploadImage($_FILES['image'], $currentImage);
            }

            // Update main recipe (enhanced to also update nutrition/rating/notes)
            $stmt = $pdo->prepare("UPDATE recipes 
                SET title=?, description=?, category_id=?, cooking_time=?, image=?, servings=?, calories=?, protein=?, carbs=?, fat=?, rating=?, notes=?
                WHERE id=?");
            $stmt->execute([
                $title, $description, $category_id, $cooking_time, $imagePath,
                $servings, $calories, $protein, $carbs, $fat, $rating, $notes,
                $id
            ]);

            // Update ingredients
            $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id=?")->execute([$id]);
            if (!empty($_POST['ingredients']) && is_array($_POST['ingredients'])) {
                foreach ($_POST['ingredients'] as $ingredientRaw) {
                    $ingredientRaw = trim($ingredientRaw);
                    if ($ingredientRaw !== '') {
                        $parts  = explode('|', $ingredientRaw);
                        $measure = trim($parts[0] ?? '');
                        $name    = trim($parts[1] ?? $parts[0]);
                        if ($name !== '') {
                            $stmtIng = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, measure, ingredient) VALUES (?, ?, ?)");
                            $stmtIng->execute([$id, $measure, $name]);
                        }
                    }
                }
            }

            // Update instructions
            $pdo->prepare("DELETE FROM recipe_instructions WHERE recipe_id=?")->execute([$id]);
            if (!empty($_POST['instructions']) && is_array($_POST['instructions'])) {
                $order = 1;
                foreach ($_POST['instructions'] as $step) {
                    $step = trim($step);
                    if ($step !== '') {
                        $stmtInst = $pdo->prepare("INSERT INTO recipe_instructions (recipe_id, step, step_order) VALUES (?, ?, ?)");
                        $stmtInst->execute([$id, $step, $order]);
                        $order++;
                    }
                }
            }

            header("Location: recipes.php?updated=1");
            exit();
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['delete_id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('Invalid recipe ID.');
            }

            // Get current image
            $stmt = $pdo->prepare("SELECT image FROM recipes WHERE id=?");
            $stmt->execute([$id]);
            $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

            // Delete related data (in case no foreign key cascade)
            $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM recipe_instructions WHERE recipe_id=?")->execute([$id]);

            // Delete recipe
            $stmt = $pdo->prepare("DELETE FROM recipes WHERE id=?");
            $stmt->execute([$id]);

            // Remove image file
            if ($recipe && !empty($recipe['image']) && file_exists(__DIR__ . '/../' . $recipe['image'])) {
                @unlink(__DIR__ . '/../' . $recipe['image']);
            }

            header("Location: recipes.php?deleted=1");
            exit();
        }
    } catch (Throwable $e) {
        // Stay on page and show error
        $error = $e->getMessage();
    }
}

// Fetch all recipes
$recipes = $pdo->query("SELECT r.*, c.name AS category, u.name AS author 
                        FROM recipes r 
                        LEFT JOIN categories c ON r.category_id=c.id 
                        LEFT JOIN users u ON r.user_id=u.id 
                        ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch ingredients and instructions grouped by recipe for modal prefill
$ingredientsByRecipe = [];
$instructionsByRecipe = [];
$recipeIds = array_map(static fn($r) => (int)$r['id'], $recipes);

if (!empty($recipeIds)) {
    $placeholders = implode(',', array_fill(0, count($recipeIds), '?'));

    $stmtIng = $pdo->prepare("SELECT recipe_id, measure, ingredient FROM recipe_ingredients WHERE recipe_id IN ($placeholders) ORDER BY id ASC");
    $stmtIng->execute($recipeIds);
    while ($row = $stmtIng->fetch(PDO::FETCH_ASSOC)) {
        $rid = (int)$row['recipe_id'];
        if (!isset($ingredientsByRecipe[$rid])) $ingredientsByRecipe[$rid] = [];
        $ingredientsByRecipe[$rid][] = [
            'measure'    => $row['measure'],
            'ingredient' => $row['ingredient']
        ];
    }

    $stmtInst = $pdo->prepare("SELECT recipe_id, step, step_order FROM recipe_instructions WHERE recipe_id IN ($placeholders) ORDER BY step_order ASC");
    $stmtInst->execute($recipeIds);
    while ($row = $stmtInst->fetch(PDO::FETCH_ASSOC)) {
        $rid = (int)$row['recipe_id'];
        if (!isset($instructionsByRecipe[$rid])) $instructionsByRecipe[$rid] = [];
        $instructionsByRecipe[$rid][] = [
            'step'       => $row['step'],
            'step_order' => (int)$row['step_order']
        ];
    }
}

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
        justify-content: center;
        padding: 0.6rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-primary { background-color: var(--primary); color: white; }
    .btn-primary:hover { background-color: var(--primary-dark); }

    .btn-success { background-color: var(--secondary); color: white; }
    .btn-success:hover { background-color: #059669; }

    .btn-danger { background-color: var(--danger); color: white; }
    .btn-danger:hover { background-color: var(--danger-dark); }

    .btn-outline {
        border: 1px solid #D1D5DB;
        background: white; color: #374151;
    }
    .btn-outline:hover {
        background: #F9FAFB;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border-radius: 10px;
        border: 1px solid #D1D5DB;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background: white;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .table-responsive { overflow-x: auto; }
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
    /* tr:last-child td { border-bottom: none; } */

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

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background-color: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .modal.show { display: flex; }
    .modal-content {
        background: white;
        border-radius: 12px;
        width: 95%;
        max-width: 720px;
        padding: 1.25rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;
    }
    .modal-title {
        font-weight: 700; font-size: 1.125rem; color: #111827;
    }

    .recipe-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 10px;
    }

    .pill {
        display: inline-block;
        background: #F3F4F6;
        color: #374151;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    @media (max-width: 640px) {
        .row { grid-template-columns: 1fr; }
        .mobile-hidden { display: none; }
    }

    .list-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .list-item {
        display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.5rem; align-items: center;
    }
    .list-item.single {
        grid-template-columns: 1fr auto;
    }
    .icon-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2rem; height: 2rem; border-radius: 8px; background: #F3F4F6; color: #374151; border: 1px solid #E5E7EB;
    }
    .icon-btn:hover { background: #E5E7EB; }

    .img-preview {
        width: 100%;
        max-height: 220px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #E5E7EB;
    }
</style>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-300">Recipe Management</h1>
            <p class="text-gray-500">Create, edit, and manage recipes with ease.</p>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-primary" onclick="openModal('addRecipeModal')">
                <i class="fas fa-plus mr-2"></i> Add Recipe
            </button>
            <a href="../index.php" class="btn btn-outline">
                <i class="fas fa-home mr-2"></i> Back to Site
            </a>
        </div>
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

    <!-- Recipes table -->
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-list mr-2 text-blue-500"></i>All Recipes
            </h2>
        </div>
        <div class="table-responsive">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="p-4">Title</th>
                        <th class="p-4">Category</th>
                        <th class="p-4 mobile-hidden">Cooking Time</th>
                        <th class="p-4 mobile-hidden">Author</th>
                        <th class="p-4">Image</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recipes)): ?>
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-300">No recipes yet. Click "Add Recipe" to get started.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recipes as $r): ?>
                            <?php
                                $rid = (int)$r['id'];
                                $recData = [
                                    'id'            => $rid,
                                    'title'         => $r['title'],
                                    'description'   => $r['description'],
                                    'category_id'   => (int)$r['category_id'],
                                    'cooking_time'  => $r['cooking_time'],
                                    'servings'      => $r['servings'],
                                    'calories'      => $r['calories'],
                                    'protein'       => $r['protein'],
                                    'carbs'         => $r['carbs'],
                                    'fat'           => $r['fat'],
                                    'rating'        => (float)$r['rating'],
                                    'notes'         => $r['notes'],
                                    'image'         => $r['image'],
                                    'ingredients'   => $ingredientsByRecipe[$rid] ?? [],
                                    'instructions'  => array_map(static fn($s) => $s['step'], $instructionsByRecipe[$rid] ?? [])
                                ];
                                $dataAttr = htmlspecialchars(json_encode($recData), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr class="hover:bg-gray-700 text-black text-gray-300">
                                <td class="p-4 font-medium"><?= htmlspecialchars($r['title']) ?></td>
                                <td class="p-4"><span class="pill"><?= htmlspecialchars($r['category'] ?? 'Uncategorized') ?></span></td>
                                <td class="p-4 mobile-hidden"><?= htmlspecialchars($r['cooking_time'] ?? '-') ?></td>
                                <td class="p-4 mobile-hidden"><?= htmlspecialchars($r['author'] ?? '—') ?></td>
                                <td class="p-4">
                                    <?php if (!empty($r['image'])): ?>
                                        <img src="../<?= htmlspecialchars($r['image']) ?>" alt="Recipe Image" class="recipe-image">
                                    <?php else: ?>
                                        <div class="recipe-image bg-gray-200 flex items-center justify-center rounded">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            class="btn btn-primary text-sm"
                                            data-recipe='<?= $dataAttr ?>'
                                            onclick="openEditFromButton(this)">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </button>

                                        <button
                                            class="btn btn-danger text-sm"
                                            onclick="openDeleteModal(<?= (int)$r['id'] ?>, '<?= htmlspecialchars(addslashes($r['title']), ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash mr-1"></i> Delete
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

<!-- Add Recipe Modal -->
<div id="addRecipeModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-utensils mr-2 text-blue-500"></i>Add New Recipe</div>
            <button class="icon-btn" onclick="closeModal('addRecipeModal')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        <form id="addRecipeForm" method="POST" enctype="multipart/form-data" class="space-y-4 text-black">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="create">

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. Creamy Alfredo Pasta" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" class="form-select">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cooking Time</label>
                    <input type="text" name="cooking_time" class="form-input" placeholder="e.g. 30 mins">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Servings</label>
                    <input type="text" name="servings" class="form-input" placeholder="e.g. 4">
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Calories</label>
                    <input type="text" name="calories" class="form-input" placeholder="e.g. 520 kcal">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Protein</label>
                    <input type="text" name="protein" class="form-input" placeholder="e.g. 25 g">
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carbs</label>
                    <input type="text" name="carbs" class="form-input" placeholder="e.g. 60 g">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fat</label>
                    <input type="text" name="fat" class="form-input" placeholder="e.g. 20 g">
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                    <input type="number" step="0.1" max="5" min="0" name="rating" class="form-input" placeholder="e.g. 4.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                    <input type="file" name="image" class="form-input" accept="image/*" onchange="previewImage(this, 'addImagePreview')">
                </div>
            </div>

            <img id="addImagePreview" class="img-preview" alt="" style="display:none;">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" class="form-textarea" rows="3" placeholder="Short description"></textarea>
            </div>

            <!-- Ingredients Repeater -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Ingredients</label>
                    <button type="button" class="icon-btn" onclick="addIngredientRow('add_ingredients_list')"><i class="fas fa-plus"></i></button>
                </div>
                <div id="add_ingredients_list" class="list-group">
                    <div class="list-item">
                        <input type="text" class="form-input" placeholder="e.g. 2 tbsp" data-role="measure">
                        <input type="text" class="form-input" placeholder="e.g. Butter" data-role="ingredient">
                        <button type="button" class="icon-btn" onclick="removeListRow(this)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>

            <!-- Instructions Repeater -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Instructions</label>
                    <button type="button" class="icon-btn" onclick="addInstructionRow('add_instructions_list')"><i class="fas fa-plus"></i></button>
                </div>
                <div id="add_instructions_list" class="list-group">
                    <div class="list-item single">
                        <input type="text" class="form-input" placeholder="e.g. Melt butter in a pan" data-role="instruction">
                        <button type="button" class="icon-btn" onclick="removeListRow(this)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipe Notes</label>
                <textarea name="notes" class="form-textarea" rows="2" placeholder="Any extra tips or notes..."></textarea>
            </div>

            <!-- Hidden fields populated by JS before submit (to match backend expectations) -->
            <div id="add_hidden_fields"></div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-outline" onclick="closeModal('addRecipeModal')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save mr-2"></i> Save Recipe</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Recipe Modal -->
<div id="editRecipeModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-pen-to-square mr-2 text-blue-500"></i>Edit Recipe</div>
            <button class="icon-btn" onclick="closeModal('editRecipeModal')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        <form id="editRecipeForm" method="POST" enctype="multipart/form-data" class="space-y-4 text-black">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="edit_id" id="edit_id">

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="edit_title" class="form-input" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="edit_category_id" class="form-select">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cooking Time</label>
                    <input type="text" name="cooking_time" id="edit_cooking_time" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Servings</label>
                    <input type="text" name="servings" id="edit_servings" class="form-input">
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Calories</label>
                    <input type="text" name="calories" id="edit_calories" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Protein</label>
                    <input type="text" name="protein" id="edit_protein" class="form-input">
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carbs</label>
                    <input type="text" name="carbs" id="edit_carbs" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fat</label>
                    <input type="text" name="fat" id="edit_fat" class="form-input">
                </div>
            </div>

            <div class="row">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                    <input type="number" step="0.1" max="5" min="0" name="rating" id="edit_rating" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Image (optional)</label>
                    <input type="file" name="image" class="form-input" accept="image/*" onchange="previewImage(this, 'editImagePreview')">
                </div>
            </div>

            <img id="editImagePreview" class="img-preview" alt="" style="display:none;">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="edit_description" class="form-textarea" rows="3"></textarea>
            </div>

            <!-- Ingredients Repeater (Edit) -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Ingredients</label>
                    <button type="button" class="icon-btn" onclick="addIngredientRow('edit_ingredients_list')"><i class="fas fa-plus"></i></button>
                </div>
                <div id="edit_ingredients_list" class="list-group"></div>
            </div>

            <!-- Instructions Repeater (Edit) -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Instructions</label>
                    <button type="button" class="icon-btn" onclick="addInstructionRow('edit_instructions_list')"><i class="fas fa-plus"></i></button>
                </div>
                <div id="edit_instructions_list" class="list-group"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipe Notes</label>
                <textarea name="notes" id="edit_notes" class="form-textarea" rows="2"></textarea>
            </div>

            <!-- Hidden fields populated by JS before submit -->
            <div id="edit_hidden_fields"></div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn btn-outline" onclick="closeModal('editRecipeModal')">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save mr-2"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteRecipeModal" class="modal" aria-hidden="true">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title text-red-600"><i class="fas fa-trash mr-2"></i>Delete Recipe</div>
            <button class="icon-btn" onclick="closeModal('deleteRecipeModal')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-gray-700 mb-3">Are you sure you want to delete this recipe?</p>
        <p class="text-gray-900 font-semibold mb-4" id="delete_recipe_title"></p>
        <form id="deleteRecipeForm" method="POST" class="flex justify-end gap-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_id" id="delete_id">
            <button type="button" class="btn btn-outline" onclick="closeModal('deleteRecipeModal')">Cancel</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-trash mr-2"></i> Delete</button>
        </form>
    </div>
</div>

<script>
    // Modal control
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
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
        }
    });
    document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('click', (e) => {
            if (e.target === m) closeModal(m.id);
        });
    });

    // Alerts auto-hide
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

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

    // Repeater helpers
    function removeListRow(el) {
        const row = el.closest('.list-item');
        const parent = row && row.parentElement;
        if (row && parent && parent.children.length > 1) {
            row.remove();
        } else if (row && parent) {
            // Clear instead of remove last row
            row.querySelectorAll('input').forEach(i => i.value = '');
        }
    }
    function addIngredientRow(containerId, data = {measure:'', ingredient:''}) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'list-item';
        wrapper.innerHTML = `
            <input type="text" class="form-input" placeholder="e.g. 1 cup" data-role="measure" value="${escapeHtml(data.measure || '')}">
            <input type="text" class="form-input" placeholder="e.g. Flour" data-role="ingredient" value="${escapeHtml(data.ingredient || '')}">
            <button type="button" class="icon-btn" onclick="removeListRow(this)"><i class="fas fa-trash"></i></button>
        `;
        container.appendChild(wrapper);
    }
    function addInstructionRow(containerId, text = '') {
        const container = document.getElementById(containerId);
        if (!container) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'list-item single';
        wrapper.innerHTML = `
            <input type="text" class="form-input" placeholder="e.g. Mix and simmer for 10 mins" data-role="instruction" value="${escapeHtml(text)}">
            <button type="button" class="icon-btn" onclick="removeListRow(this)"><i class="fas fa-trash"></i></button>
        `;
        container.appendChild(wrapper);
    }
    function escapeHtml(s) {
        return (''+s).replace(/[&<>"']/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
        });
    }

    // Build hidden fields to match backend: ingredients[] "measure|ingredient", instructions[]
    function buildHiddenFields(form, ingredientsContainerId, instructionsContainerId, hiddenContainerId) {
        const hidden = document.getElementById(hiddenContainerId);
        if (!hidden) return;
        hidden.innerHTML = '';

        const ingContainer = document.getElementById(ingredientsContainerId);
        if (ingContainer) {
            ingContainer.querySelectorAll('.list-item').forEach(row => {
                const m = (row.querySelector('input[data-role="measure"]')?.value || '').trim();
                const n = (row.querySelector('input[data-role="ingredient"]')?.value || '').trim();
                if (n !== '' || m !== '') {
                    const v = (m !== '') ? (m + '|' + n) : n;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ingredients[]';
                    input.value = v;
                    hidden.appendChild(input);
                }
            });
        }

        const instContainer = document.getElementById(instructionsContainerId);
        if (instContainer) {
            instContainer.querySelectorAll('input[data-role="instruction"]').forEach(inp => {
                const text = (inp.value || '').trim();
                if (text !== '') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'instructions[]';
                    input.value = text;
                    hidden.appendChild(input);
                }
            });
        }
    }

    // Add form submit handling
    const addForm = document.getElementById('addRecipeForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            buildHiddenFields(addForm, 'add_ingredients_list', 'add_instructions_list', 'add_hidden_fields');
        });
    }

    // Edit modal populate
    function openEditFromButton(btn) {
        const data = btn.getAttribute('data-recipe');
        if (!data) return;
        let r;
        try { r = JSON.parse(data); } catch(e) { return; }

        document.getElementById('edit_id').value = r.id;
        document.getElementById('edit_title').value = r.title || '';
        document.getElementById('edit_category_id').value = r.category_id || '';
        document.getElementById('edit_cooking_time').value = r.cooking_time || '';
        document.getElementById('edit_servings').value = r.servings || '';
        document.getElementById('edit_calories').value = r.calories || '';
        document.getElementById('edit_protein').value = r.protein || '';
        document.getElementById('edit_carbs').value = r.carbs || '';
        document.getElementById('edit_fat').value = r.fat || '';
        document.getElementById('edit_rating').value = r.rating ?? '';
        document.getElementById('edit_description').value = r.description || '';
        document.getElementById('edit_notes').value = r.notes || '';

        const editIng = document.getElementById('edit_ingredients_list');
        editIng.innerHTML = '';
        if (Array.isArray(r.ingredients) && r.ingredients.length) {
            r.ingredients.forEach(ing => addIngredientRow('edit_ingredients_list', ing));
        } else {
            addIngredientRow('edit_ingredients_list');
        }

        const editInst = document.getElementById('edit_instructions_list');
        editInst.innerHTML = '';
        if (Array.isArray(r.instructions) && r.instructions.length) {
            r.instructions.forEach(s => addInstructionRow('edit_instructions_list', s));
        } else {
            addInstructionRow('edit_instructions_list');
        }

        // Show existing image preview
        const preview = document.getElementById('editImagePreview');
        if (r.image) {
            preview.src = '../' + r.image;
            preview.style.display = 'block';
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }

        openModal('editRecipeModal');
    }

    // Edit form submit (build hidden fields)
    const editForm = document.getElementById('editRecipeForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            buildHiddenFields(editForm, 'edit_ingredients_list', 'edit_instructions_list', 'edit_hidden_fields');
        });
    }

    // Delete modal
    function openDeleteModal(id, title) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_recipe_title').textContent = title || '';
        openModal('deleteRecipeModal');
    }

    // Initialize one default row for add modal if not present (already included)
    // Provide one more for a quicker UX
    addIngredientRow('add_ingredients_list');
    addInstructionRow('add_instructions_list');
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>