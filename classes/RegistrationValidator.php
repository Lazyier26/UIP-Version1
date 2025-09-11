<?php
// Classes/RegistrationValidator.php
class RegistrationValidator {
    
    public function validateAndSanitize($data) {
        $errors = [];
        $sanitized = [];
        
        // Validate full name
        if (empty($data['name'])) {
            $errors[] = 'Full name is required';
        } else {
            $sanitized['name'] = $this->sanitizeString($data['name']);
            if (strlen($sanitized['name']) < 2) {
                $errors[] = 'Full name must be at least 2 characters long';
            }
        }
        
        // Validate email
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } else {
            $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $errors[] = 'Please enter a valid email address';
            } else {
                $sanitized['email'] = strtolower($email);
            }
        }
        
        // Validate contact number
        if (empty($data['contact'])) {
            $errors[] = 'Contact number is required';
        } else {
            $contact = preg_replace('/[^0-9+\-\s()]/', '', $data['contact']);
            if (strlen($contact) < 7) {
                $errors[] = 'Please enter a valid contact number';
            } else {
                $sanitized['contact'] = $contact;
            }
        }
        
        // Validate birthday
        if (empty($data['birthday'])) {
            $errors[] = 'Birthday is required';
        } else {
            $birthday = DateTime::createFromFormat('Y-m-d', $data['birthday']);
            if (!$birthday) {
                $errors[] = 'Please enter a valid birthday';
            } else {
                $now = new DateTime();
                $age = $now->diff($birthday)->y;
                if ($age < 16 || $age > 100) {
                    $errors[] = 'Age must be between 16 and 100 years';
                }
                $sanitized['birthday'] = $data['birthday'];
            }
        }
        
        // Validate address
        if (empty($data['address'])) {
            $errors[] = 'Complete address is required';
        } else {
            $sanitized['address'] = $this->sanitizeString($data['address']);
            if (strlen($sanitized['address']) < 10) {
                $errors[] = 'Please provide a complete address';
            }
        }
        
        // Validate school
        if (empty($data['school'])) {
            $errors[] = 'University name is required';
        } else {
            $sanitized['school'] = $this->sanitizeString($data['school']);
        }
        
        // Validate program
        if (empty($data['program'])) {
            $errors[] = 'College program is required';
        } else {
            $sanitized['program'] = $this->sanitizeString($data['program']);
        }
        
        // Validate school address
        if (empty($data['school_address'])) {
            $errors[] = 'University address is required';
        } else {
            $sanitized['school_address'] = $this->sanitizeString($data['school_address']);
        }
        
        // Validate OJT hours
        if (empty($data['ojt_hours'])) {
            $errors[] = 'Total OJT hours is required';
        } else {
            $hours = filter_var($data['ojt_hours'], FILTER_VALIDATE_INT);
            if ($hours === false || $hours <= 0) {
                $errors[] = 'OJT hours must be a positive number';
            } else if ($hours > 2000) {
                $errors[] = 'OJT hours seems too high. Please verify.';
            } else {
                $sanitized['ojt_hours'] = $hours;
            }
        }
        
        // Validate available days
        if (empty($data['days']) || !is_array($data['days'])) {
            $errors[] = 'Please select at least one available day';
        } else {
            $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $selectedDays = array_intersect($data['days'], $validDays);
            if (empty($selectedDays)) {
                $errors[] = 'Please select valid available days';
            } else {
                $sanitized['days'] = $selectedDays;
            }
        }
        
        if (!empty($errors)) {
            throw new Exception('Validation failed: ' . implode(', ', $errors));
        }
        
        return $sanitized;
    }
    
    public function validateRequiredFiles($files) {
        $errors = [];
        
        // Validate CV upload (required)
        if (empty($files['cv']) || $files['cv']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'CV/Resume upload is required';
        } else if ($files['cv']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error uploading CV/Resume';
        }
        
        // Validate picture upload (required)
        if (empty($files['picture']) || $files['picture']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = '2x2 Picture upload is required';
        } else if ($files['picture']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error uploading picture';
        }
        
        if (!empty($errors)) {
            throw new Exception('File validation failed: ' . implode(', ', $errors));
        }
    }
    
    private function sanitizeString($input) {
        // Remove HTML tags and trim whitespace
        $sanitized = trim(strip_tags($input));
        
        // Convert special characters to HTML entities
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        return $sanitized;
    }
    
    public function checkEmailExists($email, $db) {
        $sql = "SELECT id FROM registrations WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        return $stmt->fetch() !== false;
    }
}
?>