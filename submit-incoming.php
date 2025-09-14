<?php
// submit-incoming.php 
ob_start();

// Disable HTML error reporting to prevent HTML in JSON response
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Max-Age: 3600');
    http_response_code(200);
    exit;
}

// Clean any previous output
ob_clean();

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Function to log debug information (file-based, no output)
function debugLog($message) {
    $logFile = __DIR__ . '/logs/form_debug.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Function to send clean JSON response
function sendResponse($success, $message, $data = null, $httpCode = 200) {
    // Clean any output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    // Ensure clean JSON output
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // End output buffering and flush
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

// Custom error handler to prevent HTML output
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    debugLog("PHP Error [$errno]: $errstr in $errfile:$errline");
    return true; // Don't execute PHP internal error handler
}
set_error_handler('customErrorHandler');

// Custom exception handler
function customExceptionHandler($exception) {
    debugLog("Uncaught exception: " . $exception->getMessage());
    sendResponse(false, 'A system error occurred. Please try again.', null, 500);
}
set_exception_handler('customExceptionHandler');

try {
    debugLog("=== FORM SUBMISSION START ===");
    debugLog("Request Method: " . $_SERVER['REQUEST_METHOD']);
    debugLog("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        debugLog("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        sendResponse(false, 'Invalid request method. Only POST is allowed.', null, 405);
    }
    
    // Check if required files exist
    $requiredFiles = [
        'config/database.php' => 'Database configuration',
        'classes/FileUploadHandler.php' => 'File upload handler', 
        'classes/RegistrationValidator.php' => 'Registration validator'
    ];
    
    $missingFiles = [];
    foreach ($requiredFiles as $file => $description) {
        $fullPath = __DIR__ . '/' . $file;
        if (!file_exists($fullPath)) {
            $missingFiles[] = $description;
            debugLog("Missing file: $fullPath");
        }
    }
    
    if (!empty($missingFiles)) {
        debugLog("Fatal: Missing required files: " . implode(', ', $missingFiles));
        sendResponse(false, 'Server configuration error. Missing required files.', null, 500);
    }
    
    // Include required files with error suppression to prevent HTML output
    foreach ($requiredFiles as $file => $description) {
        $fullPath = __DIR__ . '/' . $file;
        $included = @include_once $fullPath;
        if ($included === false) {
            debugLog("Failed to include: $fullPath");
            sendResponse(false, 'Server configuration error. Failed to load components.', null, 500);
        }
    }
    
    debugLog("All required files loaded");

    // Check POST data
    if (empty($_POST)) {
        debugLog("No POST data, checking for JSON input");
        $jsonInput = file_get_contents('php://input');
        
        if (!empty($jsonInput)) {
            $decodedData = json_decode($jsonInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                $_POST = $decodedData;
                debugLog("JSON data decoded successfully");
            }
        }
        
        if (empty($_POST)) {
            debugLog("No form data received");
            sendResponse(false, 'No form data received. Please fill all required fields.');
        }
    }

    debugLog("POST data received: " . count($_POST) . " fields");
    debugLog("FILES data received: " . count($_FILES ?? []) . " files");

    // Verify classes exist
    if (!class_exists('Database')) {
        debugLog("Database class not available");
        sendResponse(false, 'Database class not available', null, 500);
    }
    
    if (!class_exists('FileUploadHandler')) {
        debugLog("FileUploadHandler class not available");
        sendResponse(false, 'FileUploadHandler class not available', null, 500);
    }
    
    if (!class_exists('RegistrationValidator')) {
        debugLog("RegistrationValidator class not available");
        sendResponse(false, 'RegistrationValidator class not available', null, 500);
    }

    // Test database connection
    try {
        $database = new Database();
        $db = $database->getConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }
        debugLog("Database connection successful");
    } catch (Exception $e) {
        debugLog("Database error: " . $e->getMessage());
        sendResponse(false, 'Database connection error. Please try again later.', null, 500);
    }

    // Create registration handler
    class RegistrationHandler {
        private $db;
        private $fileHandler;
        private $validator;
        private $uploadDir;

        public function __construct() {
            $this->uploadDir = __DIR__ . '/uploads/';
            
            $database = new Database();
            $this->db = $database->getConnection();
            
            if (!$this->db) {
                throw new Exception('Failed to establish database connection');
            }
            
            $this->fileHandler = new FileUploadHandler();
            $this->validator = new RegistrationValidator();
            $this->createUploadDirectories();
        }
        
        private function createUploadDirectories() {
            $dirs = [
                $this->uploadDir,
                $this->uploadDir . 'cv/',
                $this->uploadDir . 'pictures/',
                $this->uploadDir . 'documents/',
                $this->uploadDir . 'temp/'
            ];
            
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    if (!@mkdir($dir, 0755, true)) {
                        throw new Exception("Failed to create directory: $dir");
                    }
                    debugLog("Created directory: $dir");
                }
            }
            
            $htaccessContent = "Order Deny,Allow\nDeny from all\n<Files ~ \"\\.(jpg|jpeg|png|gif|pdf|doc|docx)$\">\nAllow from all\n</Files>";
            $htaccessPath = $this->uploadDir . '.htaccess';
            if (!file_exists($htaccessPath)) {
                @file_put_contents($htaccessPath, $htaccessContent);
            }
        }

        public function handleSubmission() {
            try {
                debugLog("Processing form data");
                
                // Validate and sanitize input
                $formData = $this->validator->validateAndSanitize($_POST);
                debugLog("Form validation completed");
                
                // Validate files if uploaded
                if (!empty($_FILES)) {
                    $this->validator->validateRequiredFiles($_FILES);
                    debugLog("File validation completed");
                }
                
                // Handle file uploads
                $uploadedFiles = $this->handleFileUploads($_FILES ?? []);
                debugLog("File uploads handled: " . count($uploadedFiles) . " files");
                
                // Insert registration
                $registrationId = $this->insertRegistration($formData, $uploadedFiles);
                debugLog("Registration inserted with ID: $registrationId");
                
                sendResponse(true, 'Registration submitted successfully! We will contact you soon.', [
                    'registration_id' => $registrationId
                ]);
                
            } catch (Exception $e) {
                debugLog("Handler error: " . $e->getMessage());
                $userMessage = $this->getUserFriendlyErrorMessage($e->getMessage());
                $httpCode = $this->getHttpCodeFromException($e);
                sendResponse(false, $userMessage, null, $httpCode);
            }
        }
        
        private function getUserFriendlyErrorMessage($technicalMessage) {
            $patterns = [
                '/database/i' => 'Database error. Please try again later.',
                '/file upload/i' => 'File upload failed. Please check your files.',
                '/validation/i' => 'Please check your form data.',
                '/email.*exists/i' => 'Email already registered. Use a different email.',
                '/permission/i' => 'Server permission error. Contact support.',
            ];
            
            foreach ($patterns as $pattern => $message) {
                if (preg_match($pattern, $technicalMessage)) {
                    return $message;
                }
            }
            
            return 'An error occurred. Please try again.';
        }
        
        private function getHttpCodeFromException($exception) {
            $message = strtolower($exception->getMessage());
            
            if (strpos($message, 'validation') !== false) return 400;
            if (strpos($message, 'exists') !== false) return 409;
            if (strpos($message, 'permission') !== false) return 403;
            
            return 500;
        }

        private function handleFileUploads($files) {
            $uploadedFiles = [];
            
            if (empty($files)) {
                return $uploadedFiles;
            }
            
            $fileTypes = [
                'cv' => ['dir' => 'cv/', 'extensions' => ['pdf'], 'required' => true],
                'picture' => ['dir' => 'pictures/', 'extensions' => ['jpg', 'jpeg', 'png'], 'required' => true],
                'endorsement' => ['dir' => 'documents/', 'extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 'required' => false],
                'moa' => ['dir' => 'documents/', 'extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 'required' => false]
            ];
            
            foreach ($fileTypes as $fileType => $config) {
                if (isset($files[$fileType])) {
                    $file = $files[$fileType];
                    
                    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                        if ($config['required']) {
                            throw new Exception("Required file missing: $fileType");
                        }
                        continue;
                    }
                    
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("File upload error for $fileType");
                    }
                    
                    $uploadedFiles[$fileType] = $this->fileHandler->uploadFile(
                        $file, 
                        $this->uploadDir . $config['dir'], 
                        $config['extensions']
                    );
                }
            }
            
            return $uploadedFiles;
        }

        private function insertRegistration($formData, $uploadedFiles) {
            $this->db->beginTransaction();
            
            try {
                // Check email exists
                if (method_exists($this->validator, 'checkEmailExists')) {
                    if ($this->validator->checkEmailExists($formData['email'], $this->db)) {
                        throw new Exception('Email already registered');
                    }
                }
                
                // Get/create university and program
                $universityId = $this->getOrCreateUniversity($formData['school'], $formData['school_address'] ?? '');
                $programId = $this->getOrCreateProgram($formData['program']);
                
                // Insert registration
                $registrationData = $this->prepareRegistrationData($formData, $uploadedFiles, $universityId, $programId);
                $registrationId = $this->executeRegistrationInsert($registrationData);
                
                // Insert available days
                if (!empty($formData['days'])) {
                    $this->insertAvailableDays($registrationId, $formData['days']);
                }
                
                $this->db->commit();
                return $registrationId;
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        }
        
        private function prepareRegistrationData($formData, $uploadedFiles, $universityId, $programId) {
            return [
                'full_name' => $formData['name'] ?? '',
                'email' => $formData['email'] ?? '',
                'contact_number' => $formData['contact'] ?? '',
                'birthday' => $formData['birthday'] ?? null,
                'complete_address' => $formData['address'] ?? '',
                'university_id' => $universityId,
                'program_id' => $programId,
                'total_ojt_hours' => $formData['ojt_hours'] ?? null,
                'cv_file_path' => $uploadedFiles['cv']['path'] ?? null,
                'cv_original_name' => $uploadedFiles['cv']['original_name'] ?? null,
                'cv_file_size' => $uploadedFiles['cv']['size'] ?? null,
                'picture_file_path' => $uploadedFiles['picture']['path'] ?? null,
                'picture_original_name' => $uploadedFiles['picture']['original_name'] ?? null,
                'picture_file_size' => $uploadedFiles['picture']['size'] ?? null,
                'endorsement_file_path' => $uploadedFiles['endorsement']['path'] ?? null,
                'endorsement_original_name' => $uploadedFiles['endorsement']['original_name'] ?? null,
                'endorsement_file_size' => $uploadedFiles['endorsement']['size'] ?? null,
                'moa_file_path' => $uploadedFiles['moa']['path'] ?? null,
                'moa_original_name' => $uploadedFiles['moa']['original_name'] ?? null,
                'moa_file_size' => $uploadedFiles['moa']['size'] ?? null,
                'terms_accepted' => 1,
                'terms_accepted_at' => date('Y-m-d H:i:s'),
                'terms_ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
        }
        
        private function executeRegistrationInsert($data) {
            $sql = "INSERT INTO registrations (
                full_name, email, contact_number, birthday, complete_address,
                university_id, program_id, total_ojt_hours,
                cv_file_path, cv_original_name, cv_file_size,
                picture_file_path, picture_original_name, picture_file_size,
                endorsement_file_path, endorsement_original_name, endorsement_file_size,
                moa_file_path, moa_original_name, moa_file_size,
                terms_accepted, terms_accepted_at, terms_ip_address
            ) VALUES (
                :full_name, :email, :contact_number, :birthday, :complete_address,
                :university_id, :program_id, :total_ojt_hours,
                :cv_file_path, :cv_original_name, :cv_file_size,
                :picture_file_path, :picture_original_name, :picture_file_size,
                :endorsement_file_path, :endorsement_original_name, :endorsement_file_size,
                :moa_file_path, :moa_original_name, :moa_file_size,
                :terms_accepted, :terms_accepted_at, :terms_ip_address
            )";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare registration statement");
            }
            
            $success = $stmt->execute([
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':contact_number' => $data['contact_number'],
                ':birthday' => $data['birthday'],
                ':complete_address' => $data['complete_address'],
                ':university_id' => $data['university_id'],
                ':program_id' => $data['program_id'],
                ':total_ojt_hours' => $data['total_ojt_hours'],
                ':cv_file_path' => $data['cv_file_path'],
                ':cv_original_name' => $data['cv_original_name'],
                ':cv_file_size' => $data['cv_file_size'],
                ':picture_file_path' => $data['picture_file_path'],
                ':picture_original_name' => $data['picture_original_name'],
                ':picture_file_size' => $data['picture_file_size'],
                ':endorsement_file_path' => $data['endorsement_file_path'],
                ':endorsement_original_name' => $data['endorsement_original_name'],
                ':endorsement_file_size' => $data['endorsement_file_size'],
                ':moa_file_path' => $data['moa_file_path'],
                ':moa_original_name' => $data['moa_original_name'],
                ':moa_file_size' => $data['moa_file_size'],
                ':terms_accepted' => $data['terms_accepted'],
                ':terms_accepted_at' => $data['terms_accepted_at'],
                ':terms_ip_address' => $data['terms_ip_address']
            ]);
            
            if (!$success) {
                throw new Exception("Database insert failed");
            }
            
            return $this->db->lastInsertId();
        }

        private function getOrCreateUniversity($name, $address) {
            $stmt = $this->db->prepare("SELECT id FROM universities WHERE university_name = ? LIMIT 1");
            $stmt->execute([$name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['id'];
            }
            
            $stmt = $this->db->prepare("INSERT INTO universities (university_name, university_address, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$name, $address]);
            return $this->db->lastInsertId();
        }

        private function getOrCreateProgram($name) {
            $stmt = $this->db->prepare("SELECT id FROM programs WHERE program_name = ? LIMIT 1");
            $stmt->execute([$name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['id'];
            }
            
            $stmt = $this->db->prepare("INSERT INTO programs (program_name, created_at) VALUES (?, NOW())");
            $stmt->execute([$name]);
            return $this->db->lastInsertId();
        }

        private function insertAvailableDays($registrationId, $days) {
            $stmt = $this->db->prepare("INSERT INTO available_days (registration_id, day_of_week) VALUES (?, ?)");
            foreach ($days as $day) {
                $stmt->execute([$registrationId, $day]);
            }
        }
    }

    // Execute main process
    $handler = new RegistrationHandler();
    $handler->handleSubmission();

} catch (Exception $e) {
    debugLog("Fatal error: " . $e->getMessage());
    sendResponse(false, 'System error occurred. Please try again.', null, 500);
}
?>