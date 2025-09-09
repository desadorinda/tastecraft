<?php
// includes/auth.php
require_once __DIR__ . '/database.php';

// CSRF helpers
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Auth helpers
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function login_user(array $user): void {
    // regenerate id to prevent fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
}

function logout_user(): void {
    // Clear session data and cookie
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function require_role(string $role): void {
    require_login();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        // Not authorized for that role
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Optional: fetch latest user record from DB
function current_user_from_db() {
    if (!is_logged_in()) return null;
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}
?>
