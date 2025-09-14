<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'UIPdatabase'); // Change this to your MySQL username
define('DB_PASS', 'UIPincorporated2021'); // Change this to your MySQL password
define('DB_NAME', 'uip_registration');

// File upload configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    // Create subdirectories for different file types
    mkdir(UPLOAD_DIR . 'cv/', 0755, true);
    mkdir(UPLOAD_DIR . 'pictures/', 0755, true);
    mkdir(UPLOAD_DIR . 'endorsements/', 0755, true);
    mkdir(UPLOAD_DIR . 'moa/', 0755, true);
}

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number
function isValidPhone($phone) {
    return preg_match('/^[0-9+\-\s()]{7,}$/', $phone);
}

// Function to generate unique filename
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . date('YmdHis') . '.' . $extension;
}
?>