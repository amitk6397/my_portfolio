<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mail_helper.php';

$error = '';
$success = '';
$show_otp_form = false;

if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: dashboard');
    exit;
}

if (isset($_GET['clear'])) {
    unset($_SESSION['reset_email']);
    header('Location: forgot_password');
    exit;
}

// Check if there is a pending reset email in session
if (isset($_SESSION['reset_email'])) {
    $show_otp_form = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'send_otp') {
        $email = trim($_POST['email']);

        if (!empty($email)) {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Check if user exists with this email
                $stmt = $db->prepare("SELECT * FROM `admin_users` WHERE `email` = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Generate 6-digit numeric OTP
                    $otp = sprintf("%06d", rand(100000, 999999));
                    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                    // Save OTP to database
                    $stmtUpdate = $db->prepare("UPDATE `admin_users` SET `otp_code` = :otp, `otp_expiry` = :expiry WHERE `id` = :id");
                    $stmtUpdate->execute([
                        'otp' => $otp,
                        'expiry' => $expiry,
                        'id' => $user['id']
                    ]);

                    // Fetch SMTP settings
                    $smtpQuery = $db->query("SELECT * FROM `smtp_settings` WHERE `id` = 1");
                    $smtp_settings = $smtpQuery->fetch();

                    if ($smtp_settings) {
                        $subject = "Your OTP Password Reset Code | Amit.Dev Admin";
                        $message = "
                        <div style=\"font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333333; padding: 40px 20px;\">
                            <div style=\"background-color: #ffffff; border-radius: 12px; padding: 35px 30px; max-width: 500px; margin: 0 auto; border: 1px solid #e1e4e8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: left;\">
                                <div style=\"text-align: center; margin-bottom: 25px;\">
                                    <span style=\"background-color: #00E5FF; color: #080A0F; padding: 8px 16px; font-weight: bold; border-radius: 6px; font-size: 18px; display: inline-block;\">AK</span>
                                    <h2 style=\"margin-top: 15px; margin-bottom: 5px; color: #1a1f2c; font-size: 22px; font-weight: 700;\">Password Reset OTP</h2>
                                </div>
                                <p style=\"font-size: 15px; line-height: 1.6; color: #4a5568;\">Hello,</p>
                                <p style=\"font-size: 15px; line-height: 1.6; color: #4a5568;\">We received a request to reset your admin password. Use the following One-Time Password (OTP) to proceed. This code is valid for 10 minutes.</p>
                                
                                <div style=\"font-size: 34px; font-weight: bold; color: #7B61FF; letter-spacing: 5px; text-align: center; margin: 30px 0; font-family: monospace; background-color: #f7f5ff; padding: 15px; border-radius: 8px; border: 1px dashed #7B61FF;\">
                                    $otp
                                </div>
                                
                                <div style=\"font-size: 13px; color: #718096; margin-top: 30px; line-height: 1.6; border-top: 1px solid #edf2f7; padding-top: 15px;\">
                                    <p style=\"margin-bottom: 6px;\">If you did not request this, please ignore this email.</p>
                                    <p style=\"margin: 0; color: #a0aec0;\">&copy; " . date('Y') . " Amit.Dev Portfolio System</p>
                                </div>
                            </div>
                        </div>
                        ";

                        send_smtp_email($email, $subject, $message, $smtp_settings);
                        
                        // Set session and update views
                        $_SESSION['reset_email'] = $email;
                        $show_otp_form = true;
                        $success = 'OTP code has been sent to your email address.';
                    } else {
                        $error = 'SMTP configuration is missing. Please contact system administrator.';
                    }
                } else {
                    $error = 'This email address is not registered in our system.';
                }
            } catch (Exception $e) {
                $error = 'Failed to send OTP. SMTP Error details: ' . $e->getMessage() . '<br/><br/><strong>Troubleshooting tip:</strong> Double check your SMTP/Gmail App Password settings.';
            }
        } else {
            $error = 'Please enter your email address.';
        }
    }

    if ($action === 'verify_otp') {
        $otp_entered = trim($_POST['otp_code']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';

        if (empty($email)) {
            $error = 'Session expired. Please request a new OTP.';
            $show_otp_form = false;
        } elseif (empty($otp_entered) || empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT * FROM `admin_users` WHERE `email` = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $expiry = strtotime($user['otp_expiry']);
                    if (time() > $expiry) {
                        $error = 'OTP code has expired. Please request a new one.';
                        $show_otp_form = false;
                        unset($_SESSION['reset_email']);
                    } elseif ($user['otp_code'] !== $otp_entered) {
                        $error = 'Invalid OTP code. Please try again.';
                    } else {
                        // Success! Update password and clear OTP
                        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmtUpdate = $db->prepare("UPDATE `admin_users` SET `password` = :pass, `otp_code` = NULL, `otp_expiry` = NULL WHERE `id` = :id");
                        $stmtUpdate->execute([
                            'pass' => $hashedPassword,
                            'id' => $user['id']
                        ]);

                        unset($_SESSION['reset_email']);
                        $show_otp_form = false;
                        $success = 'Password reset successful! Redirecting to login page...';
                    }
                } else {
                    $error = 'User not found.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'resend_otp') {
        $email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
        if (empty($email)) {
            $error = 'Session expired. Please enter your email again.';
            $show_otp_form = false;
        } else {
            $_POST['action'] = 'send_otp';
            $_POST['email'] = $email;
            // Re-run the send_otp handler code above
            $otp = sprintf("%06d", rand(100000, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT id FROM `admin_users` WHERE `email` = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $db->prepare("UPDATE `admin_users` SET `otp_code` = :otp, `otp_expiry` = :expiry WHERE `id` = :id")
                       ->execute(['otp' => $otp, 'expiry' => $expiry, 'id' => $user['id']]);

                    $smtpQuery = $db->query("SELECT * FROM `smtp_settings` WHERE `id` = 1");
                    $smtp_settings = $smtpQuery->fetch();

                    if ($smtp_settings) {
                        $subject = "Your OTP Password Reset Code | Resend";
                        $message = "
                        <div style=\"font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333333; padding: 40px 20px;\">
                            <div style=\"background-color: #ffffff; border-radius: 12px; padding: 35px 30px; max-width: 500px; margin: 0 auto; border: 1px solid #e1e4e8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: left;\">
                                <div style=\"text-align: center; margin-bottom: 25px;\">
                                    <span style=\"background-color: #00E5FF; color: #080A0F; padding: 8px 16px; font-weight: bold; border-radius: 6px; font-size: 18px; display: inline-block;\">AK</span>
                                    <h2 style=\"margin-top: 15px; margin-bottom: 5px; color: #1a1f2c; font-size: 22px; font-weight: 700;\">Password Reset OTP (Resend)</h2>
                                </div>
                                <p style=\"font-size: 15px; line-height: 1.6; color: #4a5568;\">Hello,</p>
                                <p style=\"font-size: 15px; line-height: 1.6; color: #4a5568;\">Use the following One-Time Password (OTP) to reset your password. This code is valid for 10 minutes.</p>
                                
                                <div style=\"font-size: 34px; font-weight: bold; color: #7B61FF; letter-spacing: 5px; text-align: center; margin: 30px 0; font-family: monospace; background-color: #f7f5ff; padding: 15px; border-radius: 8px; border: 1px dashed #7B61FF;\">
                                    $otp
                                </div>
                                
                                <div style=\"font-size: 13px; color: #718096; margin-top: 30px; line-height: 1.6; border-top: 1px solid #edf2f7; padding-top: 15px;\">
                                    <p style=\"margin-bottom: 6px;\">If you did not request this, please ignore this email.</p>
                                    <p style=\"margin: 0; color: #a0aec0;\">&copy; " . date('Y') . " Amit.Dev Portfolio System</p>
                                </div>
                            </div>
                        </div>
                        ";
                        send_smtp_email($email, $subject, $message, $smtp_settings);
                        $success = 'A new OTP has been sent to ' . htmlspecialchars($email);
                    }
                }
            } catch (Exception $e) {
                $error = 'Resend failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Forgot Password OTP | Amit.Dev</title>
  
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
      --accent: #00E5FF;
      --accent-gradient: linear-gradient(135deg, #7B61FF, #00E5FF);
      --text: #F0F2F8;
      --text2: #8B90A0;
      --red: #FF4D4D;
      --green: #00E676;
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
      margin-bottom: 24px;
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
      text-align: left;
    }

    .success-alert {
      background: rgba(0, 230, 118, 0.1);
      border: 1px solid rgba(0, 230, 118, 0.2);
      color: var(--green);
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
      color: var(--text2);
      font-size: 14px;
      pointer-events: none;
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
        <h1 class="login-title">Forgot Password</h1>
        <p class="login-subtitle">OTP Verification Flow</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="error-alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><?php echo $error; ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="success-alert">
          <i class="fa-solid fa-circle-check"></i>
          <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <?php if (strpos($success, 'successful') !== false): ?>
          <script>
            setTimeout(function() {
              window.location.href = 'login';
            }, 3000);
          </script>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (!$show_otp_form): ?>
        <!-- STEP 1: REQUEST OTP -->
        <form method="POST" action="forgot_password">
          <input type="hidden" name="action" value="send_otp" />
          <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <div class="input-wrapper">
              <input class="form-input" type="email" id="email" name="email" placeholder="Enter registered email" required autocomplete="email" />
              <i class="fa-solid fa-envelope form-icon"></i>
            </div>
          </div>

          <button class="submit-btn" type="submit">
            <i class="fa-solid fa-envelope-open-text"></i> Send OTP Code
          </button>
        </form>
      <?php else: ?>
        <!-- STEP 2: VERIFY OTP & RESET -->
        <form method="POST" action="forgot_password">
          <input type="hidden" name="action" value="verify_otp" />
          
          <div class="form-group" style="text-align: center; margin-bottom: 10px;">
            <span style="font-size: 13px; color: var(--text2);">Sending reset to: <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></span>
          </div>

          <div class="form-group">
            <label class="form-label" for="otp_code">Enter 6-Digit OTP</label>
            <div class="input-wrapper">
              <input class="form-input" type="text" id="otp_code" name="otp_code" placeholder="Enter OTP code" maxlength="6" required autocomplete="one-time-code" style="letter-spacing: 2px; font-weight: bold; text-align: center; padding-left: 16px;" />
              <i class="fa-solid fa-shield form-icon"></i>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="new_password">New Password</label>
            <div class="input-wrapper">
              <input class="form-input" type="password" id="new_password" name="new_password" placeholder="Enter new password" required autocomplete="new-password" />
              <i class="fa-solid fa-lock form-icon"></i>
              <i class="fa-solid fa-eye toggle-password" onclick="togglePassVisibility('new_password', this)"></i>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <div class="input-wrapper">
              <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required autocomplete="new-password" />
              <i class="fa-solid fa-lock form-icon"></i>
              <i class="fa-solid fa-eye toggle-password" onclick="togglePassVisibility('confirm_password', this)"></i>
            </div>
          </div>

          <button class="submit-btn" type="submit">
            <i class="fa-solid fa-key"></i> Update Password
          </button>
        </form>

        <form method="POST" action="forgot_password" style="margin-top: 15px;">
          <input type="hidden" name="action" value="resend_otp" />
          <button type="submit" style="background: none; border: none; color: var(--accent); font-family: var(--font-body); font-size: 13px; cursor: pointer; text-decoration: underline; display: block; margin: 0 auto;">
            Resend OTP Code
          </button>
        </form>

        <a href="forgot_password?clear=1" class="back-link" style="margin-top: 15px;"><i class="fa-solid fa-rotate-left"></i> Change Email</a>
      <?php endif; ?>

      <a href="login" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
    </div>
  </div>

  <script>
    function togglePassVisibility(id, toggleIcon) {
      const input = document.getElementById(id);
      if (input.type === 'password') {
        input.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>
