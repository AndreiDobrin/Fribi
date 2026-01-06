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
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        </head>
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
                <a href="favorites.php" id="log">Favorites</a>
            <?php } ?>
        
        <a href="shopping_cart.php" id="shopping_cart_icon">
            <img src=   "shopping-cart-icon.png" height="20" width="20">
        </a>
            
    </div>
        <form action="login.php" method="post">
            <label for="email">E-Mail:</label><br>
            <input type="text" id="email" name="email"><br>
            <label for="parola">Parola:</label><br>
            <input type="text" id="parola" name="parola"><br>
            <div class="g-recaptcha" data-sitekey="<?php echo getenv('RECAPTCHA_SITE_KEY'); ?>"></div>
            <input type="submit" value="Log in">
        <?php
            if(isset($_POST['email']) && isset($_POST['parola'])) {
                $whitelist = ['127.0.0.1', '::1', 'localhost'];
                $isLocal = in_array($_SERVER['REMOTE_ADDR'], $whitelist) || $_SERVER['SERVER_NAME'] === 'localhost';

                
                $captchaSuccess = false;

                if ($isLocal) {
                    // AUTOMATICALLY PASS if local
                    $captchaSuccess = true; 
                } else {
                    $secret = getenv('RECAPTCHA_SECRET_KEY');
                    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);
                    $responseData = json_decode($verifyResponse);
                    if ($responseData->success) {
                        $captchaSuccess = true;
                    }
                    }
                        if(!$captchaSuccess) {
                            echo "<h3>Please complete the CAPTCHA validation.</h3>";
                        }
                        else {
                            try {
                                $pdo = Database::getInstance()->getConnection();
                                $sql = "SELECT email,parola FROM user WHERE email = ?";
                                $stmt = $pdo->prepare($sql);
                                        
                                $email = $_POST['email'];
                                $stmt->execute([$email]);
                                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                if(!$results) {
                                    echo "Error, please try again later.";
                                }
                                else {
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
                                        else
                                            $_SESSION['privilege'] = 'User';
                                    header('Location: index.php');
                                }
                                else {
                                    <<<HTML
                                        <h3> CREDENTIALE INVALIDE </h3>
                                    HTML;
                                }                                
                            }
                                //echo "<script type='text/javascript'>alert($results[email]);</script>";
                            } catch (PDOException $e) {
                            die("❌ Connection failed: " . $e->getMessage());
                        }
        }
    }
        ?>
        </form>
        <button onclick="location.href='register.php'">Register</button>
</html>