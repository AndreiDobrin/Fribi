<?php
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    // Default Local Config (MySQL)
    private string $host = "localhost";
    private string $dbName = "andrei";
    private string $username = "root";
    private string $password = "";
    private string $port = "3307"; // Your local port

    private function __construct() {
        try {
            // Check if we are on Heroku (JawsDB MySQL)
            if (getenv('JAWSDB_URL')) {
                $url = parse_url(getenv('JAWSDB_URL'));
                
                $this->host = $url["host"];
                $this->username = $url["user"];
                $this->password = $url["pass"];
                $this->dbName = substr($url["path"], 1);
                $this->port = $url["port"];
                
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4";
            } 
            // Otherwise use Local Settings
            else {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4";
            }

            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }   

    // Singleton pattern (same as before)
    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize a singleton."); }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
}
?>  