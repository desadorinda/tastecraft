<?php
// index.php - dynamic categories
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Logged in check (config.php manages session_start)
$isLoggedIn = isset($_SESSION['user_id']);

try {
    // Detect whether categories table has an `image` column (safe SQL)
    $colCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'categories' 
          AND COLUMN_NAME = 'image'
    ");
    $colCheck->execute();
    $hasImageCol = (bool)$colCheck->fetchColumn();

    // Build query selecting available columns and the recipe count
    if ($hasImageCol) {
        $sql = "
          SELECT c.id, c.name, c.description, c.image, c.created_at,
                 COUNT(r.id) AS recipe_count
          FROM categories c
          LEFT JOIN recipes r ON r.category_id = c.id
          GROUP BY c.id, c.name, c.description, c.image, c.created_at
          ORDER BY c.name ASC
        ";
    } else {
        $sql = "
          SELECT c.id, c.name, c.description, c.created_at,
                 COUNT(r.id) AS recipe_count
          FROM categories c
          LEFT JOIN recipes r ON r.category_id = c.id
          GROUP BY c.id, c.name, c.description, c.created_at
          ORDER BY c.name ASC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // For dev: you can log $e->getMessage(). Fall back to an empty list.
    error_log('Category fetch error: ' . $e->getMessage());
    $categories = [];
}

// Helper: default images + icons for categories (fallbacks)
$defaultImages = [
  'Breakfast' => 'https://images.unsplash.com/photo-1551782450-a2132b4ba21d?w=1400&auto=format&fit=crop&q=80',
  'Lunch'     => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=1400&auto=format&fit=crop&q=80',
  'Dinner'    => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=1400&auto=format&fit=crop&q=80',
  'Desserts'  => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=1400&auto=format&fit=crop&q=80',
];
$defaultIcon = 'fa-utensils';
$mapIcons = [
  'Breakfast' => 'fa-egg',
  'Lunch'     => 'fa-bread-slice',
  'Dinner'    => 'fa-drumstick-bite',
  'Desserts'  => 'fa-ice-cream'
];
ob_start()
?>

  <style>
    /* Gradient Animations */
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
      0%{transform:translateY(0)}50%{transform:translateY(-10px)}100%{transform:translateY(0)}
    }
    @keyframes float-slow {
      0%{transform:translateY(0)}50%{transform:translateY(-5px)}100%{transform:translateY(0)}
    }
    .floating {animation: float 4s ease-in-out infinite;}
    .floating-slow {animation: float-slow 6s ease-in-out infinite;}
    .floating-delayed {animation: float 5s ease-in-out 1s infinite;}

    body {
      background: linear-gradient(135deg, #000000, #1a1a1a, #000000, #b45309, #f59e0b);
      background-attachment: fixed;
      color: #fcd34d;
    }
    .nav-gradient{
      background: linear-gradient(105deg,#000000 0%,#1a1a1a 25%,#000000 50%,#b45309 75%,#f59e0b 100%);
      background-size: 200% 200%;
      animation: gradientShift 8s ease infinite;
      box-shadow: 0 4px 30px rgba(180,83,9,0.3);
      position: relative;
    }
    .nav-gradient::before{
      content:'';
      position:absolute;top:0;left:-100%;width:100%;height:100%;
      background:linear-gradient(90deg,transparent,rgba(245,158,11,0.1),transparent);
      animation:shimmer 3s infinite;
    }
    .nav-link{
      padding:.5rem 1rem;
      position:relative;
      transition:all .3s ease;
    }
    .nav-link:hover{color:#f59e0b;}
    .card-gradient{
      background:linear-gradient(145deg,#1a1a1a,#000000,#1a1a1a);
      border:1px solid #b45309;
    }
    .testimonial-card{background:linear-gradient(145deg,#1a1a1a,#000000);border:1px solid #b45309;}
    .footer-gradient{
      background:linear-gradient(355deg,#000000 0%,#1a1a1a 20%,#000000 40%,#b45309 60%,#f59e0b 80%);
      background-size:200% 200%;
      animation: gradientShift 8s ease infinite;
      position:relative;
    }
    .social-icon{
      width:40px;height:40px;border-radius:50%;
      border:1px solid #b45309;display:flex;align-items:center;justify-content:center;
      background:linear-gradient(145deg,#1a1a1a,#000000);transition:.3s;
    }
    .social-icon:hover{transform:translateY(-5px);box-shadow:0 5px 15px rgba(180,83,9,0.4);border-color:#f59e0b;}
    .category-container{border-radius:1rem;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.3);transition:.4s}
    .category-container:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 15px 35px rgba(180,83,9,0.2);}

  :root {
    --primary-dark: #000000ff;
    --primary: #000000ff;
    --secondary: #f59e0b;
    --secondary-light: #fcd34d;
    --accent: #b45309;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --card-bg: rgba(0, 0, 0, 0.8);
    --border-soft: rgba(148,163,184,.25);
  }

  /* Fallback animations if not already present */
  @keyframes float { 0%{transform:translateY(0)}50%{transform:translateY(-8px)}100%{transform:translateY(0)} }
  .floating { animation: float 4.5s ease-in-out infinite; }
  .floating-delayed { animation: float 5.5s ease-in-out .75s infinite; }

  .hero-spot { isolation: isolate; }
  .media-frame {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    background: linear-gradient(145deg, rgba(15,23,42,.65), rgba(2,6,23,.65));
    border: 1px solid var(--border-soft);
    box-shadow: 0 12px 28px rgba(2,6,23,.5);
  }
  /* Gradient ring border */
  .media-frame::before {
    content: "";
    position: absolute; inset: 0;
    border-radius: 16px;
    padding: 1px; /* ring thickness */
    background: linear-gradient(135deg, rgba(245,158,11,.75), rgba(180,83,9,.45), rgba(148,163,184,.25));
    -webkit-mask: 
      linear-gradient(#000 0 0) content-box, 
      linear-gradient(#000 0 0);
    -webkit-mask-composite: xor; 
            mask-composite: exclude;
    pointer-events: none;
  }
  /* Skeleton shimmer while loading */
  .media-frame::after {
    content: "";
    position: absolute; inset: 0;
    background: linear-gradient(90deg, rgba(148,163,184,.08) 0%, rgba(148,163,184,.18) 50%, rgba(148,163,184,.08) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.2s linear infinite;
    opacity: 1;
    transition: opacity .3s ease;
  }
  @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
  .media-frame.loaded::after { opacity: 0; }

  .hero-img {
    display: block;
    width: 100%;
    height: auto;
    aspect-ratio: 16 / 10;
    object-fit: cover;
    transform: scale(1.01);
    transition: transform .35s ease;
  }
  .media-frame:hover .hero-img { transform: scale(1.04); }

  .img-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(2,6,23,0) 20%, rgba(2,6,23,.55) 65%, rgba(2,6,23,.8) 100%);
    pointer-events: none;
  }

  .callout {
    position: absolute;
    left: -14px;
    bottom: -14px;
    background: var(--card-bg);
    color: var(--text-primary);
    padding: .9rem 1rem;
    border-radius: 14px;
    border: 1px solid var(--border-soft);
    box-shadow: 0 16px 40px rgba(2,6,23,.55);
    backdrop-filter: blur(6px);
    max-width: min(320px, 90%);
  }
  .badge {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .25rem .55rem; border-radius: 9999px;
    background: rgba(245, 158, 11, .15);
    border: 1px solid rgba(245, 158, 11, .45);
    color: var(--secondary-light);
    font-size: .72rem; font-weight: 800;
    margin-bottom: .35rem;
  }
  .callout-title {
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: .25rem;
  }
  .callout-link {
    display: inline-flex; align-items: center; gap: .4rem;
    color: var(--secondary-light);
    text-decoration: none;
    font-weight: 800;
  }
  .callout-link:hover { text-decoration: underline; }

  /* Responsive tweak: keep callout inside on small screens */
  @media (max-width: 640px) {
    .callout { left: 8px; right: 8px; bottom: -12px; max-width: unset; }
  }
  </style>
  </style>

<!-- MAIN CONTENT -->
  <!-- Hero -->
  <section id="home" class="py-20 px-6 text-white">
    <div class="container mx-auto max-w-6xl flex flex-col md:flex-row items-center">
      <div class="md:w-1/2 mb-10">
        <h1 class="text-4xl md:text-6xl font-bold mb-6 floating">Discover 
          <span class="bg-gradient-to-r from-yellow-300 via-yellow-500 to-amber-700 bg-clip-text text-transparent">Exquisite</span> Recipes
        </h1>
        <p class="text-xl mb-8">From quick weekday meals to gourmet weekend feasts, find inspiration for every occasion</p>
        <div class="flex space-x-4">
          <a href="recipes.php" class="px-6 py-3 hover:border border-gold-500 text-gold-300 rounded-lg hover:bg-gold-500 hover:text-white/50 floating-slow">Explore Recipes</a>
            <?php if ($isLoggedIn): ?>
           <?php if (isset($_SESSION['role']) && $_SESSION['user_role'] === 'admin'): ?>
    <a href="admin/index.php" class="px-6 py-3 border border-gold-500 text-gold-300 rounded-lg hover:bg-gold-500 hover:text-black floating-slow">
      Dashboard
    </a>
  <?php else: ?>
    <a href="user/index.php" class="px-6 py-3 border border-gold-500 text-gold-300 rounded-lg hover:bg-gold-500 hover:text-white/50 floating-slow">
      Go to Dashboard
    </a>
  <?php endif; ?>

  <a href="logout.php" class="bg-gold-500 text-black px-3 py-1 rounded hover:bg-gold-300">Logout</a>
<?php else: ?>
   <a href="signup.php" class="px-6 py-3 border border-gold-500 text-gold-300 rounded-lg hover:bg-gold-500 hover:text-black floating-slow">Join Now</a>
<?php endif; ?>
         
        </div>
      </div>
      <div class="md:w-1/2">
  <figure class="relative hero-spot">
    <div class="media-frame floating-delayed">
      <img
        src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=1600&q=80"
        alt="<?= isset($recipe['title']) ? htmlspecialchars($recipe['title']) : 'Featured recipe' ?>"
        class="hero-img"
        loading="lazy"
        onload="this.closest('.media-frame').classList.add('loaded')"
      >
      <div class="img-overlay" aria-hidden="true"></div>
    </div>

    <figcaption class="callout floating">
      <span class="badge"><i class="fas fa-fire"></i> Popular</span>
      <p class="callout-title">Try our most popular recipe!</p>
      <a href="recipe-detail.php?id=<?= (int)$recipe['id'] ?>" class="callout-link">
        <?= isset($recipe['title']) ? htmlspecialchars($recipe['title']) : 'Fluffy Pancakes' ?>
        <i class="fas fa-arrow-right"></i>
      </a>
    </figcaption>
  </figure>
</div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="py-16 px-6">
    <div class="container mx-auto max-w-6xl">
      <h2 class="text-3xl font-bold text-center mb-4 floating-slow">About <span class="bg-gradient-to-r from-yellow-300 via-yellow-500 to-amber-700 bg-clip-text text-transparent">TasteCraft</span></h2>
      <p class="text-center mb-12">Discover the story behind our passion for culinary excellence</p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div><img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?..." class="rounded-lg shadow-lg floating"></div>
        <div>
          <h3 class="text-2xl font-bold mb-4">Our Culinary Journey</h3>
          <p class="mb-4">TasteCraft was born from a simple idea...</p>
          <p class="mb-6">Our mission is to make cooking accessible...</p>
          <div class="grid grid-cols-3 gap-4 text-center">
            <div class="bg-dark-800 p-4 rounded-lg floating-slow">
              <p class="text-3xl font-bold text-gold-500">500+</p><p>Recipes</p>
            </div>
            <div class="bg-dark-800 p-4 rounded-lg floating-delayed">
              <p class="text-3xl font-bold text-gold-500">50+</p><p>Chefs</p>
            </div>
            <div class="bg-dark-800 p-4 rounded-lg floating">
              <p class="text-3xl font-bold text-gold-500">1M+</p><p>Users</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Categories -->
<section id="categories" class="py-16 px-6">
    <div class="container mx-auto max-w-6xl">
      <h2 class="text-3xl font-bold text-center mb-4 floating">Recipe <span class="bg-gradient-to-r from-yellow-300 via-yellow-500 to-amber-700 bg-clip-text text-transparent">Categories</span></h2>
      <p class="text-center mb-12">Browse recipes by category</p>

      <?php if (empty($categories)): ?>
        <p class="text-center text-gray-300">No categories available yet.</p>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($categories as $category):
            // Determine image and icon fallback
            $image = '';
            if (array_key_exists('image', $category) && !empty($category['image'])) {
                $image = $category['image'];
            } elseif (isset($defaultImages[$category['name']])) {
                $image = $defaultImages[$category['name']];
            } else {
                // generic fallback
                $image = 'https://images.unsplash.com/photo-1543353071-087092ec3930?w=1400&auto=format&fit=crop&q=80';
            }

            $icon = $defaultIcon;
            if (isset($mapIcons[$category['name']])) {
                $icon = $mapIcons[$category['name']];
            } elseif (!empty($category['icon'] ?? '')) {
                $icon = $category['icon'];
            }

            $count = isset($category['recipe_count']) ? (int)$category['recipe_count'] : 0;
            ?>
          <div class="category-container h-80 relative rounded-lg overflow-hidden">
            <img src="<?= $category['image'] ? htmlspecialchars($category['image']) : 'assets/category-placeholder.jpg'; ?>" alt="<?= htmlspecialchars($category['name']); ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-50 p-6 flex flex-col justify-end">
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($category['name']) ?></h3>
              <p><?= $count ?> <?= $count === 1 ? 'recipe' : 'recipes' ?></p>
            </div>

            <!-- clickable link to category page -->
            <a href="category.php?id=<?= (int)$category['id'] ?>" class="absolute inset-0" aria-label="View <?= htmlspecialchars($category['name']) ?> recipes"></a>

            <!-- <div class="absolute top-4 right-4 bg-gold-500 text-black p-2 rounded-full">
              <i class="fas <?= htmlspecialchars($icon) ?> text-xl"></i>
            </div> -->
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>


  <!-- Testimonials -->
  <section class="py-16 px-6">
    <div class="container mx-auto max-w-6xl">
      <h2 class="text-3xl font-bold text-center mb-12 floating">What Our <span class="bg-gradient-to-r from-yellow-300 via-yellow-500 to-amber-700 bg-clip-text text-transparent">Users</span> Say</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="testimonial-card p-6 rounded-lg floating-slow">
          <div class="flex items-center mb-4"><div class="w-12 h-12 bg-gold-500 rounded-full flex items-center justify-center text-black mr-4">JD</div><div><h3>John Doe</h3><div class="text-gold-500">★★★★★</div></div></div>
          <p>"The recipes have transformed my cooking."</p>
        </div>
        <div class="testimonial-card p-6 rounded-lg floating-delayed">
          <div class="flex items-center mb-4"><div class="w-12 h-12 bg-gold-500 rounded-full flex items-center justify-center text-black mr-4">SM</div><div><h3>Sarah Miller</h3><div class="text-gold-500">★★★★☆</div></div></div>
          <p>"I love the save feature!"</p>
        </div>
        <div class="testimonial-card p-6 rounded-lg floating">
          <div class="flex items-center mb-4"><div class="w-12 h-12 bg-gold-500 rounded-full flex items-center justify-center text-black mr-4">RJ</div><div><h3>Robert Johnson</h3><div class="text-gold-500">★★★★★</div></div></div>
          <p>"Impressed with recipe quality."</p>
        </div>
      </div>
    </div>
  </section>


<script>
  const btn=document.getElementById('mobileMenuBtn');
  const menu=document.getElementById('mobileMenu');
  btn.addEventListener('click',()=>menu.classList.toggle('hidden'));
</script>

<script>
  // Ensure skeleton is removed if image is cached and already complete
  document.querySelectorAll('.media-frame img').forEach(img => {
    if (img.complete) img.closest('.media-frame')?.classList.add('loaded');
  });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>