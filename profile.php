<?php
require_once 'includes/functions.php';
require_customer();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $name = trim($_POST['name']);
    if ($name != "") {
        run($conn, "UPDATE users SET name = ? WHERE id = ?", "si", [$name, $uid]);
        $_SESSION['name'] = $name;
        flash("Profile updated.");
    }
    if ($_POST['new_password'] != "") {
        if (strlen($_POST['new_password']) >= 6) {
            run($conn, "UPDATE users SET password = ? WHERE id = ?", "si", [password_hash($_POST['new_password'], PASSWORD_DEFAULT), $uid]);
            flash("Profile and password updated.");
        } else {
            flash("Password must be at least 6 characters.");
        }
    }
    redirect("profile.php");
}

$user = get_one($conn, "SELECT * FROM users WHERE id = ?", "i", [$uid]);
$orders = get_all($conn, "SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC", "i", [$uid]);
$wish = get_all($conn, "SELECT p.* FROM wishlist w JOIN products p ON p.id = w.product_id WHERE w.user_id = ?", "i", [$uid]);

$page_title = "My Profile";
require_once 'includes/header.php';
?>
<h1>My Profile</h1>
<div class="box" style="max-width:450px">
  <form method="post">
    <?= csrf_field() ?>
    <label>Name</label>
    <input type="text" name="name" value="<?= e($user['name']) ?>" required>
    <label>Email (cannot change)</label>
    <input type="text" value="<?= e($user['email']) ?>" disabled>
    <label>New password (leave empty to keep old)</label>
    <input type="password" name="new_password">
    <br><br><button>Save</button>
  </form>
</div>

<h2>My Orders</h2>
<?php if (count($orders) == 0) { echo "<div class='box'>No orders yet.</div>"; } ?>
<?php foreach ($orders as $o) {
    $oi = get_all($conn, "SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?", "i", [$o['id']]);
?>
  <div class="box">
    <b>Order #<?= $o['id'] ?></b> - <?= e($o['created_at']) ?> - <span class="tag"><?= e($o['status']) ?></span>
    - Total: <?= money($o['total_amount']) ?>
    <ul>
      <?php foreach ($oi as $item) { ?>
        <li><?= e($item['name']) ?> <?= e($item['variation']) ?> x <?= $item['quantity'] ?> (<?= money($item['price']) ?>)</li>
      <?php } ?>
    </ul>
  </div>
<?php } ?>

<h2 id="wishlist">My Wishlist</h2>
<?php if (count($wish) == 0) { echo "<div class='box'>Your wishlist is empty.</div>"; } ?>
<div class="grid">
  <?php foreach ($wish as $p) {
      product_card($p, action_form(['action' => 'remove', 'product_id' => $p['id']], "Remove", "btn-small btn-red", "wishlist.php"));
  } ?>
</div>
<?php require_once 'includes/footer.php'; ?>
