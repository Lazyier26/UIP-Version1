<?php
// Classes/FileUploadHandler.php
class FileUploadHandler {
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    public function uploadFile($file, $uploadDir, $allowedTypes = []) {
        // Validate file
        $this->validateFile($file, $allowedTypes);
        
        // Generate unique filename
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to upload file: ' . $file['name']);
        }
        
        return [
            'path' => $filePath,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'mime_type' => $file['type']
        ];
    }
    
    private function validateFile($file, $allowedTypes) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum allowed size of 10MB');
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedTypes)) {
                throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedTypes));
            }
        }
        
        // Additional security checks
        $this->performSecurityChecks($file);
    }
    
    private function performSecurityChecks($file) {
        // Check if it's actually an uploaded file
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }
        
        // Basic file content validation
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        // Define allowed MIME types
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('File type validation failed');
        }
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
}
?>