<?php
    require_once 'database.php';
    try {
        $pdo = Database::getInstance()->getConnection();
        // echo "✅ Connection successful!<br>";
    } 
    catch (PDOException $e) {
        die("❌ Connection failed: " . $e->getMessage());
    }

?>