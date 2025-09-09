<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($category['name'] ?? 'Category') ?> - TasteCraft</title>
  <meta name="theme-color" content="#0f172a">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-dark: #0f172a;
      --primary: #1e293b;
      --secondary: #f59e0b;
      --secondary-light: #fcd34d;
      --accent: #b45309;
      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --card-bg: rgba(15, 23, 42, 0.7);
      --hover-bg: rgba(245, 158, 11, 0.1);
      --border-soft: rgba(148,163,184,.25);
    }

    /* Animations */
    @keyframes gradientShift {
      0% {background-position:0% 50%;}
      50% {background-position:100% 50%;}
      100% {background-position:0% 50%;}
    }
    @keyframes shimmer {
      0% {left: -100%;}
      100% {left: 100%;}
    }
    @keyframes float {
      0%{transform:translateY(0)}50%{transform:translateY(-8px)}100%{transform:translateY(0)}
    }

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
    .nav-link{
      padding:.5rem 1rem; border-radius: 8px; color: var(--text-secondary);
      transition: all .25s ease;
    }
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
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(2, 6, 23, 0.45);
    }

    .chip {
      display: inline-flex; align-items: center; gap:.35rem;
      padding: .25rem .5rem; border-radius: 9999px;
      background: rgba(245, 158, 11, .15);
      border: 1px solid rgba(245, 158, 11, .35);
      color: var(--secondary-light);
      font-size: .75rem; font-weight: 700;
    }

    /* Recipe Card */
    .recipe-card { overflow: hidden; position: relative; display:flex; flex-direction:column; height:100%; }
    .recipe-cover {
      position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 16 / 9; background: rgba(2,6,23,.4);
      border: 1px solid var(--border-soft);
    }
    .recipe-cover img {
      width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .35s ease;
    }
    .recipe-card:hover .recipe-cover img { transform: scale(1.03); }
    .recipe-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(180deg, rgba(2,6,23,0) 20%, rgba(2,6,23,0.6) 65%, rgba(2,6,23,0.85) 100%);
    }
    .title-link { color: var(--text-primary); }
    .title-link:hover { color: var(--secondary-light); }

    /* Line clamp without plugin */
    .clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Buttons */
    .btn { display:inline-flex; align-items:center; gap:.5rem; padding:.55rem .9rem; border-radius:10px; font-weight:800; border:1px solid transparent; }
    .btn-primary { background: var(--secondary); color:#111827; }
    .btn-primary:hover { background: var(--secondary-light); }
    .btn-outline { background: transparent; color: var(--text-secondary); border-color: var(--border-soft); }
    .btn-outline:hover { background: rgba(148,163,184,.08); color: var(--text-primary); }

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
    .social-icon{
      width:40px;height:40px;border-radius:50%;
      border:1px solid var(--border-soft);display:flex;align-items:center;justify-content:center;
      background:linear-gradient(145deg,rgba(15,23,42,.65),rgba(2,6,23,.65));transition:.25s;
      color: var(--text-secondary);
    }
    .social-icon:hover{transform:translateY(-4px);box-shadow:0 8px 18px rgba(180,83,9,0.35);border-color: var(--secondary); color: var(--secondary-light);}

    /* Utilities */
    .floating { animation: float 4.5s ease-in-out infinite; }
  </style>
</head>
<body>
    <!-- TOP NAVIGATION -->
<nav class="nav-gradient">
  <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between relative">
    <!-- Logo -->
    <a href="index.php" class="flex items-center space-x-2">
      <i class="fas fa-utensils text-2xl floating" style="color: var(--secondary);"></i>
      <span class="text-xl font-extrabold" style="color: var(--text-primary);">Taste<span style="color: var(--secondary);">Craft</span></span>
    </a>
    <!-- Menu desktop -->
    <div class="hidden md:flex space-x-2 items-center">
      <a href="index.php" class="nav-link">Home</a>
      <a href="recipes.php" class="nav-link">Recipes</a>
      <a href="contact.php" class="nav-link">Contact</a>
      <?php if ($isLoggedIn): ?>
        <span class="px-3 text-sm" style="color: var(--text-secondary);">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
        <a href="logout.php" class="btn btn-primary text-sm">Logout</a>
      <?php else: ?>
        <a href="login.php" class="nav-link">Login</a>
        <a href="signup.php" class="btn btn-primary text-sm">Sign Up</a>
      <?php endif; ?>
    </div>
    <!-- Hamburger -->
    <button id="mobileMenuBtn" class="md:hidden text-2xl" style="color: var(--text-primary);"><i class="fas fa-bars"></i></button>
  </div>
  <!-- Mobile slide menu -->
  <div id="mobileMenu" class="hidden flex-col px-6 py-3 space-y-2 md:hidden" style="background: rgba(2,6,23,.6); border-top:1px solid var(--border-soft);">
    <a href="index.php" class="nav-link">Home</a>
    <a href="recipes.php" class="nav-link">Recipes</a>
    <a href="contact.php" class="nav-link">Contact</a>
    <?php if ($isLoggedIn): ?>
      <span class="px-2 text-sm" style="color: var(--text-secondary);">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
      <a href="logout.php" class="btn btn-primary text-sm w-max">Logout</a>
    <?php else: ?>
      <a href="login.php" class="nav-link">Login</a>
      <a href="signup.php" class="btn btn-primary text-sm w-max">Sign Up</a>
    <?php endif; ?>
  </div>
</nav>

<main class="pt-10 md:pt-12">
<?= $content ?? '' ?>
</main>

<footer class="footer-gradient text-white py-12 px-6 mt-10">
  <div class="container mx-auto max-w-7xl grid grid-cols-1 md:grid-cols-4 gap-8">
    <div>
      <div class="flex items-center mb-4">
        <i class="fas fa-utensils text-2xl mr-2" style="color: var(--secondary);"></i>
        <span class="text-xl font-bold">Taste<span style="color: var(--secondary);">Craft</span></span>
      </div>
      <p style="color: var(--text-secondary);">Discover and share delicious recipes.</p>
    </div>
    <div>
      <h3 class="font-bold mb-4" style="color: var(--secondary-light);">Quick Links</h3>
      <ul class="space-y-2">
        <li><a href="index.php" class="nav-link px-0">Home</a></li>
        <li><a href="recipes.php" class="nav-link px-0">Recipes</a></li>
        <li><a href="about.php" class="nav-link px-0">About</a></li>
        <li><a href="contact.php" class="nav-link px-0">Contact</a></li>
      </ul>
    </div>
    <div>
      <h3 class="font-bold mb-4" style="color: var(--secondary-light);">Categories</h3>
      <ul class="space-y-2">
        <li><a href="category.php?id=1" class="nav-link px-0">Breakfast</a></li>
        <li><a href="category.php?id=2" class="nav-link px-0">Lunch</a></li>
        <li><a href="category.php?id=3" class="nav-link px-0">Dinner</a></li>
        <li><a href="category.php?id=4" class="nav-link px-0">Desserts</a></li>
      </ul>
    </div>
    <div>
      <h3 class="font-bold mb-4" style="color: var(--secondary-light);">Connect</h3>
      <div class="flex space-x-4">
        <a class="social-icon" href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a class="social-icon" href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a class="social-icon" href="#" aria-label="Pinterest"><i class="fab fa-pinterest-p"></i></a>
        <a class="social-icon" href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
      </div>
    </div>
  </div>
  <div class="text-center mt-10" style="color: var(--text-secondary);">&copy; <?= date('Y') ?> TasteCraft. All rights reserved.</div>
</footer>

</body>
</html>