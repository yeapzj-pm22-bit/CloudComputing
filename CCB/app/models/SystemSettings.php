<?php
// models/SystemSettings.php

class SystemSettings extends BaseModel {
    protected $table = 'system_settings';
    protected $primaryKey = 'setting_id';
    protected $fillable = [
        'setting_key', 'setting_value', 'description', 'updated_by'
    ];
    
    /**
     * Get a specific setting value by key
     */
    public function getSetting(string $key): ?string {
        $setting = $this->findBy('setting_key', $key);
        return $setting ? $setting['setting_value'] : null;
    }
    
    /**
     * Update or create a setting
     * FIXED: Added explicit nullable type for $description parameter
     */
    public function updateSetting(string $key, string $value, int $updatedBy, ?string $description = null): bool {
        $existing = $this->findBy('setting_key', $key);
        
        if ($existing) {
            $updateData = [
                'setting_value' => $value,
                'updated_by' => $updatedBy
            ];
            
            if ($description !== null) {
                $updateData['description'] = $description;
            }
            
            return $this->update($existing['setting_id'], $updateData);
        } else {
            $this->create([
                'setting_key' => $key,
                'setting_value' => $value,
                'description' => $description,
                'updated_by' => $updatedBy
            ]);
            return true;
        }
    }
    
    /**
     * Get all settings as key-value pairs
     */
    public function getAllSettings(): array {
        $settings = $this->all();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        
        return $result;
    }
    
    /**
     * Get all settings with full details
     */
    public function getAllSettingsWithDetails(): array {
        $sql = "SELECT s.*, u.first_name, u.last_name
                FROM {$this->table} s
                LEFT JOIN users u ON s.updated_by = u.user_id
                ORDER BY s.setting_key";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get settings by category (based on key prefix)
     */
    public function getSettingsByCategory(string $prefix): array {
        $sql = "SELECT * FROM {$this->table} WHERE setting_key LIKE ? ORDER BY setting_key";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix . '%']);
        return $stmt->fetchAll();
    }
    
    /**
     * Delete a setting by key
     */
    public function deleteSetting(string $key): bool {
        $setting = $this->findBy('setting_key', $key);
        if ($setting) {
            return $this->delete($setting['setting_id']);
        }
        return false;
    }
    
    /**
     * Get application-related settings
     */
    public function getApplicationSettings(): array {
        return [
            'application_fee' => $this->getSetting('application_fee') ?? '50.00',
            'max_file_size' => $this->getSetting('max_file_size') ?? '5242880',
            'allowed_file_types' => $this->getSetting('allowed_file_types') ?? 'pdf,jpg,jpeg,png,doc,docx',
            'application_deadline' => $this->getSetting('application_deadline') ?? '2024-12-31',
            'academic_year' => $this->getSetting('academic_year') ?? '2024-2025',
            'enrollment_status' => $this->getSetting('enrollment_status') ?? 'open'
        ];
    }
    
    /**
     * Set multiple settings at once
     */
    public function setMultipleSettings(array $settings, int $updatedBy): bool {
        $db = DatabaseConnection::getInstance();
        
        try {
            $db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                if (!$this->updateSetting($key, $value, $updatedBy)) {
                    throw new Exception("Failed to update setting: $key");
                }
            }
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Failed to set multiple settings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get setting with type casting
     */
    public function getTypedSetting(string $key, string $type = 'string') {
        $value = $this->getSetting($key);
        
        if ($value === null) {
            return null;
        }
        
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                return explode(',', $value);
            default:
                return $value;
        }
    }
    
    /**
     * Initialize default settings
     */
    public static function initializeDefaults(int $adminUserId = 1): void {
        $settings = new self();
        
        $defaults = [
            'application_fee' => '50.00',
            'max_file_size' => '5242880',
            'allowed_file_types' => 'pdf,jpg,jpeg,png,doc,docx',
            'application_deadline' => '2024-12-31',
            'academic_year' => '2024-2025',
            'enrollment_status' => 'open',
            'system_name' => 'University Enrollment System',
            'admin_email' => 'admin@university.edu',
            'support_email' => 'support@university.edu',
            'max_applications_per_user' => '5',
            'auto_assign_student_id' => 'true',
            'require_document_verification' => 'true',
            'notification_enabled' => 'true',
            'maintenance_mode' => 'false'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!$settings->getSetting($key)) {
                $settings->create([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'description' => ucfirst(str_replace('_', ' ', $key)),
                    'updated_by' => $adminUserId
                ]);
            }
        }
    }

    /**
     * Cache frequently accessed settings
     */
    private static $cachedSettings = [];

    public function getCachedSetting(string $key): ?string {
        if (!isset(self::$cachedSettings[$key])) {
            self::$cachedSettings[$key] = $this->getSetting($key);
        }
        return self::$cachedSettings[$key];
    }

    /**
     * Clear settings cache
     */
    public function clearCache(): void {
        self::$cachedSettings = [];
    }

    /**
     * Backup settings to array
     */
    public function exportSettings(): array {
        $settings = $this->getAllSettings();
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'settings' => $settings
        ];
    }

    /**
     * Restore settings from backup
     */
    public function importSettings(array $backup, int $updatedBy): bool {
        if (!isset($backup['settings']) || !is_array($backup['settings'])) {
            return false;
        }

        return $this->setMultipleSettings($backup['settings'], $updatedBy);
    }
}
?>