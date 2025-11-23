<?php
require_once 'database.php';
session_start();

try {
    $pdo = Database::getInstance()->getConnection();

    if (!isset($_POST['table_name']) || !isset($_POST['pk_name']) || !isset($_POST['pk_value'])) {
        die("Error");
    }
    $table   = $_POST['table_name']; 
    $pk_name = $_POST['pk_name'];
    $pk_val  = $_POST['pk_value'];
    $action  = $_POST['action'];

    // --- DELETE ---
    if ($action == 'delete_action') {

        $sql = "DELETE FROM `$table` WHERE `$pk_name` = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$pk_val]);

        echo "Record deleted successfully <a href='search.php'>Go back</a>";
    } 
    
    // --- MODIFY (Show Form) ---
    elseif ($action == 'modify_action') {
        $sql = "SELECT * FROM `$table` WHERE `$pk_name` = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$pk_val]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) die("Record not found.");

        echo "<h2>Modify Record in '$table'</h2>";
        echo "<form action='delete.php' method='POST'>";
        
        // Pass the keys again so the 'save' block knows what to update
        echo "<input type='hidden' name='table_name' value='$table'>";
        echo "<input type='hidden' name='pk_name' value='$pk_name'>";
        echo "<input type='hidden' name='pk_value' value='$pk_val'>";
        
        // Generate inputs for every column
        foreach ($row as $col => $val) {
            // Don't let them edit the ID (Primary Key)
            if ($col == $pk_name) {
                echo "<p><strong>$col:</strong> $val (Cannot change ID)</p>";
            } else {
                echo "<label>$col:</label><br>";
                echo "<input type='text' name='data[$col]' value='" . htmlspecialchars($val) . "'><br><br>";
            }
        }

        echo "<button type='submit' name='action' value='save_changes'>Save Changes</button>";
        echo "</form>";
    }

    // --- SAVE CHANGES ---
    elseif ($action == 'save_changes') {
        $newData = $_POST['data']; // [] column => new_value

        // "UPDATE user SET name=?, email=? WHERE id=?"
        $setParts = [];
        $values = [];

        foreach ($newData as $col => $val) {
            $setParts[] = "`$col` = ?";
            $values[] = $val;
        }

        $values[] = $pk_val;

        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE `$pk_name` = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        echo "Record updated successfully! <a href='search.php'>Go back</a>";
    }

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>