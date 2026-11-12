<?php
$page_title = "Shop";
require_once 'includes/header.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : 0;
$max = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : 0;
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';

// build the query step by step
$sql = "SELECT p.*, IFNULL((SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating
        FROM products p JOIN vendors v ON v.id = p.vendor_id
        WHERE v.status = 'approved'";
$types = "";
$params = [];

if ($q != "") {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $types .= "ss";
    $params[] = "%" . $q . "%";
    $params[] = "%" . $q . "%";
}
if ($cat > 0) {
    $sql .= " AND p.category_id = ?";
    $types .= "i";
    $params[] = $cat;
}
if ($min > 0) {
    $sql .= " AND p.price >= ?";
    $types .= "d";
    $params[] = $min;
}
if ($max > 0) {
    $sql .= " AND p.price <= ?";
    $types .= "d";
    $params[] = $max;
}
$sql .= " HAVING avg_rating >= ?";
$types .= "i";
$params[] = $rating;

if ($sort == 'low') {
    $sql .= " ORDER BY p.price ASC";
} elseif ($sort == 'high') {
    $sql .= " ORDER BY p.price DESC";
} else {
    $sql .= " ORDER BY p.id DESC";
}

$products = get_all($conn, $sql, $types, $params);
$categories = get_all($conn, "SELECT * FROM categories ORDER BY name");
?>
<h1>Shop</h1>
<form method="get" class="filters">
  <input type="text" name="q" placeholder="Search..." value="<?= e($q) ?>">
  <select name="category">
    <option value="0">All categories</option>
    <?php foreach ($categories as $c) { ?>
      <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php } ?>
  </select>
  <input type="number" name="min" placeholder="Min Rs" value="<?= $min > 0 ? $min : '' ?>" style="width:100px">
  <input type="number" name="max" placeholder="Max Rs" value="<?= $max > 0 ? $max : '' ?>" style="width:100px">
  <select name="rating">
    <option value="0">Any rating</option>
    <?php for ($i = 4; $i >= 1; $i--) { ?>
      <option value="<?= $i ?>" <?= $rating == $i ? 'selected' : '' ?>><?= $i ?>+ stars</option>
    <?php } ?>
  </select>
  <select name="sort">
    <option value="new" <?= $sort == 'new' ? 'selected' : '' ?>>Newest</option>
    <option value="low" <?= $sort == 'low' ? 'selected' : '' ?>>Price: low to high</option>
    <option value="high" <?= $sort == 'high' ? 'selected' : '' ?>>Price: high to low</option>
  </select>
  <button>Filter</button>
</form>
<p><?= count($products) ?> products found <small>(price filters are in INR)</small></p>
<div class="grid">
  <?php foreach ($products as $p) { product_card($p); } ?>
</div>
<?php require_once 'includes/footer.php'; ?>
