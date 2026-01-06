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
        <script src="https://www.google.com/recaptcha/api.js" async defer></script> </head>
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
        <form action="register_func.php" method="post">
            <label for="nume">Nume:</label><br>
            <input type="text" id="nume" name="nume"><br>
            <label for="prenume">Prenume:</label><br>
            <input type="text" id="prenume" name="prenume"><br>
            <label for="email">E-Mail:</label><br>
            <input type="text" id="email" name="email"><br>
            <label for="parola">Parola:</label><br>
            <input type="text" id="parola" name="parola"><br>
            <div class="g-recaptcha" data-sitekey="<?php echo getenv('RECAPTCHA_SITE_KEY'); ?>"></div>
            <input type="submit" value="Register">

        <?php
            if (!empty($status)) {
                echo "<div class='status-message'>" . htmlspecialchars($status) . "</div>";
            }
        ?>
        </form>
    </body>