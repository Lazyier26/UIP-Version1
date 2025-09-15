<?php
// submit-incoming.php

// Turn off HTML error display to prevent breaking JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Log errors instead of displaying them
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password - leave empty for XAMPP default
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

// Function to create database and table if they don't exist
function initializeDatabase() {
    try {
        // First connect without specifying database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Now connect to the specific database
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        // Create table if it doesn't exist
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS incoming_interns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            contact VARCHAR(50) NOT NULL,
            birthday DATE NOT NULL,
            address TEXT NOT NULL,
            school VARCHAR(255) NOT NULL,
            program VARCHAR(255) NOT NULL,
            school_address TEXT NOT NULL,
            ojt_hours INT NOT NULL,
            available_days VARCHAR(255) NOT NULL,
            cv_file VARCHAR(255),
            picture_file VARCHAR(255),
            endorsement_file VARCHAR(255),
            moa_file VARCHAR(255),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createTableSQL);
        
        return $pdo;
        
    } catch(PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
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

// Function to handle file upload
function handleFileUpload($fileKey, $targetDir) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }
    
    $file = $_FILES[$fileKey];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error for $fileKey: " . $file['error']);
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception(ucfirst($fileKey) . " file is too large (max 10MB)");
    }
    
    // Validate file type based on file key
    $allowedTypes = [
        'cv' => ['application/pdf'],
        'picture' => ['image/jpeg', 'image/jpg', 'image/png'],
        'endorsement' => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
        'moa' => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
    ];
    
    if (!isset($allowedTypes[$fileKey]) || !in_array($file['type'], $allowedTypes[$fileKey])) {
        throw new Exception(ucfirst($fileKey) . " file type not allowed");
    }
    
    // Generate unique filename
    $filename = generateUniqueFilename($file['name']);
    $targetPath = $targetDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save $fileKey file");
    }
    
    return $filename;
}

// Main processing starts here
try {
    // Initialize upload directories
    if (!createUploadDirectories()) {
        throw new Exception("Failed to create upload directories");
    }
    
    // Initialize database
    $pdo = initializeDatabase();
    if (!$pdo) {
        throw new Exception("Database connection failed. Please check your MySQL server and credentials.");
    }
    
    // Get and sanitize form data
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $contact = isset($_POST['contact']) ? sanitizeInput($_POST['contact']) : '';
    $birthday = isset($_POST['birthday']) ? sanitizeInput($_POST['birthday']) : '';
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
    $school = isset($_POST['school']) ? sanitizeInput($_POST['school']) : '';
    $program = isset($_POST['program']) ? sanitizeInput($_POST['program']) : '';
    $school_address = isset($_POST['school_address']) ? sanitizeInput($_POST['school_address']) : '';
    $ojt_hours = isset($_POST['ojt_hours']) ? (int)$_POST['ojt_hours'] : 0;
    $days = isset($_POST['days']) ? $_POST['days'] : [];
    
    // Validation
    $errors = [];
    
    if (empty($name)) $errors[] = 'Full Name is required';
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    if (empty($contact)) {
        $errors[] = 'Contact Number is required';
    } elseif (!isValidPhone($contact)) {
        $errors[] = 'Invalid contact number format';
    }
    if (empty($birthday)) $errors[] = 'Birthday is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($school)) $errors[] = 'School/University is required';
    if (empty($program)) $errors[] = 'College Program is required';
    if (empty($school_address)) $errors[] = 'University Address is required';
    if ($ojt_hours <= 0) $errors[] = 'OJT Hours must be greater than 0';
    if (empty($days)) $errors[] = 'At least one available day is required';
    
    // Check for required files
    if (!isset($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'CV/Resume is required';
    }
    if (!isset($_FILES['picture']) || $_FILES['picture']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = '2x2 Picture is required';
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    // Check if email already exists
    $checkEmailStmt = $pdo->prepare("SELECT id FROM incoming_interns WHERE email = ?");
    $checkEmailStmt->execute([$email]);
    if ($checkEmailStmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'An application with this email already exists'
        ]);
        exit;
    }
    
    // Process file uploads
    $uploadedFiles = [];
    
    // Required files
    $uploadedFiles['cv'] = handleFileUpload('cv', UPLOAD_DIR . 'cv/');
    $uploadedFiles['picture'] = handleFileUpload('picture', UPLOAD_DIR . 'pictures/');
    
    // Optional files
    $uploadedFiles['endorsement'] = handleFileUpload('endorsement', UPLOAD_DIR . 'endorsements/');
    $uploadedFiles['moa'] = handleFileUpload('moa', UPLOAD_DIR . 'moa/');
    
    // Insert into database
    $insertSQL = "
        INSERT INTO incoming_interns (
            name, email, contact, birthday, address, school, program, 
            school_address, ojt_hours, available_days, cv_file, 
            picture_file, endorsement_file, moa_file
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($insertSQL);
    $result = $stmt->execute([
        $name, $email, $contact, $birthday, $address, $school, $program,
        $school_address, $ojt_hours, implode(', ', $days),
        $uploadedFiles['cv'], $uploadedFiles['picture'],
        $uploadedFiles['endorsement'], $uploadedFiles['moa']
    ]);
    
    if ($result) {
        $insertedId = $pdo->lastInsertId();
        
        // Log successful registration
        error_log("New registration: ID $insertedId, Email: $email, Name: $name");
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Registration submitted successfully! We will contact you soon.',
            'data' => [
                'id' => $insertedId,
                'name' => $name,
                'email' => $email,
                'files_uploaded' => array_filter($uploadedFiles)
            ]
        ]);
    } else {
        throw new Exception('Failed to save registration to database');
    }
    
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    
    // Clean up uploaded files if database insert failed
    if (isset($uploadedFiles)) {
        foreach ($uploadedFiles as $file) {
            if ($file && file_exists(UPLOAD_DIR . $file)) {
                unlink(UPLOAD_DIR . $file);
            }
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>