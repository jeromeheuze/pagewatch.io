<?php
// includes/dbconnect.php

define('DB_HOST', 'localhost');
define('DB_USER', 'spectrum_pwio_u');
define('DB_PASS', 'F(Kde#!@BsZ5~vpN');
define('DB_NAME', 'spectrum_pagewatchio');

$DBcon = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($DBcon->connect_error) {
    die("Database connection failed: " . $DBcon->connect_error);
}

// Optional: set charset
$DBcon->set_charset("utf8mb4");
?>
