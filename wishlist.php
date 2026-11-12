<?php
require_once 'includes/functions.php';
require_customer();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $pid = (int)$_POST['product_id'];
    if ($_POST['action'] == 'add') {
        run($conn, "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)", "ii", [$_SESSION['user_id'], $pid]);
        flash("Added to wishlist.");
    } else {
        run($conn, "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?", "ii", [$_SESSION['user_id'], $pid]);
        flash("Removed from wishlist.");
    }
}
redirect("profile.php");
