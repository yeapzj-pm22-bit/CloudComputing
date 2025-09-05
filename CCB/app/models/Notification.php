<?php
// models/Notification.php

class Notification extends BaseModel {
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'is_read',
        'related_application_id', 'read_at'
    ];
    
    public function getUnreadByUser(int $userId): array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead(int $notificationId): bool {
        return $this->update($notificationId, [
            'is_read' => true,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function markAllAsReadForUser(int $userId): bool {
        $sql = "UPDATE {$this->table} SET is_read = 1, read_at = ? WHERE user_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([date('Y-m-d H:i:s'), $userId]);
    }
    
    // FIXED: Added explicit nullable type for $relatedApplicationId parameter
    public function createNotification(int $userId, string $title, string $message, string $type = 'info', ?int $relatedApplicationId = null): int {
        return $this->create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_application_id' => $relatedApplicationId
        ]);
    }

    /**
     * Get notifications for a user with limit
     */
    public function getUserNotifications($userId, $limit = 10) {
        try {
            $sql = "
                SELECT 
                    notification_id,
                    title,
                    message,
                    type,
                    is_read,
                    related_application_id,
                    created_at,
                    read_at
                FROM {$this->table}
                WHERE user_id = ?
                ORDER BY created_at DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark specific notification as read for a user
     */
    public function markNotificationRead($notificationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->table} 
                SET is_read = 1, read_at = NOW() 
                WHERE notification_id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete notification
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->table} 
                WHERE notification_id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulkNotification($userIds, $title, $message, $type = 'info') {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (user_id, title, message, type, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            foreach ($userIds as $userId) {
                $stmt->execute([$userId, $title, $message, $type]);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error sending bulk notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent notifications for dashboard
     */
    public function getRecentNotifications($userId, $limit = 5) {
        return $this->getUserNotifications($userId, $limit);
    }

    /**
     * Cleanup old notifications (older than specified days)
     */
    public function cleanupOldNotifications($days = 90) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND is_read = 1
            ");
            
            $result = $stmt->execute([$days]);
            $deletedCount = $stmt->rowCount();
            
            error_log("Cleaned up {$deletedCount} old notifications");
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error cleaning up notifications: " . $e->getMessage());
            return false;
        }
    }
}

?>