<?php
// models/Application.php

class Application extends BaseModel
{
    protected $table = 'personal';
    protected $primaryKey = 'personal_id';
    protected $fillable = [
        'user_id',
        'application_number',
        'gender',
        'nationality',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'status',
        'application_fee_paid',
        'application_fee_amount',
        'received',
        'reviewed_by',
        'reviewed_at',
        'notes'
    ];

    public function getFullApplicationData(int $personalId): ?array
    {
        $sql = "SELECT p.*, u.first_name, u.last_name, u.email, u.date_of_birth, u.phone_number, u.account_type,
                       a.program, a.program_level, a.program_category, a.enrollment_type, a.start_term,
                       a.expected_graduation_year, a.preferred_campus, a.scholarship_applied, a.scholarship_type,
                       e.education_level, e.institution_name, e.graduation_year, e.grade_type, e.grade_value,
                       e.subjects_count, e.certificate_number, e.verification_status as education_verification,
                       reviewer.first_name as reviewer_first_name, reviewer.last_name as reviewer_last_name
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                LEFT JOIN educational e ON p.personal_id = e.personal_id
                LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id
                WHERE p.personal_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personalId]);
        return $stmt->fetch() ?: null;
    }

    public function getAllApplicationsWithDetails(array $filters = []): array
    {
        $sql = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone_number,
                       a.program, a.enrollment_type, a.start_term,
                       reviewer.first_name as reviewer_first_name, reviewer.last_name as reviewer_last_name
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id";

        $whereConditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereConditions[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['program'])) {
            $whereConditions[] = "a.program LIKE ?";
            $params[] = "%{$filters['program']}%";
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "p.received >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "p.received <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR p.application_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // FIXED: Added explicit nullable type for $notes parameter
    public function updateStatus(int $personalId, string $status, int $reviewedBy, ?string $notes = null): bool
    {
        $data = [
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s')
        ];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        return $this->update($personalId, $data);
    }

    public function getApplicationsByUser(int $userId): array
    {
        $sql = "SELECT p.*, a.program, a.enrollment_type, a.start_term
                FROM personal p
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getStatusCounts(): array
    {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByApplicationNumber(string $applicationNumber): ?array
    {
        return $this->findBy('application_number', $applicationNumber);
    }

    /**
     * Get student statistics for dashboard
     */
    public function getStudentStats($userId)
    {
        try {
            // Get application stats
            $appStmt = $this->pdo->prepare("
            SELECT 
                COUNT(personal_id) as total_applications,
                COUNT(CASE WHEN status IN ('submitted', 'under-review', 'interview-scheduled') THEN 1 END) as pending_applications,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_applications
            FROM personal 
            WHERE user_id = ?
        ");
            $appStmt->execute([$userId]);
            $appStats = $appStmt->fetch();

            // Get document count separately to avoid LEFT JOIN issues
            $docStmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT d.document_id) as documents_count
            FROM personal p
            INNER JOIN documents d ON p.personal_id = d.personal_id
            WHERE p.user_id = ?
        ");
            $docStmt->execute([$userId]);
            $docStats = $docStmt->fetch();

            return [
                'total_applications' => $appStats['total_applications'] ?? 0,
                'pending_applications' => $appStats['pending_applications'] ?? 0,
                'approved_applications' => $appStats['approved_applications'] ?? 0,
                'documents_count' => $docStats['documents_count'] ?? 0
            ];
        } catch (PDOException $e) {
            error_log("Error getting student stats: " . $e->getMessage());
            return [
                'total_applications' => 0,
                'pending_applications' => 0,
                'approved_applications' => 0,
                'documents_count' => 0
            ];
        }
    }

    /**
     * Get applications for a student (with limit for recent applications)
     */
    public function getStudentApplications($userId, $limit = null)
    {
        try {
            $sql = "
                SELECT 
                    p.personal_id,
                    p.application_number,
                    p.status,
                    p.received,
                    p.created_at,
                    p.updated_at,
                    p.notes,
                    a.program,
                    a.program_level,
                    a.enrollment_type,
                    a.start_term,
                    a.expected_graduation_year,
                    a.scholarship_applied,
                    a.scholarship_type
                FROM personal p
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC
            ";

            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting student applications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new application
     */
    public function createApplication($userId, $data)
    {
        try {
            $this->pdo->beginTransaction();

            // Generate unique application number
            $applicationNumber = $this->generateApplicationNumber();

            // Insert personal information with auto-generated application number
            $personalData = [
                'user_id' => $userId,
                'application_number' => $applicationNumber,
                'gender' => $data['gender'] ?? '',
                'nationality' => $data['nationality'] ?? '',
                'address' => $data['address'] ?? '',
                'emergency_contact_name' => $data['emergency_contact_name'] ?? '',
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? '',
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? '',
                'status' => 'submitted',
                'received' => date('Y-m-d')
            ];

            $stmt = $this->pdo->prepare("
        INSERT INTO personal (
            user_id, application_number, gender, nationality, address, 
            emergency_contact_name, emergency_contact_phone, 
            emergency_contact_relationship, status, received, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

            $stmt->execute([
                $personalData['user_id'],
                $personalData['application_number'],
                $personalData['gender'],
                $personalData['nationality'],
                $personalData['address'],
                $personalData['emergency_contact_name'],
                $personalData['emergency_contact_phone'],
                $personalData['emergency_contact_relationship'],
                $personalData['status'],
                $personalData['received']
            ]);

            $personalId = $this->pdo->lastInsertId();

            // Insert academic information
            $stmt = $this->pdo->prepare("
        INSERT INTO academic (
            personal_id, program, program_level, enrollment_type,
            start_term, expected_graduation_year, preferred_campus,
            scholarship_applied, scholarship_type, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

            $stmt->execute([
                $personalId,
                $data['program'] ?? '',
                $data['program_level'] ?? '',
                $data['enrollment_type'] ?? '',
                $data['start_term'] ?? '',
                $data['expected_graduation_year'] ?? null,
                $data['preferred_campus'] ?? '',
                isset($data['scholarship_applied']) ? 1 : 0,
                $data['scholarship_type'] ?? null
            ]);

            // Insert educational background
            $stmt = $this->pdo->prepare("
        INSERT INTO educational (
            personal_id, education_level, institution_name,
            graduation_year, grade_type, grade_value,
            subjects_count, certificate_number, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

            $stmt->execute([
                $personalId,
                $data['education_level'] ?? '',
                $data['institution_name'] ?? '',
                $data['graduation_year'] ?? null,
                $data['grade_type'] ?? '',
                $data['grade_value'] ?? '',
                $data['subjects_count'] ?? null,
                $data['certificate_number'] ?? ''
            ]);

            // FIXED: Handle file uploads if files are provided
            $uploadResults = ['uploaded' => [], 'errors' => []];

            if (!empty($_FILES)) {
                // Filter out files that weren't uploaded
                $filesToUpload = array_filter($_FILES, function ($file) {
                    return $file['error'] !== UPLOAD_ERR_NO_FILE;
                });

                if (!empty($filesToUpload)) {
                    $uploadResults = FileUploadHelper::uploadApplicationFiles($personalId, $filesToUpload, $userId);

                    // If file uploads failed, we might want to continue anyway
                    // but log the errors
                    if (!empty($uploadResults['errors'])) {
                        foreach ($uploadResults['errors'] as $error) {
                            error_log("File upload error during application creation: " . $error);
                        }
                    }
                }
            }

            $this->pdo->commit();

            // Create notification
            $notification = new Notification();
            $notificationMessage = "Your application #{$applicationNumber} has been submitted successfully.";

            // Add file upload status to notification if there were issues
            if (!empty($uploadResults['errors'])) {
                $notificationMessage .= " Note: Some documents may need to be re-uploaded.";
            }

            $notification->createNotification(
                $userId,
                'Application Submitted',
                $notificationMessage,
                'success',
                $personalId
            );

            return [
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $personalId,
                'application_number' => $applicationNumber,
                'documents_uploaded' => count($uploadResults['uploaded']),
                'upload_errors' => $uploadResults['errors']
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error creating application: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to submit application. Please try again.'
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error creating application: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to submit application: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get application details with documents and history
     */
    public function getApplicationDetails($applicationId, $userId)
    {
        try {
            $stmt = $this->pdo->prepare("
            SELECT 
                p.*,
                a.program, a.program_level, a.program_category, a.enrollment_type, 
                a.start_term, a.expected_graduation_year, a.preferred_campus, 
                a.scholarship_applied, a.scholarship_type,
                e.education_level, e.institution_name, e.graduation_year, 
                e.grade_type, e.grade_value, e.subjects_count, e.certificate_number,
                e.verification_status as education_verification,
                u.first_name, u.last_name, u.email, u.phone_number, u.date_of_birth
            FROM personal p
            LEFT JOIN academic a ON p.personal_id = a.personal_id
            LEFT JOIN educational e ON p.personal_id = e.personal_id
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE p.personal_id = ? AND p.user_id = ?
        ");

            $stmt->execute([$applicationId, $userId]);
            $application = $stmt->fetch();

            if ($application) {
                // Get documents using Document model
                $documentModel = new Document();
                $application['documents'] = $documentModel->getApplicationDocuments($applicationId);

                // Get status history
                $application['status_history'] = $this->getStatusHistory($applicationId);

                // Debug logging
                error_log("Application {$applicationId} has " . count($application['documents']) . " documents");
            }

            return $application;
        } catch (PDOException $e) {
            error_log("Error getting application details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get documents for an application
     */
    public function getApplicationDocuments($applicationId)
    {
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
            FROM documents 
            WHERE personal_id = ?
            ORDER BY 
                CASE document_type
                    WHEN 'transcript' THEN 1
                    WHEN 'certificate' THEN 2
                    WHEN 'identity' THEN 3
                    WHEN 'photo' THEN 4
                    ELSE 5
                END,
                upload_date DESC
        ");

            $stmt->execute([$applicationId]);
            $documents = $stmt->fetchAll();

            // Debug log
            error_log("Fetched " . count($documents) . " documents for application " . $applicationId);

            return $documents;
        } catch (PDOException $e) {
            error_log("Error getting application documents: " . $e->getMessage());
            return [];
        }
    }

    public function getApplicationForEdit($applicationId, $userId)
    {
        try {
            // Get the main application data
            $application = $this->getApplicationDetails($applicationId, $userId);

            if (!$application) {
                return null;
            }

            // Check editability
            $editableStatuses = ['submitted', 'under-review'];
            $deletableStatuses = ['submitted'];

            return [
                'data' => $application,
                'editable' => in_array($application['status'], $editableStatuses),
                'deletable' => in_array($application['status'], $deletableStatuses),
                'status' => $application['status']
            ];
        } catch (Exception $e) {
            error_log("Error getting application for edit: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get status history for an application
     */
    public function getStatusHistory($applicationId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    ash.old_status,
                    ash.new_status,
                    ash.change_reason,
                    ash.change_date,
                    CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                FROM application_status_history ash
                LEFT JOIN users u ON ash.changed_by = u.user_id
                WHERE ash.personal_id = ?
                ORDER BY ash.change_date ASC
            ");

            $stmt->execute([$applicationId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting status history: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Update application data
     */
    public function updateApplication($applicationId, $userId, $data)
    {
        try {
            $this->pdo->beginTransaction();

            // Verify user owns this application
            $stmt = $this->pdo->prepare("
            SELECT personal_id FROM personal 
            WHERE personal_id = ? AND user_id = ?
        ");
            $stmt->execute([$applicationId, $userId]);

            if (!$stmt->fetch()) {
                throw new Exception("Application not found or access denied");
            }

            // Update personal information
            if (isset($data['gender']) || isset($data['nationality']) || isset($data['address'])) {
                $personalData = [];
                if (isset($data['gender'])) $personalData['gender'] = $data['gender'];
                if (isset($data['nationality'])) $personalData['nationality'] = $data['nationality'];
                if (isset($data['address'])) $personalData['address'] = $data['address'];
                if (isset($data['emergency_contact_name'])) $personalData['emergency_contact_name'] = $data['emergency_contact_name'];
                if (isset($data['emergency_contact_phone'])) $personalData['emergency_contact_phone'] = $data['emergency_contact_phone'];
                if (isset($data['emergency_contact_relationship'])) $personalData['emergency_contact_relationship'] = $data['emergency_contact_relationship'];

                if (!empty($personalData)) {
                    $this->update($applicationId, $personalData);
                }
            }

            // Update academic information
            if (isset($data['program']) || isset($data['program_level'])) {
                $academicModel = new Academic();
                $academicData = [];

                if (isset($data['program'])) $academicData['program'] = $data['program'];
                if (isset($data['program_level'])) $academicData['program_level'] = $data['program_level'];
                if (isset($data['enrollment_type'])) $academicData['enrollment_type'] = $data['enrollment_type'];
                if (isset($data['start_term'])) $academicData['start_term'] = $data['start_term'];
                if (isset($data['expected_graduation_year'])) $academicData['expected_graduation_year'] = $data['expected_graduation_year'];
                if (isset($data['preferred_campus'])) $academicData['preferred_campus'] = $data['preferred_campus'];
                if (isset($data['scholarship_applied'])) $academicData['scholarship_applied'] = $data['scholarship_applied'];
                if (isset($data['scholarship_type'])) $academicData['scholarship_type'] = $data['scholarship_type'];

                if (!empty($academicData)) {
                    $academicModel->updateByPersonalId($applicationId, $academicData);
                }
            }

            // Update educational information
            if (isset($data['education_level']) || isset($data['institution_name'])) {
                $educationalModel = new Educational();
                $educationalData = [];

                if (isset($data['education_level'])) $educationalData['education_level'] = $data['education_level'];
                if (isset($data['institution_name'])) $educationalData['institution_name'] = $data['institution_name'];
                if (isset($data['graduation_year'])) $educationalData['graduation_year'] = $data['graduation_year'];
                if (isset($data['grade_type'])) $educationalData['grade_type'] = $data['grade_type'];
                if (isset($data['grade_value'])) $educationalData['grade_value'] = $data['grade_value'];
                if (isset($data['subjects_count'])) $educationalData['subjects_count'] = $data['subjects_count'];
                if (isset($data['certificate_number'])) $educationalData['certificate_number'] = $data['certificate_number'];

                if (!empty($educationalData)) {
                    $educationalModel->updateByPersonalId($applicationId, $educationalData);
                }
            }

            $this->pdo->commit();

            // Create notification
            $notification = new Notification();
            $notification->createNotification(
                $userId,
                'Application Updated',
                'Your application has been updated successfully.',
                'success'
            );

            return [
                'success' => true,
                'message' => 'Application updated successfully'
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating application: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update application: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete application (soft delete by changing status)
     */
    public function deleteApplication($applicationId, $userId)
    {
        try {
            // Verify user owns this application and it's not already processed
            $stmt = $this->pdo->prepare("
            SELECT status FROM personal 
            WHERE personal_id = ? AND user_id = ?
        ");
            $stmt->execute([$applicationId, $userId]);
            $application = $stmt->fetch();

            if (!$application) {
                return [
                    'success' => false,
                    'message' => 'Application not found or access denied'
                ];
            }

            // Check if application can be deleted (only submitted applications)
            if ($application['status'] !== 'submitted') {
                return [
                    'success' => false,
                    'message' => 'Cannot delete application that is already being processed'
                ];
            }

            // Soft delete by updating status
            $result = $this->update($applicationId, [
                'status' => 'withdrawn',
                'notes' => 'Application withdrawn by student on ' . date('Y-m-d H:i:s')
            ]);

            if ($result) {
                // Create notification
                $notification = new Notification();
                $notification->createNotification(
                    $userId,
                    'Application Withdrawn',
                    'Your application has been withdrawn successfully.',
                    'warning'
                );

                return [
                    'success' => true,
                    'message' => 'Application withdrawn successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to withdraw application'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error deleting application: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }

    /**
     * Get available programs for dropdown
     */
    public function getAvailablePrograms()
    {
        try {
            // You might want to create a programs table in the future
            // For now, return some sample programs
            return [
                'Computer Science' => ['Bachelor', 'Master', 'PhD'],
                'Business Administration' => ['Bachelor', 'Master'],
                'Engineering' => ['Bachelor', 'Master', 'PhD'],
                'Medicine' => ['Bachelor', 'Master'],
                'Law' => ['Bachelor', 'Master'],
                'Education' => ['Bachelor', 'Master', 'PhD'],
                'Arts and Humanities' => ['Bachelor', 'Master', 'PhD']
            ];
        } catch (Exception $e) {
            error_log("Error getting programs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate unique application number
     */
    private function generateApplicationNumber()
    {
        $year = date('Y');
        $month = date('m');

        // Get the last application number for this month
        $stmt = $this->pdo->prepare("
        SELECT application_number 
        FROM personal 
        WHERE application_number LIKE ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
        $stmt->execute(["{$year}{$month}%"]);
        $lastApp = $stmt->fetch();

        if ($lastApp) {
            // Extract sequence number and increment
            $lastSequence = (int)substr($lastApp['application_number'], -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $year . $month . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }






















    // ========================================
    // NEW ADMIN METHODS
    // ========================================

    /**
     * Get dashboard statistics for admin
     */
    public function getAdminDashboardStats()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_applications,
                    COUNT(CASE WHEN status IN ('submitted', 'under-review', 'interview-scheduled') THEN 1 END) as pending_applications,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_applications,
                    COUNT(CASE WHEN status = 'enrolled' THEN 1 END) as enrolled_students
                FROM personal
                WHERE status != 'deleted'
            ");

            $stmt->execute();
            $stats = $stmt->fetch();

            return [
                'total_applications' => $stats['total_applications'] ?? 0,
                'pending_applications' => $stats['pending_applications'] ?? 0,
                'approved_applications' => $stats['approved_applications'] ?? 0,
                'enrolled_students' => $stats['enrolled_students'] ?? 0
            ];
        } catch (PDOException $e) {
            error_log("Error getting admin dashboard stats: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all applications for admin with optional pagination and filters
     */
    public function getAllApplicationsForAdmin($limit = null, $page = null, $perPage = 10, $filters = [])
{
    try {
        $whereConditions = ["p.status != 'deleted'"];
        $params = [];

        // Build filter conditions
        if (!empty($filters['status'])) {
            $whereConditions[] = "p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['program'])) {
            $whereConditions[] = "a.program LIKE ?";
            $params[] = "%{$filters['program']}%";
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "p.received >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "p.received <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR p.application_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Base query
        $sql = "
            SELECT 
                p.personal_id,
                p.application_number,
                p.status,
                p.received,
                p.created_at,
                p.updated_at,
                p.notes,
                u.first_name,
                u.last_name,
                u.email,
                u.phone_number,
                a.program,
                a.program_level,
                a.enrollment_type,
                a.start_term,
                reviewer.first_name as reviewer_first_name,
                reviewer.last_name as reviewer_last_name
            FROM personal p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN academic a ON p.personal_id = a.personal_id
            LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
        ";

        // FIXED: Check for simple limit (when page is null)
        if ($limit && $page === null) {
            // Simple limit for recent applications - return array directly
            $sql .= " LIMIT " . intval($limit);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();

            // Always return an array, never false for simple queries
            return is_array($result) ? $result : [];
            
        } elseif ($page !== null && $perPage) {
            // Paginated results
            $offset = ($page - 1) * $perPage;

            // Get total count
            $countSql = "
                SELECT COUNT(*) as total
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                WHERE {$whereClause}
            ";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];

            // Get paginated data
            $sql .= " LIMIT {$perPage} OFFSET {$offset}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            return [
                'data' => $data,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ];
        } else {
            // No pagination, return all
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Error getting applications for admin: " . $e->getMessage());
        if ($limit && $page === null) {
            return []; // Return empty array for simple queries
        }
        return false;
    }
}

    /**
     * Get detailed application information for admin view
     */
    public function getApplicationDetailsForAdmin($applicationId)
    {
        try {
            // Get application details
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    u.first_name, u.last_name, u.email, u.phone_number, u.date_of_birth,
                    a.program, a.program_level, a.program_category, a.enrollment_type, 
                    a.start_term, a.expected_graduation_year, a.preferred_campus, 
                    a.scholarship_applied, a.scholarship_type,
                    e.education_level, e.institution_name, e.graduation_year, 
                    e.grade_type, e.grade_value, e.subjects_count, e.certificate_number,
                    e.verification_status as education_verification,
                    reviewer.first_name as reviewer_first_name, 
                    reviewer.last_name as reviewer_last_name
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                LEFT JOIN educational e ON p.personal_id = e.personal_id
                LEFT JOIN users reviewer ON p.reviewed_by = reviewer.user_id
                WHERE p.personal_id = ?
            ");

            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();

            if (!$application) {
                return false;
            }

            // Get documents
            $docStmt = $this->pdo->prepare("
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
                FROM documents 
                WHERE personal_id = ?
                ORDER BY 
                    CASE document_type
                        WHEN 'transcript' THEN 1
                        WHEN 'certificate' THEN 2
                        WHEN 'identity' THEN 3
                        WHEN 'photo' THEN 4
                        ELSE 5
                    END,
                    upload_date DESC
            ");
            $docStmt->execute([$applicationId]);
            $documents = $docStmt->fetchAll();

            // Get status history
            $historyStmt = $this->pdo->prepare("
                SELECT 
                    ash.old_status,
                    ash.new_status,
                    ash.change_reason,
                    ash.change_date,
                    u.first_name,
                    u.last_name,
                    CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                FROM application_status_history ash
                LEFT JOIN users u ON ash.changed_by = u.user_id
                WHERE ash.personal_id = ?
                ORDER BY ash.change_date DESC
            ");
            $historyStmt->execute([$applicationId]);
            $statusHistory = $historyStmt->fetchAll();

            return [
                'application' => $application,
                'documents' => $documents,
                'status_history' => $statusHistory
            ];
        } catch (PDOException $e) {
            error_log("Error getting application details for admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update application status by admin
     */
    public function updateApplicationStatusByAdmin($applicationId, $newStatus, $adminUserId, $notes = '')
    {
        try {
            $this->pdo->beginTransaction();

            // Get current application status
            $stmt = $this->pdo->prepare("SELECT status FROM personal WHERE personal_id = ?");
            $stmt->execute([$applicationId]);
            $currentApp = $stmt->fetch();

            if (!$currentApp) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Application not found'];
            }

            $oldStatus = $currentApp['status'];

            // Update application status
            $updateStmt = $this->pdo->prepare("
                UPDATE personal 
                SET status = ?, reviewed_by = ?, reviewed_at = NOW(), notes = ?, updated_at = NOW()
                WHERE personal_id = ?
            ");

            $result = $updateStmt->execute([$newStatus, $adminUserId, $notes, $applicationId]);

            if (!$result) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Failed to update application status'];
            }

            // Log status change in history
            $this->logStatusChange($applicationId, $oldStatus, $newStatus, $adminUserId, $notes ?: "Status updated by admin");

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Application status updated successfully',
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating application status by admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    /**
     * Get application statistics for reports
     */
    public function getApplicationStatistics($dateFrom = null, $dateTo = null)
    {
        try {
            $whereClause = "WHERE p.status != 'deleted'";
            $params = [];

            if ($dateFrom) {
                $whereClause .= " AND p.created_at >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo) {
                $whereClause .= " AND p.created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    p.status,
                    a.program,
                    a.program_level,
                    COUNT(*) as count,
                    DATE(p.created_at) as application_date
                FROM personal p
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                {$whereClause}
                GROUP BY p.status, a.program, a.program_level, DATE(p.created_at)
                ORDER BY p.created_at DESC
            ");

            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting application statistics: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get applications by status for admin dashboard
     */
    public function getApplicationsByStatus($status, $limit = 10)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.personal_id,
                    p.application_number,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    u.first_name,
                    u.last_name,
                    u.email,
                    a.program,
                    a.program_level
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                WHERE p.status = ? AND p.status != 'deleted'
                ORDER BY p.created_at DESC
                LIMIT ?
            ");

            $stmt->execute([$status, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting applications by status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search applications for admin
     */
    public function searchApplicationsForAdmin($searchTerm, $limit = 50)
    {
        try {
            $searchTerm = "%{$searchTerm}%";

            $stmt = $this->pdo->prepare("
                SELECT 
                    p.personal_id,
                    p.application_number,
                    p.status,
                    p.created_at,
                    u.first_name,
                    u.last_name,
                    u.email,
                    a.program,
                    a.program_level
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                WHERE (
                    u.first_name LIKE ? OR 
                    u.last_name LIKE ? OR 
                    u.email LIKE ? OR 
                    p.application_number LIKE ? OR
                    a.program LIKE ?
                ) AND p.status != 'deleted'
                ORDER BY p.created_at DESC
                LIMIT ?
            ");

            $stmt->execute([
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $searchTerm,
                $limit
            ]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching applications: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get applications requiring attention (pending review, missing documents, etc.)
     */
    public function getApplicationsRequiringAttention($limit = 20)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.personal_id,
                    p.application_number,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    u.first_name,
                    u.last_name,
                    u.email,
                    a.program,
                    CASE 
                        WHEN p.status = 'submitted' AND p.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'pending_review_overdue'
                        WHEN p.status = 'submitted' THEN 'pending_review'
                        WHEN p.status = 'under-review' AND p.updated_at < DATE_SUB(NOW(), INTERVAL 14 DAY) THEN 'review_overdue'
                        ELSE 'attention_needed'
                    END as attention_type
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                WHERE p.status IN ('submitted', 'under-review') 
                    AND p.status != 'deleted'
                ORDER BY 
                    CASE 
                        WHEN p.status = 'submitted' AND p.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1
                        WHEN p.status = 'under-review' AND p.updated_at < DATE_SUB(NOW(), INTERVAL 14 DAY) THEN 2
                        WHEN p.status = 'submitted' THEN 3
                        ELSE 4
                    END,
                    p.created_at ASC
                LIMIT ?
            ");

            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting applications requiring attention: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get applications with missing required documents
     */
    public function getApplicationsWithMissingDocuments($limit = 20)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT
                    p.personal_id,
                    p.application_number,
                    p.status,
                    p.created_at,
                    u.first_name,
                    u.last_name,
                    u.email,
                    a.program,
                    COUNT(d.document_id) as uploaded_documents
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                LEFT JOIN documents d ON p.personal_id = d.personal_id
                WHERE p.status IN ('submitted', 'under-review')
                    AND p.status != 'deleted'
                GROUP BY p.personal_id
                HAVING uploaded_documents < 3
                ORDER BY p.created_at ASC
                LIMIT ?
            ");

            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting applications with missing documents: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk update application status
     */
    public function bulkUpdateApplicationStatus($applicationIds, $newStatus, $adminUserId, $notes = '')
    {
        try {
            $this->pdo->beginTransaction();

            $successCount = 0;
            $failedIds = [];

            foreach ($applicationIds as $applicationId) {
                $result = $this->updateApplicationStatusByAdmin($applicationId, $newStatus, $adminUserId, $notes);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedIds[] = $applicationId;
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'updated_count' => $successCount,
                'failed_ids' => $failedIds,
                'message' => "Updated {$successCount} applications successfully"
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in bulk update: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    /**
     * Log status changes
     */
    private function logStatusChange($personalId, $oldStatus, $newStatus, $changedBy, $reason = '')
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO application_status_history 
                (personal_id, old_status, new_status, changed_by, change_reason, change_date)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([$personalId, $oldStatus, $newStatus, $changedBy, $reason]);
        } catch (PDOException $e) {
            error_log("Error logging status change: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get document count for application
     */
    public function getDocumentCount($personalId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM documents 
                WHERE personal_id = ?
            ");
            $stmt->execute([$personalId]);
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            error_log("Error getting document count: " . $e->getMessage());
            return 0;
        }
    }

    public function isApplicationComplete($personalId)
    {
        try {
            // Check if all required sections are filled
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.personal_id,
                    a.academic_id,
                    e.educational_id,
                    COUNT(d.document_id) as document_count
                FROM personal p
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                LEFT JOIN educational e ON p.personal_id = e.personal_id
                LEFT JOIN documents d ON p.personal_id = d.personal_id
                WHERE p.personal_id = ?
                GROUP BY p.personal_id
            ");

            $stmt->execute([$personalId]);
            $result = $stmt->fetch();

            if (!$result) {
                return false;
            }

            // Check if all required sections exist and have minimum documents
            return $result['academic_id'] && $result['educational_id'] && $result['document_count'] >= 2;
        } catch (PDOException $e) {
            error_log("Error checking application completeness: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get required documents for a program
     */
    public function getRequiredDocuments($program = null)
    {
        $defaultRequired = [
            'transcript' => 'Academic Transcript',
            'certificate' => 'Certificate/Diploma',
            'identity' => 'Identity Document',
            'photo' => 'Passport Photo'
        ];

        // You can customize required documents based on program
        switch ($program) {
            case 'Graduate':
                $defaultRequired['recommendation_letter'] = 'Recommendation Letter';
                $defaultRequired['personal_statement'] = 'Personal Statement';
                break;
            case 'Undergraduate':
                // Standard requirements
                break;
        }

        return $defaultRequired;
    }


    /**
     * Get application progress percentage
     */
    public function getApplicationProgress($personalId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.personal_id,
                    p.first_name,
                    p.last_name,
                    p.email,
                    p.phone_number,
                    p.date_of_birth,
                    a.academic_id,
                    e.educational_id,
                    COUNT(d.document_id) as document_count
                FROM personal p
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                LEFT JOIN educational e ON p.personal_id = e.personal_id
                LEFT JOIN documents d ON p.personal_id = d.personal_id
                WHERE p.personal_id = ?
                GROUP BY p.personal_id
            ");

            $stmt->execute([$personalId]);
            $result = $stmt->fetch();

            if (!$result) {
                return 0;
            }

            $progress = 0;
            $totalSteps = 4;

            // Personal info (25%)
            if ($result['first_name'] && $result['last_name'] && $result['email']) {
                $progress += 25;
            }

            // Academic info (25%)
            if ($result['academic_id']) {
                $progress += 25;
            }

            // Educational info (25%)
            if ($result['educational_id']) {
                $progress += 25;
            }

            // Documents (25%) - minimum 2 documents required
            if ($result['document_count'] >= 2) {
                $progress += 25;
            } elseif ($result['document_count'] > 0) {
                $progress += 12; // Partial credit
            }

            return min(100, $progress);
        } catch (PDOException $e) {
            error_log("Error calculating application progress: " . $e->getMessage());
            return 0;
        }
    }

    public function getApplicationTimeline($personalId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    'status_change' as event_type,
                    ash.old_status as from_value,
                    ash.new_status as to_value,
                    ash.change_reason as description,
                    ash.change_date as event_date,
                    CONCAT(u.first_name, ' ', u.last_name) as actor_name
                FROM application_status_history ash
                LEFT JOIN users u ON ash.changed_by = u.user_id
                WHERE ash.personal_id = ?
                
                UNION ALL
                
                SELECT 
                    'document_upload' as event_type,
                    d.document_type as from_value,
                    d.original_filename as to_value,
                    CONCAT('Document uploaded: ', d.document_type) as description,
                    d.upload_date as event_date,
                    'Student' as actor_name
                FROM documents d
                WHERE d.personal_id = ?
                
                ORDER BY event_date DESC
            ");

            $stmt->execute([$personalId, $personalId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting application timeline: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent applications that don't have admin notifications yet
     */
    public function getRecentApplicationsWithoutAdminNotifications($minutesBack = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.personal_id,
                    p.application_number,
                    p.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as student_name,
                    a.program
                FROM personal p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                AND p.status IN ('submitted', 'under-review')
                AND p.admin_notifications_created IS NULL
                ORDER BY p.created_at DESC
            ");
            
            $stmt->execute([$minutesBack]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error getting recent applications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark application as having admin notifications created
     */
    public function markAdminNotificationsCreated($applicationId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE personal 
                SET admin_notifications_created = NOW() 
                WHERE personal_id = ?
            ");
            
            return $stmt->execute([$applicationId]);
            
        } catch (PDOException $e) {
            error_log("Error marking admin notifications created: " . $e->getMessage());
            return false;
        }
    }
}
