<?php
// models/Document.php

class Document extends BaseModel {
    protected $table = 'documents';
    protected $primaryKey = 'document_id';
    protected $fillable = [
        'personal_id', 'document_type', 'original_filename', 'stored_filename',
        'file_path', 'file_size', 'mime_type', 'uploaded_by',
        'verification_status', 'verification_notes', 'is_required'
    ];
    
    public function findByPersonalId(int $personalId): array {
        $sql = "SELECT * FROM {$this->table} WHERE personal_id = ? ORDER BY upload_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personalId]);
        return $stmt->fetchAll();
    }
    
    public function updateVerificationStatus(int $documentId, string $status, ?string $notes = null): bool {
        $data = ['verification_status' => $status];
        if ($notes !== null) {
            $data['verification_notes'] = $notes;
        }
        return $this->update($documentId, $data);
    }
    
    public function getDocumentsByType(string $type): array {
        $sql = "SELECT * FROM {$this->table} WHERE document_type = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    
    /**
     * FIXED: Delete document record and S3 file
     */
    public function deleteDocumentAndFile(int $documentId): bool {
        $document = $this->find($documentId);
        if (!$document) {
            return false;
        }

        // Delete from database first
        if ($this->delete($documentId)) {
            // Try to delete from S3 if S3FileUploadHelper is available
            if (class_exists('S3FileUploadHelper')) {
                // file_path contains the S3 key
                $s3Key = $document['file_path'];
                if ($s3Key) {
                    $deleted = S3FileUploadHelper::deleteFile($s3Key);
                    if (!$deleted) {
                        error_log("Failed to delete S3 file: " . $s3Key);
                        // Don't fail the operation if S3 delete fails
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Get all documents for a specific application/personal ID
     */
    public function getApplicationDocuments($personalId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    document_id,
                    document_type,
                    original_filename,
                    stored_filename,
                    file_path,
                    file_size,
                    mime_type,
                    upload_date,
                    verification_status,
                    verification_notes,
                    is_required
                FROM {$this->table} 
                WHERE personal_id = ?
                ORDER BY document_type, upload_date DESC
            ");
            
            $stmt->execute([$personalId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error getting application documents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Upload and store document information
     * NOTE: This method should primarily be used for database operations.
     * For actual file uploads, use S3FileUploadHelper::uploadApplicationFiles()
     */
    public function uploadDocument($personalId, $documentType, $filename, $filePath, $fileSize, $mimeType, $uploadedBy, $isRequired = false) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (
                    personal_id, 
                    document_type, 
                    original_filename, 
                    stored_filename,
                    file_path, 
                    file_size, 
                    mime_type, 
                    uploaded_by,
                    is_required,
                    upload_date,
                    verification_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
            ");
            
            return $stmt->execute([
                $personalId,
                $documentType,
                $filename,
                basename($filePath),
                $filePath,
                $fileSize,
                $mimeType,
                $uploadedBy,
                $isRequired ? 1 : 0
            ]);
            
        } catch (PDOException $e) {
            error_log("Error uploading document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if required documents are uploaded
     */
    public function checkRequiredDocuments($personalId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    document_type,
                    COUNT(*) as count,
                    MAX(verification_status) as status
                FROM {$this->table} 
                WHERE personal_id = ? AND is_required = 1
                GROUP BY document_type
            ");
            
            $stmt->execute([$personalId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error checking required documents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get document statistics
     */
    public function getDocumentStats($personalId = null) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_documents,
                    COUNT(CASE WHEN verification_status = 'verified' THEN 1 END) as verified_documents,
                    COUNT(CASE WHEN verification_status = 'pending' THEN 1 END) as pending_documents,
                    COUNT(CASE WHEN verification_status = 'rejected' THEN 1 END) as rejected_documents,
                    COUNT(CASE WHEN is_required = 1 THEN 1 END) as required_documents
                FROM {$this->table}
            ";
            
            $params = [];
            if ($personalId) {
                $sql .= " WHERE personal_id = ?";
                $params[] = $personalId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Error getting document stats: " . $e->getMessage());
            return [
                'total_documents' => 0,
                'verified_documents' => 0,
                'pending_documents' => 0,
                'rejected_documents' => 0,
                'required_documents' => 0
            ];
        }
    }

    /**
     * Clean up orphaned S3 files (utility method for maintenance)
     */
    public function cleanupOrphanedS3Files() {
        try {
            // Get all S3 keys from database
            $stmt = $this->pdo->prepare("SELECT file_path FROM {$this->table} WHERE file_path IS NOT NULL AND file_path != ''");
            $stmt->execute();
            $dbKeys = array_column($stmt->fetchAll(), 'file_path');
            
            // Note: You would need to implement S3 bucket listing in S3FileUploadHelper
            // to compare with actual S3 contents and clean up orphaned files
            error_log("Database contains " . count($dbKeys) . " S3 file references");
            
            return true;
        } catch (PDOException $e) {
            error_log("Error in cleanup: " . $e->getMessage());
            return false;
        }
    }
}
?>
