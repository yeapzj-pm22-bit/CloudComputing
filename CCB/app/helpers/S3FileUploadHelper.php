<?php
// helpers/S3FileUploadHelper.php

require_once __DIR__ . '/../vendor/autoload.php';


use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3FileUploadHelper {
    
    private static $s3Client = null;
    private static $bucketName = 'university-portal-documents-prod';
    private static $region = 'us-east-1'; // Update if different
    
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
     * Initialize S3 client
     */
    private static function getS3Client() {
        if (self::$s3Client === null) {
            try {
                self::$s3Client = new S3Client([
                    'version' => 'latest',
                    'region' => self::$region,
                    // EC2 instance will use IAM role automatically
                ]);
            } catch (Exception $e) {
                error_log("S3 Client initialization failed: " . $e->getMessage());
                throw new Exception("Storage service unavailable");
            }
        }
        return self::$s3Client;
    }
    
    /**
     * Generate S3 key maintaining your existing naming convention
     */
    private static function generateS3Key($personalId, $documentType, $extension) {
        $timestamp = time();
        $sanitizedType = preg_replace('/[^a-zA-Z0-9_-]/', '', $documentType);
        return "documents/{$personalId}/{$personalId}_{$sanitizedType}_{$timestamp}.{$extension}";
    }
    
    /**
     * Upload multiple files - SAME interface as original FileUploadHelper
     */
    public static function uploadApplicationFiles($personalId, $files, $userId) {
        $results = [];
        $errors = [];
        
        foreach ($files as $fieldName => $file) {
            if (is_array($file['name'])) {
                continue;
            }
            
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
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
     * Upload single file to S3
     */
    private static function uploadSingleFile($personalId, $documentType, $file, $userId) {
        try {
            // Validate file - same validation as original
            $validation = self::validateFile($file, $documentType);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
            
            $s3 = self::getS3Client();
            
            // Generate file info
            $originalName = $file['name'];
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $s3Key = self::generateS3Key($personalId, $documentType, $extension);
            $storedFilename = basename($s3Key); // For compatibility with existing code
            
            // Upload to S3
            $result = $s3->putObject([
                'Bucket' => self::$bucketName,
                'Key' => $s3Key,
                'SourceFile' => $file['tmp_name'],
                'ContentType' => $file['type'],
                'ServerSideEncryption' => 'AES256',
                'Metadata' => [
                    'original-filename' => $originalName,
                    'document-type' => $documentType,
                    'uploaded-by' => (string)$userId,
                    'personal-id' => (string)$personalId,
                    'upload-timestamp' => (string)time()
                ],
                'ACL' => 'private'
            ]);
            
            // Save to database - SAME format as original
            $documentModel = new Document();
            $documentId = $documentModel->create([
                'personal_id' => $personalId,
                'document_type' => $documentType,
                'original_filename' => $originalName,
                'stored_filename' => $storedFilename,
                'file_path' => $s3Key, // Store S3 key in file_path for compatibility
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'uploaded_by' => $userId,
                'verification_status' => 'pending',
                'is_required' => in_array($documentType, ['transcript', 'certificate', 'identity', 'photo']),
            ]);
            
            return [
                'success' => true,
                'document_id' => $documentId,
                'stored_filename' => $storedFilename,
                'original_filename' => $originalName,
                's3_key' => $s3Key
            ];
            
        } catch (AwsException $e) { 
            error_log("S3 upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Storage service error'];
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Serve file - SAME interface as original but uses S3 presigned URLs
     */
    public static function serveFile($documentId, $userId, $forceDownload = false) {
    $documentModel = new Document();
    $document = $documentModel->find($documentId);
    
    if (!$document) {
        error_log("Document not found: $documentId");
        http_response_code(404);
        exit('File not found');
    }
    
    // Access control logic
    $userModel = new User();
    $currentUser = $userModel->getUserById($userId);
    
    if (!$currentUser) {
        error_log("User not found: $userId");
        http_response_code(403);
        exit('Access denied');
    }
    
    $isAdmin = ($currentUser['account_type'] === 'Admin' || $currentUser['account_type'] === 'Staff');
    
    // Non-admin users can only access their own documents
    if (!$isAdmin) {
        $applicationModel = new Application();
        $application = $applicationModel->find($document['personal_id']);
        
        if (!$application || $application['user_id'] != $userId) {
            error_log("Access denied for document $documentId, user $userId");
            http_response_code(403);
            exit('Access denied');
        }
    }
    
    // Generate presigned URL for secure access
    try {
        $s3 = self::getS3Client();
        
        // DIRECT STREAMING (bypasses presigned URL issues)
        $result = $s3->getObject([
            'Bucket' => self::$bucketName,
            'Key' => $document['file_path']
        ]);
        
        // Clean any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set appropriate headers
        header('Content-Type: ' . ($document['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . strlen($result['Body']));
        header('Content-Disposition: attachment; filename="' . $document['original_filename'] . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Output file content directly
        echo $result['Body'];
        exit;
        
    } catch (Exception $e) {
        error_log("S3 download error: " . $e->getMessage());
        http_response_code(500);
        echo "Download failed: " . $e->getMessage();
        exit;
    }
}
    
    /**
     * Generate secure presigned URL
     */
    public static function generatePresignedUrl($s3Key, $expirationMinutes = 15) {
        try {
            $s3 = self::getS3Client();
            
            $command = $s3->getCommand('GetObject', [
                'Bucket' => self::$bucketName,
                'Key' => $s3Key
            ]);
            
            $request = $s3->createPresignedRequest($command, "+{$expirationMinutes} minutes");
            return (string) $request->getUri();
            
        } catch (Exception $e) {
            error_log("Presigned URL generation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete file from S3
     */
    public static function deleteFile($s3Key) {
        try {
            $s3 = self::getS3Client();
            
            $s3->deleteObject([
                'Bucket' => self::$bucketName,
                'Key' => $s3Key
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("S3 delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * SAME validation as original FileUploadHelper
     */
    private static function validateFile($file, $documentType) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error: ' . self::getUploadErrorMessage($file['error'])];
        }
        
        $maxSize = ($documentType === 'photo') ? self::MAX_PHOTO_SIZE : self::MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            return ['valid' => false, 'message' => "File too large. Maximum size: {$maxSizeMB}MB"];
        }
        
        if (isset(self::ALLOWED_TYPES[$documentType])) {
            if (!in_array($file['type'], self::ALLOWED_TYPES[$documentType])) {
                return ['valid' => false, 'message' => 'Invalid file type for ' . $documentType];
            }
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($actualMimeType !== $file['type']) {
            return ['valid' => false, 'message' => 'File content does not match file type'];
        }
        
        return ['valid' => true];
    }
    
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
}


