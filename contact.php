<?php
require_once __DIR__ . '/includes/database.php';


$db = new Database();
$pdo = $db->getConnection();

$feedback = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $userId = $_SESSION['user_id'] ?? null;

    if (!$name || !$email || !$subject || !$message) {
        $feedback = '<p class="text-red-500 font-bold">⚠ Please fill in all fields.</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = '<p class="text-red-500 font-bold">⚠ Invalid email address.</p>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$userId, $name, $email, $subject, $message])) {
            $feedback = '<p class="text-green-500 font-bold">✅ Thank you! Your message has been sent successfully.</p>';
        } else {
            $feedback = '<p class="text-red-500 font-bold">❌ Something went wrong. Please try again later.</p>';
        }
    }
}
$isLoggedIn = isset($_SESSION['user_id']);

ob_start()
?>


  <style>
    body{font-family:'Poppins',sans-serif;
      background:linear-gradient(135deg,#000000,#1a1a1a,#000000,#b45309,#f59e0b);min-height:100vh;overflow-x:hidden;color:#fcd34d}
    @keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
    @keyframes shimmer{0%{left:-100%}100%{left:100%}}
    @keyframes float{0%{transform:translateY(0)}50%{transform:translateY(-20px)}100%{transform:translateY(0)}}
    .nav-gradient{background:linear-gradient(105deg,#000000,#1a1a1a,#000000,#b45309,#f59e0b);
      background-size:200% 200%;animation:gradientShift 8s ease infinite;box-shadow:0 4px 30px rgba(180,83,9,.3);position:relative}
    .nav-gradient::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
      background:linear-gradient(90deg,transparent,rgba(245,158,11,.1),transparent);animation:shimmer 3s infinite}
    .nav-link{padding:.5rem 1rem}
    .nav-link:hover{color:#f59e0b}
    .footer-gradient{background:linear-gradient(355deg,#000000 0%,#1a1a1a 20%,#000000 40%,#b45309 60%,#f59e0b 80%);
      background-size:200% 200%;animation:gradientShift 8s ease infinite}

    /* Contact form styles */
    .form-container{background:rgba(0,0,0,.7);backdrop-filter:blur(10px);border-radius:20px;
      box-shadow:0 15px 35px rgba(212,175,55,.2);border:1px solid rgba(212,175,55,.3)}
    .food-gradient-text{background:linear-gradient(135deg,#D4AF37 0%,#FFDF7F 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .input-field{transition:.3s;border:2px solid rgba(212,175,55,.3);background:rgba(0,0,0,.6);color:#FFDF7F}
    .input-field:focus{border-color:#D4AF37;box-shadow:0 0 0 3px rgba(212,175,55,.2)}
    .submit-btn{background:linear-gradient(135deg,#D4AF37,#B8860B);color:#000;transition:.3s}
    .submit-btn:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(212,175,55,.5)}
    .food-animation{position:absolute;font-size:2rem;opacity:.7;animation:float 6s ease-in-out infinite;color:#D4AF37;z-index:0}

  </style>


<!-- MAIN -->
<div class="pt-24 min-h-screen py-12 px-4 flex items-center justify-center relative">
  <!-- Floating Food Emojis -->
  <div class="food-animation" style="top:10%;left:5%">🍕</div>
  <div class="food-animation" style="top:20%;right:8%">🍔</div>
  <div class="food-animation" style="top:40%;left:7%">🍦</div>

  <div class="w-full max-w-2xl relative z-10">
    <div class="text-center mb-12">
      <h1 class="text-4xl md:text-5xl font-bold mb-4">Contact <span class="food-gradient-text">Us</span></h1>
      <p class="max-w-md mx-auto text-lg text-gold-300">Have questions or need assistance? We're here to help!</p>
    </div>

    <div class="form-container p-8 md:p-10 rounded-2xl">
      <?php if (!empty($feedback)) {
          echo "<div class='mb-4 text-center'>$feedback</div>";
      } ?>
      <form method="POST" class="space-y-6">
        <div>
          <label class="block mb-2">Full Name</label>
          <input type="text" name="name" class="w-full input-field rounded-lg py-3 px-4">
        </div>
        <div>
          <label class="block mb-2">Email Address</label>
          <input type="email" name="email" class="w-full input-field rounded-lg py-3 px-4">
        </div>
        <div>
          <label class="block mb-2">Subject</label>
          <input type="text" name="subject" class="w-full input-field rounded-lg py-3 px-4">
        </div>
        <div>
          <label class="block mb-2">Message</label>
          <textarea name="message" rows="5" class="w-full input-field rounded-lg py-3 px-4"></textarea>
        </div>
        <button type="submit" class="w-full submit-btn font-bold py-3 px-6 rounded-lg"><i class="fas fa-paper-plane mr-2"></i>Send Message</button>
      </form>
      <div class="mt-6 text-center">
        <p>Prefer to email us directly?</p>
        <a href="mailto:hello@foodexample.com" class="text-gold-500">hello@foodexample.com</a>
      </div>
    </div>
  </div>
</div>


<script>
 document.getElementById('mobileMenuBtn').addEventListener('click',()=>document.getElementById('mobileMenu').classList.toggle('hidden'));
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>