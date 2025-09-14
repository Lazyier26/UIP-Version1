<?php
require_once 'config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get database connection
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Validate and sanitize input data
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $contact = sanitizeInput($_POST['contact'] ?? '');
    $birthday = $_POST['birthday'] ?? '';
    $address = sanitizeInput($_POST['address'] ?? '');
    $school = sanitizeInput($_POST['school'] ?? '');
    $program = sanitizeInput($_POST['program'] ?? '');
    $school_address = sanitizeInput($_POST['school_address'] ?? '');
    $ojt_hours = (int)($_POST['ojt_hours'] ?? 0);
    $days = $_POST['days'] ?? [];

    // Validation
    $errors = [];
    
    if (empty($name)) $errors[] = 'Full name is required';
    if (!isValidEmail($email)) $errors[] = 'Valid email is required';
    if (!isValidPhone($contact)) $errors[] = 'Valid contact number is required';
    if (empty($birthday)) $errors[] = 'Birthday is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($school)) $errors[] = 'School name is required';
    if (empty($program)) $errors[] = 'College program is required';
    if (empty($school_address)) $errors[] = 'School address is required';
    if ($ojt_hours < 1) $errors[] = 'Valid OJT hours required';
    if (empty($days)) $errors[] = 'At least one available day must be selected';

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM incoming_interns WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Email address is already registered';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Handle file uploads
    $fileUploads = [
        'cv' => ['required' => true, 'dir' => 'cv/', 'types' => ['pdf']],
        'picture' => ['required' => true, 'dir' => 'pictures/', 'types' => ['jpg', 'jpeg', 'png']],
        'endorsement' => ['required' => false, 'dir' => 'endorsements/', 'types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']],
        'moa' => ['required' => false, 'dir' => 'moa/', 'types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']]
    ];

    $uploadedFiles = [];
    
    foreach ($fileUploads as $fieldName => $config) {
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fieldName];
            
            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                throw new Exception("File {$fieldName} is too large. Maximum size is 10MB.");
            }
            
            // Validate file type
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $config['types'])) {
                throw new Exception("Invalid file type for {$fieldName}. Allowed types: " . implode(', ', $config['types']));
            }
            
            // Generate unique filename and move file
            $uniqueFilename = generateUniqueFilename($file['name']);
            $uploadPath = UPLOAD_DIR . $config['dir'] . $uniqueFilename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $uploadedFiles[$fieldName] = [
                    'filename' => $file['name'],
                    'filepath' => $uploadPath
                ];
            } else {
                throw new Exception("Failed to upload {$fieldName}");
            }
        } elseif ($config['required']) {
            throw new Exception(ucfirst($fieldName) . " is required");
        }
    }

    // Insert into database
    $sql = "INSERT INTO incoming_interns (
        name, email, contact, birthday, address, school, program, school_address, 
        ojt_hours, available_days, cv_filename, cv_filepath, picture_filename, 
        picture_filepath, endorsement_filename, endorsement_filepath, 
        moa_filename, moa_filepath, terms_accepted
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $name,
        $email,
        $contact,
        $birthday,
        $address,
        $school,
        $program,
        $school_address,
        $ojt_hours,
        json_encode($days),
        $uploadedFiles['cv']['filename'] ?? null,
        $uploadedFiles['cv']['filepath'] ?? null,
        $uploadedFiles['picture']['filename'] ?? null,
        $uploadedFiles['picture']['filepath'] ?? null,
        $uploadedFiles['endorsement']['filename'] ?? null,
        $uploadedFiles['endorsement']['filepath'] ?? null,
        $uploadedFiles['moa']['filename'] ?? null,
        $uploadedFiles['moa']['filepath'] ?? null,
        true // terms_accepted
    ]);

    if ($result) {
        // Send success response
        echo json_encode([
            'success' => true, 
            'message' => 'Registration submitted successfully! We will contact you soon.',
            'registration_id' => $pdo->lastInsertId()
        ]);
        
        // Optional: Send email notification (you can implement this later)
        // sendNotificationEmail($email, $name);
        
    } else {
        throw new Exception('Failed to save registration');
    }

} catch (Exception $e) {
    // Clean up uploaded files if database insert fails
    if (isset($uploadedFiles)) {
        foreach ($uploadedFiles as $fileInfo) {
            if (file_exists($fileInfo['filepath'])) {
                unlink($fileInfo['filepath']);
            }
        }
    }
    
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>