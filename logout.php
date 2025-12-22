<?php
    session_start();
    unset($_SESSION['username']);
    $_SESSION['username'] = 'Guest';
    $_SESSION['privilege'] = 'Guest';
    header('Location: index.php');
?>