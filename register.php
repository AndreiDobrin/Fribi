    <?php
        session_start(); //daca se foloseste variabila $_SESSION, este necesara apelarea functiei session_start
        //include 'index1.php';
        if(isset($_SESSION['status'])) {
            $status = $_SESSION['status'];
            unset($_SESSION['status']);
        }
        ?>
    <!DOCTYPE HTML>
    <head>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <div class="topnav">
            <a class="active" href="index.php">Home</a>
            <a href="#news">News</a>
            <a href="#contact">Contact</a>
            <a href="search.php">Search</a>
            <a href="login.php">Log in</a>
            <a href="register.php">Register</a>
        </div>
        <form action="register_func.php" method="post">
            <label for="nume">Nume:</label><br>
            <input type="text" id="nume" name="nume"><br>
            <label for="prenume">Prenume:</label><br>
            <input type="text" id="prenume" name="prenume"><br>
            <label for="email">E-Mail:</label><br>
            <input type="text" id="email" name="email"><br>
            <input type="submit">

        <?php
            if (!empty($status)) {
                echo "<div class='status-message'>" . htmlspecialchars($status) . "</div>";
            }
        ?>
        </form>
    </body>