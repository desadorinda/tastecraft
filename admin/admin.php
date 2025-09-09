<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - TasteCraft</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary-dark: #000000ff;
  --primary: #000000ff;
  --secondary: #f59e0b;
  --secondary-light: #fcd34d;
  --accent: #b45309;
  --text-primary: #f8fafc;
  --text-secondary: #cbd5e1;
  --card-bg: rgba(0, 0, 0, 0.8);
  --hover-bg: rgba(245, 158, 11, 0.1);
}

body {
  background: linear-gradient(135deg, var(--primary-dark), var(--primary));
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  color: var(--text-primary);
}

.sidebar {
  background: var(--primary-dark);
  border-right: 1px solid rgba(245, 158, 11, 0.1);
}

.topbar {
  background: var(--primary);
  border-bottom: 1px solid rgba(245, 158, 11, 0.1);
  backdrop-filter: blur(10px);
}

.card {
  background: var(--card-bg);
  border-radius: 0.75rem;
  border: 1px solid rgba(245, 158, 11, 0.15);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.input-field {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(245, 158, 11, 0.2);
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  width: 100%;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.input-field:focus {
  border-color: var(--secondary);
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
  outline: none;
}

.submit-btn {
  background: linear-gradient(to right, var(--secondary), var(--accent));
  color: var(--primary-dark);
  font-weight: 600;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  transition: all 0.3s ease;
}

.submit-btn:hover {
  background: linear-gradient(to right, var(--secondary-light), var(--secondary));
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(180, 83, 9, 0.3);
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  color: var(--text-secondary);
  transition: all 0.3s ease;
  margin-bottom: 0.25rem;
}

.nav-link:hover {
  background: var(--hover-bg);
  color: var(--secondary-light);
}

.nav-link.active {
  background: var(--hover-bg);
  color: var(--secondary);
  border-left: 3px solid var(--secondary);
}

.stats-card {
  transition: all 0.3s ease;
}

.stats-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
}

.mobile-menu-btn {
  display: none;
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: 50;
  }
  
  .sidebar.open {
    transform: translateX(0);
  }
  
  .mobile-menu-btn {
    display: block;
  }
  
  .overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 40;
  }
  
  .overlay.open {
    display: block;
  }
}
</style>
</head>
<body class="bg-gray-900">

<!-- Overlay for mobile -->
<div class="overlay" id="overlay"></div>

<!-- Topbar -->
<header class="topbar fixed top-0 left-0 right-0 z-30 py-3 px-6 flex justify-between items-center">
  <div class="flex items-center">
    <button class="mobile-menu-btn mr-4 text-amber-400" id="mobileMenuButton">
      <i class="fas fa-bars text-xl"></i>
    </button>
    <a href="index.php" class="flex items-center space-x-2">
      <i class="fas fa-utensils text-2xl text-amber-400"></i>
      <span class="font-bold text-xl">Taste<span class="text-amber-400">Craft</span> Admin</span>
    </a>
  </div>
  
  <div class="flex items-center space-x-4">
    <div class="text-right hidden md:block">
      <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES) ?></p>
      <p class="text-xs text-amber-300">Administrator</p>
    </div>
    <a href="../logout.php" class="bg-amber-500 text-gray-900 px-4 py-2 rounded-lg font-medium hover:bg-amber-400 transition">Logout</a>
  </div>
</header>

<div class="flex pt-16">
  <!-- Sidebar -->
  <aside class="sidebar fixed inset-y-0 left-0 top-16 w-64 pt-6 pb-10 overflow-y-auto" id="sidebar">
    <nav class="px-4">
      <div class="mb-8 px-3">
        <h2 class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">Dashboard</h2>
        <a href="admin-dashboard.php" class="nav-link <?= $current_page == 'admin-dashboard.php' ? 'active' : '' ?>">
          <i class="fas fa-chart-pie mr-3"></i>Overview
        </a>
      </div>
      
      <div class="mb-8 px-3">
        <h2 class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">Content Management</h2>
        <a href="add-recipe.php" class="nav-link <?= $current_page == 'add-recipe.php' ? 'active' : '' ?>">
          <i class="fas fa-plus-circle mr-3"></i>Add Recipe
        </a>
        <a href="manage-recipes.php" class="nav-link <?= $current_page == 'manage-recipes.php' ? 'active' : '' ?>">
          <i class="fas fa-list mr-3"></i>Manage Recipes
        </a>
        <a href="categories.php" class="nav-link <?= $current_page == 'categories.php' ? 'active' : '' ?>">
          <i class="fas fa-tags mr-3"></i>Categories
        </a>
      </div>
      
      <div class="mb-8 px-3">
        <h2 class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">User Management</h2>
        <a href="users.php" class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>">
          <i class="fas fa-users mr-3"></i>Users
        </a>
      </div>
      
      <div class="px-3">
        <h2 class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">Settings</h2>
        <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
          <i class="fas fa-cog mr-3"></i>System Settings
        </a>
      </div>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 md:ml-64 p-6">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-2xl md:text-3xl font-bold mb-2">Admin Dashboard</h1>
      <p class="text-gray-400 mb-8">Welcome back, <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES) ?>. Here's what's happening today.</p>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
        <div class="stats-card card p-5">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-400 text-sm">Total Recipes</p>
              <h3 class="text-2xl font-bold mt-1">145</h3>
            </div>
            <div class="bg-amber-400/10 p-3 rounded-lg">
              <i class="fas fa-utensils text-amber-400"></i>
            </div>
          </div>
          <p class="text-amber-400 text-xs mt-3"><i class="fas fa-arrow-up mr-1"></i> 12% from last month</p>
        </div>
        
        <div class="stats-card card p-5">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-gray-400 text-sm">Registered Users</p>
              <h3 class="text-2xl font-bold mt-1">520</h3>
            </div>
            <div class="bg-amber-400/10 p-3 rounded-lg">
              <i class="fas fa-users text-amber-400"></i>
            </div>
          </div>
          <p class="text-amber-400 text-xs mt-3"><i class="fas fa-arrow-up mr-1"></i> 8% from last month</p>
        </div>
    
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Add Recipe Form -->
        <div class="card p-6 lg:col-span-2">
          <h2 class="text-xl font-bold mb-6 flex items-center">
            <i class="fas fa-plus-circle mr-2 text-amber-400"></i> Add New Recipe
          </h2>
          <form method="POST" action="save-recipe.php" enctype="multipart/form-data" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Recipe Title</label>
                <input type="text" name="title" class="input-field" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                <select name="category" class="input-field" required>
                  <option value="">Select category</option>
                  <option>Breakfast</option>
                  <option>Lunch</option>
                  <option>Dinner</option>
                  <option>Dessert</option>
                </select>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Cooking Time</label>
                <input type="text" name="time" class="input-field" placeholder="30 mins">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Recipe Image</label>
                <input type="file" name="image" class="input-field py-2.5">
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
              <textarea name="description" rows="3" class="input-field"></textarea>
            </div>
            
            <div class="pt-4">
              <button type="submit" class="submit-btn">
                <i class="fas fa-plus-circle mr-2"></i> Add Recipe
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
// Mobile menu functionality
const mobileMenuButton = document.getElementById('mobileMenuButton');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

function toggleSidebar() {
  sidebar.classList.toggle('open');
  overlay.classList.toggle('open');
}

mobileMenuButton.addEventListener('click', toggleSidebar);
overlay.addEventListener('click', toggleSidebar);

// Active link highlighting
const currentPage = '<?= $current_page ?>';
const navLinks = document.querySelectorAll('.nav-link');

navLinks.forEach(link => {
  const href = link.getAttribute('href');
  if (href === currentPage) {
    link.classList.add('active');
  }
});
</script>
</body>
</html>