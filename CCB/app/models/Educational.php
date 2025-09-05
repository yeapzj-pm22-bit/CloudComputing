<?php
// models/Educational.php

class Educational extends BaseModel {
    protected $table = 'educational';
    protected $primaryKey = 'educational_id';
    protected $fillable = [
        'personal_id', 'education_level', 'institution_name', 'graduation_year',
        'grade_type', 'grade_value', 'subjects_count', 'certificate_number',
        'verification_status'
    ];
    
    public function findByPersonalId(int $personalId): array {
        $sql = "SELECT * FROM {$this->table} WHERE personal_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personalId]);
        return $stmt->fetchAll();
    }

    /**
     * Update educational information by personal_id
     */
    public function updateByPersonalId(int $personalId, array $data): bool {
        $educational = $this->findByPersonalId($personalId);
        
        if (!empty($educational)) {
            // Update the first educational record (assuming one per application)
            return $this->update($educational[0]['educational_id'], $data);
        } else {
            // Create new educational record if it doesn't exist
            $data['personal_id'] = $personalId;
            $this->create($data);
            return true;
        }
    }

    /**
     * Get educational background by personal_id (single record)
     */
    public function getByPersonalId(int $personalId): ?array {
        $records = $this->findByPersonalId($personalId);
        return !empty($records) ? $records[0] : null;
    }

    /**
     * Validate grade value based on grade type
     */
    public function validateGrade(string $gradeType, string $gradeValue): bool {
        switch (strtolower($gradeType)) {
            case 'gpa':
                return is_numeric($gradeValue) && $gradeValue >= 0 && $gradeValue <= 4.0;
            case 'percentage':
                return is_numeric($gradeValue) && $gradeValue >= 0 && $gradeValue <= 100;
            case 'letter':
                return in_array(strtoupper($gradeValue), ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'F']);
            case 'pass/fail':
                return in_array(strtolower($gradeValue), ['pass', 'fail']);
            default:
                return true; // Allow any value for other grade types
        }
    }

    /**
     * Get education level statistics
     */
    public function getEducationLevelStats(): array {
        try {
            $sql = "SELECT 
                        education_level,
                        COUNT(*) as count,
                        AVG(CASE 
                            WHEN grade_type = 'percentage' THEN CAST(grade_value AS DECIMAL(5,2))
                            WHEN grade_type = 'gpa' THEN CAST(grade_value AS DECIMAL(3,2)) * 25
                            ELSE NULL 
                        END) as avg_grade
                    FROM {$this->table} 
                    GROUP BY education_level
                    ORDER BY education_level";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting education stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete educational record by personal_id
     */
    public function deleteByPersonalId(int $personalId): bool {
        $educational = $this->findByPersonalId($personalId);
        if (!empty($educational)) {
            // Delete all educational records for this personal_id
            foreach ($educational as $record) {
                $this->delete($record['educational_id']);
            }
        }
        return true;
    }

    /**
     * Get institutions by education level
     */
    public function getInstitutionsByLevel(string $educationLevel): array {
        try {
            $sql = "SELECT DISTINCT institution_name 
                    FROM {$this->table} 
                    WHERE education_level = ? 
                    ORDER BY institution_name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$educationLevel]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error getting institutions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get graduation years range
     */
    public function getGraduationYearsRange(): array {
        try {
            $sql = "SELECT 
                        MIN(graduation_year) as min_year,
                        MAX(graduation_year) as max_year
                    FROM {$this->table} 
                    WHERE graduation_year IS NOT NULL";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch() ?: ['min_year' => null, 'max_year' => null];
        } catch (PDOException $e) {
            error_log("Error getting graduation years range: " . $e->getMessage());
            return ['min_year' => null, 'max_year' => null];
        }
    }

    /**
     * Get grade distribution by education level
     */
    public function getGradeDistribution(?string $educationLevel = null): array {
        try {
            $sql = "SELECT 
                        grade_type,
                        grade_value,
                        COUNT(*) as count
                    FROM {$this->table} 
                    WHERE grade_type IS NOT NULL AND grade_value IS NOT NULL";
            
            $params = [];
            if ($educationLevel) {
                $sql .= " AND education_level = ?";
                $params[] = $educationLevel;
            }
            
            $sql .= " GROUP BY grade_type, grade_value ORDER BY grade_type, grade_value";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting grade distribution: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate educational data before saving
     */
    public function validateEducationalData(array $data): array {
        $errors = [];
        
        // Required fields
        if (empty($data['education_level'])) {
            $errors[] = 'Education level is required';
        }
        
        if (empty($data['institution_name'])) {
            $errors[] = 'Institution name is required';
        }
        
        if (empty($data['graduation_year'])) {
            $errors[] = 'Graduation year is required';
        }
        
        // Validate graduation year
        if (!empty($data['graduation_year'])) {
            $currentYear = date('Y');
            if ($data['graduation_year'] < 1900 || $data['graduation_year'] > $currentYear) {
                $errors[] = 'Graduation year must be between 1900 and ' . $currentYear;
            }
        }
        
        // Validate grade if provided
        if (!empty($data['grade_type']) && !empty($data['grade_value'])) {
            if (!$this->validateGrade($data['grade_type'], $data['grade_value'])) {
                $errors[] = 'Invalid grade value for the selected grade type';
            }
        }
        
        // Validate subjects count
        if (!empty($data['subjects_count']) && (!is_numeric($data['subjects_count']) || $data['subjects_count'] < 1)) {
            $errors[] = 'Subjects count must be a positive number';
        }
        
        return $errors;
    }

    /**
     * Get recent educational records for dashboard
     */
    public function getRecentEducationalRecords(int $limit = 10): array {
        try {
            $sql = "SELECT e.*, p.application_number, u.first_name, u.last_name
                    FROM {$this->table} e
                    LEFT JOIN personal p ON e.personal_id = p.personal_id
                    LEFT JOIN users u ON p.user_id = u.user_id
                    ORDER BY e.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting recent educational records: " . $e->getMessage());
            return [];
        }
    }
}
?>