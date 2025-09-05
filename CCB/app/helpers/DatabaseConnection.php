<?php
// helpers/DatabaseConnection.php
//mysql -u root -p -h localhost -P 3306 university_portal
class DatabaseConnection {
    private static $instance = null;
    private $pdo;
    
    // Database configuration constants - UPDATE THESE WITH YOUR ACTUAL CREDENTIALS
    private const DB_HOST = 'localhost';
    private const DB_USER = 'root';
    private const DB_PASS = '1234';  // Update with your password
    private const DB_NAME = 'university_portal';
    private const DB_PORT = 3306;
    private const DB_CHARSET = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";port=" . self::DB_PORT . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET . " COLLATE " . self::DB_CHARSET . "_unicode_ci"
            ];
            
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance(): DatabaseConnection {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO {
        return $this->pdo;
    }
    
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }
    
    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
}
?>