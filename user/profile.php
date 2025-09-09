<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('user');

require_once __DIR__ . '/../includes/database.php';
$db = new Database();
$pdo = $db->getConnection();

$userId = $_SESSION['user_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$success = $error = null;

// Handle update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new RuntimeException('Invalid CSRF token.');
        }

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name && $email) {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $hashedPassword, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $userId]);
            }
            $success = "Profile updated successfully.";
            $_SESSION['user_name'] = $name; // update session name
            // Refresh $user values for the form
            $user['name'] = $name;
            $user['email'] = $email;
        } else {
            $error = "Name and Email are required.";
        }
    } catch (PDOException $e) {
        // If there's a unique constraint on email, surface a friendly message
        if ($e->getCode() === '23000') {
            $error = "Email is already in use.";
        } else {
            $error = "Error updating profile.";
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = "Dashboard - TasteCraft";
$current_page = "profile.php";

// Compute user initial for avatar
$initial = 'U';
if (!empty($user['name'])) {
    if (function_exists('mb_substr')) {
        $initial = strtoupper(mb_substr($user['name'], 0, 1, 'UTF-8'));
    } else {
        $initial = strtoupper(substr($user['name'], 0, 1));
    }
}

ob_start();
?>
<style>
  :root {
    --primary-dark: #000000ff;
    --danger-dark: #bd1907ff;
    --danger: red;
    --primary: #000000ff;
    --secondary: #f59e0b;
    --secondary-light: #fcd34d;
    --accent: #b45309;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --card-bg: rgba(0, 0, 0, 0.8);
    --hover-bg: rgba(245, 158, 11, 0.1);
    --border-soft: rgba(0, 0, 0, 0.25);
  }

  .page-head { color: var(--text-primary); }
  .subtext { color: var(--text-secondary); }

  .card {
    background: var(--card-bg);
    border: 1px solid var(--border-soft);
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(2, 6, 23, 0.35);
    backdrop-filter: blur(6px);
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .card:hover { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(2,6,23,.45); }

  .btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: .5rem; padding: .6rem 1rem; border-radius: 10px;
    font-weight: 700; border: 1px solid transparent; cursor: pointer;
    transition: all .15s ease;
  }
  .btn-primary { background: var(--secondary); color: #111827; }
  .btn-primary:hover { background: var(--secondary-light); }
  .btn-outline { background: transparent; color: var(--text-secondary); border-color: rgba(148,163,184,.25); }
  .btn-outline:hover { background: rgba(148,163,184,.08); color: var(--text-primary); }
  .btn-danger { background: var(--danger); color: white; }
  .btn-danger:hover { background: var(--danger-dark); }

  .form-input, .form-textarea {
    width: 100%; padding: .75rem .9rem; border-radius: 10px;
    border: 1px solid var(--border-soft);
    background: rgba(2, 6, 23, 0.35); color: var(--text-primary);
    transition: border-color .15s ease, box-shadow .15s ease;
  }
  .form-input:focus, .form-textarea:focus {
    outline: none; border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
  }
  label { color: var(--text-secondary); font-weight: 700; }

  .avatar {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(145deg, rgba(15,23,42,.65), rgba(2,6,23,.65));
    border: 1px solid var(--border-soft);
    color: var(--secondary-light); font-weight: 900; font-size: 1.5rem;
    display:flex; align-items:center; justify-content:center;
  }

  .input-group { position: relative; }
  .input-icon {
    position: absolute; right: .6rem; top: 50%; transform: translateY(-50%);
    color: var(--text-secondary); cursor: pointer; border: 1px solid var(--border-soft);
    width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px; background: rgba(2, 6, 23, 0.35);
  }
  .input-icon:hover { background: rgba(148,163,184,.12); }

  .strength {
    height: 8px; border-radius: 9999px; background: rgba(148,163,184,.2); overflow: hidden;
    border: 1px solid var(--border-soft);
  }
  .strength-bar {
    height: 100%; width: 0%; transition: width .25s ease;
    background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981, #22c55e);
  }
  .strength-label { font-size: .85rem; color: var(--text-secondary); }

  .alert {
    padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem;
    display: flex; align-items: center; gap: 0.5rem; font-weight: 600;
  }
  .alert-success { background: rgba(16,185,129,.12); color: #34d399; border: 1px solid rgba(16,185,129,.35); }
  .alert-danger { background: rgba(239,68,68,.12); color: #f87171; border: 1px solid rgba(239,68,68,.35); }
</style>

<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl md:text-3xl font-extrabold page-head mb-1">My Profile</h1>
    <p class="subtext">Manage your account info and password.</p>
  </div>
  <div class="flex items-center gap-2">
    <a href="../index.php" class="btn btn-outline"><i class="fas fa-home"></i> Back to Site</a>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card p-6">
  <div class="flex items-center gap-4 mb-6">
    <div class="avatar"><?= htmlspecialchars($initial) ?></div>
    <div>
      <div class="text-xl font-extrabold page-head"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
      <div class="subtext text-sm"><?= htmlspecialchars($user['email'] ?? '') ?></div>
    </div>
  </div>

  <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="col-span-1">
      <label class="block mb-1">Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="form-input" placeholder="Your full name" required>
    </div>

    <div class="col-span-1">
      <label class="block mb-1">Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-input" placeholder="you@example.com" required>
    </div>

    <div class="md:col-span-2">
      <label class="block mb-1">New Password (leave blank to keep current)</label>
      <div class="input-group">
        <input id="password" type="password" name="password" class="form-input pr-12" placeholder="Enter a strong password">
        <button type="button" class="input-icon" onclick="togglePassword()" aria-label="Toggle password visibility">
          <i id="pwToggleIcon" class="fas fa-eye"></i>
        </button>
      </div>
      <div class="mt-2 strength"><div id="strengthBar" class="strength-bar"></div></div>
      <div id="strengthLabel" class="strength-label mt-1">Strength: —</div>
    </div>

    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
    </div>
  </form>
</div>

<script>
  // Auto-hide alerts
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .35s ease';
      setTimeout(() => el.remove(), 350);
    });
  }, 4000);

  // Password show/hide
  function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('pwToggleIcon');
    if (!input) return;
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }

  // Password strength meter (client-side UX only)
  const pwInput = document.getElementById('password');
  const strengthBar = document.getElementById('strengthBar');
  const strengthLabel = document.getElementById('strengthLabel');

  function calcStrength(pw) {
    let score = 0;
    if (!pw) return 0;
    if (pw.length >= 8) score++;
    if (/[a-z]/.test(pw)) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.min(score, 5);
  }

  function updateStrength() {
    const pw = pwInput.value || '';
    const score = calcStrength(pw);
    const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
    const labels = ['—', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];

    strengthBar.style.width = widths[score];
    strengthLabel.textContent = 'Strength: ' + labels[score];
  }

  if (pwInput) {
    pwInput.addEventListener('input', updateStrength);
  }
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>