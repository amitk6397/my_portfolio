<?php
// Check if running on local environment (localhost)
$is_local = (
    isset($_SERVER['HTTP_HOST']) && (
        $_SERVER['HTTP_HOST'] === 'localhost' || 
        $_SERVER['HTTP_HOST'] === '127.0.0.1' || 
        strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0
    )
) || (
    isset($_SERVER['SERVER_ADDR']) && (
        $_SERVER['SERVER_ADDR'] === '127.0.0.1' || 
        $_SERVER['SERVER_ADDR'] === '::1'
    )
);

if ($is_local) {
    // Local Configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'portfolio_db');
} else {
    // Hosting Configuration (Ezyro)
    // Note: If your Ezyro Control Panel shows a specific MySQL Host (like sqlXXX.ezyro.com), please replace 'localhost' with that host.
    define('DB_HOST', 'localhost'); 
    define('DB_USER', 'ezyro_42236713');
    define('DB_PASS', 'AMIT@1234555');
    define('DB_NAME', 'ezyro_42236713_portfolio_db');
}

// Path Configuration
define('UPLOAD_DIR', __DIR__ . '/public/uploads/');
define('UPLOAD_URL', 'public/uploads/');

// Session initialization
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
