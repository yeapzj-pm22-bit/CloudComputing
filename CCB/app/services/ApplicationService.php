<?php
// services/ApplicationService.php

class ApplicationService {
    private $application;
    private $academic;
    private $educational;
    private $document;
    private $notification;
    private $statusHistory;
    
    public function __construct() {
        $this->application = new Application();
        
        // Only instantiate classes that exist
        if (class_exists('Academic')) {
            $this->academic = new Academic();
        }
        if (class_exists('Educational')) {
            $this->educational = new Educational();
        }
        
        $this->document = new Document();
        $this->notification = new Notification();
        
        if (class_exists('StatusHistory')) {
            $this->statusHistory = new StatusHistory();
        }
    }
    
    public function createCompleteApplication(array $data): array {
        $db = DatabaseConnection::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Create personal record
            $personalId = $this->application->create([
                'user_id' => $data['user_id'] ?? null,
                'gender' => $data['gender'],
                'nationality' => $data['nationality'],
                'address' => $data['address'],
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'received' => date('Y-m-d'),
                'status' => 'submitted'
            ]);
            
            // Create academic record if Academic class exists
            if ($this->academic) {
                $this->academic->create([
                    'personal_id' => $personalId,
                    'program' => $data['program'],
                    'program_level' => $data['program_level'],
                    'program_category' => $data['program_category'] ?? null,
                    'enrollment_type' => $data['enrollment_type'],
                    'start_term' => $data['start_term'],
                    'expected_graduation_year' => $data['expected_graduation_year'] ?? null,
                    'preferred_campus' => $data['preferred_campus'] ?? null,
                    'scholarship_applied' => $data['scholarship_applied'] ?? false,
                    'scholarship_type' => $data['scholarship_type'] ?? null
                ]);
            }
            
            // Create educational record if Educational class exists
            if ($this->educational) {
                $this->educational->create([
                    'personal_id' => $personalId,
                    'education_level' => $data['education_level'],
                    'institution_name' => $data['institution_name'],
                    'graduation_year' => $data['graduation_year'],
                    'grade_type' => $data['grade_type'] ?? null,
                    'grade_value' => $data['grade_value'] ?? null,
                    'subjects_count' => $data['subjects_count'] ?? null,
                    'certificate_number' => $data['certificate_number'] ?? null
                ]);
            }
            
            // Handle document uploads if provided
            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $docType => $file) {
                    if (class_exists('FileUploadHelper')) {
                        $uploadResult = FileUploadHelper::upload($file);
                        if (isset($uploadResult['success'])) {
                            $this->document->create([
                                'personal_id' => $personalId,
                                'document_type' => $docType,
                                'original_filename' => $uploadResult['original_name'],
                                'stored_filename' => $uploadResult['stored_name'],
                                'file_path' => $uploadResult['file_path'],
                                'file_size' => $uploadResult['file_size'],
                                'mime_type' => $uploadResult['mime_type'],
                                'uploaded_by' => $data['user_id'] ?? null
                            ]);
                        }
                    }
                }
            }
            
            // Create notification if user is logged in
            if (!empty($data['user_id'])) {
                $applicationData = $this->application->find($personalId);
                $this->notification->createNotification(
                    $data['user_id'],
                    'Application Submitted',
                    "Your application {$applicationData['application_number']} has been successfully submitted and is under review.",
                    'success',
                    $personalId
                );
            }
            
            $db->commit();
            
            return [
                'success' => true,
                'personal_id' => $personalId,
                'application_number' => $this->application->find($personalId)['application_number']
            ];
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Application creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create application: ' . $e->getMessage()
            ];
        }
    }
    
    // FIXED: Added explicit nullable type for $notes parameter
    public function updateApplicationStatus(int $personalId, string $status, int $reviewedBy, ?string $notes = null): bool {
        $application = $this->application->find($personalId);
        if (!$application) {
            return false;
        }
        
        $oldStatus = $application['status'];
        $result = $this->application->updateStatus($personalId, $status, $reviewedBy, $notes);
        
        if ($result) {
            // Log status change if StatusHistory class exists
            if ($this->statusHistory) {
                $this->statusHistory->logStatusChange($personalId, $oldStatus, $status, $reviewedBy, $notes);
            }
            
            // Create notification for user if exists
            if ($application['user_id']) {
                $statusMessages = [
                    'under-review' => 'Your application is now under review.',
                    'interview-scheduled' => 'An interview has been scheduled for your application.',
                    'approved' => 'Congratulations! Your application has been approved.',
                    'rejected' => 'Unfortunately, your application has been rejected.',
                    'waitlisted' => 'Your application has been placed on the waitlist.'
                ];
                
                $message = $statusMessages[$status] ?? "Your application status has been updated to: $status";
                
                $this->notification->createNotification(
                    $application['user_id'],
                    'Application Status Update',
                    $message,
                    in_array($status, ['approved']) ? 'success' : 'info',
                    $personalId
                );
            }
        }
        
        return $result;
    }
    
    public function deleteCompleteApplication(int $personalId): bool {
        $db = DatabaseConnection::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Delete documents from filesystem
            $documents = $this->document->findByPersonalId($personalId);
            foreach ($documents as $doc) {
                if (class_exists('FileUploadHelper')) {
                    FileUploadHelper::delete($doc['file_path']);
                }
            }
            
            // Delete from database (cascade will handle related records)
            $result = $this->application->delete($personalId);
            
            $db->commit();
            return $result;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Application deletion failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Additional helper methods
    public function getApplicationById(int $personalId): ?array {
        return $this->application->getFullApplicationData($personalId);
    }
    
    public function getApplicationsByUser(int $userId): array {
        return $this->application->getApplicationsByUser($userId);
    }
    
    public function getAllApplications(array $filters = []): array {
        return $this->application->getAllApplicationsWithDetails($filters);
    }

    /**
     * Get application statistics
     */
    public function getApplicationStats(): array {
        try {
            $stats = $this->application->getStatusCounts();
            $result = [
                'total' => 0,
                'submitted' => 0,
                'under-review' => 0,
                'approved' => 0,
                'rejected' => 0,
                'waitlisted' => 0
            ];

            foreach ($stats as $stat) {
                $status = $stat['status'] ?? 'unknown';
                $count = (int)($stat['count'] ?? 0);
                
                $result['total'] += $count;
                if (isset($result[$status])) {
                    $result[$status] = $count;
                }
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error getting application stats: " . $e->getMessage());
            return [
                'total' => 0,
                'submitted' => 0,
                'under-review' => 0,
                'approved' => 0,
                'rejected' => 0,
                'waitlisted' => 0
            ];
        }
    }

    /**
     * Validate application data before submission
     */
    public function validateApplicationData(array $data): array {
        $errors = [];

        // Required fields validation
        $requiredFields = ['gender', 'nationality', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        // Email validation if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Phone validation if provided
        if (!empty($data['emergency_contact_phone']) && !preg_match('/^[\d\s\+\-\(\)]{10,20}$/', $data['emergency_contact_phone'])) {
            $errors['emergency_contact_phone'] = 'Invalid phone number format';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Process bulk status updates
     */
    public function bulkUpdateStatus(array $applicationIds, string $status, int $reviewedBy, ?string $notes = null): array {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($applicationIds as $id) {
            try {
                if ($this->updateApplicationStatus($id, $status, $reviewedBy, $notes)) {
                    $results[$id] = 'success';
                    $successful++;
                } else {
                    $results[$id] = 'failed';
                    $failed++;
                }
            } catch (Exception $e) {
                $results[$id] = 'error: ' . $e->getMessage();
                $failed++;
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'details' => $results
        ];
    }

    /**
     * Get applications that need review
     */
    public function getApplicationsNeedingReview(int $limit = 50): array {
        return $this->getAllApplications([
            'status' => 'submitted',
            'limit' => $limit
        ]);
    }

    /**
     * Get overdue applications
     */
    public function getOverdueApplications(int $daysOverdue = 30): array {
        $overdueDate = date('Y-m-d', strtotime("-{$daysOverdue} days"));
        return $this->getAllApplications([
            'status' => 'under-review',
            'date_to' => $overdueDate
        ]);
    }
}
?>