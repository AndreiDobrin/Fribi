<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'Guest') {
    $_SESSION['status'] = "Please log in to add items to the cart.";
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $email = $_SESSION['username'];

    try {
        $pdo = Database::getInstance()->getConnection();

        // Get User ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) die("User not found.");
        $user_id = $user['id'];

        // Get or Create Cart
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE id_user = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch();

        if ($cart) {
            $cart_id = $cart['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (id_user) VALUES (?)");
            $stmt->execute([$user_id]);
            $cart_id = $pdo->lastInsertId();
        }

        // Add Product to Cart (or update quantity)
        $stmt = $pdo->prepare("SELECT quantity FROM cart_products WHERE id_cart = ? AND id_product = ?");
        $stmt->execute([$cart_id, $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE cart_products SET quantity = quantity + 1 WHERE id_cart = ? AND id_product = ?");
            $stmt->execute([$cart_id, $product_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart_products (id_cart, id_product, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$cart_id, $product_id]);
        }

        // Optional: Redirect with success message
        $_SESSION['status'] = "Product added to cart!";
        header('Location: ' . $_SERVER['HTTP_REFERER']); // Go back to the previous page

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header('Location: index.php');
}
?>