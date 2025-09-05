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
    
    // FIXED: Added explicit nullable type for $notes parameter
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
    
    public function deleteDocumentAndFile(int $documentId): bool {
        $document = $this->find($documentId);
        if ($document && $this->delete($documentId)) {
            // Only delete file if FileUploadHelper class exists
            if (class_exists('FileUploadHelper')) {
                FileUploadHelper::delete($document['file_path']);
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
}
?>