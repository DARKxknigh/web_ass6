<?php
require_once 'includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $email = trim($_POST['email']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        run($conn, "INSERT IGNORE INTO newsletter (email) VALUES (?)", "s", [$email]);
        flash("Subscribed to the newsletter.");
    } else {
        flash("Enter a valid email.");
    }
}
redirect("index.php");
