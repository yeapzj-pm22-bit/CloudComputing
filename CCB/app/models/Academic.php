<?php
// models/Academic.php

class Academic extends BaseModel {
    protected $table = 'academic';
    protected $primaryKey = 'academic_id';
    protected $fillable = [
        'personal_id', 'program', 'program_level', 'program_category',
        'enrollment_type', 'start_term', 'expected_graduation_year',
        'preferred_campus', 'scholarship_applied', 'scholarship_type'
    ];
    
    public function findByPersonalId(int $personalId): ?array {
        return $this->findBy('personal_id', $personalId);
    }
    
    public function getProgramStatistics(): array {
        $sql = "SELECT program, COUNT(*) as count FROM {$this->table} GROUP BY program ORDER BY count DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update academic information by personal_id
     */
    public function updateByPersonalId(int $personalId, array $data): bool {
        $academic = $this->findByPersonalId($personalId);
        
        if ($academic) {
            return $this->update($academic['academic_id'], $data);
        } else {
            // Create new academic record if it doesn't exist
            $data['personal_id'] = $personalId;
            $this->create($data);
            return true;
        }
    }

    /**
     * Get all programs with levels
     */
    public function getAllPrograms(): array {
        try {
            $sql = "SELECT DISTINCT program, program_level, program_category 
                    FROM {$this->table} 
                    ORDER BY program, program_level";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting programs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get enrollment statistics
     */
    public function getEnrollmentStats(): array {
        try {
            $sql = "SELECT 
                        enrollment_type,
                        program_level,
                        COUNT(*) as count
                    FROM {$this->table} 
                    GROUP BY enrollment_type, program_level
                    ORDER BY enrollment_type, program_level";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting enrollment stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete academic record by personal_id
     */
    public function deleteByPersonalId(int $personalId): bool {
        $academic = $this->findByPersonalId($personalId);
        if ($academic) {
            return $this->delete($academic['academic_id']);
        }
        return true; // Return true if no record exists (nothing to delete)
    }

    /**
     * Get programs by level
     */
    public function getProgramsByLevel(string $level): array {
        try {
            $sql = "SELECT DISTINCT program FROM {$this->table} WHERE program_level = ? ORDER BY program";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$level]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error getting programs by level: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get scholarship statistics
     */
    public function getScholarshipStats(): array {
        try {
            $sql = "SELECT 
                        scholarship_type,
                        COUNT(*) as count,
                        program_level
                    FROM {$this->table} 
                    WHERE scholarship_applied = 1 
                    GROUP BY scholarship_type, program_level
                    ORDER BY count DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting scholarship stats: " . $e->getMessage());
            return [];
        }
    }
}
?>