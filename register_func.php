    <?php
        session_start();


        require_once 'database.php';
        try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Connection successful!<br>";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }

        if(isset($_POST['email']) && isset($_POST['nume']) && isset($_POST['prenume']))  {
            $secret = getenv('RECAPTCHA_SECRET_KEY');
                $whitelist = ['127.0.0.1', '::1', 'localhost'];
                $isLocal = in_array($_SERVER['REMOTE_ADDR'], $whitelist) || $_SERVER['SERVER_NAME'] === 'localhost';
            $captcha_response = $_POST['g-recaptcha-response'] ?? '';
                    $captchaSuccess = false;

                if ($isLocal) {
                    // AUTOMATICALLY PASS if local
                    $captchaSuccess = true;
                }
                else {
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);            $responseData = json_decode($verifyResponse);
            
            if(!$responseData->success) {
                $_SESSION['status'] = "Please verify you are not a robot.";
                header("Location: register.php");
                exit;
            }
            $email = $_POST['email'];
        }

        try {
            $pdo = Database::getInstance()->getConnection();
                    $sql = "SELECT email FROM user WHERE email = ?";
            $stmt = $pdo->prepare($sql);


            $stmt->execute([$email]);
            $results = $stmt->fetch(PDO::FETCH_ASSOC);
            if($results){
                echo $results['email'];
                $_SESSION['status'] = "Acest e-mail este deja inregistrat";
                header("Location: register.php");
                exit;
            }
            else {
                $sql = "INSERT INTO user (nume, prenume, email, parola) VALUES (?, ?, ?, ?)";
                $stmt = $pdo -> prepare($sql);
                $stmt -> execute([$_POST['nume'], $_POST['prenume'], $_POST['email'], $_POST['parola']]);
                $_SESSION['status'] = "Inregistrat cu succes";
                header("Location: register.php");
                exit;
            }
            //echo "<script type='text/javascript'>alert($results[email]);</script>";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
            //header("Location: index1.php");
    }

    ?>