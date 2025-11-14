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
            $email = $_POST['email'];

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
                $sql = "INSERT INTO user (nume, prenume, email) VALUES (?, ?, ?)";
                $stmt = $pdo -> prepare($sql);
                $stmt -> execute([$_POST['nume'], $_POST['prenume'], $_POST['email']]);
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