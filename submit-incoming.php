<?php
// submit-incoming.php - Enhanced with better error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log the request for debugging
file_put_contents('form_debug.log', date('Y-m-d H:i:s') . " - Form submission received\n", FILE_APPEND);

try {
    // Check if files exist before including
    $requiredFiles = [
        'config/database.php',
        'classes/FileUploadHandler.php', 
        'classes/RegistrationValidator.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file not found: $file");
        }
        require_once $file;
    }

    class RegistrationHandler {
        private $db;
        private $fileHandler;
        private $validator;
        private $uploadDir = 'uploads/';

        public function __construct() {
            try {
                $database = new Database();
                $this->db = $database->getConnection();
                $this->fileHandler = new FileUploadHandler();
                $this->validator = new RegistrationValidator();
                
                // Create upload directory if it doesn't exist
                $this->createUploadDirectories();
                
            } catch (Exception $e) {
                file_put_contents('form_debug.log', "Constructor error: " . $e->getMessage() . "\n", FILE_APPEND);
                throw $e;
            }
        }
        
        private function createUploadDirectories() {
            $dirs = [
                $this->uploadDir,
                $this->uploadDir . 'cv/',
                $this->uploadDir . 'pictures/',
                $this->uploadDir . 'documents/'
            ];
            
            foreach ($dirs as $dir) {
                if (!file_exists($dir)) {
                    if (!mkdir($dir, 0755, true)) {
                        throw new Exception("Failed to create directory: $dir");
                    }
                }
            }
        }

        public function handleSubmission() {
            try {
                file_put_contents('form_debug.log', "Starting form processing\n", FILE_APPEND);
                
                // Validate request method
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('Invalid request method');
                }

                // Check if form data exists
                if (empty($_POST)) {
                    throw new Exception('No form data received');
                }
                
                file_put_contents('form_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
                file_put_contents('form_debug.log', "FILES data: " . print_r($_FILES, true) . "\n", FILE_APPEND);

                // Validate and sanitize input data
                $formData = $this->validator->validateAndSanitize($_POST);
                file_put_contents('form_debug.log', "Form data validated\n", FILE_APPEND);
                
                // Validate required files
                $this->validator->validateRequiredFiles($_FILES);
                file_put_contents('form_debug.log', "Files validated\n", FILE_APPEND);
                
                // Handle file uploads
                $uploadedFiles = $this->handleFileUploads($_FILES);
                file_put_contents('form_debug.log', "Files uploaded\n", FILE_APPEND);
                
                // Insert registration
                $registrationId = $this->insertRegistration($formData, $uploadedFiles);
                file_put_contents('form_debug.log', "Registration inserted with ID: $registrationId\n", FILE_APPEND);
                
                // Send success response
                $this->sendSuccessResponse($registrationId);
                
            } catch (Exception $e) {
                file_put_contents('form_debug.log', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
                file_put_contents('form_debug.log', "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
                $this->sendErrorResponse($e->getMessage());
            }
        }

        private function handleFileUploads($files) {
            $uploadedFiles = [];
            
            try {
                // Handle CV upload (required)
                if (isset($files['cv']) && $files['cv']['error'] === UPLOAD_ERR_OK) {
                    $uploadedFiles['cv'] = $this->fileHandler->uploadFile(
                        $files['cv'], 
                        $this->uploadDir . 'cv/', 
                        ['pdf']
                    );
                }
                
                // Handle picture upload (required)
                if (isset($files['picture']) && $files['picture']['error'] === UPLOAD_ERR_OK) {
                    $uploadedFiles['picture'] = $this->fileHandler->uploadFile(
                        $files['picture'], 
                        $this->uploadDir . 'pictures/', 
                        ['jpg', 'jpeg', 'png']
                    );
                }
                
                // Handle endorsement letter (optional)
                if (isset($files['endorsement']) && $files['endorsement']['error'] === UPLOAD_ERR_OK) {
                    $uploadedFiles['endorsement'] = $this->fileHandler->uploadFile(
                        $files['endorsement'], 
                        $this->uploadDir . 'documents/', 
                        ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']
                    );
                }
                
                // Handle MOA (optional)
                if (isset($files['moa']) && $files['moa']['error'] === UPLOAD_ERR_OK) {
                    $uploadedFiles['moa'] = $this->fileHandler->uploadFile(
                        $files['moa'], 
                        $this->uploadDir . 'documents/', 
                        ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']
                    );
                }
                
            } catch (Exception $e) {
                file_put_contents('form_debug.log', "File upload error: " . $e->getMessage() . "\n", FILE_APPEND);
                throw new Exception("File upload failed: " . $e->getMessage());
            }
            
            return $uploadedFiles;
        }

        private function insertRegistration($formData, $uploadedFiles) {
            $this->db->beginTransaction();
            
            try {
                // Check if email already exists
                if ($this->validator->checkEmailExists($formData['email'], $this->db)) {
                    throw new Exception('Email address is already registered. Please use a different email or contact support.');
                }
                
                // Insert or get university
                $universityId = $this->getOrCreateUniversity(
                    $formData['school'], 
                    $formData['school_address']
                );
                
                // Insert or get program
                $programId = $this->getOrCreateProgram($formData['program']);
                
                // Insert registration
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
                
                $params = [
                    ':full_name' => $formData['name'],
                    ':email' => $formData['email'],
                    ':contact_number' => $formData['contact'],
                    ':birthday' => $formData['birthday'],
                    ':complete_address' => $formData['address'],
                    ':university_id' => $universityId,
                    ':program_id' => $programId,
                    ':total_ojt_hours' => $formData['ojt_hours'],
                    ':cv_file_path' => $uploadedFiles['cv']['path'] ?? null,
                    ':cv_original_name' => $uploadedFiles['cv']['original_name'] ?? null,
                    ':cv_file_size' => $uploadedFiles['cv']['size'] ?? null,
                    ':picture_file_path' => $uploadedFiles['picture']['path'] ?? null,
                    ':picture_original_name' => $uploadedFiles['picture']['original_name'] ?? null,
                    ':picture_file_size' => $uploadedFiles['picture']['size'] ?? null,
                    ':endorsement_file_path' => $uploadedFiles['endorsement']['path'] ?? null,
                    ':endorsement_original_name' => $uploadedFiles['endorsement']['original_name'] ?? null,
                    ':endorsement_file_size' => $uploadedFiles['endorsement']['size'] ?? null,
                    ':moa_file_path' => $uploadedFiles['moa']['path'] ?? null,
                    ':moa_original_name' => $uploadedFiles['moa']['original_name'] ?? null,
                    ':moa_file_size' => $uploadedFiles['moa']['size'] ?? null,
                    ':terms_accepted' => 1,
                    ':terms_accepted_at' => date('Y-m-d H:i:s'),
                    ':terms_ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
                ];
                
                $stmt->execute($params);
                $registrationId = $this->db->lastInsertId();
                
                // Insert available days
                $this->insertAvailableDays($registrationId, $formData['days']);
                
                // Insert file upload audit records
                $this->insertFileAuditRecords($registrationId, $uploadedFiles);
                
                $this->db->commit();
                return $registrationId;
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        }

        private function getOrCreateUniversity($universityName, $universityAddress) {
            // Check if university exists
            $sql = "SELECT id FROM universities WHERE university_name = :name LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':name' => $universityName]);
            
            $result = $stmt->fetch();
            if ($result) {
                return $result['id'];
            }
            
            // Insert new university
            $sql = "INSERT INTO universities (university_name, university_address) VALUES (:name, :address)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $universityName,
                ':address' => $universityAddress
            ]);
            
            return $this->db->lastInsertId();
        }

        private function getOrCreateProgram($programName) {
            // Check if program exists
            $sql = "SELECT id FROM programs WHERE program_name = :name LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':name' => $programName]);
            
            $result = $stmt->fetch();
            if ($result) {
                return $result['id'];
            }
            
            // Insert new program
            $sql = "INSERT INTO programs (program_name) VALUES (:name)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':name' => $programName]);
            
            return $this->db->lastInsertId();
        }

        private function insertAvailableDays($registrationId, $days) {
            if (empty($days)) return;
            
            $sql = "INSERT INTO available_days (registration_id, day_of_week) VALUES (:registration_id, :day)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($days as $day) {
                $stmt->execute([
                    ':registration_id' => $registrationId,
                    ':day' => $day
                ]);
            }
        }

        private function insertFileAuditRecords($registrationId, $uploadedFiles) {
            $sql = "INSERT INTO file_uploads_audit (
                registration_id, file_type, original_filename, stored_filename, 
                file_path, file_size, mime_type, upload_ip_address
            ) VALUES (
                :registration_id, :file_type, :original_filename, :stored_filename,
                :file_path, :file_size, :mime_type, :upload_ip_address
            )";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($uploadedFiles as $fileType => $fileInfo) {
                $stmt->execute([
                    ':registration_id' => $registrationId,
                    ':file_type' => $fileType,
                    ':original_filename' => $fileInfo['original_name'],
                    ':stored_filename' => basename($fileInfo['path']),
                    ':file_path' => $fileInfo['path'],
                    ':file_size' => $fileInfo['size'],
                    ':mime_type' => $fileInfo['mime_type'],
                    ':upload_ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
            }
        }

        private function sendSuccessResponse($registrationId) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Registration submitted successfully! We will contact you soon.',
                'registration_id' => $registrationId
            ]);
            exit;
        }

        private function sendErrorResponse($message) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit;
        }
    }

    // Handle the request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $handler = new RegistrationHandler();
        $handler->handleSubmission();
    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    file_put_contents('form_debug.log', "Fatal error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>