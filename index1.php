<!DOCTYPE html>
<html lang="en">
<head>
</head>

<body>
    <h1>CREATION</h1>
        <form action="register.php" method="post">
            <label for="nume">Nume:</label><br>
            <input type="text" id="nume" name="nume"><br>
            <label for="prenume">Prenume:</label><br>
            <input type="text" id="prenume" name="prenume"><br>
            <label for="email">E-Mail:</label><br>
            <textarea id="email" name="email"></textarea><br>
            <input type="submit">
        </form>
    </body>
    <?php
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