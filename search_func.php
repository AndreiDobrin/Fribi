<?php 
require_once 'database.php';
        try {
            $pdo = Database::getInstance()->getConnection();
            // echo "✅ Connection successful!<br>";
        } 
        catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
// 1. Read the raw JSON data from the request body
$json_data = file_get_contents('php://input');

// 2. Decode the JSON data into a PHP associative array or object
// Pass `true` as the second argument to get an associative array
$data = json_decode($json_data);

// 3. Check if the data and our 'table' property exist
if (isset($data) && isset($data->table)) {
    
    // 4. Get the value
    $selectedTable = $data->table;

    // 5. Process the data (e.g., save to database, log to file)
    // For this example, we'll just send a response back.
    
    // Add a check for an empty value
    if (!empty($selectedTable)) {
                    //echo 'Received your choice: ' . htmlspecialchars($selectedTable);
                    $sql = "DESCRIBE `".$selectedTable."`";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $x = 1;
                    foreach($columns as $column) {
                        echo '<input class="column'. $x .'" placeholder='. $column["Field"] .'>';
                        //echo '<select id=column'. $x .'.>';
                        echo '</input>';
                        $x += 1;
                    }
                    
        // Send a success response back to the JavaScript

    } else {
        echo "You cleared the selection.";
    }

} else {
    // Send an error response back if no data was received
    http_response_code(400); // Bad Request
    echo "No data received.";
}
?>

