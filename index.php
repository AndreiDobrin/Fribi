<?php
    session_start();

    if(empty($_SESSION['username'])) {
        // header('Location: login.php');
        $_SESSION['username'] = 'Guest';
        $_SESSION['privilege'] = 'Guest';
    }


    if(isset($_SESSION['status'])) {
        $status = $_SESSION['status'];
        unset($_SESSION['status']);
    }

    if (!empty($status)) {
        echo "<div class='status-message'>" . htmlspecialchars($status) . "</div>";
    }
    require_once 'database.php';
    try {
        $pdo = Database::getInstance()->getConnection();
        //echo "✅ Connection successful!<br>";
    } catch (PDOException $e) {
        die("❌ Connection failed: " . $e->getMessage());
    }


    // Pagination config
    $results_per_page = 20; // products per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start_from = ($page - 1) * $results_per_page;

    function price_discount_megaimage($offer_string, &$loyalty_card, &$offer_percentage, &$offer_multi_product) {

    $loyalty_card = 0;
    $offer_percentage = 0;
    $offer_multi_product = 0;
                                        
    if (strchr($offer_string, "CONNECT") != False){
        $loyalty_card = 1;
    }
    if (strpos($offer_string, '-') != False || strpos($offer_string, '-') == 0) {
        $offer_percentage = substr($offer_string, strpos($offer_string, '-') + 1, strpos($offer_string, '%') - strpos($offer_string, '-') -1);
    }
        if (strpos($offer_string, "-lea") != False) {   
        $offer_multi_product = 1;
        $offer_per_product = round($offer_percentage/$offer_string[strpos($offer_string, "-lea") - 1], 2);
    }
    if (strpos($offer_string, "comanzi ") != False && strpos($offer_string, "platesti ") != False) {
        $offer_percentage = (round((intval($offer_string[strpos($offer_string, "platesti ") + 9])) / (intval($offer_string[strpos($offer_string, "comanzi ") + 8])), 2))*100;
        $offer_multi_product = 1;
    }

    //echo $offer_string[strpos($offer_string, "platesti ") + 9] . " strpos<br>";
    //echo $offer_string[strpos($offer_string, "comanzi ") + 8] . " strposs<br>";
    //echo $loyalty_card . "<br>" . $offer_percentage . "<br>" . $offer_multi_product . "<br><br>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <h1><?php echo htmlspecialchars($_SESSION['privilege']); ?></h1>

    <div class="topnav">
        <a class="active" href="index.php">Home</a>
        <?php
            
            if($_SESSION['privilege'] == 'Admin') {
                echo '<a href="search.php">Search</a>';
            }
            if($_SESSION['username'] != 'Guest') {
                echo '<a href="logout.php">Log out</a>';
                echo "<a>".$_SESSION['username']. "</a>";
            }
            else {
                echo '<a href="login.php">Log in</a>';
                echo '<a href="register.php">Register</a>';
            }
        ?>
            <a href="shopping_cart.php" id="shopping_cart_icon">
                <img src="shopping-cart-icon.png" height="25" width="25">
            </a>
    </div>

    <div style="margin: 20px; text-align: left;">
        <form action="" method="GET">
                <input type="checkbox" name="filter" value="offers">Show Offers Only</button>
            <select name="category" id="category">
                <option value="all">All Categories</option>
                <?php
                    require_once 'database.php';
                    try {
                        $pdo = Database::getInstance()->getConnection();
                        // echo "✅ Connection successful!<br>";
                    } 
                    catch (PDOException $e) {
                        die("❌ Connection failed: " . $e->getMessage());
                    }
                    $catStmt = $pdo->query("SELECT nume FROM category");
                    $allCategories = $catStmt->fetchAll(PDO::FETCH_NUM);
                    foreach($allCategories as $cat) {
                        $selected = (isset($_GET['category']) && $_GET['category'] == $cat[0]) ? 'selected' : '';
                        echo '<option value="'. htmlspecialchars($cat[0]) .'" '.$selected.'>' . htmlspecialchars($cat[0]) . '</option>';
                    }
                    
                    echo '</select>';
                ?>
            <input type="submit" value="Search" style="margin-right:10px">
            %%TOTAL%% Results
            </form>
            
            </div>
    <div class="product-grid">
        <?php
            try {
                // Build query dynamically
                $sql = "SELECT * FROM PRODUCT";
                $whereClauses = [];
                $params = [];

                // Offer filter
                if (isset($_GET['filter'])) {
                    $whereClauses[] = "offer != 0";
                }

                // Handle Category Filter
                if (isset($_GET['category']) && $_GET['category'] != 'all') {
                    // (Ideally, <option> value should be the ID, not the name, to skip this loop)
                    $catId = 0;
                    foreach($allCategories as $index => $catRow) {
                        if($catRow[0] == $_GET['category']) {
                            $catId = $index + 1; // Assuming IDs start at 1 and match index
                            break;
                        }
                    }
                    if ($catId > 0) {
                        $whereClauses[] = "id_category = ?";
                        $params[] = $catId;
                    }
                }

                // Combine WHERE clauses
                if (count($whereClauses) > 0) {
                    $sql .= " WHERE " . implode(' AND ', $whereClauses);
                }

                // Count Total Results
                $countSql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute($params);
                $total_records = $countStmt->fetchColumn();
                $total_pages = ceil($total_records / $results_per_page);

                $content = ob_get_clean();
                echo str_replace("%%TOTAL%%", $total_records, $content);

                // Fetch data LIMIT
                $sql .= " LIMIT $start_from, $results_per_page";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Show products
                if($records) {
                    foreach ($records as $record) {
                        price_discount_megaimage($record['offer'],$loyalty_card, $offer_percentage, $offer_multi_product);
                        ?>
                        <div class="product-card">
                            <h3><?php echo htmlspecialchars($record['product_brand'] . " " . $record['product_name']); ?></h3>

                            <img src="<?php echo htmlspecialchars($record['image_src']); ?>" alt="Product Image">

                            <div class="price-box">
                                <?php if($record['offer'] != 0): ?>
                                    <span class="current-price"><?php echo round($record['price'] - $record['price'] * $offer_percentage / 100, 2); ?> Lei</span>
                                    <br>
                                    <!-- <span class="original-price"><?php #echo round($originalPrice, 2); ?> Lei</span> -->
                                    <!-- <span class="discount-badge">(<?php #echo abs($record['offer']); ?>% OFF)</span> -->
                                     <span class="original-price"> <?php echo $record['price'] . " Lei" ?></span>
                                     <span class="discount-badge"><?php echo $record['offer'] ?></span>
                                <?php else: ?>
                                    <span class="current-price"><?php echo $record['price']; ?> Lei</span>
                                <?php endif; ?>
                            </div>

                            <p class="unit-price"><?php echo $record['price_per_unit'] . ' Lei/' . $record['unit']; ?></p>
                            <button type="button">Add to cart</button>
                            <button type="button">Add to favorites</button>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No products found matching your criteria.</p>";
                }

            } catch (PDOException $e) {
                die("Error: " . $e->getMessage());
            }
        ?>
    </div> <div class="pagination">
        <?php
            //Preserve current filters in the pagination links
            $queryParams = $_GET;
            
            for ($i = 1; $i <= $total_pages; $i++) {
                $queryParams['page'] = $i;
                $link = '?' . http_build_query($queryParams); // Rebuild URL with new page number
                $activeClass = ($i == $page) ? 'active' : '';
                echo "<a class='$activeClass' href='$link'>$i</a>";
            }
        ?>
    </div>
    </body>
    
</html>