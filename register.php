    <?php
        require_once 'database.php';
        try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Connection successful!<br>";
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }



        try {
            echo "ok";
            $pdo = Database::getInstance()->getConnection();
                    $sql = "INSERT INTO user (nume, prenume, email) 
                    VALUES (:lname, :fname, :e_mail)";

            $stmt = $pdo->prepare($sql);

            // Sample data
            $data = [
                'lname' => $_POST['nume'] ?? $nume,
                'fname' => $_POST['prenume'] ?? $prenume,
                'e_mail' => $_POST['email'] ?? $email
            ];
        
            $stmt->execute($data);
        } catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }

        header("Location: index1.php");
    ?>