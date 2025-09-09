<?php
session_start();
require_once __DIR__ . '/includes/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Get recipe ID from URL
$recipeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch main recipe
$stmt = $pdo->prepare("SELECT r.*, c.name AS category 
                       FROM recipes r 
                       JOIN categories c ON r.category_id = c.id 
                       WHERE r.id = ?");
$stmt->execute([$recipeId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: recipes.php");
    exit();
}

// Fetch ingredients
$stmtIng = $pdo->prepare("SELECT measure, ingredient FROM recipe_ingredients WHERE recipe_id = ?");
$stmtIng->execute([$recipeId]);
$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

// Fetch instructions
$stmtInst = $pdo->prepare("SELECT step, step_order 
                           FROM recipe_instructions 
                           WHERE recipe_id = ? ORDER BY step_order ASC");
$stmtInst->execute([$recipeId]);
$instructions = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

// Related recipes (same category, excluding current)
$stmtRelated = $pdo->prepare("SELECT id, title, image, cooking_time AS time 
                              FROM recipes 
                              WHERE category_id = ? AND id != ? 
                              LIMIT 3");
$stmtRelated->execute([$recipe['category_id'], $recipeId]);
$relatedRecipes = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);

// Check if user logged in
$isLoggedIn = isset($_SESSION['user_id']);
ob_start()
?>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            300: '#fcd34d',
                            500: '#f59e0b',
                            700: '#b45309',
                        },
                        dark: {
                            900: '#0a0a0a',
                            800: '#1a1a1a',
                            700: '#2a2a2a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #000000, #1a1a1a, #000000, #b45309, #f59e0b);
            background-attachment: fixed;
            color: #fcd34d;
            min-height: 100vh;
        }
        .nav-gradient {
            background: linear-gradient(to right, #000000, #1a1a1a, #000000, #b45309, #f59e0b, #b45309, #000000, #1a1a1a, #000000);
        }
        .card-gradient {
            background: linear-gradient(145deg, #1a1a1a, #000000, #1a1a1a);
            border: 1px solid #b45309;
        }
        .gold-gradient-text {
            background: linear-gradient(to right, #fcd34d, #f59e0b, #b45309);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .footer-gradient {
            background: linear-gradient(to top, #000000, #1a1a1a, #000000, #b45309, #f59e0b);
        }
        .ingredient-list li, .instruction-list li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }
        .ingredient-list li:before {
            content: "•";
            color: #f59e0b;
            position: absolute;
            left: 0;
        }
        .instruction-list li:before {
            content: counter(step);
            counter-increment: step;
            background: #f59e0b;
            color: #000;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            left: 0;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .instruction-list {
            counter-reset: step;
        }
        .recipe-hero {
            height: 400px;
        }
        @media (max-width: 768px) {
            .recipe-hero {
                height: 300px;
            }
        }
    </style>

    <!-- Recipe Details Section -->
    <section class="py-8 px-6">
        <div class="container mx-auto max-w-6xl">
            <!-- Back Button -->
            <a href="recipes.php" class="inline-flex items-center text-gold-500 hover:text-gold-300 mb-6">
                <i class="fas fa-arrow-left mr-2"></i> Back to Recipes
            </a>

            <div id="recipe-container">
                <!-- Recipe Header -->
                <div class="card-gradient rounded-2xl overflow-hidden mb-8">
                    <div class="relative recipe-hero">
                        <img src="<?php echo $recipe['image']; ?>" alt="<?php echo $recipe['title']; ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent"></div>
                        <div class="absolute bottom-0 left-0 p-8 w-full">
                            <div class="flex justify-between items-start mb-4">
                                <span class="bg-gold-500 text-black text-sm px-3 py-1 rounded-full"><?php echo $recipe['category']; ?></span>
                                <?php if ($isLoggedIn): ?>
                                <form method="post">
                                    <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                    <input type="hidden" name="save_recipe" value="1">
                                    <button type="submit" class="bg-dark-800 p-3 rounded-full <?php echo !empty($recipe['saved']) ? 'bg-gold-500 text-black' : 'text-gold-500'; ?> hover:bg-gold-500 hover:text-black transition-colors">
    <i class="<?php echo !empty($recipe['saved']) ? 'fas' : 'far'; ?> fa-bookmark text-xl"></i>
</button>

                                </form>
                                <?php endif; ?>
                            </div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-2"><?php echo $recipe['title']; ?></h1>
                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-gold-500 mr-2"></i>
                                    <span><?php echo $recipe['cooking_time']; ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-user-friends text-gold-500 mr-2"></i>
                                    <span><?php echo $recipe['servings']; ?> servings</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-star text-gold-500 mr-2"></i>
                                    <span><?php echo $recipe['rating']; ?> (<?php echo rand(20, 100); ?> reviews)</span>
                                </div>
                                <?php if (isset($recipe['area'])): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-globe text-gold-500 mr-2"></i>
                                    <span><?php echo $recipe['area']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recipe Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Ingredients Card -->
                    <div class="card-gradient rounded-2xl p-6 lg:col-span-1">
                        <h2 class="text-2xl font-bold mb-6 gold-gradient-text">Ingredients</h2>
                        <ul class="ingredient-list">
                            <?php foreach ($ingredients as $ingredient): ?>
    <li><?php echo htmlspecialchars($ingredient['measure'] . ' ' . $ingredient['ingredient']); ?></li>
<?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Instructions Card -->
                    <div class="card-gradient rounded-2xl p-6 lg:col-span-2">
                        <h2 class="text-2xl font-bold mb-6 gold-gradient-text">Instructions</h2>
                        <ol class="instruction-list space-y-4">
                            <?php foreach ($instructions as $instruction): ?>
    <li> <?php echo htmlspecialchars($instruction['step']); ?></li>
<?php endforeach; ?>

                        </ol>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                    <!-- Nutrition Info -->
<div class="mt-6 bg-white/10 p-4 rounded-lg">
    <h3 class="text-lg font-bold mb-2">Nutrition Information</h3>
    <ul class="grid grid-cols-2 gap-2 text-sm">
        <li>Calories: <?php echo htmlspecialchars($recipe['calories'] ?? 'N/A'); ?></li>
        <li>Protein: <?php echo htmlspecialchars($recipe['protein'] ?? 'N/A'); ?></li>
        <li>Carbs: <?php echo htmlspecialchars($recipe['carbs'] ?? 'N/A'); ?></li>
        <li>Fat: <?php echo htmlspecialchars($recipe['fat'] ?? 'N/A'); ?></li>
    </ul>
</div>

<!-- Servings -->
<div class="mt-4">
    <span class="inline-block bg-blue-500 text-white px-3 py-1 rounded-full text-sm">
        <?php echo htmlspecialchars($recipe['servings'] ?? ''); ?>
    </span>
</div>

<!-- Rating -->
<div class="mt-4 flex items-center">
    <?php
    $rating = (float)($recipe['rating'] ?? 0);
$fullStars = floor($rating);
$halfStar = ($rating - $fullStars >= 0.5);
?>
    <div class="flex text-yellow-400">
        <?php for ($i = 0; $i < $fullStars; $i++): ?>
            <i class="fas fa-star"></i>
        <?php endfor; ?>
        <?php if ($halfStar): ?>
            <i class="fas fa-star-half-alt"></i>
        <?php endif; ?>
        <?php for ($i = $fullStars + $halfStar; $i < 5; $i++): ?>
            <i class="far fa-star"></i>
        <?php endfor; ?>
    </div>
    <span class="ml-2 text-sm text-gray-300">
        <?php echo number_format($rating, 1); ?>/5
    </span>
</div>


                    <!-- Recipe Notes -->
                    <!-- Recipe Notes -->
<?php if (!empty($recipe['notes'])): ?>
<div class="mt-6 bg-white/10 p-4 rounded-lg">
    <h3 class="text-lg font-bold mb-2">Recipe Notes</h3>
    <p class="text-sm text-gray-200"><?php echo nl2br(htmlspecialchars($recipe['notes'])); ?></p>
</div>
<?php endif; ?>
                </div>

                <!-- Related Recipes -->
                <?php foreach ($relatedRecipes as $related): ?>
<div class="card-gradient rounded-2xl overflow-hidden">
    <a href="recipe-detail.php?id=<?php echo $related['id']; ?>">
        <img src="<?php echo htmlspecialchars($related['image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="w-full h-48 object-cover">
        <div class="p-4">
            <span class="bg-gold-500 text-black text-xs px-2 py-1 rounded-full">
                <?php echo htmlspecialchars($recipe['category']); ?>
            </span>
            <h3 class="text-lg font-bold my-2"><?php echo htmlspecialchars($related['title']); ?></h3>
            <div class="flex justify-between items-center">
                <span class="text-sm"><i class="fas fa-clock mr-1"></i> <?php echo htmlspecialchars($related['time']); ?></span>
            </div>
        </div>
    </a>
</div>
<?php endforeach; ?>

            </div>
        </div>
    </section>

    <script>
        // Mobile Menu Functionality
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const closeMenuBtn = document.getElementById('close-menu');
        
        if (mobileMenuBtn && mobileMenu && closeMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.add('active');
            });
            
            closeMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
            });
        }
    </script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>