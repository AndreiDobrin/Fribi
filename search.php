<?php
    session_start();
    if(!$_SESSION['username']) {
        header('Location: login.php');
    }
    else if($_SESSION['privilege'] != 'Admin') {
        header('Location: index.php');
        echo('bl');
    }

    require_once 'database.php';
        try {
            $pdo = Database::getInstance()->getConnection();
            // echo "✅ Connection successful!<br>";
        } 
        catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
    

?>
<!DOCTYPE html>

    <head>
        <link rel="stylesheet" href="styles.css">
    </head>

    <body>
    <div class="topnav">
        <a class="active" href="index.php">Home</a>
        <?php
            
            if($_SESSION['privilege'] == 'Admin') {
                echo '<a href="search.php">Search</a>';
            }

            echo '<a id="username">' . htmlspecialchars($_SESSION['username']) . '</a>';

            if($_SESSION['username'] != 'Guest') {
                echo '<a id="log" href="logout.php">Log out</a>';
            }
            else {
                echo '<a id="log" href="login.php">Log in</a>';
                echo '<a id="log" href="register.php">Register</a>';
            }
        ?>
        <?php if($_SESSION['username'] != 'Guest') { ?>
                <a href="favorites.php" style="margin-left: auto; margin-right: 0;">Favorites </a>
            <?php } ?>
        
        <a href="shopping_cart.php" id="shopping_cart_icon">
            <img src=   "shopping-cart-icon.png" height="20" width="20">
        </a>
            
    </div>
        <div class="searchform">
            <form class="form" action="search_submit.php" method="post"> <!-- de facut cu get -->
                <!--<input list="table"> -->
                <select name="table" id="table">
                                <?php
                                    require_once 'database.php';
                                    try {
                                        $pdo = Database::getInstance()->getConnection();
                                        // echo "✅ Connection successful!<br>";
                                    } 
                                    catch (PDOException $e) {
                                        die("❌ Connection failed: " . $e->getMessage());
                                    }
                                    $sql = "SHOW TABLES";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute();
                                    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
                                    foreach($tables as $table_row) {
                                        echo '<option value="'. $table_row[0] .'">' . $table_row[0] . '</option>';
                                    }
                                    echo '</select>';
                
                ?>
                <div id="response"></div>
                <input type="submit" value="Search">
            </form>
            </div>
            <script src="search_input_check.js"></script>      
    </body>

    <?php
        require_once 'database.php';
        try {
            $pdo = Database::getInstance()->getConnection();
            // echo "✅ Connection successful!<br>";
        } 
        catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
        try {
            $sql = "SELECT * FROM ?";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
        ?>
<?php

?>
</html>