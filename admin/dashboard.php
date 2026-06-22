<?php
require_once __DIR__ . '/../db.php';

// Authentication Check
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login');
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$msg_type = 'success'; // 'success' or 'error'

// Handle AJAX OTP Request for settings changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_settings_otp') {
    header('Content-Type: application/json');
    try {
        $username = $_SESSION['admin_username'] ?? '';
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'User session not found. Please log in again.']);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM `admin_users` WHERE `username` = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Admin user not found.']);
            exit;
        }
        
        $email = $user['email'] ?? '';
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Admin email is not set. Please update your email address.']);
            exit;
        }
        
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
        
        // Load mail helper & SMTP settings
        require_once __DIR__ . '/../mail_helper.php';
        $smtpQuery = $db->query("SELECT * FROM `smtp_settings` WHERE `id` = 1");
        $smtp_settings = $smtpQuery->fetch();
        
        if ($smtp_settings) {
            $subject = "Security Verification Code | Amit.Dev Admin";
            $messageBody = "
            <div style=\"font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333333; padding: 40px 20px;\">
                <div style=\"background-color: #ffffff; border-radius: 12px; padding: 35px 30px; max-width: 500px; margin: 0 auto; border: 1px solid #e1e4e8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: left;\">
                    <div style=\"text-align: center; margin-bottom: 25px;\">
                        <span style=\"background-color: #00E5FF; color: #080A0F; padding: 8px 16px; font-weight: bold; border-radius: 6px; font-size: 18px; display: inline-block;\">AK</span>
                        <h2 style=\"margin-top: 15px; margin-bottom: 5px; color: #1a1f2c; font-size: 22px; font-weight: 700;\">Security Verification</h2>
                    </div>
                    <p style=\"font-size: 15px; line-height: 1.6; color: #4a5568;\">Hello,</p>
                    <p style=\"font-size: 15px; line-height: 1.6; color: #4a5568;\">Use the following One-Time Password (OTP) to authorize updates to your admin account credentials (password or email). This code is valid for 10 minutes.</p>
                    
                    <div style=\"font-size: 34px; font-weight: bold; color: #7B61FF; letter-spacing: 5px; text-align: center; margin: 30px 0; font-family: monospace; background-color: #f7f5ff; padding: 15px; border-radius: 8px; border: 1px dashed #7B61FF;\">
                        $otp
                    </div>
                    
                    <div style=\"font-size: 13px; color: #718096; margin-top: 30px; line-height: 1.6; border-top: 1px solid #edf2f7; padding-top: 15px;\">
                        <p style=\"margin-bottom: 6px;\">If you did not request this, please verify your account security.</p>
                        <p style=\"margin: 0; color: #a0aec0;\">&copy; " . date('Y') . " Amit.Dev Portfolio System</p>
                    </div>
                </div>
            </div>
            ";
            
            send_smtp_email($email, $subject, $messageBody, $smtp_settings);
            echo json_encode(['success' => true, 'message' => 'OTP has been sent to your registered email: ' . htmlspecialchars($email)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SMTP configuration missing in database.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP: ' . $e->getMessage()]);
    }
    exit;
}

// Helper for file uploads
function uploadFile($file, $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], $max_size = 10000000) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Create upload dir if not exists
    $uploadDir = __DIR__ . '/../public/uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = basename($file['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_extensions)) {
        return null;
    }
    
    if ($file['size'] > $max_size) {
        return null;
    }
    
    $new_filename = uniqid('file_', true) . '.' . $ext;
    $target_file = $uploadDir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return 'public/uploads/' . $new_filename;
    }
    return null;
}

// Handle Form Submissions (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $tab = isset($_POST['current_tab']) ? $_POST['current_tab'] : 'overview';

    // 1. UPDATE ABOUT SETTINGS
    if ($action === 'update_about') {
        $hero_name = trim($_POST['hero_name']);
        $hero_desc = trim($_POST['hero_desc']);
        $linkedin_url = trim($_POST['linkedin_url']);
        $github_url = trim($_POST['github_url']);
        $contact_email = trim($_POST['contact_email']);
        $contact_phone = trim($_POST['contact_phone']);
        $contact_location = trim($_POST['contact_location']);
        $status_text = trim($_POST['status_text']);
        $stack_text = trim($_POST['stack_text']);
        $experience_years = trim($_POST['experience_years']);
        $passion_text = trim($_POST['passion_text']);
        
        $story_title = trim($_POST['story_title']);
        $story_desc = trim($_POST['story_desc']);
        $education_title = trim($_POST['education_title']);
        $education_desc = trim($_POST['education_desc']);
        $backend_title = trim($_POST['backend_title']);
        $backend_desc = trim($_POST['backend_desc']);
        $philosophy_title = trim($_POST['philosophy_title']);
        $philosophy_desc = trim($_POST['philosophy_desc']);
        $firebase_title = trim($_POST['firebase_title']);
        $firebase_desc = trim($_POST['firebase_desc']);

        try {
            // Fetch current settings to preserve files if no new uploads
            $stmt = $db->query("SELECT photo_url, resume_url FROM about_settings WHERE id = 1");
            $current = $stmt->fetch();
            
            $photo_url = $current['photo_url'];
            $resume_url = $current['resume_url'];

            // Handle photo upload
            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $uploaded_photo = uploadFile($_FILES['photo_file'], ['jpg', 'jpeg', 'png', 'webp']);
                if ($uploaded_photo) {
                    $photo_url = $uploaded_photo;
                }
            }

            // Handle CV upload
            if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
                $uploaded_resume = uploadFile($_FILES['resume_file'], ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
                if ($uploaded_resume) {
                    $resume_url = $uploaded_resume;
                }
            }

            $stmtUpdate = $db->prepare("UPDATE about_settings SET 
                hero_name = :hero_name, hero_desc = :hero_desc, linkedin_url = :linkedin_url, github_url = :github_url,
                contact_email = :contact_email, contact_phone = :contact_phone, contact_location = :contact_location,
                status_text = :status_text, stack_text = :stack_text, experience_years = :experience_years, passion_text = :passion_text,
                story_title = :story_title, story_desc = :story_desc, education_title = :education_title, education_desc = :education_desc,
                backend_title = :backend_title, backend_desc = :backend_desc, philosophy_title = :philosophy_title, philosophy_desc = :philosophy_desc,
                firebase_title = :firebase_title, firebase_desc = :firebase_desc, photo_url = :photo_url, resume_url = :resume_url
                WHERE id = 1");

            $stmtUpdate->execute([
                'hero_name' => $hero_name, 'hero_desc' => $hero_desc, 'linkedin_url' => $linkedin_url, 'github_url' => $github_url,
                'contact_email' => $contact_email, 'contact_phone' => $contact_phone, 'contact_location' => $contact_location,
                'status_text' => $status_text, 'stack_text' => $stack_text, 'experience_years' => $experience_years, 'passion_text' => $passion_text,
                'story_title' => $story_title, 'story_desc' => $story_desc, 'education_title' => $education_title, 'education_desc' => $education_desc,
                'backend_title' => $backend_title, 'backend_desc' => $backend_desc, 'philosophy_title' => $philosophy_title, 'philosophy_desc' => $philosophy_desc,
                'firebase_title' => $firebase_title, 'firebase_desc' => $firebase_desc, 'photo_url' => $photo_url, 'resume_url' => $resume_url
            ]);

            header("Location: dashboard?tab=about&status=updated");
            exit;
        } catch (PDOException $e) {
            $message = 'Error updating About settings: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }

    // 2. ADD PROJECT
    if ($action === 'add_project') {
        $project_key = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['project_key']);
        $name = trim($_POST['name']);
        $status = trim($_POST['status']);
        $emoji = trim($_POST['emoji']);
        $role = trim($_POST['role']);
        $company = trim($_POST['company']);
        $period = trim($_POST['period']);
        $description = trim($_POST['description']);
        $tags = trim($_POST['tags']);
        $playstore = trim($_POST['playstore']);

        if (empty($project_key) || empty($name)) {
            $message = 'Project key and name are required.';
            $msg_type = 'error';
        } else {
            try {
                $icon_url = '';
                if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
                    $icon_url = uploadFile($_FILES['icon_file'], ['jpg', 'jpeg', 'png', 'webp']);
                }

                // Handle screenshots
                $screenshots = [];
                if (isset($_FILES['screenshots_files']) && !empty($_FILES['screenshots_files']['name'][0])) {
                    $filesCount = count($_FILES['screenshots_files']['name']);
                    for ($i = 0; $i < $filesCount; $i++) {
                        $file = [
                            'name' => $_FILES['screenshots_files']['name'][$i],
                            'type' => $_FILES['screenshots_files']['type'][$i],
                            'tmp_name' => $_FILES['screenshots_files']['tmp_name'][$i],
                            'error' => $_FILES['screenshots_files']['error'][$i],
                            'size' => $_FILES['screenshots_files']['size'][$i]
                        ];
                        $uploaded_url = uploadFile($file, ['jpg', 'jpeg', 'png', 'webp']);
                        if ($uploaded_url) {
                            $screenshots[] = $uploaded_url;
                        }
                    }
                }

                $stmtInsert = $db->prepare("INSERT INTO projects 
                    (project_key, name, status, emoji, icon, screenshots, role, company, period, description, tags, playstore)
                    VALUES (:project_key, :name, :status, :emoji, :icon, :screenshots, :role, :company, :period, :description, :tags, :playstore)");
                
                $stmtInsert->execute([
                    'project_key' => $project_key,
                    'name' => $name,
                    'status' => $status,
                    'emoji' => $emoji ?: '🎮',
                    'icon' => $icon_url ?: 'public/playOn/logo.jpeg',
                    'screenshots' => json_encode($screenshots),
                    'role' => $role,
                    'company' => $company,
                    'period' => $period,
                    'description' => $description,
                    'tags' => $tags,
                    'playstore' => !empty($playstore) ? $playstore : null
                ]);

                header("Location: dashboard?tab=projects&status=added");
                exit;
            } catch (PDOException $e) {
                $message = 'Error adding project: ' . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }

    // 3. EDIT PROJECT
    if ($action === 'edit_project') {
        $id = intval($_POST['id']);
        $project_key = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['project_key']);
        $name = trim($_POST['name']);
        $status = trim($_POST['status']);
        $emoji = trim($_POST['emoji']);
        $role = trim($_POST['role']);
        $company = trim($_POST['company']);
        $period = trim($_POST['period']);
        $description = trim($_POST['description']);
        $tags = trim($_POST['tags']);
        $playstore = trim($_POST['playstore']);

        try {
            // Get current project values
            $stmt = $db->prepare("SELECT icon, screenshots FROM projects WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetch();

            $icon_url = $current['icon'];
            $screenshots = json_decode($current['screenshots'], true) ?: [];

            // Edit icon
            if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
                $new_icon = uploadFile($_FILES['icon_file'], ['jpg', 'jpeg', 'png', 'webp']);
                if ($new_icon) {
                    $icon_url = $new_icon;
                }
            }

            // Edit screenshots (Append new ones, or replace if selected)
            if (isset($_FILES['screenshots_files']) && !empty($_FILES['screenshots_files']['name'][0])) {
                $filesCount = count($_FILES['screenshots_files']['name']);
                $new_screens = [];
                for ($i = 0; $i < $filesCount; $i++) {
                    $file = [
                        'name' => $_FILES['screenshots_files']['name'][$i],
                        'type' => $_FILES['screenshots_files']['type'][$i],
                        'tmp_name' => $_FILES['screenshots_files']['tmp_name'][$i],
                        'error' => $_FILES['screenshots_files']['error'][$i],
                        'size' => $_FILES['screenshots_files']['size'][$i]
                    ];
                    $uploaded_url = uploadFile($file, ['jpg', 'jpeg', 'png', 'webp']);
                    if ($uploaded_url) {
                        $new_screens[] = $uploaded_url;
                    }
                }
                
                // Overwrite or append logic
                if (isset($_POST['replace_screenshots']) && $_POST['replace_screenshots'] == '1') {
                    $screenshots = $new_screens;
                } else {
                    $screenshots = array_merge($screenshots, $new_screens);
                }
            }

            $stmtUpdate = $db->prepare("UPDATE projects SET 
                project_key = :project_key, name = :name, status = :status, emoji = :emoji, icon = :icon, 
                screenshots = :screenshots, role = :role, company = :company, period = :period, 
                description = :description, tags = :tags, playstore = :playstore 
                WHERE id = :id");

            $stmtUpdate->execute([
                'project_key' => $project_key,
                'name' => $name,
                'status' => $status,
                'emoji' => $emoji ?: '🎮',
                'icon' => $icon_url,
                'screenshots' => json_encode($screenshots),
                'role' => $role,
                'company' => $company,
                'period' => $period,
                'description' => $description,
                'tags' => $tags,
                'playstore' => !empty($playstore) ? $playstore : null,
                'id' => $id
            ]);

            header("Location: dashboard?tab=projects&status=updated");
            exit;
        } catch (PDOException $e) {
            $message = 'Error updating project: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }

    // 4. ADD EXPERIENCE
    if ($action === 'add_experience') {
        $role = trim($_POST['role']);
        $company = trim($_POST['company']);
        $period = trim($_POST['period']);
        $type = trim($_POST['type']);
        $description = trim($_POST['description']);
        $is_current = isset($_POST['is_current']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order']);

        if (empty($role) || empty($company) || empty($period)) {
            $message = 'Role, company, and period are required.';
            $msg_type = 'error';
        } else {
            try {
                // If this is set to current, we might want to unset previous current role
                if ($is_current) {
                    $db->exec("UPDATE experience SET is_current = 0");
                }

                $stmtInsert = $db->prepare("INSERT INTO experience 
                    (role, company, period, type, description, is_current, sort_order)
                    VALUES (:role, :company, :period, :type, :description, :is_current, :sort_order)");

                $stmtInsert->execute([
                    'role' => $role,
                    'company' => $company,
                    'period' => $period,
                    'type' => $type,
                    'description' => $description,
                    'is_current' => $is_current,
                    'sort_order' => $sort_order
                ]);

                header("Location: dashboard?tab=experience&status=added");
                exit;
            } catch (PDOException $e) {
                $message = 'Error adding experience: ' . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }

    // 5. EDIT EXPERIENCE
    if ($action === 'edit_experience') {
        $id = intval($_POST['id']);
        $role = trim($_POST['role']);
        $company = trim($_POST['company']);
        $period = trim($_POST['period']);
        $type = trim($_POST['type']);
        $description = trim($_POST['description']);
        $is_current = isset($_POST['is_current']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order']);

        try {
            if ($is_current) {
                $db->prepare("UPDATE experience SET is_current = 0 WHERE id != :id")->execute(['id' => $id]);
            }

            $stmtUpdate = $db->prepare("UPDATE experience SET 
                role = :role, company = :company, period = :period, type = :type, 
                description = :description, is_current = :is_current, sort_order = :sort_order 
                WHERE id = :id");

            $stmtUpdate->execute([
                'role' => $role,
                'company' => $company,
                'period' => $period,
                'type' => $type,
                'description' => $description,
                'is_current' => $is_current,
                'sort_order' => $sort_order,
                'id' => $id
            ]);

            header("Location: dashboard?tab=experience&status=updated");
            exit;
        } catch (PDOException $e) {
            $message = 'Error updating experience: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }

    // 6. UPDATE SMTP SETTINGS
    if ($action === 'update_smtp') {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = intval($_POST['smtp_port']);
        $smtp_user = trim($_POST['smtp_user']);
        $smtp_pass = trim($_POST['smtp_pass']);
        $smtp_secure = trim($_POST['smtp_secure']);
        $sender_email = trim($_POST['sender_email']);
        $sender_name = trim($_POST['sender_name']);
        $recipient_email = trim($_POST['recipient_email']);

        try {
            $stmtUpdate = $db->prepare("UPDATE smtp_settings SET 
                smtp_host = :smtp_host, smtp_port = :smtp_port, smtp_user = :smtp_user, smtp_pass = :smtp_pass,
                smtp_secure = :smtp_secure, sender_email = :sender_email, sender_name = :sender_name, recipient_email = :recipient_email
                WHERE id = 1");

            $stmtUpdate->execute([
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_user' => $smtp_user,
                'smtp_pass' => $smtp_pass,
                'smtp_secure' => $smtp_secure,
                'sender_email' => $sender_email,
                'sender_name' => $sender_name,
                'recipient_email' => $recipient_email
            ]);

            header("Location: dashboard?tab=settings&status=smtp_updated");
            exit;
        } catch (PDOException $e) {
            $message = 'Error updating SMTP settings: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }

    // 7. CHANGE ADMIN PASSWORD
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $otp_code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password) || empty($otp_code)) {
            $message = 'All fields including the OTP are required.';
            $msg_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match.';
            $msg_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'New password must be at least 6 characters long.';
            $msg_type = 'error';
        } else {
            try {
                $username = $_SESSION['admin_username'];
                $stmt = $db->prepare("SELECT * FROM `admin_users` WHERE `username` = :username");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                if (!$user) {
                    $message = 'User not found.';
                    $msg_type = 'error';
                } elseif ($user['otp_code'] !== $otp_code) {
                    $message = 'Invalid OTP code. Please try again.';
                    $msg_type = 'error';
                } elseif (strtotime($user['otp_expiry']) < time()) {
                    $message = 'OTP code has expired. Please request a new one.';
                    $msg_type = 'error';
                } elseif (password_verify($current_password, $user['password'])) {
                    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmtUpdate = $db->prepare("UPDATE `admin_users` SET `password` = :pass, `otp_code` = NULL, `otp_expiry` = NULL WHERE `id` = :id");
                    $stmtUpdate->execute([
                        'pass' => $hashedPassword,
                        'id' => $user['id']
                    ]);
                    header("Location: dashboard?tab=settings&status=pass_updated");
                    exit;
                } else {
                    $message = 'Incorrect current password.';
                    $msg_type = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Error changing password: ' . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }

    // 8. CHANGE ADMIN EMAIL
    if ($action === 'change_email') {
        $current_password = $_POST['current_password'];
        $new_email = trim($_POST['new_email']);
        $otp_code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';

        if (empty($current_password) || empty($new_email) || empty($otp_code)) {
            $message = 'All fields including the OTP are required.';
            $msg_type = 'error';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid new email format.';
            $msg_type = 'error';
        } else {
            try {
                $username = $_SESSION['admin_username'];
                $stmt = $db->prepare("SELECT * FROM `admin_users` WHERE `username` = :username");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                if (!$user) {
                    $message = 'User not found.';
                    $msg_type = 'error';
                } elseif ($user['otp_code'] !== $otp_code) {
                    $message = 'Invalid OTP code. Please try again.';
                    $msg_type = 'error';
                } elseif (strtotime($user['otp_expiry']) < time()) {
                    $message = 'OTP code has expired. Please request a new one.';
                    $msg_type = 'error';
                } elseif (password_verify($current_password, $user['password'])) {
                    $stmtUpdate = $db->prepare("UPDATE `admin_users` SET `email` = :email, `otp_code` = NULL, `otp_expiry` = NULL WHERE `id` = :id");
                    $stmtUpdate->execute([
                        'email' => $new_email,
                        'id' => $user['id']
                    ]);
                    header("Location: dashboard?tab=settings&status=email_updated");
                    exit;
                } else {
                    $message = 'Incorrect current password.';
                    $msg_type = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Error changing email: ' . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }

    // 9. TEST SMTP CONNECTION
    if ($action === 'test_smtp') {
        $test_email = trim($_POST['test_email']);
        if (empty($test_email)) {
            $message = 'Test email address is required.';
            $msg_type = 'error';
        } else {
            try {
                require_once __DIR__ . '/../mail_helper.php';
                $smtpQuery = $db->query("SELECT * FROM `smtp_settings` WHERE `id` = 1");
                $smtp_settings = $smtpQuery->fetch();
                
                if ($smtp_settings) {
                    $subject = "SMTP Test Connection | Amit.Dev Admin";
                    $test_message = "<h3>SMTP Test Connection Successful</h3><p>If you received this message, your Gmail App Password and SMTP configurations are working perfectly!</p>";
                    send_smtp_email($test_email, $subject, $test_message, $smtp_settings);
                    $message = 'SMTP Test email sent successfully to ' . htmlspecialchars($test_email) . '!';
                    $msg_type = 'success';
                } else {
                    $message = 'SMTP configuration is missing in database.';
                    $msg_type = 'error';
                }
            } catch (Exception $e) {
                $message = 'SMTP connection test failed: ' . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }
}

// Handle GET Actions (like delete, edit display loading)
$action_get = isset($_GET['action']) ? $_GET['action'] : '';
if ($action_get === 'delete_project') {
    $id = intval($_GET['id']);
    try {
        $stmt = $db->prepare("DELETE FROM projects WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: dashboard?tab=projects&status=deleted");
        exit;
    } catch (PDOException $e) {
        $message = 'Error deleting project: ' . $e->getMessage();
        $msg_type = 'error';
    }
}

if ($action_get === 'delete_experience') {
    $id = intval($_GET['id']);
    try {
        $stmt = $db->prepare("DELETE FROM experience WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: dashboard?tab=experience&status=deleted");
        exit;
    } catch (PDOException $e) {
        $message = 'Error deleting experience: ' . $e->getMessage();
        $msg_type = 'error';
    }
}

if ($action_get === 'delete_inquiry') {
    $id = intval($_GET['id']);
    try {
        $stmt = $db->prepare("DELETE FROM inquiries WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: dashboard?tab=overview&status=deleted_msg");
        exit;
    } catch (PDOException $e) {
        $message = 'Error deleting inquiry: ' . $e->getMessage();
        $msg_type = 'error';
    }
}

// Set notifications from redirect status
$status = isset($_GET['status']) ? $_GET['status'] : '';
if ($status === 'updated') {
    $message = 'Changes saved successfully!';
    $msg_type = 'success';
} elseif ($status === 'added') {
    $message = 'Item added successfully!';
    $msg_type = 'success';
} elseif ($status === 'deleted') {
    $message = 'Item deleted successfully!';
    $msg_type = 'success';
} elseif ($status === 'deleted_msg') {
    $message = 'Inquiry deleted successfully!';
    $msg_type = 'success';
} elseif ($status === 'smtp_updated') {
    $message = 'SMTP Settings updated successfully!';
    $msg_type = 'success';
} elseif ($status === 'pass_updated') {
    $message = 'Password changed successfully!';
    $msg_type = 'success';
} elseif ($status === 'email_updated') {
    $message = 'Email updated successfully!';
    $msg_type = 'success';
}

// Load current Tab
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Load Database Queries for Render
$about = $db->query("SELECT * FROM about_settings WHERE id = 1")->fetch();
$projects = $db->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll();
$experiences = $db->query("SELECT * FROM experience ORDER BY sort_order ASC, id DESC")->fetchAll();
$inquiries = $db->query("SELECT * FROM inquiries ORDER BY id DESC")->fetchAll();
$smtp_settings = $db->query("SELECT * FROM smtp_settings WHERE id = 1")->fetch();

$admin_user = $db->query("SELECT email FROM admin_users WHERE username = " . $db->quote($_SESSION['admin_username']))->fetch();
$current_admin_email = $admin_user ? $admin_user['email'] : '';

// Count stats
$inqCount = count($inquiries);
$projCount = count($projects);
$expCount = count($experiences);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Dashboard | Amit.Dev</title>
  
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=DM+Sans:wght@400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet"/>
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  
  <!-- Custom Admin Dashboard CSS -->
  <link rel="stylesheet" href="style.css"/>
</head>
<body class="admin-body">

  <div class="admin-layout">
    
    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
      <div class="sidebar-header">
        <div class="nav-logo-box">AK</div>
        <div class="sidebar-logo-text">Amit.Dev <span>Admin</span></div>
      </div>
      
      <div class="admin-profile">
        <div class="ap-avatar">
          <img src="../<?php echo htmlspecialchars($about['photo_url']); ?>" alt="Admin Profile" onerror="this.src='../amit_photo.jpeg'"/>
        </div>
        <div class="ap-name"><?php echo htmlspecialchars($about['hero_name']); ?></div>
        <div class="ap-role">Administrator</div>
      </div>

      <nav class="sidebar-menu">
        <a href="?tab=overview" class="menu-item <?php echo $current_tab === 'overview' ? 'active' : ''; ?>">
          <i class="fa-solid fa-gauge"></i> Overview &amp; Inquiries
          <?php if ($inqCount > 0): ?>
            <span class="badge"><?php echo $inqCount; ?></span>
          <?php endif; ?>
        </a>
        <a href="?tab=about" class="menu-item <?php echo $current_tab === 'about' ? 'active' : ''; ?>">
          <i class="fa-solid fa-user-gear"></i> Edit About Me
        </a>
        <a href="?tab=projects" class="menu-item <?php echo $current_tab === 'projects' ? 'active' : ''; ?>">
          <i class="fa-solid fa-layer-group"></i> Manage Projects
        </a>
        <a href="?tab=experience" class="menu-item <?php echo $current_tab === 'experience' ? 'active' : ''; ?>">
          <i class="fa-solid fa-briefcase"></i> Work Experience
        </a>
        <a href="?tab=settings" class="menu-item <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
          <i class="fa-solid fa-gears"></i> System Settings
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="../" target="_blank" class="menu-item client-site">
          <i class="fa-solid fa-globe"></i> View Website
        </a>
        <a href="logout" class="menu-item logout">
          <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
      </nav>
    </aside>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="admin-main">
      
      <!-- TOP HEADER -->
      <header class="admin-header">
        <div class="ah-title-wrap">
          <h1 class="ah-title">
            <?php 
              if ($current_tab === 'about') echo 'Edit About Me';
              elseif ($current_tab === 'projects') echo 'Manage Projects';
              elseif ($current_tab === 'experience') echo 'Work Experience';
              elseif ($current_tab === 'settings') echo 'System Settings';
              else echo 'Dashboard Overview';
            ?>
          </h1>
          <p class="ah-subtitle">Manage your portfolio details and review messages.</p>
        </div>
        
        <div class="ah-actions">
          <span class="status-indicator">
            <span class="pulse-dot"></span> System Live
          </span>
        </div>
      </header>

      <!-- NOTIFICATIONS -->
      <?php if (!empty($message)): ?>
        <div class="admin-alert <?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>" id="adminAlert">
          <div class="alert-icon">
            <?php if ($msg_type === 'success'): ?>
              <i class="fa-solid fa-circle-check"></i>
            <?php else: ?>
              <i class="fa-solid fa-circle-exclamation"></i>
            <?php endif; ?>
          </div>
          <div class="alert-content">
            <?php echo htmlspecialchars($message); ?>
          </div>
          <button class="alert-close" onclick="document.getElementById('adminAlert').style.display='none'">&times;</button>
        </div>
        <script>
          setTimeout(function() {
            var alert = document.getElementById('adminAlert');
            if (alert) alert.style.opacity = '0';
            setTimeout(function() { if (alert) alert.style.display = 'none'; }, 600);
          }, 4000);
        </script>
      <?php endif; ?>

      <!-- TAB SECTIONS -->
      <div class="admin-tab-content">
        
        <!-- SECTION 1: OVERVIEW & INQUIRIES -->
        <?php if ($current_tab === 'overview'): ?>
          
          <!-- Stat Grid -->
          <div class="admin-stats-grid">
            <div class="stat-box">
              <div class="sb-icon bg-cyan"><i class="fa-solid fa-envelope"></i></div>
              <div class="sb-details">
                <div class="sb-value"><?php echo $inqCount; ?></div>
                <div class="sb-label">Inquiries Received</div>
              </div>
            </div>
            <div class="stat-box">
              <div class="sb-icon bg-purple"><i class="fa-solid fa-layer-group"></i></div>
              <div class="sb-details">
                <div class="sb-value"><?php echo $projCount; ?></div>
                <div class="sb-label">Total Projects</div>
              </div>
            </div>
            <div class="stat-box">
              <div class="sb-icon bg-orange"><i class="fa-solid fa-briefcase"></i></div>
              <div class="sb-details">
                <div class="sb-value"><?php echo $expCount; ?></div>
                <div class="sb-label">Work Milestones</div>
              </div>
            </div>
          </div>

          <!-- Inquiries Table -->
          <div class="admin-card">
            <div class="card-header">
              <h2 class="card-title"><i class="fa-solid fa-inbox"></i> Client Inquiries Inbox</h2>
              <p class="card-subtitle">Messages sent by visitors through your contact form.</p>
            </div>
            <div class="card-body">
              <?php if (empty($inquiries)): ?>
                <div class="empty-state">
                  <div class="es-icon"><i class="fa-solid fa-envelope-open"></i></div>
                  <h3>No inquiries yet</h3>
                  <p>When users send messages, they will show up here.</p>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="admin-table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th class="actions-col">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($inquiries as $inq): ?>
                        <tr>
                          <td class="date-cell"><?php echo date('M d, Y H:i', strtotime($inq['created_at'])); ?></td>
                          <td class="name-cell"><strong><?php echo htmlspecialchars($inq['name']); ?></strong></td>
                          <td><a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" class="table-email-link"><?php echo htmlspecialchars($inq['email']); ?></a></td>
                          <td class="msg-cell">
                            <div class="message-preview"><?php echo nl2br(htmlspecialchars($inq['message'])); ?></div>
                          </td>
                          <td class="actions-cell">
                            <a href="?tab=overview&action=delete_inquiry&id=<?php echo $inq['id']; ?>" class="action-btn btn-danger" onclick="return confirm('Are you sure you want to delete this message?');" title="Delete Inquiry">
                              <i class="fa-solid fa-trash"></i> Delete
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

        <!-- SECTION 2: EDIT ABOUT ME -->
        <?php elseif ($current_tab === 'about'): ?>
          
          <form method="POST" action="dashboard" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_about"/>
            <input type="hidden" name="current_tab" value="about"/>
            
            <div class="form-grid-layout">
              
              <!-- Profile Card Edit -->
              <div class="admin-card">
                <div class="card-header">
                  <h2 class="card-title"><i class="fa-solid fa-address-card"></i> Hero Card &amp; Basic Info</h2>
                </div>
                <div class="card-body">
                  
                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Full Name</label>
                      <input type="text" name="hero_name" value="<?php echo htmlspecialchars($about['hero_name']); ?>" required class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Primary Tech Stack</label>
                      <input type="text" name="stack_text" value="<?php echo htmlspecialchars($about['stack_text']); ?>" placeholder="e.g. Flutter / Dart" required class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Job Status</label>
                      <select name="status_text" class="admin-select">
                        <option value="Available" <?php echo $about['status_text'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Available for hire" <?php echo $about['status_text'] === 'Available for hire' ? 'selected' : ''; ?>>Available for hire</option>
                        <option value="Busy" <?php echo $about['status_text'] === 'Busy' ? 'selected' : ''; ?>>Busy</option>
                        <option value="Freelancing" <?php echo $about['status_text'] === 'Freelancing' ? 'selected' : ''; ?>>Freelancing</option>
                      </select>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Years of Experience</label>
                      <input type="text" name="experience_years" value="<?php echo htmlspecialchars($about['experience_years']); ?>" placeholder="e.g. 2+" required class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Hero Description (Intro Subtitle)</label>
                    <textarea name="hero_desc" rows="3" required class="admin-textarea"><?php echo htmlspecialchars($about['hero_desc']); ?></textarea>
                  </div>

                  <div class="form-row-3">
                    <div class="form-control">
                      <label class="form-lbl">Location</label>
                      <input type="text" name="contact_location" value="<?php echo htmlspecialchars($about['contact_location']); ?>" required class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Email Address</label>
                      <input type="email" name="contact_email" value="<?php echo htmlspecialchars($about['contact_email']); ?>" required class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Phone Number</label>
                      <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($about['contact_phone']); ?>" required class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">LinkedIn URL</label>
                      <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($about['linkedin_url']); ?>" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">GitHub Profile URL</label>
                      <input type="url" name="github_url" value="<?php echo htmlspecialchars($about['github_url']); ?>" class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Passion Text</label>
                      <input type="text" name="passion_text" value="<?php echo htmlspecialchars($about['passion_text']); ?>" placeholder="e.g. Mobile UX & Clean Arch" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Current Profile Image</label>
                      <div class="file-uploader-wrap">
                        <img src="../<?php echo htmlspecialchars($about['photo_url']); ?>" class="mini-preview" alt="Avatar"/>
                        <input type="file" name="photo_file" class="file-input"/>
                      </div>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Resume / CV Document (PDF / Doc)</label>
                    <div class="file-uploader-wrap">
                      <span class="file-path-info">Current: <a href="../<?php echo htmlspecialchars($about['resume_url']); ?>" target="_blank"><?php echo basename($about['resume_url']); ?></a></span>
                      <input type="file" name="resume_file" class="file-input"/>
                    </div>
                  </div>

                </div>
              </div>

              <!-- About Me Info Cards -->
              <div class="admin-card">
                <div class="card-header">
                  <h2 class="card-title"><i class="fa-solid fa-id-card"></i> About Section Story Blocks</h2>
                </div>
                <div class="card-body">
                  
                  <!-- Story -->
                  <div class="story-block-edit">
                    <h3 class="sb-edit-title">1. My Story Block</h3>
                    <div class="form-control">
                      <label class="form-lbl">Title</label>
                      <input type="text" name="story_title" value="<?php echo htmlspecialchars($about['story_title']); ?>" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Description</label>
                      <textarea name="story_desc" rows="4" class="admin-textarea"><?php echo htmlspecialchars($about['story_desc']); ?></textarea>
                    </div>
                  </div>

                  <!-- Education -->
                  <div class="story-block-edit">
                    <h3 class="sb-edit-title">2. Education Block</h3>
                    <div class="form-control">
                      <label class="form-lbl">Title</label>
                      <input type="text" name="education_title" value="<?php echo htmlspecialchars($about['education_title']); ?>" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Description</label>
                      <textarea name="education_desc" rows="3" class="admin-textarea"><?php echo htmlspecialchars($about['education_desc']); ?></textarea>
                    </div>
                  </div>

                  <!-- Backend -->
                  <div class="story-block-edit">
                    <h3 class="sb-edit-title">3. Backend &amp; APIs Block</h3>
                    <div class="form-control">
                      <label class="form-lbl">Title</label>
                      <input type="text" name="backend_title" value="<?php echo htmlspecialchars($about['backend_title']); ?>" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Description</label>
                      <textarea name="backend_desc" rows="3" class="admin-textarea"><?php echo htmlspecialchars($about['backend_desc']); ?></textarea>
                    </div>
                  </div>

                  <!-- Philosophy -->
                  <div class="story-block-edit">
                    <h3 class="sb-edit-title">4. Philosophy Block</h3>
                    <div class="form-control">
                      <label class="form-lbl">Title</label>
                      <input type="text" name="philosophy_title" value="<?php echo htmlspecialchars($about['philosophy_title']); ?>" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Description</label>
                      <textarea name="philosophy_desc" rows="3" class="admin-textarea"><?php echo htmlspecialchars($about['philosophy_desc']); ?></textarea>
                    </div>
                  </div>

                  <!-- Firebase -->
                  <div class="story-block-edit" style="border-bottom: none; padding-bottom: 0;">
                    <h3 class="sb-edit-title">5. Firebase / Cloud Block</h3>
                    <div class="form-control">
                      <label class="form-lbl">Title</label>
                      <input type="text" name="firebase_title" value="<?php echo htmlspecialchars($about['firebase_title']); ?>" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Description</label>
                      <textarea name="firebase_desc" rows="3" class="admin-textarea"><?php echo htmlspecialchars($about['firebase_desc']); ?></textarea>
                    </div>
                  </div>

                </div>
              </div>

            </div>
            
            <div class="sticky-footer-actions">
              <button type="submit" class="action-btn btn-primary btn-large"><i class="fa-solid fa-save"></i> Save About Changes</button>
            </div>
          </form>

        <!-- SECTION 3: MANAGE PROJECTS -->
        <?php elseif ($current_tab === 'projects'): ?>
          
          <?php
          // Handle Loading Project for Edit View
          $edit_proj = null;
          if (isset($_GET['edit_id'])) {
              $edit_id = intval($_GET['edit_id']);
              foreach ($projects as $p) {
                  if ($p['id'] === $edit_id) {
                      $edit_proj = $p;
                      break;
                  }
              }
          }
          ?>

          <?php if ($edit_proj): ?>
            <!-- Project Edit Form -->
            <div class="admin-card">
              <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-pen-to-square"></i> Edit Project: <?php echo htmlspecialchars($edit_proj['name']); ?></h2>
                <a href="?tab=projects" class="action-btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
              </div>
              <div class="card-body">
                <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="edit_project"/>
                  <input type="hidden" name="id" value="<?php echo $edit_proj['id']; ?>"/>
                  <input type="hidden" name="current_tab" value="projects"/>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Project Name</label>
                      <input type="text" name="name" value="<?php echo htmlspecialchars($edit_proj['name']); ?>" required class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Project Key / Unique ID (Only alphanumeric)</label>
                      <input type="text" name="project_key" value="<?php echo htmlspecialchars($edit_proj['project_key']); ?>" required class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-row-3">
                    <div class="form-control">
                      <label class="form-lbl">Status</label>
                      <select name="status" class="admin-select">
                        <option value="dev" <?php echo $edit_proj['status'] === 'dev' ? 'selected' : ''; ?>>In Development</option>
                        <option value="published" <?php echo $edit_proj['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                      </select>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Project Emoji Icon</label>
                      <input type="text" name="emoji" value="<?php echo htmlspecialchars($edit_proj['emoji']); ?>" placeholder="🎮" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Upload Icon Image</label>
                      <div class="file-uploader-wrap">
                        <img src="../<?php echo htmlspecialchars($edit_proj['icon']); ?>" class="mini-preview" alt="Icon" onerror="this.style.display='none'"/>
                        <input type="file" name="icon_file" class="file-input"/>
                      </div>
                    </div>
                  </div>

                  <div class="form-row-3">
                    <div class="form-control">
                      <label class="form-lbl">Role on Project</label>
                      <input type="text" name="role" value="<?php echo htmlspecialchars($edit_proj['role']); ?>" placeholder="e.g. Senior Flutter Developer" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Company</label>
                      <input type="text" name="company" value="<?php echo htmlspecialchars($edit_proj['company']); ?>" placeholder="e.g. Kriti Digital Solutions" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Development Period</label>
                      <input type="text" name="period" value="<?php echo htmlspecialchars($edit_proj['period']); ?>" placeholder="e.g. April 2026 – Present" class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Description</label>
                    <textarea name="description" rows="4" required class="admin-textarea"><?php echo htmlspecialchars($edit_proj['description']); ?></textarea>
                  </div>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Tags (comma-separated)</label>
                      <input type="text" name="tags" value="<?php echo htmlspecialchars($edit_proj['tags']); ?>" placeholder="Flutter, GetX, API, OTT" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Play Store Link (URL)</label>
                      <input type="url" name="playstore" value="<?php echo htmlspecialchars($edit_proj['playstore']); ?>" placeholder="https://play.google.com/..." class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Screenshots (Select multiple images)</label>
                    <div style="margin-bottom: 10px;">
                      <label class="admin-checkbox-label">
                        <input type="checkbox" name="replace_screenshots" value="1"/> Replace existing screenshots (unchecking will append instead)
                      </label>
                    </div>
                    <input type="file" name="screenshots_files[]" multiple class="admin-input" style="padding: 8px;"/>
                    
                    <?php 
                    $screens = json_decode($edit_proj['screenshots'], true) ?: [];
                    if (!empty($screens)):
                    ?>
                      <div class="project-screens-preview-row">
                        <span class="fs-lbl">Current Screens (<?php echo count($screens); ?>):</span>
                        <div class="screens-row-flex">
                          <?php foreach ($screens as $sc): ?>
                            <div class="screen-preview-card">
                              <img src="../<?php echo htmlspecialchars($sc); ?>" alt="screenshot"/>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="form-actions" style="margin-top: 24px;">
                    <button type="submit" class="action-btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                    <a href="?tab=projects" class="action-btn btn-secondary">Cancel</a>
                  </div>
                </form>
              </div>
            </div>
            
          <?php else: ?>
            
            <!-- Add Project Form (Collapsible) -->
            <div class="admin-card" style="margin-bottom: 30px;">
              <div class="card-header" onclick="document.getElementById('addProjectCollapse').classList.toggle('collapsed');" style="cursor: pointer;">
                <h2 class="card-title"><i class="fa-solid fa-circle-plus"></i> Add New Project</h2>
                <span class="collapse-icon"><i class="fa-solid fa-chevron-down"></i></span>
              </div>
              <div class="card-body collapsed" id="addProjectCollapse">
                <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="add_project"/>
                  <input type="hidden" name="current_tab" value="projects"/>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Project Name *</label>
                      <input type="text" name="name" required placeholder="e.g. PLAYON" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Project Key / Unique ID * (alphanumeric, e.g. playOn)</label>
                      <input type="text" name="project_key" required placeholder="e.g. playOn" class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-row-3">
                    <div class="form-control">
                      <label class="form-lbl">Status</label>
                      <select name="status" class="admin-select">
                        <option value="dev">In Development</option>
                        <option value="published">Published</option>
                      </select>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Project Emoji Icon</label>
                      <input type="text" name="emoji" placeholder="🎮" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Upload Icon Logo Image</label>
                      <input type="file" name="icon_file" class="admin-input" style="padding: 8px;"/>
                    </div>
                  </div>

                  <div class="form-row-3">
                    <div class="form-control">
                      <label class="form-lbl">Role on Project</label>
                      <input type="text" name="role" placeholder="e.g. Senior Flutter Developer" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Company</label>
                      <input type="text" name="company" placeholder="e.g. Kriti Digital Solutions" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Development Period</label>
                      <input type="text" name="period" placeholder="e.g. April 2026 – Present" class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Description *</label>
                    <textarea name="description" rows="4" required placeholder="Write application details..." class="admin-textarea"></textarea>
                  </div>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Tags (comma-separated)</label>
                      <input type="text" name="tags" placeholder="Flutter, GetX, Dio, OTT" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Play Store Link (URL)</label>
                      <input type="url" name="playstore" placeholder="https://play.google.com/..." class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Screenshots (Select multiple images)</label>
                    <input type="file" name="screenshots_files[]" multiple class="admin-input" style="padding: 8px;"/>
                  </div>

                  <div class="form-actions" style="margin-top: 16px;">
                    <button type="submit" class="action-btn btn-primary"><i class="fa-solid fa-plus"></i> Add Project</button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Projects Table List -->
            <div class="admin-card">
              <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-list-check"></i> Project List</h2>
                <p class="card-subtitle">Manage all active portfolio project entries.</p>
              </div>
              <div class="card-body">
                <?php if (empty($projects)): ?>
                  <div class="empty-state">
                    <div class="es-icon"><i class="fa-solid fa-cubes"></i></div>
                    <h3>No projects found</h3>
                    <p>Click "Add New Project" above to create one.</p>
                  </div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="admin-table">
                      <thead>
                        <tr>
                          <th>App</th>
                          <th>Status</th>
                          <th>Role / Company</th>
                          <th>Period</th>
                          <th>Tags</th>
                          <th class="actions-col">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($projects as $proj): ?>
                          <tr>
                            <td>
                              <div class="table-app-info">
                                <div class="table-app-icon">
                                  <img src="../<?php echo htmlspecialchars($proj['icon']); ?>" alt="" onerror="this.parentElement.textContent='<?php echo htmlspecialchars($proj['emoji']); ?>';"/>
                                </div>
                                <div class="table-app-details">
                                  <strong><?php echo htmlspecialchars($proj['name']); ?></strong>
                                  <span class="app-key"><?php echo htmlspecialchars($proj['project_key']); ?></span>
                                </div>
                              </div>
                            </td>
                            <td>
                              <span class="status-badge <?php echo $proj['status'] === 'published' ? 'pub' : 'dev'; ?>">
                                <?php echo $proj['status'] === 'published' ? 'Published' : 'In Dev'; ?>
                              </span>
                            </td>
                            <td>
                              <div class="table-text-block">
                                <span class="role"><?php echo htmlspecialchars($proj['role']); ?></span>
                                <span class="company"><?php echo htmlspecialchars($proj['company']); ?></span>
                              </div>
                            </td>
                            <td><?php echo htmlspecialchars($proj['period']); ?></td>
                            <td>
                              <div class="table-tags">
                                <?php 
                                $tagsArr = explode(',', $proj['tags']);
                                foreach (array_slice($tagsArr, 0, 3) as $t): 
                                  if (!empty($t)):
                                ?>
                                  <span class="p-mini-tag"><?php echo htmlspecialchars(trim($t)); ?></span>
                                <?php 
                                  endif;
                                endforeach; 
                                if (count($tagsArr) > 3) echo '...';
                                ?>
                              </div>
                            </td>
                            <td class="actions-cell">
                              <a href="?tab=projects&edit_id=<?php echo $proj['id']; ?>" class="action-btn btn-warning" title="Edit Project">
                                <i class="fa-solid fa-edit"></i> Edit
                              </a>
                              <a href="?tab=projects&action=delete_project&id=<?php echo $proj['id']; ?>" class="action-btn btn-danger" onclick="return confirm('Are you sure you want to delete this project?');" title="Delete Project">
                                <i class="fa-solid fa-trash"></i> Delete
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            </div>

          <?php endif; ?>

        <!-- SECTION 4: WORK EXPERIENCE -->
        <?php elseif ($current_tab === 'experience'): ?>
          
          <?php
          // Handle Loading Experience for Edit View
          $edit_exp = null;
          if (isset($_GET['edit_id'])) {
              $edit_id = intval($_GET['edit_id']);
              foreach ($experiences as $e) {
                  if ($e['id'] === $edit_id) {
                      $edit_exp = $e;
                      break;
                  }
              }
          }
          ?>

          <?php if ($edit_exp): ?>
            <!-- Experience Edit Form -->
            <div class="admin-card">
              <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-pen-to-square"></i> Edit Work Experience</h2>
                <a href="?tab=experience" class="action-btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
              </div>
              <div class="card-body">
                <form method="POST" action="dashboard.php">
                  <input type="hidden" name="action" value="edit_experience"/>
                  <input type="hidden" name="id" value="<?php echo $edit_exp['id']; ?>"/>
                  <input type="hidden" name="current_tab" value="experience"/>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Job Role *</label>
                      <input type="text" name="role" value="<?php echo htmlspecialchars($edit_exp['role']); ?>" required class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Company *</label>
                      <input type="text" name="company" value="<?php echo htmlspecialchars($edit_exp['company']); ?>" required class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-row-3">
                    <div class="form-control">
                      <label class="form-lbl">Time Period *</label>
                      <input type="text" name="period" value="<?php echo htmlspecialchars($edit_exp['period']); ?>" placeholder="e.g. Jan 2026 – Present" required class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Job Type</label>
                      <select name="type" class="admin-select">
                        <option value="fulltime" <?php echo $edit_exp['type'] === 'fulltime' ? 'selected' : ''; ?>>Full-Time</option>
                        <option value="intern" <?php echo $edit_exp['type'] === 'intern' ? 'selected' : ''; ?>>Internship</option>
                      </select>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Sort Order (Lower appears first)</label>
                      <input type="number" name="sort_order" value="<?php echo intval($edit_exp['sort_order']); ?>" class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Description</label>
                    <textarea name="description" rows="4" class="admin-textarea"><?php echo htmlspecialchars($edit_exp['description']); ?></textarea>
                  </div>

                  <div class="form-control" style="margin-bottom: 20px;">
                    <label class="admin-checkbox-label">
                      <input type="checkbox" name="is_current" value="1" <?php echo $edit_exp['is_current'] ? 'checked' : ''; ?>/> Mark as current role (Only one can be marked current)
                    </label>
                  </div>

                  <div class="form-actions">
                    <button type="submit" class="action-btn btn-primary"><i class="fa-solid fa-save"></i> Save Experience</button>
                    <a href="?tab=experience" class="action-btn btn-secondary">Cancel</a>
                  </div>
                </form>
              </div>
            </div>
            
          <?php else: ?>
            
            <!-- Add Experience Form -->
            <div class="admin-card" style="margin-bottom: 30px;">
              <div class="card-header" onclick="document.getElementById('addExpCollapse').classList.toggle('collapsed');" style="cursor: pointer;">
                <h2 class="card-title"><i class="fa-solid fa-circle-plus"></i> Add New Experience</h2>
                <span class="collapse-icon"><i class="fa-solid fa-chevron-down"></i></span>
              </div>
              <div class="card-body collapsed" id="addExpCollapse">
                <form method="POST" action="dashboard.php">
                  <input type="hidden" name="action" value="add_experience"/>
                  <input type="hidden" name="current_tab" value="experience"/>

                  <div class="form-row-2">
                    <div class="form-control">
                      <label class="form-lbl">Job Role *</label>
                      <input type="text" name="role" required placeholder="e.g. Flutter Developer" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Company *</label>
                      <input type="text" name="company" required placeholder="e.g. Kriti Digital Solutions" class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-row-3">
                    <div class="form-control">
                      <label class="form-lbl">Time Period *</label>
                      <input type="text" name="period" required placeholder="e.g. Jan 2026 – Present" class="admin-input"/>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Job Type</label>
                      <select name="type" class="admin-select">
                        <option value="fulltime">Full-Time</option>
                        <option value="intern">Internship</option>
                      </select>
                    </div>
                    <div class="form-control">
                      <label class="form-lbl">Sort Order</label>
                      <input type="number" name="sort_order" value="<?php echo count($experiences) + 1; ?>" class="admin-input"/>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Description</label>
                    <textarea name="description" rows="4" placeholder="Write role description, milestones, responsibilities..." class="admin-textarea"></textarea>
                  </div>

                  <div class="form-control" style="margin-bottom: 20px;">
                    <label class="admin-checkbox-label">
                      <input type="checkbox" name="is_current" value="1"/> Mark as current role
                    </label>
                  </div>

                  <div class="form-actions">
                    <button type="submit" class="action-btn btn-primary"><i class="fa-solid fa-plus"></i> Add Experience</button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Experience List Table -->
            <div class="admin-card">
              <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-timeline"></i> Timeline History</h2>
                <p class="card-subtitle">Manage your employment history milestones.</p>
              </div>
              <div class="card-body">
                <?php if (empty($experiences)): ?>
                  <div class="empty-state">
                    <div class="es-icon"><i class="fa-solid fa-briefcase"></i></div>
                    <h3>No experience entries found</h3>
                    <p>Click "Add New Experience" above to create one.</p>
                  </div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="admin-table">
                      <thead>
                        <tr>
                          <th>Sort</th>
                          <th>Role</th>
                          <th>Company</th>
                          <th>Period</th>
                          <th>Type</th>
                          <th>Current</th>
                          <th class="actions-col">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($experiences as $exp): ?>
                          <tr>
                            <td class="sort-cell"><?php echo htmlspecialchars($exp['sort_order']); ?></td>
                            <td><strong><?php echo htmlspecialchars($exp['role']); ?></strong></td>
                            <td><?php echo htmlspecialchars($exp['company']); ?></td>
                            <td><?php echo htmlspecialchars($exp['period']); ?></td>
                            <td>
                              <span class="type-badge <?php echo $exp['type']; ?>">
                                <?php echo $exp['type'] === 'fulltime' ? 'Full-Time' : 'Internship'; ?>
                              </span>
                            </td>
                            <td>
                              <?php if ($exp['is_current']): ?>
                                <span class="current-indicator"><span class="dot"></span> Current</span>
                              <?php else: ?>
                                <span class="old-indicator">-</span>
                              <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                              <a href="?tab=experience&edit_id=<?php echo $exp['id']; ?>" class="action-btn btn-warning" title="Edit Experience">
                                <i class="fa-solid fa-edit"></i> Edit
                              </a>
                              <a href="?tab=experience&action=delete_experience&id=<?php echo $exp['id']; ?>" class="action-btn btn-danger" onclick="return confirm('Are you sure you want to delete this work experience?');" title="Delete Experience">
                                <i class="fa-solid fa-trash"></i> Delete
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            </div>

          <?php endif; ?>

        <!-- SECTION 5: SYSTEM SETTINGS -->
        <?php elseif ($current_tab === 'settings'): ?>
          <div class="form-grid-layout">
            
            <!-- Change Password Form -->
            <div class="admin-card">
              <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-key"></i> Change Admin Password</h2>
              </div>
              <div class="card-body">
                <form method="POST" action="dashboard">
                  <input type="hidden" name="action" value="change_password"/>
                  <input type="hidden" name="current_tab" value="settings"/>

                  <div class="form-control">
                    <label class="form-lbl">Current Password *</label>
                    <div class="input-wrapper" style="position: relative;">
                      <input type="password" id="current_password" name="current_password" required class="admin-input" style="padding-right: 40px;"/>
                      <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text2);" onclick="toggleDashboardPassVisibility('current_password', this)"></i>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">New Password *</label>
                    <div class="input-wrapper" style="position: relative;">
                      <input type="password" id="new_password" name="new_password" required class="admin-input" style="padding-right: 40px;"/>
                      <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text2);" onclick="toggleDashboardPassVisibility('new_password', this)"></i>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Confirm New Password *</label>
                    <div class="input-wrapper" style="position: relative;">
                      <input type="password" id="confirm_password" name="confirm_password" required class="admin-input" style="padding-right: 40px;"/>
                      <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text2);" onclick="toggleDashboardPassVisibility('confirm_password', this)"></i>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Security Verification OTP *</label>
                    <div class="otp-request-group" style="display: flex; gap: 10px; align-items: center;">
                      <input type="text" name="otp_code" required placeholder="6-digit OTP" maxlength="6" class="admin-input" style="letter-spacing: 2px; font-weight: bold; text-align: center; max-width: 140px;"/>
                      <button type="button" class="action-btn btn-warning request-otp-btn" onclick="requestSettingsOTP(this)">
                        <i class="fa-solid fa-paper-plane"></i> Send OTP
                      </button>
                    </div>
                  </div>

                  <div class="form-actions" style="margin-top: 24px;">
                    <button type="submit" class="action-btn btn-primary"><i class="fa-solid fa-lock"></i> Update Password</button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Change Email Form -->
            <div class="admin-card">
              <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-envelope"></i> Change Admin Email</h2>
              </div>
              <div class="card-body">
                <form method="POST" action="dashboard">
                  <input type="hidden" name="action" value="change_email"/>
                  <input type="hidden" name="current_tab" value="settings"/>

                  <div class="form-control">
                    <label class="form-lbl">Current Password *</label>
                    <div class="input-wrapper" style="position: relative;">
                      <input type="password" id="email_current_password" name="current_password" required class="admin-input" style="padding-right: 40px;"/>
                      <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text2);" onclick="toggleDashboardPassVisibility('email_current_password', this)"></i>
                    </div>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">New Email *</label>
                    <input type="email" name="new_email" value="<?php echo htmlspecialchars($current_admin_email); ?>" required class="admin-input"/>
                  </div>

                  <div class="form-control">
                    <label class="form-lbl">Security Verification OTP *</label>
                    <div class="otp-request-group" style="display: flex; gap: 10px; align-items: center;">
                      <input type="text" name="otp_code" required placeholder="6-digit OTP" maxlength="6" class="admin-input" style="letter-spacing: 2px; font-weight: bold; text-align: center; max-width: 140px;"/>
                      <button type="button" class="action-btn btn-warning request-otp-btn" onclick="requestSettingsOTP(this)">
                        <i class="fa-solid fa-paper-plane"></i> Send OTP
                      </button>
                    </div>
                  </div>

                  <div class="form-actions" style="margin-top: 24px;">
                    <button type="submit" class="action-btn btn-primary"><i class="fa-solid fa-save"></i> Update Email</button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- SMTP Configuration & Diagnostics Card -->
          <div class="admin-card" style="margin-top: 30px;">
            <div class="card-header" onclick="document.getElementById('smtpDiagnosticCollapse').classList.toggle('collapsed');" style="cursor: pointer;">
              <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> SMTP Configuration &amp; Diagnostics</h2>
              <span class="collapse-icon"><i class="fa-solid fa-chevron-down"></i></span>
            </div>
            <div class="card-body collapsed" id="smtpDiagnosticCollapse">
              <form method="POST" action="dashboard">
                <input type="hidden" name="action" value="update_smtp"/>
                <input type="hidden" name="current_tab" value="settings"/>
                
                <div class="form-row-3">
                  <div class="form-control">
                    <label class="form-lbl">SMTP Host *</label>
                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp_settings['smtp_host'] ?? ''); ?>" required class="admin-input"/>
                  </div>
                  <div class="form-control">
                    <label class="form-lbl">SMTP Port *</label>
                    <input type="number" name="smtp_port" value="<?php echo intval($smtp_settings['smtp_port'] ?? 587); ?>" required class="admin-input"/>
                  </div>
                  <div class="form-control">
                    <label class="form-lbl">Encryption</label>
                    <select name="smtp_secure" class="admin-select">
                      <option value="tls" <?php echo ($smtp_settings['smtp_secure'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS (usually port 587)</option>
                      <option value="ssl" <?php echo ($smtp_settings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (usually port 465)</option>
                      <option value="none" <?php echo ($smtp_settings['smtp_secure'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                  </div>
                </div>
                
                <div class="form-row-2">
                  <div class="form-control">
                    <label class="form-lbl">SMTP Username * (e.g. Gmail address)</label>
                    <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($smtp_settings['smtp_user'] ?? ''); ?>" required class="admin-input"/>
                  </div>
                  <div class="form-control">
                    <label class="form-lbl">SMTP Password * (e.g. Gmail App Password)</label>
                    <div class="input-wrapper" style="position: relative;">
                      <input type="password" id="smtp_pass" name="smtp_pass" value="<?php echo htmlspecialchars($smtp_settings['smtp_pass'] ?? ''); ?>" required class="admin-input" style="padding-right: 40px;"/>
                      <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text2);" onclick="toggleDashboardPassVisibility('smtp_pass', this)"></i>
                    </div>
                  </div>
                </div>
                
                <div class="form-row-3">
                  <div class="form-control">
                    <label class="form-lbl">Sender Email * (e.g. Gmail address)</label>
                    <input type="email" name="sender_email" value="<?php echo htmlspecialchars($smtp_settings['sender_email'] ?? ''); ?>" required class="admin-input"/>
                  </div>
                  <div class="form-control">
                    <label class="form-lbl">Sender Name *</label>
                    <input type="text" name="sender_name" value="<?php echo htmlspecialchars($smtp_settings['sender_name'] ?? ''); ?>" required class="admin-input"/>
                  </div>
                  <div class="form-control">
                    <label class="form-lbl">Recipient Email * (for contact forms)</label>
                    <input type="email" name="recipient_email" value="<?php echo htmlspecialchars($smtp_settings['recipient_email'] ?? ''); ?>" required class="admin-input"/>
                  </div>
                </div>
                
                <div class="form-actions" style="margin-top: 16px;">
                  <button type="submit" class="action-btn btn-primary"><i class="fa-solid fa-save"></i> Save SMTP Settings</button>
                </div>
              </form>
              
              <hr style="border: 0; border-top: 1px solid var(--border); margin: 30px 0;"/>
              
              <!-- Test Connection Section -->
              <form method="POST" action="dashboard">
                <input type="hidden" name="action" value="test_smtp"/>
                <input type="hidden" name="current_tab" value="settings"/>
                
                <h3 style="font-size: 16px; margin-bottom: 12px;"><i class="fa-solid fa-vial"></i> Send Test Connection Email</h3>
                <p style="font-size: 13px; color: var(--text2); margin-bottom: 16px;">
                  Make sure you have saved your updated credentials above before testing. This will send a real test email through your configured SMTP server.
                </p>
                
                <div class="form-control" style="max-width: 400px;">
                  <label class="form-lbl">Test Recipient Email Address</label>
                  <input type="email" name="test_email" value="<?php echo htmlspecialchars($current_admin_email); ?>" required class="admin-input" placeholder="e.g. test@example.com"/>
                </div>
                
                <div class="form-actions" style="margin-top: 16px;">
                  <button type="submit" class="action-btn btn-warning"><i class="fa-solid fa-envelope-circle-check"></i> Send Test Email</button>
                </div>
              </form>
            </div>
          </div>

        <?php endif; ?>

      </div>

    </main>

  </div>

  <script>
    function toggleDashboardPassVisibility(id, toggleIcon) {
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

    // Collapsible box code helper
    document.querySelectorAll('.collapsed').forEach(c => {
      c.style.display = 'none';
    });
    
    document.querySelectorAll('.card-header').forEach(header => {
      header.addEventListener('click', function() {
        var collapseBlock = this.nextElementSibling;
        if (collapseBlock && (collapseBlock.id === 'addProjectCollapse' || collapseBlock.id === 'addExpCollapse' || collapseBlock.id === 'smtpDiagnosticCollapse')) {
          var icon = this.querySelector('.collapse-icon i');
          if (collapseBlock.style.display === 'none') {
            collapseBlock.style.display = 'block';
            if (icon) {
              icon.classList.remove('fa-chevron-down');
              icon.classList.add('fa-chevron-up');
            }
          } else {
            collapseBlock.style.display = 'none';
            if (icon) {
              icon.classList.remove('fa-chevron-up');
              icon.classList.add('fa-chevron-down');
            }
          }
        }
      });
    });

    // AJAX OTP request helper for dashboard settings
    function requestSettingsOTP(btn) {
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
      
      const formData = new FormData();
      formData.append('action', 'send_settings_otp');
      
      fetch('dashboard', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          btn.innerHTML = '<i class="fa-solid fa-check"></i> Sent!';
          setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
          }, 30000); // 30 second rate limit visual
        } else {
          alert('Error: ' + data.message);
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      })
      .catch(err => {
        console.error(err);
        alert('An error occurred while requesting OTP.');
        btn.disabled = false;
        btn.innerHTML = originalText;
      });
    }
  </script>
</body>
</html>
