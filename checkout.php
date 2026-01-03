<?php
session_start();
require_once 'database.php';
require_once 'fpdf.php';

if (!isset($_SESSION['username']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = $_SESSION['username'];
$shipping_address = $_POST['address'] ?? 'N/A';
$total_amount_post = $_POST['total_amount'] ?? 0;

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get User Details
    $stmt = $pdo->prepare("SELECT id, nume, prenume FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $user_id = $user['id'];
    $full_name = $user['nume'] . ' ' . $user['prenume'];

    // Fetch Cart Items for Order
    $sql = "SELECT p.id, p.product_name, p.product_brand, p.price, p.offer, cp.quantity 
            FROM cart c
            JOIN cart_products cp ON c.id = cp.id_cart
            JOIN product p ON cp.id_product = p.id
            WHERE c.id_user = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    if (empty($cart_items)) {
        die("Cart is empty.");
    }

    // Create Order
    $sql = "INSERT INTO shopping_order (ID_USER, TOTAL_AMOUNT, SHIPPING_ADDRESS, DETAILS) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $total_amount_post, $shipping_address, "Generated via PDF"]);
    $order_id = $pdo->lastInsertId();

    // Move items to order_products and calculate total
    function get_discounted_price_checkout($price, $offer_string) {
        if ($offer_string == 0) return $price;
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

    $calculated_total = 0;

    foreach ($cart_items as $item) {
        $price_at_purchase = get_discounted_price_checkout($item['price'], $item['offer']);
        $sql_item = "INSERT INTO order_products (id_order, id_product, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        $stmt_item->execute([$order_id, $item['id'], $item['quantity'], $price_at_purchase]);
        $calculated_total += ($price_at_purchase * $item['quantity']);
    }

    // Clear Cart
    $stmt = $pdo->prepare("DELETE FROM cart_products WHERE id_cart = (SELECT id FROM cart WHERE id_user = ?)");
    $stmt->execute([$user_id]);



    // GENERATE PDF

    class PDF extends FPDF
    {
        function Header()
        {
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(80);
            $this->Cell(30, 10, 'INVOICE', 0, 0, 'C');
            $this->Ln(20);
        }
        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    // Invoice Header Info
    $pdf->Cell(0, 10, 'Order ID: #' . $order_id, 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Cell(0, 10, 'Customer: ' . $full_name, 0, 1);
    $pdf->Cell(0, 10, 'Address: ' . $shipping_address, 0, 1);
    $pdf->Ln(10);

    // Table Header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 10, 'Product', 1);
    $pdf->Cell(30, 10, 'Price', 1);
    $pdf->Cell(20, 10, 'Qty', 1);
    $pdf->Cell(40, 10, 'Total', 1);
    $pdf->Ln();

    // Table Rows
    $pdf->SetFont('Arial', '', 12);
    foreach ($cart_items as $item) {
        $price = get_discounted_price_checkout($item['price'], $item['offer']);
        $total = $price * $item['quantity'];

        // Clean text for PDF (remove special chars if necessary)
        $product_name = substr($item['product_brand'] . ' ' . $item['product_name'], 0, 40); 

        $pdf->Cell(100, 10, $product_name, 1);
        $pdf->Cell(30, 10, number_format($price, 2), 1);
        $pdf->Cell(20, 10, $item['quantity'], 1);
        $pdf->Cell(40, 10, number_format($total, 2), 1);
        $pdf->Ln();
    }

    // Total
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(150, 10, 'Grand Total', 1);
    $pdf->Cell(40, 10, number_format($calculated_total, 2) . ' Lei', 1);

    $pdf->Output('D', 'Invoice_Order_' . $order_id . '.pdf');

} catch (PDOException $e) {
    die("Transaction failed: " . $e->getMessage());
}
?>