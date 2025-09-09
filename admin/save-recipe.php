<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category_id = (int)$_POST['category'];
    $time = trim($_POST['time']);
    $description = trim($_POST['description']);
    $user_id = $_SESSION['user_id']; // current admin

    // Handle image upload
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/recipes/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO recipes (user_id, category_id, title, description, cooking_time, image) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $category_id, $title, $description, $time, $imagePath]);

    header("Location: manage-recipes.php?success=1");
    exit;
}
