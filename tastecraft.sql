-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2025 at 04:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tastecraft`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `icon`, `created_at`) VALUES
(5, 'Breakfast', 'The first meal of the day', 'uploads/categories/1757106853_side-view-shawarma-with-fried-potatoes-board-cookware.jpg', NULL, '2025-09-05 21:14:13'),
(6, 'Lunch', 'The second meal of the day', 'uploads/categories/1757115807_34a960b4.jpg', NULL, '2025-09-05 23:43:27'),
(8, 'Dinner', 'The last meal of the day', 'uploads/categories/1757357136_57bd6bf0.jpg', NULL, '2025-09-08 18:45:36'),
(9, 'Deserts', 'A shot meal before dinner', 'uploads/categories/1757358282_2e6e459c.jpg', NULL, '2025-09-08 19:04:42'),
(10, 'Snack', 'A quick meal', 'uploads/categories/1757359069_3fd19e7a.jpg', NULL, '2025-09-08 19:17:49');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, NULL, 'Agaba Solomon', 'solomon@gmail.com', 'Enquires', 'Just wanted to be sure everything works perfectly', 'read', '2025-09-06 02:22:16');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `cooking_time` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `servings` varchar(50) DEFAULT NULL,
  `calories` varchar(50) DEFAULT NULL,
  `protein` varchar(50) DEFAULT NULL,
  `carbs` varchar(50) DEFAULT NULL,
  `fat` varchar(50) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `category_id`, `title`, `description`, `cooking_time`, `image`, `created_at`, `servings`, `calories`, `protein`, `carbs`, `fat`, `rating`, `notes`) VALUES
(13, 1, 5, 'Classic Pancakes', 'Fluffy, golden pancakes perfect for a cozy breakfast.', '20 mins', 'uploads/1757330009_4e937415.jpg', '2025-09-08 11:13:29', '4', '210kcal', '6g', '32g', '7g', 4.5, NULL),
(14, 1, 5, 'Vegetable Omelette', 'A fluffy omelette loaded with fresh veggies.', '15 mins', 'uploads/1757330642_03bbac22.jpg', '2025-09-08 11:24:02', '2', '180kcal', '12g', '6g', '12g', 4.6, NULL),
(15, 1, 6, 'Classic Chicken Caesar Wrap', 'A quick and portable version of the classic salad. Tender chicken, crisp romaine, and creamy Caesar dressing are wrapped in a soft tortilla.', '15 mins', 'uploads/1757356902_2a17fdb7.jpg', '2025-09-08 18:41:42', '2', '480 kcal', '35g', '32g', '24g', 4.5, NULL),
(16, 1, 8, 'Creamy Cajun Sausage Pasta', 'A rich, spicy, and creamy pasta dish made with smoky sausage, Cajun spices, and a velvety sauce. Perfect for a comforting dinner.', '30 mins', 'uploads/1757357602_353e685d.jpg', '2025-09-08 18:53:22', '4', '520 kcal', '22g', '55g', '23g', 5.0, 'Tips:\r\n\r\nAdd spinach or mushrooms for extra veggies.\r\n\r\nFor extra heat, add red pepper flakes or more Cajun spice.\r\n\r\nCan substitute chicken sausage or turkey sausage for a lighter version.'),
(17, 1, 9, 'Red Velvet Strawberry Cheesecake', 'A decadent, creamy cheesecake with a red velvet base and luscious strawberry topping. Elegant and indulgent, perfect for special occasions.', '1 hr 20 mins', 'uploads/1757358863_5cd50554.jpg', '2025-09-08 19:14:23', '12', '480 kcal', '7g', '46g', '29g', 5.0, 'Serving Tip: Garnish with whipped cream swirls, extra strawberries, or white chocolate shavings for a bakery-style look.'),
(18, 1, 6, 'Quinoa Power Bowl with Lemon Tahini Dressing', 'A nutrient-packed, vegan bowl with quinoa, roasted sweet potatoes, chickpeas, and avocado. The lemon tahini dressing is creamy and tangy.', '35 mins', 'uploads/1757359505_0a401d3d.jpg', '2025-09-08 19:25:05', '2', '610 kcal', '18g', '78g', '28g', 5.0, NULL),
(19, 1, 10, 'Peanut Butter Stuffed Chocolate Chip Cookies', 'Gooey, bakery-style chocolate chip cookies with a creamy peanut butter center. Crispy on the edges, soft in the middle, and stuffed with nutty goodness.', '25 mins', 'uploads/1757360241_8ff35c6c.jpg', '2025-09-08 19:37:21', '14', '320 kcal', '6g', '34g', '18g', 5.0, 'Tips for Best Results:\r\n\r\nDon’t skip freezing the peanut butter filling, or it will melt out.\r\n\r\nUse a mix of chocolate chips and chunks for extra gooeyness.\r\n\r\nSprinkle a little flaky sea salt on top before baking for a gourmet touch.'),
(20, 1, 5, 'Avocado Toast with Eggs', 'Crunchy toast with creamy avocado and sunny-side eggs.', '10 mins', 'uploads/1757360718_42293a09.jpg', '2025-09-08 19:45:18', '2', '250 kcal', '10g', '20g', '15g', 4.8, NULL),
(21, 1, 5, 'Greek Yogurt Parfait', 'A refreshing layered yogurt with granola and berries.', '5 mins', 'uploads/1757370018_194f93da.jpg', '2025-09-08 22:20:18', '2', '180kcal', '12g', '25g', '4g', 5.0, NULL),
(22, 1, 5, 'Banana Smoothie', 'Creamy and energy-boosting banana smoothie.', '5 mins', 'uploads/1757370354_08c2a0bb.jpg', '2025-09-08 22:25:54', '2', '200 kcal', '8g', '35g', '3g', 4.7, NULL),
(23, 1, 5, 'Breakfast Burrito', 'A hearty tortilla filled with eggs, beans, and cheese.', '20 mins', 'uploads/1757370845_917cb94c.jpg', '2025-09-08 22:31:29', '2', '350 kcal', '18g', '32g', '15g', 4.5, NULL),
(24, 1, 5, 'Overnight Oats', 'No-cook creamy oats, ready in the morning.', '5 mins (overnight set)', 'uploads/1757371129_389c80eb.jpg', '2025-09-08 22:38:49', '2', '220 kcal', '9 g', '35 g', '6 g', 5.0, NULL),
(25, 1, 5, 'French Toast', 'Sweet, golden-brown bread dipped in egg custard.', '15 mins', 'uploads/1757371401_d4238ece.jpg', '2025-09-08 22:43:21', '4', '230 kcal', '8 g', '28 g', '9 g', 4.7, NULL),
(26, 1, 5, 'Bagel with Cream Cheese & Salmon', 'A protein-packed bagel with creamy cheese and smoked salmon.', '10 mins', 'uploads/1757417327_6d6e138a.jpg', '2025-09-09 11:28:47', '4', '300 kcal', '15g', '28g', '12g', 4.6, NULL),
(27, 1, 5, 'Breakfast Muffins', 'Soft, healthy muffins with oats and banana.', '25 mins', 'uploads/1757417950_e05512c9.jpg', '2025-09-09 11:39:10', '6', '190 kcal', '6g', '24 g', '7 g', 4.5, NULL),
(28, 1, 6, 'Creamy Tomato Soup & Grilled Cheese', 'The ultimate comfort food duo. Smooth, rich tomato soup paired with a crispy, cheesy sandwich for dipping.', '25 mins', 'uploads/1757418974_de653df6.jpg', '2025-09-09 11:56:14', '2', '550 kcal', '18 g', '52 g', '32 g', 4.7, NULL),
(29, 1, 6, 'Asian-Inspired Chicken Lettuce Wraps', 'A light, fresh, and flavorful low-carb option. Savory chicken mince is served in crisp lettuce cups.', '20 mins', 'uploads/1757419418_813f80a2.jpg', '2025-09-09 12:03:38', '3', '320 kcal', '28 g', '15 g', '18 g', 5.0, NULL),
(30, 1, 6, 'Tuna Salad Sandwich', 'A simple, classic, and protein-rich lunch. Creamy tuna salad with crunchy celery and onion on your choice of bread.', '10 mins', 'uploads/1757420134_d40586d5.jpg', '2025-09-09 12:15:34', '2', '380 kcal', '25 g', '35 g', '16 g', 4.0, NULL),
(31, 1, 6, 'Grilled Chicken Caesar Salad', 'Juicy grilled chicken over crisp romaine with creamy Caesar dressing.', '25 mins', 'uploads/1757420523_52c9c85f.jpg', '2025-09-09 12:22:03', '2', '380 kcal', '30 g', '12 g', '22 g', 5.0, NULL),
(32, 1, 6, 'Beef Stir-Fry with Vegetables', 'Quick, flavorful beef stir-fry with crisp veggies and soy sauce.', '20 mins', 'uploads/1757420925_0ed6bdd9.jpg', '2025-09-09 12:28:45', '3', '410 kcal', '28 g', '25 g', '19 g', 5.0, NULL),
(33, 1, 6, 'Shrimp Fried Rice', 'Golden fried rice with shrimp, peas, and scrambled egg.', '25 mins', 'uploads/1757421144_ab0e1707.jpg', '2025-09-09 12:32:24', '4', '420 kcal', '22 g', '55 g', '12 g', 5.0, NULL),
(34, 1, 6, 'Turkey Avocado Wrap', 'A light yet filling wrap with smoked turkey, creamy avocado, and crisp lettuce.', '10 mins', 'uploads/1757422446_075fad2d.jpg', '2025-09-09 12:54:06', '2', '320 kcal', '20 g', '30 g', '15 g', 5.0, NULL),
(35, 1, 6, 'Mediterranean Chickpea Bowl', 'A refreshing vegetarian bowl with chickpeas, cucumbers, and tzatziki.', '15 mins', 'uploads/1757422808_3751e0f4.jpg', '2025-09-09 13:00:08', '2', '350 kcal', '14 g', '45 g', '12 g', 5.0, NULL),
(36, 1, 8, 'Garlic Butter Shrimp with Rice', 'Juicy shrimp sautéed in garlic butter, served over fluffy rice.', '25 mins', 'uploads/1757423747_211c0568.jpg', '2025-09-09 13:15:47', '4', '410 kcal', '28 g', '35 g', '15 g', 4.6, NULL),
(37, 1, 8, 'Lemon Herb Grilled Chicken', 'Fresh, zesty grilled chicken infused with lemon and herbs.', '30 mins', 'uploads/1757423910_ea431d78.jpg', '2025-09-09 13:18:30', '4', '350 kcal', '42 g', '6 g', '14 g', 5.0, NULL),
(38, 1, 9, 'Classic Chocolate Brownies', 'Fudgy and rich chocolate brownies with a chewy texture and glossy top.', '40 mins', 'uploads/1757424818_d88a7fe5.jpg', '2025-09-09 13:33:38', '12', '320 kcal', '4 g', '40 g', '16 g', 5.0, NULL),
(39, 1, 9, 'New York Cheesecake', 'Creamy, dense cheesecake with a buttery graham cracker crust.', '1 hr 20 mins (+chill time)', 'uploads/1757425180_4d9648d7.jpg', '2025-09-09 13:39:40', '10', '410 kcal', '8 g', '34 g', '27 g', 5.0, NULL),
(40, 1, 9, 'Tiramisu', 'Italian coffee-flavored layered dessert with mascarpone cream.', '30 mins', 'uploads/1757425448_f74a39d3.jpg', '2025-09-09 13:44:08', '8', '480 kcal', '7 g', '42 g', '28 g', 5.0, NULL),
(41, 1, 10, 'Baked Sweet Potato Fries', 'Crispy on the outside, soft inside, and lightly seasoned with paprika.', '30 mins', 'uploads/1757425712_121f52db.jpg', '2025-09-09 13:48:32', '4', '180 kcal', '2 g', '40 g', '3 g', 4.5, NULL),
(42, 1, 10, 'Guacamole with Tortilla Chips', 'Smooth avocado dip served with crunchy tortilla chips.', '10 mins', 'uploads/1757425960_cbb030cc.jpg', '2025-09-09 13:52:40', '4', '250 kcal', '3 g', '18 g', '20 g', 5.0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recipe_ingredients`
--

CREATE TABLE `recipe_ingredients` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `measure` varchar(100) DEFAULT NULL,
  `ingredient` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`id`, `recipe_id`, `measure`, `ingredient`) VALUES
(24, 13, '1 cup all-purpose flour', '1 tsp baking powder'),
(25, 13, '2 tbsp sugar', '1/2 tsp baking soda'),
(26, 13, '1/4 tsp salt', '1 cup buttermilk'),
(27, 13, '1 egg', '2 tbsp melted butter'),
(28, 14, '3 eggs', '1/4 cup diced bell peppers'),
(29, 14, '1/4 cup diced onions', '1/4 cup chopped spinach'),
(30, 14, '1 tbsp olive oil', 'Salt & pepper to taste'),
(31, 15, '2 large flour tortillas', '2 cups romaine lettuce, chopped'),
(32, 15, '1 cup cooked chicken breast, shredded or diced', '¼ cup Parmesan cheese, grated'),
(33, 15, '3 tbsp Caesar dressing', 'Freshly ground black pepper'),
(34, 16, '12 oz (340g) penne pasta (or any short pasta)', '12 oz (340g) smoked sausage, sliced into rounds'),
(35, 16, '1 tbsp olive oil', '1 small onion, diced'),
(36, 16, '1 red bell pepper, sliced', '3 garlic cloves, minced'),
(37, 16, '2 tsp Cajun seasoning (adjust to taste)', '1 cup heavy cream'),
(38, 16, '1 cup chicken broth', '1/2 cup grated Parmesan cheese'),
(39, 16, '1 tbsp tomato paste (optional for richness)', 'Salt & pepper to taste'),
(40, 17, '1 ½ cups crushed red velvet cake crumbs (or red velvet Oreos)', '2 tbsp sugar'),
(41, 17, '¼ cup melted butter', '24 oz (680g) cream cheese (softened)'),
(42, 17, '1 cup granulated sugar', '2 tbsp all-purpose flour'),
(43, 17, '1 cup sour cream', '2 cups fresh strawberries (sliced)'),
(44, 17, '3 large eggs', '½ cup strawberry preserves or jam'),
(45, 17, '2 tsp vanilla extract', '1 tbsp lemon juice'),
(46, 18, '1 cup cooked quinoa', '1 can (15 oz) chickpeas, rinsed'),
(47, 18, '1 sweet potato, cubed and roasted', '1 avocado, sliced'),
(48, 19, '1 cup unsalted butter (softened)', '1 cup brown sugar (packed)'),
(49, 19, '½ cup granulated sugar', '2 large eggs'),
(50, 19, '2 tsp vanilla extract', '3 cups all-purpose flour'),
(51, 19, '1 tsp baking soda', '½ tsp salt'),
(52, 19, '2 cups semi-sweet chocolate chips', '¾ cup creamy peanut butter'),
(53, 20, '2 slices whole-grain bread', '2 eggs'),
(54, 20, '1 avocado (mashed)', '1 tsp olive oil'),
(55, 21, '1 cup Greek yogurt', '1/2 cup mixed berries'),
(56, 21, '1/2 cup granola', '1 tbsp honey'),
(57, 22, '2 bananas', '1 cup milk (or almond milk)'),
(58, 22, '1/2 cup Greek yogurt', '1 tbsp honey'),
(59, 23, '2 flour tortillas', '3 scrambled eggs'),
(60, 23, '1/2 cup black beans', '1/4 cup shredded cheese'),
(61, 24, '1 cup rolled oats', '1 cup milk'),
(62, 24, '1 tbsp chia seeds', '1 tbsp honey'),
(63, 25, '4 slices bread', '2 eggs'),
(64, 25, '1/2 cup milk', '1 tsp cinnamon'),
(65, 26, '2 bagels', '4 tbsp cream cheese'),
(66, 26, '4 slices smoked salmon', 'Fresh dill & lemon juice'),
(67, 27, '2 ripe bananas (mashed)', '1 cup flour'),
(68, 27, '1 cup oats', '1/4 cup honey'),
(69, 27, '1 egg', '1 tsp baking powder'),
(70, 28, '1 can (28 oz) crushed tomatoes', '½ cup vegetable broth'),
(71, 28, '¼ cup cream', '1 tbsp olive oil'),
(72, 28, '1 small onion (diced)', '2 garlic cloves (minced)'),
(73, 28, 'basil', 'salt, pepper'),
(74, 28, '4 slices bread', '2 tbsp butter'),
(75, 29, '1 lb ground chicken', '2 tbsp hoisin sauce'),
(76, 29, '1 tbsp olive oil', '1 tbsp soy sauce'),
(77, 29, '1 tsp rice vinegar', '1 clove garlic, minced'),
(78, 29, '1 tsp ginger, minced', '4-6 large butter or romaine lettuce leaves'),
(79, 30, '2 cans (5 oz each) tuna in water, drained', '¼ cup mayonnaise (or Greek yogurt)'),
(80, 30, '1 celery stalk, finely diced', '2 tbsp red onion, finely diced'),
(81, 30, '1 tbsp lemon juice', 'Salt and pepper to taste'),
(82, 30, '4 slices bread', 'Lettuce leaves (optional)'),
(83, 31, '2 chicken breasts', '1 head romaine lettuce'),
(84, 31, '½ cup croutons', '¼ cup Parmesan cheese'),
(85, 32, '300g beef strips', '1 carrot (julienned)'),
(86, 32, '1 red bell pepper (sliced)', '1 cup broccoli florets'),
(87, 32, '3 tbsp soy sauce', '1 tbsp oyster sauce'),
(88, 33, '3 cups cooked rice (cold)', '2 eggs'),
(89, 33, '250g shrimp (peeled)', '½ cup peas & carrots'),
(90, 33, '3 tbsp soy sauce', '1 tsp sesame oil'),
(91, 34, '2 whole-wheat tortillas', '6 slices smoked turkey'),
(92, 34, '1 avocado (sliced)', '2 lettuce leaves'),
(93, 35, '1 can chickpeas (rinsed)', '1 tomato (chopped)'),
(94, 35, '1 cucumber (diced)', '2 tbsp olive oil'),
(95, 35, '2 tbsp feta cheese', '2 tbsp tzatziki'),
(96, 36, '1 lb shrimp (peeled, deveined)', '3 tbsp butter'),
(97, 36, '4 cloves garlic, minced', '1 tsp paprika'),
(98, 36, '1 cup jasmine rice', '2 cups chicken broth'),
(99, 37, '4 chicken breasts', '3 tbsp olive oil'),
(100, 37, 'Juice of 2 lemons', '1 tbsp oregano'),
(101, 37, '3 garlic cloves, minced', 'Salt & pepper'),
(102, 38, '1 cup unsalted butter', '1 ½ cups sugar'),
(103, 38, '4 large eggs', '1 tsp vanilla extract'),
(104, 38, '1 cup all-purpose flour', '1 cup cocoa powder'),
(105, 38, '½ tsp salt', '1 cup chocolate chips'),
(106, 39, '2 cups graham cracker crumbs', '½ cup melted butter'),
(107, 39, '2 tbsp sugar', '4 packs (8oz) cream cheese'),
(108, 39, '1 cup sugar', '4 eggs'),
(109, 39, '1 tsp vanilla extract', '1 cup sour cream'),
(110, 40, '1 pack ladyfinger biscuits', '½ cup sugar'),
(111, 40, '1 ½ cups strong brewed coffee', '3 eggs (separated)'),
(112, 40, '1 cup mascarpone cheese', '2 tbsp cocoa powder'),
(113, 41, '2 large sweet potatoes', '1 tsp paprika'),
(114, 41, '2 tbsp olive oil', '½ tsp garlic powder'),
(115, 42, '2 ripe avocados', '½ onion, finely chopped'),
(116, 42, '1 lime (juice)', '1 tomato, diced'),
(117, 42, '1 jalapeño, minced', 'Salt (to taste)');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_instructions`
--

CREATE TABLE `recipe_instructions` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `step` text DEFAULT NULL,
  `step_order` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_instructions`
--

INSERT INTO `recipe_instructions` (`id`, `recipe_id`, `step`, `step_order`) VALUES
(34, 13, 'Mix dry ingredients in one bowl.', 1),
(35, 13, 'Whisk buttermilk, egg, and butter in another.', 2),
(36, 13, 'Combine wet and dry, don’t overmix.', 3),
(37, 13, 'Cook on greased skillet until bubbles form, flip until golden.', 4),
(38, 14, 'Whisk eggs with salt & pepper.', 1),
(39, 14, 'Heat oil, sauté veggies.', 2),
(40, 14, 'Pour eggs over veggies.', 3),
(41, 14, 'Cook until set, fold and serve.', 4),
(42, 15, 'Lay the tortillas flat on a surface.', 1),
(43, 15, 'Spread a tablespoon of Caesar dressing down the center of each tortilla.', 2),
(44, 15, 'Top with chicken, romaine lettuce, and Parmesan cheese. Season with black pepper.', 3),
(45, 15, 'Fold in the sides of the tortilla and roll tightly from the bottom to form a wrap.', 4),
(46, 15, 'Slice in half diagonally and serve.', 5),
(47, 16, 'Cook pasta: Boil salted water, cook pasta according to package instructions. Drain and set aside.', 1),
(48, 16, 'Cook sausage: Heat olive oil in a large skillet over medium heat. Add sausage slices and brown on both sides (about 5 minutes). Remove and set aside.', 2),
(49, 16, 'Sauté veggies: In the same skillet, add onion, bell pepper, and garlic. Sauté until soft and fragrant.', 3),
(50, 16, 'Add seasoning & liquids: Stir in Cajun seasoning, tomato paste (if using), chicken broth, and heavy cream. Bring to a gentle simmer.', 4),
(51, 16, 'Add cheese & pasta: Stir in Parmesan cheese until melted. Return sausage and pasta to the skillet. Toss until pasta is coated in the creamy sauce.', 5),
(52, 16, 'Serve: Garnish with chopped parsley and extra Parmesan. Serve warm.', 6),
(53, 17, 'Step 1: Prepare Crust', 1),
(54, 17, 'Preheat oven to 325°F (160°C).', 2),
(55, 17, 'Mix cake crumbs (or cookies), sugar, and melted butter until crumbly.', 3),
(56, 17, 'Press into the bottom of a greased 9-inch springform pan. Bake for 10 minutes, then cool.', 4),
(57, 17, 'Step 2: Make Cheesecake Filling', 5),
(58, 17, 'Beat cream cheese until smooth.', 6),
(59, 17, 'Add sugar, flour, sour cream, lemon juice, and vanilla. Mix well.', 7),
(60, 17, 'Add eggs one at a time, mixing on low (don’t overbeat).', 8),
(61, 17, 'Pour filling over crust.', 9),
(62, 17, 'Step 3: Bake & Chill', 10),
(63, 17, 'Place springform pan in a water bath (to prevent cracks).', 11),
(64, 17, 'Bake at 325°F (160°C) for 55–65 minutes, until edges are set but center jiggles slightly.', 12),
(65, 17, 'Turn off oven, let cheesecake rest inside with door cracked for 1 hour.', 13),
(66, 17, 'Refrigerate for at least 4 hours (preferably overnight).', 14),
(67, 17, 'Step 4: Strawberry Topping', 15),
(68, 17, 'In a small saucepan, heat preserves and lemon juice until smooth.', 16),
(69, 17, 'Toss in sliced strawberries, mix until coated.', 17),
(70, 17, 'Spread topping over chilled cheesecake.', 18),
(71, 18, 'Preheat oven to 400°F (200°C). Toss sweet potato cubes with olive oil, salt, and pepper. Roast for 25-30 mins until tender.', 1),
(72, 18, 'Whisk all dressing ingredients together until smooth.', 2),
(73, 18, 'Divide quinoa between two bowls. Top with roasted sweet potato, chickpeas, and avocado.', 3),
(74, 18, 'Drizzle generously with lemon tahini dressing.', 4),
(75, 19, 'Step 1: Peanut Butter Filling', 1),
(76, 19, 'Mix peanut butter and powdered sugar until smooth.', 2),
(77, 19, 'Scoop small teaspoon-sized dollops onto a baking sheet lined with parchment.', 3),
(78, 19, 'Freeze for at least 30 minutes (this helps the filling hold shape).', 4),
(79, 19, 'Step 2: Cookie Dough', 5),
(80, 19, 'In a large bowl, cream together butter, brown sugar, and white sugar until fluffy.', 6),
(81, 19, 'Beat in eggs and vanilla.', 7),
(82, 19, 'In another bowl, whisk flour, baking soda, and salt. Add to wet mixture.', 8),
(83, 19, 'Stir in chocolate chips. Chill dough for 30 minutes.', 9),
(84, 19, 'Step 3: Assemble Cookies', 10),
(85, 19, 'Scoop 2 tablespoons of dough, flatten slightly.', 11),
(86, 19, 'Place a frozen peanut butter dollop in the center.', 12),
(87, 19, 'Cover with another tablespoon of dough, sealing edges well.', 13),
(88, 19, 'Arrange on a lined baking sheet, leaving space between cookies.', 14),
(89, 19, 'Step 4: Bake', 15),
(90, 19, 'Preheat oven to 350°F (175°C).', 16),
(91, 19, 'Bake for 12–14 minutes until golden on edges but soft in the center.', 17),
(92, 19, 'Cool on pan for 5 minutes, then transfer to a wire rack.', 18),
(93, 20, 'Toast bread.', 1),
(94, 20, 'Mash avocado with salt.', 2),
(95, 20, 'Fry eggs sunny-side up.', 3),
(96, 20, 'Spread avocado on toast, top with eggs.', 4),
(97, 21, 'Layer yogurt, berries, and granola in a glass.', 1),
(98, 21, 'Drizzle honey on top.', 2),
(99, 22, 'Blend all ingredients until smooth.', 1),
(100, 22, 'Pour into glasses and serve chilled.', 2),
(101, 23, 'Warm tortillas.', 1),
(102, 23, 'Fill with eggs, beans, cheese, and salsa.', 2),
(103, 23, 'Roll and serve.', 3),
(104, 24, 'Mix oats, milk, chia, and honey in a jar.', 1),
(105, 24, 'Refrigerate overnight.', 2),
(106, 24, 'Top with berries before serving.', 3),
(107, 25, 'Whisk eggs, milk, and cinnamon.', 1),
(108, 25, 'Dip bread slices in mixture.', 2),
(109, 25, 'Cook in butter until golden.', 3),
(110, 26, 'Toast bagels.', 1),
(111, 26, 'Spread cream cheese.', 2),
(112, 26, 'Top with salmon, dill, and lemon juice.', 3),
(113, 27, 'Preheat oven to 180°C.', 1),
(114, 27, 'Mix banana, egg, and honey.', 2),
(115, 27, 'Stir in oats, flour, and baking powder.', 3),
(116, 27, 'Fill muffin cups and bake 20 mins.', 4),
(117, 28, 'Sauté onion and garlic in olive oil until soft', 1),
(118, 28, 'Add tomatoes and broth, simmer for 15 mins', 2),
(119, 28, 'Blend until smooth, stir in cream, and season.', 3),
(120, 28, 'Butter one side of each bread slice', 4),
(121, 28, 'Place cheese between unbuttered sides', 5),
(122, 28, 'Grill in a pan over medium heat until golden brown and cheese is melted.', 6),
(123, 28, 'Serve soup hot with the grilled cheese sandwich on the side.', 7),
(124, 29, 'Heat oil in a skillet over medium-high heat. Cook chicken until no longer pink, breaking it up as it cooks', 1),
(125, 29, 'Add garlic and ginger, cook for 1 minute until fragrant.', 2),
(126, 29, 'Stir in hoisin, soy sauce, and rice vinegar. Cook for another 2-3 minutes.', 3),
(127, 29, 'Spoon the mixture into lettuce cups and garnish with green onions and water chestnuts.', 4),
(128, 30, 'In a medium bowl, flake the tuna with a fork.', 1),
(129, 30, 'Add mayonnaise, celery, red onion, lemon juice, salt, and pepper. Mix well.', 2),
(130, 30, 'Toast the bread if desired. Place lettuce on one slice, top with a generous amount of tuna salad, and close the sandwich.', 3),
(131, 31, 'Season chicken with salt, pepper, and olive oil. Grill until cooked through.', 1),
(132, 31, 'Chop lettuce, toss with dressing.', 2),
(133, 31, 'Slice chicken, add on top with croutons and Parmesan.', 3),
(134, 32, 'Heat oil in wok, sauté beef until browned. Remove.', 1),
(135, 32, 'Stir-fry vegetables with soy & oyster sauce.', 2),
(136, 32, 'Return beef, toss together, serve hot with rice.', 3),
(137, 33, 'Scramble eggs in wok, set aside.', 1),
(138, 33, 'Stir-fry shrimp until pink.', 2),
(139, 33, 'Add rice, soy sauce, sesame oil, peas, and eggs. Mix well.', 3),
(140, 34, 'Spread mayo/hummus on tortilla.', 1),
(141, 34, 'Layer turkey, avocado, and lettuce.', 2),
(142, 34, 'Roll tightly, slice in half, and serve.', 3),
(143, 35, 'Mix chickpeas, cucumber, tomato, olive oil.', 1),
(144, 35, 'Divide into bowls, top with feta and tzatziki.', 2),
(145, 36, 'Cook rice in broth until fluffy.', 1),
(146, 36, 'In skillet, melt butter, sauté garlic.', 2),
(147, 36, 'Add shrimp, paprika, salt & pepper. Cook 3–4 mins.', 3),
(148, 36, 'Serve shrimp over rice, garnish with parsley.', 4),
(149, 37, 'Marinate chicken in lemon, oil, herbs, garlic for 15 mins.', 1),
(150, 37, 'Grill until golden and cooked through (8–10 mins).', 2),
(151, 37, 'Serve with roasted veggies or salad.', 3),
(152, 38, 'Preheat oven to 350°F (175°C). Grease a 9x13 pan.', 1),
(153, 38, 'Melt butter and stir in sugar, eggs, and vanilla.', 2),
(154, 38, 'Mix flour, cocoa, and salt, then fold into wet mix.', 3),
(155, 38, 'Stir in chocolate chips.', 4),
(156, 38, 'Spread into pan and bake 30–35 mins.', 5),
(157, 39, 'Mix crumbs, butter, sugar → press into pan.', 1),
(158, 39, 'Beat cream cheese and sugar until smooth.', 2),
(159, 39, 'Add eggs one at a time, then vanilla.', 3),
(160, 39, 'Fold in sour cream.', 4),
(161, 39, 'Bake at 325°F (160°C) for 60–70 mins. Chill overnight.', 5),
(162, 40, 'Whip yolks + sugar until creamy.', 1),
(163, 40, 'Fold in mascarpone.', 2),
(164, 40, 'Beat whites until stiff → fold in.', 3),
(165, 40, 'Dip ladyfingers in coffee, layer in dish.', 4),
(166, 40, 'Spread cream, repeat layers.', 5),
(167, 40, 'Dust with cocoa. Chill 6 hrs.', 6),
(168, 41, 'Preheat oven to 425°F (220°C).', 1),
(169, 41, 'Peel and cut sweet potatoes into thin fries.', 2),
(170, 41, 'Toss with olive oil, paprika, garlic powder, salt, and pepper.', 3),
(171, 41, 'Spread on baking sheet (single layer).', 4),
(172, 41, 'Bake 25–30 mins, flipping halfway, until crispy.', 5),
(173, 42, 'Mash avocados in a bowl.', 1),
(174, 42, 'Mix in lime juice, onion, tomato, and jalapeño.', 2),
(175, 42, 'Season with salt.', 3),
(176, 42, 'Serve with tortilla chips.', 4);

-- --------------------------------------------------------

--
-- Table structure for table `saved_recipes`
--

CREATE TABLE `saved_recipes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_recipes`
--

INSERT INTO `saved_recipes` (`id`, `user_id`, `recipe_id`, `created_at`) VALUES
(5, 4, 42, '2025-09-09 14:15:05'),
(6, 4, 38, '2025-09-09 14:15:13'),
(7, 4, 37, '2025-09-09 14:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$4asNBXDWc9/ERAjl0/KFqetVpauup.0e0MIqPN9ifGKiYT2SkFeny', 'admin', '2025-09-05 10:57:18'),
(4, 'Mike', 'meka@gmail.com', '$2y$10$bGcQ/c8AFukSQI135rLymO1EbRMhjZukyEJFdN8ToOxbF6qFAOCnC', 'user', '2025-09-09 13:56:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `recipe_instructions`
--
ALTER TABLE `recipe_instructions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `saved_recipes`
--
ALTER TABLE `saved_recipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_recipe` (`user_id`,`recipe_id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `recipe_instructions`
--
ALTER TABLE `recipe_instructions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT for table `saved_recipes`
--
ALTER TABLE `saved_recipes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipes_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_instructions`
--
ALTER TABLE `recipe_instructions`
  ADD CONSTRAINT `recipe_instructions_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_recipes`
--
ALTER TABLE `saved_recipes`
  ADD CONSTRAINT `saved_recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_recipes_ibfk_2` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
