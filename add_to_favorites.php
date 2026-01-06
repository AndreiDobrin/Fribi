<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'Guest') {
    $_SESSION['status'] = "Trebuie să fii autentificat pentru a adăuga la favorite.";
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $email = $_SESSION['username'];

    try {
        $pdo = Database::getInstance()->getConnection();

        $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user_id = $user['id'];

            // Check if exists
            $check = $pdo->prepare("SELECT 1 FROM favorites WHERE id_user = ? AND id_product = ?");
            $check->execute([$user_id, $product_id]);

            if (!$check->fetch()) {
                // Add to favs
                $insert = $pdo->prepare("INSERT INTO favorites (id_user, id_product) VALUES (?, ?)");
                $insert->execute([$user_id, $product_id]);
                $_SESSION['status'] = "Produs adăugat la favorite!";
            } else {
                $_SESSION['status'] = "Produsul este deja la favorite.";
            }
        }
    } catch (PDOException $e) {
        // Ignor erorile
    }
}
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>