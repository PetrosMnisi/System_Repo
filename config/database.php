<?php
/**
 * Database Configuration
 * IDMA SMS/LMS System
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'idma_sms_lms');
define('DB_PORT', 3306);

// Application settings
define('APP_NAME', 'IDMA SMS/LMS');
define('APP_URL', 'http://localhost/idma-sms-lms');
define('APP_ENV', 'development'); // development or production

// Security settings
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('PASSWORD_HASH_ALGO', 'bcrypt');
define('TOKEN_EXPIRY', 3600); // 1 hour

// Email settings
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 587);
define('MAIL_USER', 'noreply@idma.sz');
define('MAIL_PASS', '');
define('MAIL_FROM', 'IDMA SMS/LMS <noreply@idma.sz>');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif']);

// Academic settings
define('ASSIGNMENT_WEIGHT', 0.40);
define('TEST_WEIGHT', 0.40);
define('EXAM_WEIGHT', 0.60);
define('PASS_MARK', 40);
define('DISTINCTION_MARK', 70);

// Payment settings
define('DEPOSIT_PERCENTAGE', 0.40); // 40% deposit required
define('CURRENCY', 'SZL'); // Swazi Lilangeni

// Database connection
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
    
    // Set charset
    $mysqli->set_charset('utf8mb4');
    
} catch (Exception $e) {
    error_log('Database Error: ' . $e->getMessage());
    die('Database connection error. Please contact administrator.');
}

// PDO alternative (optional)
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('PDO Error: ' . $e->getMessage());
}

?>
