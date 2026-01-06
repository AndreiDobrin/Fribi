<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'Guest') {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['username'];

try {
    $pdo = Database::getInstance()->getConnection();
    
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user_id = $stmt->fetchColumn();

    if (isset($_POST['remove_id'])) {
        $del = $pdo->prepare("DELETE FROM favorites WHERE id_user = ? AND id_product = ?");
        $del->execute([$user_id, $_POST['remove_id']]);
        $status = "Produs eliminat de la favorite.";
    }

    $sql = "SELECT p.* FROM favorites f
            JOIN product p ON f.id_product = p.id
            WHERE f.id_user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Favorites</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
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
                <a href="favorites.php" style="margin-left: auto; margin-right: 0;">Favorites</a>
            <?php } ?>
        
        <a href="shopping_cart.php" id="shopping_cart_icon">
            <img src=   "shopping-cart-icon.png" height="20" width="20">
        </a>
            
    </div>

    <h2 style="text-align:center">Produsele tale Favorite</h2>
    <?php if(isset($status)) echo "<p style='text-align:center;color:green'>$status</p>"; ?>

    <div class="product-grid">
        <?php if ($favorites): ?>
            <?php foreach ($favorites as $record): ?>
                <div class="product-card">
                    <h3><?php echo htmlspecialchars($record['product_brand'] . " " . $record['product_name']); ?></h3>
                    <img src="<?php echo htmlspecialchars($record['image_src']); ?>" alt="Product Image" style="max-width:150px">
                    <p>Price: <?php echo $record['price']; ?> Lei</p>
                    
                    <div style="display: flex; justify-content: center; gap: 10px;">
                        <form action="add_to_cart.php" method="post">
                            <input type="hidden" name="product_id" value="<?php echo $record['id']; ?>">
                            <button type="submit">Add to Cart</button>
                        </form>
                        
                        <form action="favorites.php" method="post">
                            <input type="hidden" name="remove_id" value="<?php echo $record['id']; ?>">
                            <button type="submit" style="background-color: #d9534f;">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center">Nu ai niciun produs la favorite.</p>
        <?php endif; ?>
    </div>
</body>
</html>