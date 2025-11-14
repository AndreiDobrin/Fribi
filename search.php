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
            <form action="/action_page.php">
                <input list="table">
                <datalist id="table">
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
                                        echo '<option value='. $table["Tables_in_andrei"] .'>';
                                    }

                                    if(isset($_POST['table'])) {
                                        echo "ble";
                                    }
                                ?>
                </datalist>
                <input list="options">
                <datalist id="options">

                </datalist>
            </form>

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