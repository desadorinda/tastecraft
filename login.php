<?php
// login.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in redirect to the appropriate dashboard
if (is_logged_in()) {
    if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header('Location: ' . BASE_URL . 'admin/index.php');
    } else {
        header('Location: ' . BASE_URL . 'user/user.php');
    }
    exit();
}

$error = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $old_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        if ($email === '' || $password === '') {
            $error = "Please enter both email and password.";
        } else {
            try {
                $db = new Database();
                $conn = $db->getConnection();
                $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Success: login user and redirect based on role
                    login_user($user);
                    if ($user['role'] === 'admin') {
                        header('Location: ' . BASE_URL . 'admin/index.php');
                    } else {
                        header('Location: ' . BASE_URL . 'user/index.php');
                    }
                    exit();
                } else {
                    $error = "Incorrect email or password.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get CSRF token for form
$csrf_token = generate_csrf_token();
$isLoggedIn = is_logged_in();
ob_start()
?>

<style>
body{
  background:linear-gradient(135deg,#000000,#1a1a1a,#000000,#b45309,#f59e0b);
  background-attachment:fixed;color:#fcd34d;min-height:100vh;
  font-family:'Poppins',sans-serif;
}
@keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
@keyframes shimmer{0%{left:-100%}100%{left:100%}}
.nav-gradient{
  background:linear-gradient(105deg,#000,#1a1a1a,#000,#b45309,#f59e0b);
  background-size:200% 200%;animation:gradientShift 8s ease infinite;position:relative
}
.nav-gradient::before{
  content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(245,158,11,.1),transparent);
  animation:shimmer 3s infinite;
}
.nav-link{padding:.5rem 1rem}.nav-link:hover{color:#f59e0b}
.card-gradient{
  background:linear-gradient(160deg,#0a0a0a,#1a1a1a 60%,#000);
  border:1px solid #b45309;box-shadow:0 10px 25px rgba(0,0,0,.5);
}
.footer-gradient{
  background:linear-gradient(355deg,#000,#1a1a1a,#000,#b45309,#f59e0b);
  background-size:200% 200%;animation:gradientShift 8s ease infinite
}
.input-field{
  background:rgba(26,26,26,.85);border:2px solid #2a2a2a;color:#fcd34d;
  padding:0.75rem 1rem;border-radius:0.75rem;width:100%;
  transition:.3s;outline:none;
}
.input-field:focus{
  border-color:#f59e0b;box-shadow:0 0 8px rgba(245,158,11,.6);
}
.label-text{color:#f59e0b;font-weight:600;margin-bottom:0.5rem;display:block}
.password-container{position:relative}
.password-toggle{position:absolute;top:50%;right:15px;transform:translateY(-50%);cursor:pointer;color:#f59e0b;transition:.3s}
.password-toggle:hover{color:#fcd34d}
.login-btn{
  background:linear-gradient(to right,#f59e0b,#b45309);color:#000;font-weight:bold;
  width:100%;padding:0.85rem;border-radius:0.75rem;transition:.3s;
}
.login-btn:hover{background:linear-gradient(to right,#fcd34d,#f59e0b);transform:translateY(-2px)}
</style>


<!-- LOGIN SECTION -->
<section class="pt-32 pb-16 px-6 flex items-center justify-center">
  <div class="w-full max-w-md card-gradient rounded-2xl p-10">
    <h1 class="text-3xl font-bold text-center mb-2 bg-gradient-to-r from-yellow-300 via-yellow-500 to-amber-700 bg-clip-text text-transparent">Welcome Back</h1>
    <p class="text-center text-gold-300 mb-8">Log in to continue your culinary journey</p>

    <?php if ($error): ?>
      <div class="bg-red-800 text-red-200 p-3 rounded-lg mb-6 text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif;?>

   <form method="POST" novalidate class="space-y-6">
  <!-- CSRF token (required) -->
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

  <div>
    <label class="label-text" for="email">Email</label>
    <input
      type="email"
      id="email"
      name="email"
      required
      class="input-field"
      placeholder="you@example.com"
      value="<?= htmlspecialchars($old_email ?? '', ENT_QUOTES, 'UTF-8') ?>"
    >
  </div>

  <div>
    <label class="label-text" for="password">Password</label>
    <div class="password-container">
      <input
        type="password"
        id="password"
        name="password"
        required
        class="input-field pr-10"
        placeholder="Enter password"
      >
      <span id="togglePassword" class="password-toggle"><i class="fas fa-eye"></i></span>
    </div>
  </div>

  <button type="submit" class="login-btn">Login</button>
</form>


    <div class="mt-6 text-center text-gold-300">
      Don't have an account? <a href="signup.php" class="text-gold-500 hover:text-gold-300 font-semibold">Sign up</a>
    </div>
  </div>
</section>

<script>
document.getElementById('togglePassword').onclick=()=>{
  const p=document.getElementById('password');
  const icon=document.querySelector('#togglePassword i');
  p.type = p.type==='password'?'text':'password';
  icon.classList.toggle('fa-eye');
  icon.classList.toggle('fa-eye-slash');
};
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>