<?php
session_start();


require_once 'database.php';
        try {
            $pdo = Database::getInstance()->getConnection();
            // echo "✅ Connection successful!<br>";
        } 
        catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }

?>

<html>
        <head>
        <link rel="stylesheet" href="styles.css">
        </head>
        <div class="topnav">
            <a class="active" href="index.php">Home</a>
            <!-- <a href="search.php">Search</a> -->
            <?php
                    echo '<a href="login.php">Log in</a>';
                    echo '<a href="register.php">Register</a>';

            ?>
        </div>
        <form action="login.php" method="post">
            <label for="email">E-Mail:</label><br>
            <input type="text" id="email" name="email"><br>
            <label for="parola">Parola:</label><br>
            <input type="text" id="parola" name="parola"><br>
            <input type="submit" value="Log in">
        <?php
            if(isset($_POST['email']) && isset($_POST['parola'])) {

                    try {
            $pdo = Database::getInstance()->getConnection();
            $sql = "SELECT email,parola FROM user WHERE email = ?";
            $stmt = $pdo->prepare($sql);

            $email = $_POST['email'];
            $stmt->execute([$email]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo $results[0]['parola'];
            $inputted_password = $_POST['parola'];
            if($inputted_password == $results[0]['parola']) {
                $_SESSION['username'] = $results[0]['email'];
                    $sql = "SELECT privilege FROM user WHERE email = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$email]);
                    $results = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($results['privilege'] == 'Admin')
                        $_SESSION['privilege'] = 'Admin';
                header('Location: index.php');
            }
            else {
                <<<HTML
                    <h3> Parola gresita </h3>
                HTML;
            }
            //echo "<script type='text/javascript'>alert($results[email]);</script>";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
        }
        ?>
        </form>
        <button onclick="location.href='register.php'">Register</button>
</html>