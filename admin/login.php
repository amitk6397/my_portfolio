<?php
require_once __DIR__ . '/../db.php';

$error = '';

if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM `admin_users` WHERE `username` = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_id'] = $user['id'];
                header('Location: dashboard');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Login | Amit.Dev</title>
  
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=DM+Sans:wght@400;500;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet"/>
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  
  <style>
    :root {
      --bg: #080A0F;
      --bg2: #0D1018;
      --surface: #141824;
      --border: rgba(255, 255, 255, 0.07);
      --border2: rgba(255, 255, 255, 0.12);
      --accent: #00E5FF;
      --accent-gradient: linear-gradient(135deg, #7B61FF, #00E5FF);
      --text: #F0F2F8;
      --text2: #8B90A0;
      --red: #FF4D4D;
      --font-body: 'DM Sans', sans-serif;
      --font-mono: 'Fira Code', monospace;
      --font-display: 'Barlow Condensed', sans-serif;
      --radius: 16px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: var(--font-body);
      background-color: var(--bg);
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      overflow: hidden;
      position: relative;
    }

    /* Noise & Orb background */
    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.02'/%3E%3C/svg%3E");
      pointer-events: none;
      z-index: 0;
      opacity: 0.5;
    }

    .orb {
      position: absolute;
      width: 500px;
      height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(0, 229, 255, 0.08) 0%, transparent 70%);
      top: -100px;
      right: -100px;
      pointer-events: none;
      z-index: 0;
    }

    .orb2 {
      position: absolute;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(123, 97, 255, 0.06) 0%, transparent 70%);
      bottom: -200px;
      left: -200px;
      pointer-events: none;
      z-index: 0;
    }

    .login-container {
      width: 100%;
      max-width: 440px;
      padding: 24px;
      position: relative;
      z-index: 1;
      animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .login-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 40px 32px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(10px);
    }

    /* Glowing top border line */
    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--accent-gradient);
    }

    .login-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .logo-badge {
      width: 48px;
      height: 48px;
      background: var(--accent);
      color: var(--bg);
      font-family: var(--font-mono);
      font-weight: 700;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      margin: 0 auto 16px;
      box-shadow: 0 0 20px rgba(0, 229, 255, 0.3);
    }

    .login-title {
      font-family: var(--font-display);
      font-size: 32px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .login-subtitle {
      font-size: 14px;
      color: var(--text2);
    }

    .error-alert {
      background: rgba(255, 77, 77, 0.1);
      border: 1px solid rgba(255, 77, 77, 0.2);
      color: var(--red);
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 13.5px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-label {
      display: block;
      font-size: 12px;
      color: var(--text2);
      margin-bottom: 8px;
      text-transform: uppercase;
      font-family: var(--font-mono);
      letter-spacing: 0.05em;
    }

    .input-wrapper {
      position: relative;
    }

    .form-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text3);
      font-size: 14px;
      pointer-events: none;
      transition: color 0.3s;
    }

    .form-input {
      width: 100%;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px 16px 12px 42px;
      font-family: var(--font-body);
      font-size: 14px;
      color: var(--text);
      outline: none;
      transition: all 0.3s;
    }

    .form-input:focus {
      border-color: var(--accent);
      background: rgba(255, 255, 255, 0.05);
      box-shadow: 0 0 12px rgba(0, 229, 255, 0.1);
    }

    .form-input:focus + .form-icon {
      color: var(--accent);
    }

    .toggle-password {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text2);
      cursor: pointer;
      font-size: 14px;
      transition: color 0.3s;
    }

    .toggle-password:hover {
      color: var(--text);
    }

    .submit-btn {
      width: 100%;
      padding: 12px;
      background: var(--accent);
      color: var(--bg);
      border: none;
      border-radius: 8px;
      font-family: var(--font-body);
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      letter-spacing: 0.5px;
      margin-top: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .submit-btn:hover {
      background: var(--text);
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(0, 229, 255, 0.2);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 24px;
      font-size: 13px;
      color: var(--text2);
      text-decoration: none;
      transition: color 0.3s;
    }

    .back-link:hover {
      color: var(--accent);
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>

  <div class="orb"></div>
  <div class="orb2"></div>

  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-badge">AK</div>
        <h1 class="login-title">Admin Hub</h1>
        <p class="login-subtitle">Sign in to manage your portfolio</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="error-alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="login">
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <div class="input-wrapper">
            <input class="form-input" type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username" />
            <i class="fa-solid fa-user form-icon"></i>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrapper">
            <input class="form-input" type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password" />
            <i class="fa-solid fa-lock form-icon"></i>
            <i class="fa-solid fa-eye toggle-password" id="togglePassword" onclick="togglePassVisibility()"></i>
          </div>
        </div>

        <button class="submit-btn" type="submit">
          <i class="fa-solid fa-right-to-bracket"></i> Login
        </button>
      </form>

      <a href="forgot_password" class="back-link" style="margin-top: 16px;"><i class="fa-solid fa-unlock-keyhole"></i> Forgot Password?</a>

      <a href="../" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Portfolio</a>
    </div>
  </div>

  <script>
    function togglePassVisibility() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('togglePassword');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>
