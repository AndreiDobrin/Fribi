<?php
    session_start();
    if(!$_SESSION['username']) {
        header('Location: login.php');
    }

    if(isset($_SESSION['status'])) {
        $status = $_SESSION['status'];
        unset($_SESSION['status']);
    }

    if (!empty($status)) {
        echo "<div class='status-message'>" . htmlspecialchars($status) . "</div>";
    }
    require_once 'database.php';
    try {
        $pdo = Database::getInstance()->getConnection();
        //echo "✅ Connection successful!<br>";
    } catch (PDOException $e) {
        die("❌ Connection failed: " . $e->getMessage());
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
        <a href="search.php">Search</a>
        <?php
            if($_SESSION['username']) {
                echo '<a href="logout.php">Log out</a>';
                echo "<a>".$_SESSION['username']. "</a>";
                echo '<a href="register.php">Register</a>';
            }
            else {
                echo '<a href="login.php">Log in</a>';
            }
        ?>
    </div>
    </body>
    <?php
        try {
            $test = 'user';
            $sql = "Describe `".$test."`";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($columns as $column) {
                echo '<h3>' . $column["Field"] . '</h3>';
            }
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