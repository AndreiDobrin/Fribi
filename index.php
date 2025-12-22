<?php
    session_start();

    if(!$_SESSION['username']) {
        // header('Location: login.php');
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
    <?php 
    echo '<h1>' . $_SESSION['privilege'] .'</h1>';
    ?>
    <div class="topnav">
        <a class="active" href="index.php">Home</a>
        
        <?php
            
            if($_SESSION['privilege'] == 'Admin') {
                echo '<a href="search.php">Search</a>';
            }
            if($_SESSION['username'] != 'Guest') {
                echo '<a href="logout.php">Log out</a>';
                echo "<a>".$_SESSION['username']. "</a>";
            }
            else {
                echo '<a href="login.php">Log in</a>';
                echo '<a href="register.php">Register</a>';
            }
        ?>
    </div>
    </div>

    <div style="margin: 20px; text-align: center;">
        <form action="" method="GET">
            <button type="submit" name="filter" value="all">Show All Products</button>
            <button type="submit" name="filter" value="offers">Show Offers Only</button>
        </form>
    </div>
    </body>
    <?php

        try {
            $sql = "SELECT * from PRODUCT";

            if (isset($_GET['filter']) && $_GET['filter'] == 'offers') {
                $sql .= " WHERE offer != 0";
            }
                
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
                
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($records!=NULL)
            {
                foreach ($records as $record) {
                    echo('<h1>' . $record['product_brand'] . " "  . $record['product_name'] . "</h1>");
                    if($record['offer'] != 0) {
                        echo('<h1>' . $record['price'] . ' Lei <del> '. round($record['price'] * 100 / (100 + $record['offer']), 2) .' Lei</del>('. abs($record['offer']) .'% OFF)</h1>');
                    }
                    else {
                        echo('<h1>' . $record['price'] . ' Lei</h1>');
                    }
                    echo('<h1>' . $record['price_per_unit'] . ' Lei/' . $record['unit'] .'</h1>');
                    echo('<img src=' . $record['image_src'] . '>');
                    echo "<hr>";
                }
            }
            else {
                echo('<h1> Nu au fost gasite rezultate </h1>');
            }
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }


    ?>
</html>