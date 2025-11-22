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
            <form>
                <!--<input list="table"> -->
                <select id="table">
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

                                    foreach($tables as $table) {
                                        echo '<option value='. $table["Tables_in_andrei"] .'>' . $table["Tables_in_andrei"] . '</option>';
                                    }
                                    echo '</select>';
                
                ?>
                <input type="submit" value="Search">
            </form>
            <div id="response"></div>
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