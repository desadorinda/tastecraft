<?php
// signup.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect away
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$error = '';
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $old['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $old['email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        if (!$name || !$email || !$password || !$confirm) {
            $error = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password too short. Minimum 6 characters.";
        } else {
            try {
                $db = new Database();
                $conn = $db->getConnection();

                // Check duplicate email
                $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    $error = "Email already registered. Try logging in.";
                } else {
                    // Insert user (default role 'user')
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':password' => $hash,
                        ':role' => 'user'
                    ]);
                    $id = $conn->lastInsertId();
                    // auto-login
                    $user = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'user'];
                    login_user($user);
                    header('Location: ' . BASE_URL . 'user.php');
                    exit();
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

$csrf_token = generate_csrf_token();
$isLoggedIn = is_logged_in();
ob_start()
?>

<style>
body{
  background:linear-gradient(135deg,#000,#1a1a1a,#000,#b45309,#f59e0b);
  background-attachment:fixed;color:#fcd34d;min-height:100vh;font-family:'Poppins',sans-serif;
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
  background-size:200% 200%;animation:gradientShift 8s ease infinite;
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
.signup-btn{
  background:linear-gradient(to right,#f59e0b,#b45309);color:#000;font-weight:bold;
  width:100%;padding:0.85rem;border-radius:0.75rem;transition:.3s;
}
.signup-btn:hover{background:linear-gradient(to right,#fcd34d,#f59e0b);transform:translateY(-2px)}
</style>


<!-- SIGN UP FORM -->
<section class="pt-32 px-6">
  <div class="max-w-md mx-auto card-gradient p-10 rounded-2xl">
    <h1 class="text-3xl font-bold text-center mb-2 bg-gradient-to-r from-yellow-300 via-yellow-500 to-amber-700 bg-clip-text text-transparent">Create Account</h1>
    <p class="text-center text-gold-300 mb-6">Join our community of food lovers today ✨</p>

    <?php if ($error): ?>
      <div class="bg-red-800 text-red-200 p-3 rounded mb-6 text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif;?>

    <form method="POST" novalidate class="space-y-6">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

      <div>
        <label for="name" class="label-text">Full Name</label>
        <input id="name" name="name" value="<?= $old['name'] ?>" class="input-field" placeholder="John Doe" required>
      </div>

      <div>
        <label for="email" class="label-text">Email</label>
        <input type="email" id="email" name="email" value="<?= $old['email'] ?>" class="input-field" placeholder="you@example.com" required>
      </div>

      <div>
        <label for="password" class="label-text">Password</label>
        <div class="password-container">
          <input type="password" id="password" name="password" class="input-field pr-10" placeholder="At least 6 characters" required>
          <span id="togglePassword" class="password-toggle"><i class="fas fa-eye"></i></span>
        </div>
      </div>

      <div>
        <label for="confirm_password" class="label-text">Confirm Password</label>
        <div class="password-container">
          <input type="password" id="confirm_password" name="confirm_password" class="input-field pr-10" placeholder="Re-enter password" required>
          <span id="toggleConfirmPassword" class="password-toggle"><i class="fas fa-eye"></i></span>
        </div>
      </div>

      <button type="submit" class="signup-btn">Create Account</button>
    </form>

    <div class="text-center mt-6 text-gold-300">
      Already have an account? <a href="login.php" class="text-gold-500 hover:text-gold-300 font-semibold">Login</a>
    </div>
  </div>
</section>

<script>
document.getElementById('mobileMenuBtn').onclick=()=>document.getElementById('mobileMenu')?.classList.toggle('hidden');

const pass=document.getElementById('password'),toggle=document.getElementById('togglePassword'),
      c=document.getElementById('confirm_password'),toggleC=document.getElementById('toggleConfirmPassword');

toggle.onclick=()=>{pass.type=pass.type==='password'?'text':'password';
 toggle.querySelector('i').classList.toggle('fa-eye');toggle.querySelector('i').classList.toggle('fa-eye-slash');}
toggleC.onclick=()=>{c.type=c.type==='password'?'text':'password';
 toggleC.querySelector('i').classList.toggle('fa-eye');toggleC.querySelector('i').classList.toggle('fa-eye-slash');}
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>