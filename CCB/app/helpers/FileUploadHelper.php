<?php
// helpers/FileUploadHelper.php - Create this new file

class FileUploadHelper {
    
    const UPLOAD_DIR = '../uploads/documents/';
    const MAX_FILE_SIZE = 5242880; // 5MB
    const MAX_PHOTO_SIZE = 2097152; // 2MB
    
    const ALLOWED_TYPES = [
        'transcript' => ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'certificate' => ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'identity' => ['application/pdf', 'image/jpeg', 'image/png'],
        'photo' => ['image/jpeg', 'image/png'],
        'personal_statement' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'recommendation_letter' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    ];
    
    /**
     * Initialize upload directory
     */
    public static function init() {
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }
    }
    
    /**
     * Upload multiple files for an application
     */
    public static function uploadApplicationFiles($personalId, $files, $userId) {
        self::init();
        
        $results = [];
        $errors = [];
        
        foreach ($files as $fieldName => $file) {
            if (is_array($file['name'])) {
                // Handle multiple files (shouldn't happen in your case, but good to handle)
                continue;
            }
            
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip if no file uploaded
            }
            
            $result = self::uploadSingleFile($personalId, $fieldName, $file, $userId);
            
            if ($result['success']) {
                $results[] = $result;
            } else {
                $errors[] = "Failed to upload {$fieldName}: " . $result['message'];
            }
        }
        
        return [
            'success' => empty($errors),
            'uploaded' => $results,
            'errors' => $errors
        ];
    }
    
    /**
     * Upload a single file
     */
    private static function uploadSingleFile($personalId, $documentType, $file, $userId) {
        try {
            // Validate file
            $validation = self::validateFile($file, $documentType);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
            
            // Generate unique filename
            $originalName = $file['name'];
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $storedName = $personalId . '_' . $documentType . '_' . time() . '.' . $extension;
            $filePath = self::UPLOAD_DIR . $storedName;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }
            
            // Save to database
            $documentModel = new Document();
            $documentId = $documentModel->create([
                'personal_id' => $personalId,
                'document_type' => $documentType,
                'original_filename' => $originalName,
                'stored_filename' => $storedName,
                'file_path' => $filePath,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'uploaded_by' => $userId,
                'verification_status' => 'pending',
                'is_required' => in_array($documentType, ['transcript', 'certificate', 'identity', 'photo'])
            ]);
            
            return [
                'success' => true,
                'document_id' => $documentId,
                'stored_filename' => $storedName,
                'original_filename' => $originalName
            ];
            
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private static function validateFile($file, $documentType) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error: ' . self::getUploadErrorMessage($file['error'])];
        }
        
        // Check file size
        $maxSize = ($documentType === 'photo') ? self::MAX_PHOTO_SIZE : self::MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            return ['valid' => false, 'message' => "File too large. Maximum size: {$maxSizeMB}MB"];
        }
        
        // Check MIME type
        if (isset(self::ALLOWED_TYPES[$documentType])) {
            if (!in_array($file['type'], self::ALLOWED_TYPES[$documentType])) {
                return ['valid' => false, 'message' => 'Invalid file type for ' . $documentType];
            }
        }
        
        // Additional security check - verify file content matches extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($actualMimeType !== $file['type']) {
            return ['valid' => false, 'message' => 'File content does not match file type'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get human-readable upload error message
     */
    private static function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File is too large';
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
    
    /**
     * Delete a file
     */
    public static function deleteFile($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true;
    }
    
    /**
     * Get file URL for download/viewing
     */
    public static function getFileUrl($storedFilename) {
        return '../uploads/documents/' . $storedFilename;
    }
    
    /**
     * Serve file for download (with access control)
     */
    public static function serveFile($documentId, $userId) {
    $documentModel = new Document();
    $document = $documentModel->find($documentId);
    
    if (!$document) {
        error_log("Document not found: $documentId");
        http_response_code(404);
        exit('File not found');
    }
    
    // Get user information to check account type
    $userModel = new User();
    $currentUser = $userModel->getUserById($userId);
    
    if (!$currentUser) {
        error_log("User not found: $userId");
        http_response_code(403);
        exit('Access denied');
    }
    
    $isAdmin = ($currentUser['account_type'] === 'Admin');
    
    // Check if user has access to this document
    if (!$isAdmin) {
        // For students, check if they own the application
        $applicationModel = new Application();
        $application = $applicationModel->find($document['personal_id']);
        
        if (!$application || $application['user_id'] != $userId) {
            error_log("Access denied for document $documentId, user $userId (student access)");
            http_response_code(403);
            exit('Access denied');
        }
    } else {
        // For admins, log the access for audit purposes
        error_log("Admin access granted for document $documentId by user $userId");
    }
    
    $filePath = $document['file_path'];
    if (!file_exists($filePath)) {
        error_log("File not found on disk: $filePath");
        http_response_code(404);
        exit('File not found on disk');
    }
    
    // Check if download is requested
    $download = isset($_GET['download']) && $_GET['download'] == '1';
    
    error_log("Serving document $documentId: " . $document['original_filename'] . 
              " (MIME: " . $document['mime_type'] . ", Download: " . ($download ? 'yes' : 'no') . 
              ", User: " . ($isAdmin ? 'Admin' : 'Student') . ")");
    
    // Clear any output buffers to prevent corruption
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if ($download) {
        // Force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($document['original_filename']) . '"');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        error_log("Forcing download for document $documentId");
    } else {
        // For preview/inline display
        header('Content-Type: ' . $document['mime_type']);
        
        // CRITICAL: This header allows PDF to display in iframe (fixes white screen)
        header('X-Frame-Options: SAMEORIGIN');
        
        // Allow inline display
        header('Content-Disposition: inline; filename="' . addslashes($document['original_filename']) . '"');
        
        if ($document['mime_type'] === 'application/pdf') {
            // PDF-specific headers
            header('Accept-Ranges: bytes'); // Important for PDF streaming
            header('Cache-Control: private, no-cache, no-store, must-revalidate'); // Prevent caching issues during development
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Additional PDF compatibility headers
            header('X-Content-Type-Options: nosniff');
            
            error_log("Set PDF-specific headers for document $documentId");
        } else {
            // For images and other files
            header('Cache-Control: private, max-age=3600');
            header('X-Content-Type-Options: nosniff');
            
            error_log("Set standard preview headers for document $documentId");
        }
    }
    
    // Set content length
    header('Content-Length: ' . filesize($filePath));
    
    // Output the file
    if (readfile($filePath) === false) {
        error_log("Failed to read file: $filePath");
        http_response_code(500);
        exit('Failed to read file');
    }
    
    error_log("Successfully served document $documentId");
    exit;
}
}
?>