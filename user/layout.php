<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard - TasteCraft</title>
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
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 15px -3px rgba(180, 83, 9, 0.3);
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

.recipe-image {
  height: 180px;
  object-fit: cover;
  border-top-left-radius: 0.75rem;
  border-top-right-radius: 0.75rem;
}

.btn-primary {
  background: linear-gradient(to right, var(--secondary), var(--accent));
  color: var(--primary-dark);
  font-weight: 600;
  border-radius: 0.5rem;
  padding: 0.5rem 1rem;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background: linear-gradient(to right, var(--secondary-light), var(--secondary));
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(180, 83, 9, 0.3);
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

<!-- Top Navbar -->
<nav class="bg-gray-900 text-white px-4 py-2 flex justify-between items-center shadow">
  <div class="flex items-center gap-2">
    <span class="font-bold text-lg">TasteCraft</span>
  </div>
  <ul class="flex gap-4">
    <li><a href="index.php" class="hover:text-yellow-400">Dashboard</a></li>
    <li><a href="saved_recipes.php" class="hover:text-yellow-400">Saved Recipes</a></li>
    <li><a href="profile.php" class="hover:text-yellow-400">Profile</a></li>
    <li><a href="../logout.php" class="hover:text-yellow-400">Logout</a></li>
  </ul>
</nav>
<main class="p-6" style="background: var(--card-bg); min-height: 100vh;">
  <div class="max-w-7xl mx-auto">
    <?= $content ?? '' ?>
  </div>
</main>

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