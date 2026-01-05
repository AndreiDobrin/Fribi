<?php
session_start();
require_once 'database.php';

if (!isset($_GET['product_id'])) {
    header('Location: index.php');
    exit;
}

$product_id = intval($_GET['product_id']);

try {
    $pdo = Database::getInstance()->getConnection();

    $stmt = $pdo->prepare("SELECT product_name, product_brand, price FROM product WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Product not found.");
    }

    $stmt = $pdo->prepare("SELECT price, changed_at FROM price_history WHERE id_product = ? ORDER BY changed_at DESC");
    $stmt->execute([$product_id]);
    $history = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Price History - <?php echo htmlspecialchars($product['product_name']); ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .history-container { max-width: 600px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .history-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .history-table th, .history-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .history-table th { background-color: #f2f2f2; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #333; font-weight: bold; }
    </style>
</head>
<body>
    <div class="topnav">
        <a href="index.php">Home</a>
    </div>

    <div class="history-container">
        <a href="index.php" class="back-link">&larr; Back to Products</a>
        
        <h2>Price History</h2>
        <h3><?php echo htmlspecialchars($product['product_brand'] . ' ' . $product['product_name']); ?></h3>
        
        <p><strong>Current Price:</strong> <span style="color: #d9534f; font-weight: bold;"><?php echo $product['price']; ?> Lei</span></p>

        <?php if (count($history) > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date of Change</th>
                        <th>Old Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo date('F j, Y, g:i a', strtotime($row['changed_at'])); ?></td>
                            <td><?php echo $row['price']; ?> Lei</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No price changes recorded in the last 30 days.</p>
        <?php endif; ?>
    </div>
</body>
</html>