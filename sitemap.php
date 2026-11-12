<?php
// XML sitemap for search engines
require_once 'includes/functions.php';
header("Content-Type: application/xml; charset=utf-8");
$host = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo "<url><loc>" . e($host . BASE_URL) . "</loc></url>\n";
echo "<url><loc>" . e($host . BASE_URL . "search.php") . "</loc></url>\n";
echo "<url><loc>" . e($host . BASE_URL . "contact.php") . "</loc></url>\n";
$products = get_all($conn, "SELECT p.id, p.name FROM products p JOIN vendors v ON v.id = p.vendor_id WHERE v.status = 'approved'");
foreach ($products as $p) {
    echo "<url><loc>" . e($host . product_url($p)) . "</loc></url>\n";
}
echo "</urlset>";
