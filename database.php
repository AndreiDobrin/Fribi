<?php
class Database {
    private static ?Database $instance = null;  // Single instance of the class
    private PDO $connection;

    // Config database
    private string $host = "localhost";
    private string $dbName = "andrei";
    private string $username = "root";
    private string $password = "";

    private array $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Private constructor to prevent multiple instances
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};port=3307;dbname={$this->dbName};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }   

    // Prevent cloning
    private function __clone() {}

    // Prevent unserializing
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }

    // Get the single instance of Database
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Get the PDO connection
    public function getConnection(): PDO {
        return $this->connection;
    }
}


//echo "Server time is: " . date('Y-m-d H:i:s');
//echo "\n";
?>
