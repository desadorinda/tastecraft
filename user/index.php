<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

// Create PDO connection using Database class
require_once __DIR__ . '/../includes/database.php';
$db = new Database();
$pdo = $db->getConnection();  // now $pdo exists

$userId = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_recipes WHERE user_id = ?");
$stmt->execute([$userId]);
$totalSavedRecipe = $stmt->fetchColumn();


$pageTitle = "Dashboard - TasteCraft";
$current_page = "index.php";

ob_start(); ?>
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
    --gold-primary: #FFD700;
    --gold-dark: #B8860B;
    --gold-light: #F8DE7E;
  }
  body {
    background: linear-gradient(135deg, var(--gold-primary) 0%, var(--primary-dark) 100%);
    color: var(--text-primary);
    min-height: 100vh;
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

</style>

         <div class="flex items-center justify-between mb-6">
<div>
<h1 class="text-2xl md:text-3xl font-bold mb-2">Welcome back, <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES) ?>!</h1>
      <p class="text-gray-400 mb-8">Here's what you've been cooking lately.</p>
</div>
      <div class="flex items-center gap-2">
        <a href="../index.php" class="btn btn-outline"><i class="fas fa-home"></i> Back to Site</a>
      </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
        <div class="stats-card card p-5">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-400 text-sm">Total Saved Recipes</p>
              <h3 class="text-2xl font-bold mt-1"><?= $totalSavedRecipe ?></h3>
            </div>
            <div class="bg-amber-400/10 p-3 rounded-lg">
              <i class="fas fa-utensils text-amber-400"></i>
            </div>
          </div>
          <!-- <p class="text-amber-400 text-xs mt-3"><i class="fas fa-arrow-up mr-1"></i> 12% from last month</p> -->
        </div>
    
      </div>
<?php
$content = ob_get_clean();
include 'layout.php';
