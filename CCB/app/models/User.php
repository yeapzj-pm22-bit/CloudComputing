<?php
// models/User.php

class User extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'first_name', 'last_name', 'email', 'date_of_birth', 
        'phone_number', 'account_type', 'password_hash', 
        'is_active', 'email_verified', 'last_login'
    ];
    protected $hidden = ['password_hash'];
    
    /**
     * Find user by email address
     */
    public function findByEmail(string $email): ?array {
        return $this->findBy('email', $email);
    }
    
    /**
     * Verify user password
     */
    public function verifyPassword(int $userId, string $password): bool {
        $user = $this->find($userId);
        if ($user) {
            return password_verify($password, $user['password_hash']);
        }
        return false;
    }
    
    /**
     * Verify user password by email
     */
    public function verifyPasswordByEmail(string $email, string $password): ?array {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            // Remove password hash from returned data
            unset($user['password_hash']);
            return $user;
        }
        return null;
    }
    
    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $newPassword): bool {
        return $this->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }
    
    /**
     * Change password with current password verification
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool {
        if ($this->verifyPassword($userId, $currentPassword)) {
            return $this->updatePassword($userId, $newPassword);
        }
        return false;
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): bool {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get active users only
     */
    public function getActiveUsers(): array {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get users by account type
     */
    public function getUsersByType(string $accountType): array {
        $sql = "SELECT * FROM {$this->table} WHERE account_type = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$accountType]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search users with pagination
     */
    public function searchUsers(string $search, int $page = 1, int $perPage = 15): array {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($sql);
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
        $data = $stmt->fetchAll();
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} 
                     WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $total = $countStmt->fetch()['count'];
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Create new user with validation
     */
    public function createUser(array $userData): array {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'date_of_birth', 'phone_number'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => ucfirst($field) . ' is required'];
                }
            }
            
            // Validate email
            if (!ValidationHelper::validateEmail($userData['email'])) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if email already exists
            if ($this->findByEmail($userData['email'])) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password if provided, otherwise generate temporary password
            if (empty($userData['password'])) {
                $userData['password'] = bin2hex(random_bytes(8)); // Generate random password
            }
            
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']); // Remove plain password
            
            // Set defaults and convert booleans to integers for MySQL
            $userData['account_type'] = $userData['account_type'] ?? 'Student';
            $userData['is_active'] = isset($userData['is_active']) ? (int)$userData['is_active'] : 1;
            $userData['email_verified'] = isset($userData['email_verified']) ? (int)$userData['email_verified'] : 0;
            
            $userId = $this->create($userData);
            
            return [
                'success' => true, 
                'user_id' => $userId,
                'message' => 'User created successfully'
            ];
            
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $profileData): array {
        try {
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email', 'phone_number', 'date_of_birth'];
            foreach ($requiredFields as $field) {
                if (empty($profileData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field " . ucwords(str_replace('_', ' ', $field)) . " is required"
                    ];
                }
            }

            // Check if email is already used by another user
            if (isset($profileData['email'])) {
                $stmt = $this->pdo->prepare("
                    SELECT user_id FROM {$this->table} 
                    WHERE email = ? AND user_id != ?
                ");
                $stmt->execute([$profileData['email'], $userId]);
                
                if ($stmt->fetch()) {
                    return [
                        'success' => false,
                        'message' => 'Email address is already in use'
                    ];
                }
            }

            // Update user profile
            $updateResult = $this->update($userId, [
                'first_name' => $profileData['first_name'],
                'last_name' => $profileData['last_name'],
                'email' => $profileData['email'],
                'phone_number' => $profileData['phone_number'],
                'date_of_birth' => $profileData['date_of_birth']
            ]);

            if ($updateResult) {
                // Update session data if this is the current user
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                    $_SESSION['first_name'] = $profileData['first_name'];
                    $_SESSION['last_name'] = $profileData['last_name'];
                    $_SESSION['email'] = $profileData['email'];
                }
                
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update profile'
                ];
            }

        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }

    /**
     * Get user by ID safely (without password hash)
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    user_id, first_name, last_name, email, 
                    date_of_birth, phone_number, account_type,
                    is_active, email_verified, last_login, created_at
                FROM {$this->table} 
                WHERE user_id = ? AND is_active = 1
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Error getting user by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Change user password with verification
     */
    public function changeUserPassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->pdo->prepare("
                SELECT password_hash FROM {$this->table} WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }

            // Validate new password
            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'New password must be at least 8 characters long'
                ];
            }

            // Update password
            $updateResult = $this->update($userId, [
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);

            if ($updateResult) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to change password'
                ];
            }

        } catch (PDOException $e) {
            error_log("Error changing password: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Deactivate user instead of deleting
     */
    public function deactivateUser(int $userId): bool {
        return $this->update($userId, ['is_active' => false]);
    }
    
    /**
     * Reactivate user
     */
    public function reactivateUser(int $userId): bool {
        return $this->update($userId, ['is_active' => true]);
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats(): array {
        $sql = "SELECT 
                    account_type,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified
                FROM {$this->table} 
                GROUP BY account_type";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get recently registered users
     */
    public function getRecentUsers(int $limit = 10): array {
        $sql = "SELECT user_id, first_name, last_name, email, account_type, created_at
                FROM {$this->table} 
                WHERE is_active = 1
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Verify user email
     */
    public function verifyEmail(int $userId): bool {
        return $this->update($userId, ['email_verified' => true]);
    }
    
    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(string $email): ?string {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }
        
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database (you might want to create a separate password_resets table)
        $this->update($user['user_id'], [
            'reset_token' => $token,
            'reset_token_expiry' => $expiry
        ]);
        
        return $token;
    }
    
    /**
     * Reset password using token
     */
    public function resetPasswordWithToken(string $token, string $newPassword): bool {
        $sql = "SELECT user_id FROM {$this->table} 
                WHERE reset_token = ? AND reset_token_expiry > NOW() AND is_active = 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $this->update($user['user_id'], [
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'reset_token' => null,
                'reset_token_expiry' => null
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user with applications count
     */
    public function getUserWithApplicationsCount(int $userId): ?array {
        $sql = "SELECT u.*, COUNT(p.personal_id) as applications_count
                FROM {$this->table} u
                LEFT JOIN personal p ON u.user_id = p.user_id
                WHERE u.user_id = ?
                GROUP BY u.user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Override create method to hash password automatically
     */
    public function create(array $data): int {
        // Hash password if present and not already hashed
        if (isset($data['password']) && !isset($data['password_hash'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        return parent::create($data);
    }
    
    /**
     * Get safe user data (without sensitive information)
     */
    public function getSafeUserData(int $userId): ?array {
        $user = $this->find($userId);
        if ($user) {
            // Remove sensitive data
            unset($user['password_hash'], $user['reset_token'], $user['reset_token_expiry']);
            return $user;
        }
        return null;
    }
    
    /**
     * Check if user can perform action based on account type
     */
    public function canPerformAction(int $userId, string $action): bool {
        $user = $this->find($userId);
        if (!$user || !$user['is_active']) {
            return false;
        }
        
        $permissions = [
            'Admin' => ['*'], // Admin can do everything
            'Staff' => ['view_applications', 'update_applications', 'view_students'],
            'Student' => ['view_own_applications', 'create_applications', 'update_own_profile']
        ];
        
        $userType = $user['account_type'];
        if (!isset($permissions[$userType])) {
            return false;
        }
        
        return in_array('*', $permissions[$userType]) || in_array($action, $permissions[$userType]);
    }


    /**
     * Get all students with their application info for admin management
     */
    public function getAllStudentsForAdmin($limit = null, $page = null, $perPage = 20, $filters = []) {
    try {
        $whereConditions = ["u.account_type = 'Student'"];
        $params = [];

        // Build filter conditions (existing code is correct)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.user_id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $whereConditions[] = "u.is_active = 1";
            } elseif ($filters['status'] === 'inactive') {
                $whereConditions[] = "u.is_active = 0";
            }
        }

        if (!empty($filters['program'])) {
            $whereConditions[] = "p.status = 'enrolled' AND a.program LIKE ?";
            $params[] = "%{$filters['program']}%";
        }

        $whereClause = implode(' AND ', $whereConditions);

        // ✅ Fixed SQL - group only by user_id and use MAX/MIN for other fields
        $sql = "
            SELECT 
                u.user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone_number,
                u.date_of_birth,
                u.is_active,
                u.email_verified,
                u.last_login,
                u.created_at,
                u.updated_at,
                MAX(p.personal_id) as personal_id,
                MAX(p.application_number) as application_number,
                MAX(p.status) as status,
                MAX(p.received) as application_date,
                -- Get program info for enrolled students only
                MAX(CASE WHEN p.status = 'enrolled' THEN a.program END) as program,
                MAX(CASE WHEN p.status = 'enrolled' THEN a.program_level END) as program_level,
                MAX(CASE WHEN p.status = 'enrolled' THEN a.enrollment_type END) as enrollment_type,
                MAX(CASE WHEN p.status = 'enrolled' THEN a.start_term END) as start_term,
                COUNT(DISTINCT d.document_id) as documents_count
            FROM users u
            LEFT JOIN personal p ON u.user_id = p.user_id 
            LEFT JOIN academic a ON p.personal_id = a.personal_id
            LEFT JOIN documents d ON p.personal_id = d.personal_id
            WHERE {$whereClause}
            GROUP BY u.user_id  -- ✅ Only group by user_id
            ORDER BY 
                u.is_active DESC,
                u.created_at DESC
        ";

        if ($limit && !$page) {
            $sql .= " LIMIT " . intval($limit);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

    } catch (PDOException $e) {
        error_log("Error getting students for admin: " . $e->getMessage());
        return false;
    }
}

    /**
     * Get student details for admin view
     */
    public function getStudentDetailsForAdmin($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.phone_number,
                    u.date_of_birth,
                    u.is_active,
                    u.email_verified,
                    u.last_login,
                    u.created_at as registration_date,
                    COUNT(DISTINCT p.personal_id) as total_applications,
                    COUNT(DISTINCT CASE WHEN p.status = 'approved' THEN p.personal_id END) as approved_applications,
                    COUNT(DISTINCT CASE WHEN p.status = 'enrolled' THEN p.personal_id END) as enrolled_applications,
                    COUNT(DISTINCT d.document_id) as total_documents,
                    MAX(p.created_at) as last_application_date,
                    GROUP_CONCAT(DISTINCT a.program ORDER BY p.created_at DESC) as programs_applied
                FROM users u
                LEFT JOIN personal p ON u.user_id = p.user_id
                LEFT JOIN academic a ON p.personal_id = a.personal_id
                LEFT JOIN documents d ON p.personal_id = d.personal_id
                WHERE u.user_id = ? AND u.account_type = 'Student'
                GROUP BY u.user_id
            ");

            $stmt->execute([$userId]);
            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error getting student details for admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get student statistics for admin dashboard
     */
    public function getStudentStatisticsForAdmin() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT u.user_id) as total_students,
                    COUNT(DISTINCT CASE WHEN p.status = 'enrolled' THEN u.user_id END) as enrolled_students,
                    COUNT(DISTINCT CASE WHEN p.status = 'approved' THEN u.user_id END) as approved_students,
                    COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.user_id END) as new_students_month,
                    COUNT(DISTINCT CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN u.user_id END) as active_students_week
                FROM users u
                LEFT JOIN personal p ON u.user_id = p.user_id
                WHERE u.account_type = 'Student' AND u.is_active = 1
            ");

            $stmt->execute();
            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Error getting student statistics: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update student status (activate/deactivate)
     */
    public function updateStudentStatus($userId, $isActive, $adminUserId) {
        try {
            $result = $this->update($userId, [
                'is_active' => $isActive ? 1 : 0
            ]);

            if ($result) {
                // Log the action
                error_log("Student {$userId} " . ($isActive ? 'activated' : 'deactivated') . " by admin {$adminUserId}");
                
                return [
                    'success' => true,
                    'message' => 'Student status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update student status'
                ];
            }

        } catch (PDOException $e) {
            error_log("Error updating student status: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }

    /**
     * Get all admin users for notifications
     */
    public function getAllAdminUsers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id, first_name, last_name, email 
                FROM users 
                WHERE account_type = 'Admin' AND is_active = 1
            ");
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error getting admin users: " . $e->getMessage());
            return [];
        }
    }
}

?>