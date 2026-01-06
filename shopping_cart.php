<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] === 'Guest') {
    header('Location: login.php');
    exit;
}
function get_discounted_price($price, $offer_string) {
    if ($offer_string == 0) return $price;
    
    $loyalty_card = 0;
    $offer_percentage = 0;
    
    if (strpos($offer_string, '-') !== false && strpos($offer_string, '%') !== false) {
        $start = strpos($offer_string, '-') + 1;
        $len = strpos($offer_string, '%') - $start;
        $offer_percentage = (float)substr($offer_string, $start, $len);
    }
    if ($offer_percentage > 0) {
        return round($price - ($price * $offer_percentage / 100), 2);
    }
    return $price;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $email = $_SESSION['username'];

    // Get User ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $user_id = $user['id'];

    // Fetch Cart Items
    $sql = "SELECT p.id, p.product_name, p.product_brand, p.price, p.offer, cp.quantity 
            FROM cart c
            JOIN cart_products cp ON c.id = cp.id_cart
            JOIN product p ON cp.id_product = p.id
            WHERE c.id_user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .cart-container { padding: 20px; max-width: 800px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .total-row { font-weight: bold; font-size: 1.2em; }
        .checkout-btn { background-color: #04AA6D; color: white; padding: 10px 20px; text-decoration: none; border: none; cursor: pointer; font-size: 16px; }
    </style>
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
                <a href="favorites.php" style="margin-left: auto; margin-right: 0;">Favorites </a>
            <?php } ?>
        
        <a href="shopping_cart.php" id="shopping_cart_icon">
            <img src=   "shopping-cart-icon.png" height="20" width="20">
        </a>
            
    </div>

    <div class="cart-container">
        <h1>Your Shopping Cart</h1>
        <?php if (empty($cart_items)): ?>
            <p>Your cart is empty.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    foreach ($cart_items as $item): 
                        $final_price = get_discounted_price($item['price'], $item['offer']);
                        $line_total = $final_price * $item['quantity'];
                        $grand_total += $line_total;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['product_brand']); ?></strong><br>
                            <?php echo htmlspecialchars($item['product_name']); ?>
                            <?php if($item['offer'] != '0') echo "<br><small style='color:red'>Offer: {$item['offer']}</small>"; ?>
                        </td>
                        <td><?php echo number_format($final_price, 2); ?> Lei</td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($line_total, 2); ?> Lei</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">Total Amount:</td>
                        <td><?php echo number_format($grand_total, 2); ?> Lei</td>
                    </tr>
                </tbody>
            </table>

            <form action="checkout.php" method="post">
                <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
                <label for="address">Shipping Address:</label>
                <input type="text" name="address" required placeholder="Enter your address" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                <button type="submit" class="checkout-btn">Proceed to Checkout & Download Invoice</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>