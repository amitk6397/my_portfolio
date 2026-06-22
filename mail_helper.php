<?php
// Standalone SMTP Client function using standard PHP socket streams.
// This does not require Composer, PHPMailer or any external packages.

function send_smtp_email($to, $subject, $message, $smtp_settings) {
    try {
        return send_smtp_email_socket($to, $subject, $message, $smtp_settings);
    } catch (Exception $e) {
        $from_email = $smtp_settings['sender_email'];
        $from_name = $smtp_settings['sender_name'];
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>\r\n";
        
        error_log("SMTP socket connection failed (" . $e->getMessage() . "). Falling back to PHP mail().");
        if (@mail($to, $subject, $message, $headers)) {
            return true;
        }
        throw $e;
    }
}

function send_smtp_email_socket($to, $subject, $message, $smtp_settings) {
    $host = $smtp_settings['smtp_host'];
    $port = intval($smtp_settings['smtp_port']);
    $username = $smtp_settings['smtp_user'];
    $password = $smtp_settings['smtp_pass'];
    $secure = strtolower($smtp_settings['smtp_secure']);
    $from_email = $smtp_settings['sender_email'];
    $from_name = $smtp_settings['sender_name'];

    // For SSL, we prefix host with ssl://
    $socket_host = ($secure === 'ssl') ? "ssl://$host" : $host;
    
    $socket = @stream_socket_client("$socket_host:$port", $errno, $errstr, 15);
    if (!$socket) {
        throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
    }

    // Response reader helper
    $read_response = function($socket, $expected_code) use (&$read_response) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        $code = substr($response, 0, 3);
        if ($code !== (string)$expected_code) {
            throw new Exception("SMTP Error: Expected $expected_code, got response: $response");
        }
        return $response;
    };

    try {
        // 1. Read greeting
        $read_response($socket, 220);

        // 2. Say EHLO
        fwrite($socket, "EHLO localhost\r\n");
        $read_response($socket, 250);

        // If using TLS, start encryption
        if ($secure === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            $read_response($socket, 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                throw new Exception("Failed to start TLS encryption");
            }
            // Say EHLO again after TLS handshake
            fwrite($socket, "EHLO localhost\r\n");
            $read_response($socket, 250);
        }

        // 3. Login Authentication
        fwrite($socket, "AUTH LOGIN\r\n");
        $read_response($socket, 334);

        fwrite($socket, base64_encode($username) . "\r\n");
        $read_response($socket, 334);

        fwrite($socket, base64_encode($password) . "\r\n");
        $read_response($socket, 235);

        // 4. Mail From
        fwrite($socket, "MAIL FROM:<$from_email>\r\n");
        $read_response($socket, 250);

        // 5. Rcpt To
        fwrite($socket, "RCPT TO:<$to>\r\n");
        $read_response($socket, 250);

        // 6. Data
        fwrite($socket, "DATA\r\n");
        $read_response($socket, 354);

        // Headers & Message Body
        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>",
            "To: <$to>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "Date: " . date('r'),
            "Message-ID: <" . uniqid('', true) . "@" . ($host ?: 'localhost') . ">"
        ];
        
        // SMTP data block terminates with <CRLF>.<CRLF>
        // Ensure all single dots on a line are escaped as double dots to prevent early termination
        $escaped_message = str_replace("\n.", "\n..", $message);
        
        $email_data = implode("\r\n", $headers) . "\r\n\r\n" . $escaped_message . "\r\n.\r\n";
        fwrite($socket, $email_data);
        $read_response($socket, 250);

        // 7. Quit
        fwrite($socket, "QUIT\r\n");
        $read_response($socket, 221);
    } finally {
        fclose($socket);
    }

    return true;
}
?>
