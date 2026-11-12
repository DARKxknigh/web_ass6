<?php
$page_title = "Home";
require_once 'includes/header.php';

$banners = get_all($conn, "SELECT * FROM banners WHERE active = 1 ORDER BY id DESC");
$categories = get_all($conn, "SELECT * FROM categories ORDER BY name");
$base_select = "SELECT p.*, IFNULL((SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating
                FROM products p JOIN vendors v ON v.id = p.vendor_id WHERE v.status = 'approved'";
$featured = get_all($conn, $base_select . " AND p.featured = 1 ORDER BY p.id DESC LIMIT 8");
$latest = get_all($conn, $base_select . " ORDER BY p.id DESC LIMIT 8");
?>

<?php foreach ($banners as $b) { ?>
  <div class="banner">
    <h2><?= e($b['title']) ?></h2>
    <p><?= e($b['subtitle']) ?></p>
    <?php if ($b['link'] != "") { ?><a href="<?= e($b['link']) ?>">Shop now</a><?php } ?>
  </div>
<?php } ?>

<h2><?= t('categories') ?></h2>
<div class="cats">
  <?php foreach ($categories as $c) { ?>
    <a href="search.php?category=<?= $c['id'] ?>"><?= e($c['name']) ?></a>
  <?php } ?>
</div>

<h2><?= t('featured') ?></h2>
<div class="grid">
  <?php foreach ($featured as $p) { product_card($p); } ?>
</div>

<h2>New Arrivals</h2>
<div class="grid">
  <?php foreach ($latest as $p) { product_card($p); } ?>
</div>

<?php
// recently viewed products (saved in session)
if (isset($_SESSION['recent']) && count($_SESSION['recent']) > 0) {
    echo "<h2>Recently Viewed</h2><div class='grid'>";
    foreach ($_SESSION['recent'] as $id) {
        $p = get_one($conn, "SELECT * FROM products WHERE id = ?", "i", [$id]);
        if ($p) {
            product_card($p);
        }
    }
    echo "</div>";
}
require_once 'includes/footer.php';
