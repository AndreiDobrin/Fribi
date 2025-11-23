<?php
    require_once 'database.php';
    if(isset($_POST['table'])) {
        
        try {
            $queried_table = $_POST['table'];
            $pdo = Database::getInstance()->getConnection();
                    $sql = "SELECT * FROM `". $queried_table ."`";
            $stmt = $pdo->prepare($sql);


            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sql = "DESCRIBE `". $queried_table ."`";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            $table_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            $sql = "SELECT * FROM `". $queried_table ."`";

            for($x = 0; $x < count($table_columns); $x ++) {

                if(isset($_POST[$table_columns[$x]["Field"]]) && !empty($_POST[$table_columns[$x]["Field"]])) {
                    if ($count == 0) {
                        $sql = $sql . " " . "WHERE ";
                    }
                    if ($count > 0) {
                        $sql = $sql . " " . "AND ";
                    }
                    $sql = $sql . "" . $table_columns[$x]['Field'] ." = '".$_POST[$table_columns[$x]['Field']]."'";

                    echo " $sql </h1>";
                    $count ++;
                }
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //var_dump($results);
            foreach($results as $result) {
                echo '<div id="result">';
                foreach($table_columns as $table_column) {
                    echo '<h4>'. $table_column["Field"]. ': ' . $result[$table_column["Field"]] . '</h4>';
                }
            echo "
                <form action=\"delete.php\" method=\"post\">
                    <input type=\"hidden\" name=\"{$table_columns[0]['Field']}\" value=\"{$results[0][$table_column['Field']]}\">
                    <button type=\"submit\" name=\"action\" value=\"delete_action\">Delete</button>
                    <button type=\"submit\" name=\"action\" value=\"modify_action\">Modify</button></div>
                </form>
                ";
            }
/*
            foreach($results as $result) {

                foreach($table_columns as $table_column) {
                    echo '<h1>' . $table_column["Field"] . ': ';
                    echo $result
                }

            }
*/



        }  catch (PDOException $e) {
            die("❌ Connection failed: " . $e->getMessage());
        }
}
else echo '<h1> some error </h1>';
?>