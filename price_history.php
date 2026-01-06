<?php
session_start();
require_once 'database.php';

if (!isset($_GET['product_id'])) {
    header('Location: index.php');
    exit;
}

$product_id = intval($_GET['product_id']);

function price_discount_megaimage($offer_string, &$loyalty_card, &$offer_percentage, &$offer_multi_product) {
    $loyalty_card = 0;
    $offer_percentage = 0;
    $offer_multi_product = 0;
    
    if (!$offer_string) return;

    if (strchr($offer_string, "CONNECT") != False){
        $loyalty_card = 1;
    }

    if (strpos($offer_string, '-') !== False) {
        $start = strpos($offer_string, '-');
        $end = strpos($offer_string, '%');
        if ($end !== False && $end > $start) {
             $offer_percentage = substr($offer_string, $start + 1, $end - $start - 1);
        }
    }
    if (strpos($offer_string, "-lea") != False) {   
        $offer_multi_product = 1;

    }
    if (strpos($offer_string, "comanzi ") != False && strpos($offer_string, "platesti ") != False) {

        $idx_pay = strpos($offer_string, "platesti ");
        $idx_order = strpos($offer_string, "comanzi ");
        
        if ($idx_pay !== False && $idx_order !== False) {
             $val_pay = intval($offer_string[$idx_pay + 9]);
             $val_order = intval($offer_string[$idx_order + 8]);
             if ($val_order > 0) {
                $offer_percentage = (1 - ($val_pay / $val_order)) * 100;
                $offer_percentage = round($offer_percentage, 2);
             }
        }
        $offer_multi_product = 1;
    }
}

try {
    $pdo = Database::getInstance()->getConnection();


    $stmt = $pdo->prepare("SELECT product_name, product_brand, price, offer FROM product WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Product not found.");
    }


    $stmt = $pdo->prepare("SELECT price, changed_at FROM price_history WHERE id_product = ? ORDER BY changed_at DESC");
    $stmt->execute([$product_id]);
    $history = $stmt->fetchAll();


    $current_price = (float)$product['price'];
    $final_current_price = $current_price;
    $offer_text = "";
    

    if (!empty($product['offer']) && $product['offer'] != '0') {
        $loyalty_card = 0;
        $offer_percentage = 0;
        $offer_multi_product = 0;
        
        price_discount_megaimage($product['offer'], $loyalty_card, $offer_percentage, $offer_multi_product);
        
        if ($offer_percentage > 0) {
            $final_current_price = round($current_price - ($current_price * $offer_percentage / 100), 2);
            $offer_text = " (Discounted from " . $current_price . " Lei)";
        }
    }


    $chart_labels = [];
    $chart_data = [];


    $chronological_history = array_reverse($history);
    foreach ($chronological_history as $row) {
        $chart_labels[] = date('d-m-Y', strtotime($row['changed_at']));
        $chart_data[] = (float)$row['price'];
    }


    $chart_labels[] = "Prezent";
    $chart_data[] = $final_current_price;

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .history-container { max-width: 800px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .history-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .history-table th, .history-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .history-table th { background-color: #f2f2f2; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #333; font-weight: bold; }
        .chart-box { width: 100%; height: 400px; margin-bottom: 30px; }
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
                <a href="favorites.php" id="log">Favorites</a>
            <?php } ?>
        
        <a href="shopping_cart.php" id="shopping_cart_icon">
            <img src=   "shopping-cart-icon.png" height="20" width="20">
        </a>
            
    </div>

    <div class="history-container">
        <a href="index.php" class="back-link">&larr; Back to Products</a>
        
        <h2>Price History</h2>
        <h3><?php echo htmlspecialchars($product['product_brand'] . ' ' . $product['product_name']); ?></h3>
        
        <p>
            <strong>Current Price:</strong> 
            <span style="color: #d9534f; font-weight: bold; font-size: 1.2em;">
                <?php echo number_format($final_current_price, 2); ?> Lei
            </span>
            <?php if(!empty($offer_text)): ?>
                <span style="color: #999; text-decoration: line-through; margin-left: 10px;">
                    <?php echo number_format($current_price, 2); ?> Lei
                </span>
                <span style="color: green; font-size: 0.9em; margin-left: 5px;">
                    <?php echo htmlspecialchars($product['offer']); ?>
                </span>
            <?php endif; ?>
        </p>

        <div class="chart-box">
            <canvas id="priceChart"></canvas>
        </div>

        <h3>Detailed Logs</h3>
        <?php if (count($history) > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date of Change</th>
                        <th>Historical Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo date('F j, Y, g:i a', strtotime($row['changed_at'])); ?></td>
                            <td><?php echo number_format($row['price'], 2); ?> Lei</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No price changes recorded in the past.</p>
        <?php endif; ?>
    </div>

    <script>
        const ctx = document.getElementById('priceChart').getContext('2d');
        const labels = <?php echo json_encode($chart_labels); ?>;
        const data = <?php echo json_encode($chart_data); ?>;

        const priceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Preț (Lei)',
                    data: data,
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderColor: '#4CAF50',
                    borderWidth: 2,
                    pointBackgroundColor: '#d9534f',
                    pointRadius: 5,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        title: { display: true, text: 'Preț (Lei)' }
                    },
                    x: {
                        title: { display: true, text: 'Data Modificării' }
                    }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' Lei';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>