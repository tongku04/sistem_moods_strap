<?php
session_start();
session_unset();

// Hancurkan session
session_destroy();

if(!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}