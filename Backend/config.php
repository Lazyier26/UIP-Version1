<?php
// Turn off HTML error display to prevent breaking JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Log errors instead of displaying them
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password
define('DB_NAME', 'uip_registration');

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes

// Create uploads directory if it doesn't exist
function createUploadDirectories() {
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            error_log('Failed to create main upload directory');
            return false;
        }
    }
    
    // Create subdirectories for different file types
    $subdirs = ['cv', 'pictures', 'endorsements', 'moa'];
    foreach ($subdirs as $subdir) {
        $path = UPLOAD_DIR . $subdir . '/';
        if (!file_exists($path)) {
            if (!mkdir($path, 0755, true)) {
                error_log("Failed to create upload directory: $path");
                return false;
            }
        }
    }
    return true;
}

// Initialize upload directories
createUploadDirectories();

// Database connection function
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// Function to sanitize input
function sanitizeInput($input) {
    if (is_string($input)) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to validate phone number
function isValidPhone($phone) {
    return preg_match('/^[0-9+\-\s()]{7,}$/', $phone);
}

// Function to generate unique filename
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . date('YmdHis') . '.' . strtolower($extension);
}

// Function to check if database and table exist
function checkDatabase() {
    try {
        // First check if we can connect to MySQL server
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        
        // Check if database exists
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([DB_NAME]);
        
        if (!$stmt->fetch()) {
            error_log("Database '" . DB_NAME . "' does not exist");
            return false;
        }
        
        // Now connect to the specific database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        
        // Check if table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'incoming_interns'");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            error_log("Table 'incoming_interns' does not exist");
            return false;
        }
        
        return true;
        
    } catch(PDOException $e) {
        error_log("Database check failed: " . $e->getMessage());
        return false;
    }
}
?>