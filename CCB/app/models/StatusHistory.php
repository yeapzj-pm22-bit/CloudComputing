<?php
// models/StatusHistory.php

class StatusHistory extends BaseModel {
    protected $table = 'application_status_history';
    protected $primaryKey = 'history_id';
    protected $fillable = [
        'personal_id', 'old_status', 'new_status', 'changed_by', 'change_reason'
    ];
    protected $timestamps = false;
    
    public function getHistoryByApplication(int $personalId): array {
        $sql = "SELECT h.*, u.first_name, u.last_name
                FROM {$this->table} h
                LEFT JOIN users u ON h.changed_by = u.user_id
                WHERE h.personal_id = ?
                ORDER BY h.change_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personalId]);
        return $stmt->fetchAll();
    }
    
    // FIXED: Added explicit nullable type for $reason parameter
    public function logStatusChange(int $personalId, string $oldStatus, string $newStatus, int $changedBy, ?string $reason = null): int {
        return $this->create([
            'personal_id' => $personalId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'change_reason' => $reason
        ]);
    }

    /**
     * Get status changes for a specific user's applications
     */
    public function getHistoryByUser(int $userId): array {
        $sql = "SELECT h.*, u.first_name, u.last_name, p.application_number
                FROM {$this->table} h
                LEFT JOIN users u ON h.changed_by = u.user_id
                LEFT JOIN personal p ON h.personal_id = p.personal_id
                WHERE p.user_id = ?
                ORDER BY h.change_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get recent status changes (for admin dashboard)
     */
    public function getRecentChanges(int $limit = 10): array {
        $sql = "SELECT h.*, u.first_name, u.last_name, p.application_number,
                       applicant.first_name as applicant_first_name,
                       applicant.last_name as applicant_last_name
                FROM {$this->table} h
                LEFT JOIN users u ON h.changed_by = u.user_id
                LEFT JOIN personal p ON h.personal_id = p.personal_id
                LEFT JOIN users applicant ON p.user_id = applicant.user_id
                ORDER BY h.change_date DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get status change statistics
     */
    public function getStatusChangeStats(): array {
        $sql = "SELECT 
                    new_status,
                    COUNT(*) as count,
                    DATE(change_date) as change_day
                FROM {$this->table} 
                WHERE change_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY new_status, DATE(change_date)
                ORDER BY change_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Delete old status history records (cleanup)
     */
    public function cleanupOldHistory(int $daysToKeep = 365): bool {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->table} 
                WHERE change_date < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            $result = $stmt->execute([$daysToKeep]);
            $deletedCount = $stmt->rowCount();
            
            error_log("Cleaned up {$deletedCount} old status history records");
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error cleaning up status history: " . $e->getMessage());
            return false;
        }
    }
}
?>