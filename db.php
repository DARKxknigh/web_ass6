<?php
// Database connection - change these values for your server
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "omnimart_store";

// Folder name of the project in htdocs (with slashes)
define('BASE_URL', '/ecommerce/');

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
