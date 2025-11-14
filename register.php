    <?php
        //include 'index1.php';
        require_once 'database.php';
        try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Connection successful!<br>";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }

        if(isset($_POST['email']) ) {
            $email = $_POST['email'];
        }

        try {
            $pdo = Database::getInstance()->getConnection();
                    $sql = "SELECT email FROM user WHERE email = ?";
            $stmt = $pdo->prepare($sql);


            $stmt->execute([$email]);
            $results = $stmt->fetch(PDO::FETCH_ASSOC);
            if($results){
                echo($results['email']);
            }
            else {
                echo "Nu exista";
            }
            //echo "<script type='text/javascript'>alert($results[email]);</script>";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
            //header("Location: index1.php");

    ?>