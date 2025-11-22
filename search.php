<!DOCTYPE html>

    <head>
        <link rel="stylesheet" href="styles.css">
    </head>

    <body>
        <div class="topnav">
            <a class="active" href="index.php">Home</a>
            <a href="#news">News</a>
            <a href="#contact">Contact</a>
            <a href="search.php">Search</a>
            <a href="login.php">Log in</a>
            <a href="register.php">Register</a>
        </div>
            <form action="search_submit.php" method="post">
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
                                    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach($tables as $table_value) {
                                        echo '<option value='. $table_value["Tables_in_andrei"] .'>' . $table_value["Tables_in_andrei"] . '</option>';
                                    }
                                    echo '</select>';
                
                ?>
                <div id="response"></div>
                <input type="submit" value="Search">
            </form>
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

</html>