<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Get POST variables
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("INSERT INTO `inquiries` (`name`, `email`, `message`) VALUES (:name, :email, :message)");
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'message' => $message
    ]);

    // Send email notification to Admin
    $smtpQuery = $db->query("SELECT * FROM `smtp_settings` WHERE `id` = 1");
    $smtp_settings = $smtpQuery->fetch();
    
    if ($smtp_settings) {
        require_once __DIR__ . '/mail_helper.php';
        
        $subject = "New Inquiry from " . $name;
        $body = "
        <div style=\"font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333333; padding: 40px 20px;\">
            <div style=\"background-color: #ffffff; border-radius: 12px; padding: 35px 30px; max-width: 600px; margin: 0 auto; border: 1px solid #e1e4e8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: left;\">
                <div style=\"border-bottom: 2px solid #7B61FF; padding-bottom: 15px; margin-bottom: 25px;\">
                    <h2 style=\"margin: 0; color: #1a1f2c; font-size: 22px; font-weight: 700;\">New Client Inquiry Received</h2>
                    <p style=\"margin: 5px 0 0 0; font-size: 14px; color: #718096;\">Sent at: " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <p style=\"font-size: 15px; color: #4a5568; line-height: 1.6; margin-bottom: 20px;\">Hello Admin,</p>
                <p style=\"font-size: 15px; color: #4a5568; line-height: 1.6; margin-bottom: 20px;\">You have received a new inquiry from your portfolio website contact form. The details are listed below:</p>
                
                <table style=\"width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 15px;\">
                    <tr>
                        <th style=\"text-align: left; padding: 12px; border-bottom: 1px solid #edf2f7; color: #718096; width: 120px; font-weight: 600;\">Name:</th>
                        <td style=\"padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748; font-weight: bold;\">" . htmlspecialchars($name) . "</td>
                    </tr>
                    <tr>
                        <th style=\"text-align: left; padding: 12px; border-bottom: 1px solid #edf2f7; color: #718096; font-weight: 600;\">Email:</th>
                        <td style=\"padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748;\"><a href='mailto:" . htmlspecialchars($email) . "' style='color: #7B61FF; text-decoration: none; font-weight: 500;'>" . htmlspecialchars($email) . "</a></td>
                    </tr>
                    <tr>
                        <th style=\"text-align: left; padding: 12px; border-bottom: 1px solid #edf2f7; color: #718096; font-weight: 600; vertical-align: top;\">Message:</th>
                        <td style=\"padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748; line-height: 1.6; white-space: pre-line;\">" . nl2br(htmlspecialchars($message)) . "</td>
                    </tr>
                </table>
                
                <div style=\"font-size: 12px; color: #a0aec0; margin-top: 35px; border-top: 1px solid #edf2f7; padding-top: 15px;\">
                    &copy; " . date('Y') . " Amit.Dev Portfolio System
                </div>
            </div>
        </div>
        ";
        
        try {
            send_smtp_email($smtp_settings['recipient_email'], $subject, $body, $smtp_settings);
        } catch (Exception $mailEx) {
            // Log mail failure but do not impact the success response back to the client
            error_log("Failed to send inquiry notification email: " . $mailEx->getMessage());
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Message sent — I\'ll get back to you soon.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
