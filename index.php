<?php
    session_start(); //check if user is already registered

    if(isset($_SESSION['status'])) {
        $status = $_SESSION['status'];
        unset($_SESSION['status']);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="topnav">
        <a class="active" href="index.php">Home</a>
        <a href="#news">News</a>
        <a href="#contact">Contact</a>
        <a href="Search">Search</a>
        <a href="login.php">Log in</a>
        <a href="register.php">Register</a>
    </div>
    </body>
    <?php
        if (!empty($status)) {
            echo "<div class='status-message'>" . htmlspecialchars($status) . "</div>";
        }
        require_once 'database.php';
        try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Connection successful!<br>";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }


        try {
            $sql = "SELECT * from user";
                
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
                
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($records as $record) {
                echo('<h1>' . $record['nume'] . "</h1>");
                echo('<h1>' . $record['prenume'] . "</h1>");
                echo('<h1>' . $record['email'] . "</h1>");
                echo "<hr>";
            }
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }


    ?>
</html>