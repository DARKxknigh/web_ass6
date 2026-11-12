<?php
require_once 'includes/functions.php';
require_customer();

$items = cart_items($conn);
$done_order = 0;
$errors = [];

if (count($items) == 0 && !isset($_GET['done'])) {
    flash("Your cart is empty.");
    redirect("cart.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment = $_POST['payment'];

    // validation
    if (!preg_match('/^[0-9+ -]{8,15}$/', $phone)) {
        $errors[] = "Enter a valid phone number.";
    }
    if (strlen($address) < 10) {
        $errors[] = "Address is too short.";
    }
    if (!in_array($payment, ['COD', 'Online (demo)'])) {
        $errors[] = "Choose a payment method.";
    }
    foreach ($items as $i) {
        if ($i['qty'] > $i['stock']) {
            $errors[] = $i['product']['name'] . " has only " . $i['stock'] . " in stock.";
        }
    }

    if (count($errors) == 0) {
        $subtotal = cart_subtotal($items);
        $discount = coupon_discount($conn, $subtotal);
        $total = $subtotal - $discount;
        $coupon = isset($_SESSION['coupon']) ? $_SESSION['coupon'] : null;

        mysqli_begin_transaction($conn);
        run($conn, "INSERT INTO orders (user_id, total_amount, discount, coupon_code, phone, address, payment_method)
                    VALUES (?, ?, ?, ?, ?, ?, ?)", "iddssss",
            [$_SESSION['user_id'], $total, $discount, $coupon, $phone, $address, $payment]);
        $order_id = mysqli_insert_id($conn);

        foreach ($items as $i) {
            $pid = $i['product']['id'];
            list($p2, $vid) = explode("_", $i['key']);
            run($conn, "INSERT INTO order_items (order_id, product_id, variation, quantity, price) VALUES (?, ?, ?, ?, ?)",
                "iisid", [$order_id, $pid, $i['variation'], $i['qty'], $i['product']['price']]);
            run($conn, "UPDATE products SET stock = stock - ? WHERE id = ?", "ii", [$i['qty'], $pid]);
            if ($vid > 0) {
                run($conn, "UPDATE product_variations SET stock = stock - ? WHERE id = ?", "ii", [$i['qty'], $vid]);
            }
        }
        mysqli_commit($conn);

        // email confirmation (works only if mail is set up on the server)
        $user = get_one($conn, "SELECT * FROM users WHERE id = ?", "i", [$_SESSION['user_id']]);
        @mail($user['email'], "Order #$order_id confirmed", "Thank you for your order. Total: $total", "From: " . setting($conn, 'admin_email'));

        unset($_SESSION['cart']);
        unset($_SESSION['coupon']);
        $done_order = $order_id;
    }
}

$page_title = "Checkout";
require_once 'includes/header.php';

if ($done_order > 0) {
    echo "<div class='box'><h1>Order placed!</h1><p>Your order number is <b>#$done_order</b>.</p>";
    echo "<p>Payment method: " . e($payment) . "</p>";
    echo "<a class='btn' href='profile.php'>View my orders</a></div>";
    require_once 'includes/footer.php';
    exit;
}

$subtotal = cart_subtotal($items);
$discount = coupon_discount($conn, $subtotal);
$total = $subtotal - $discount;
?>
<h1>Checkout</h1>
<?php foreach ($errors as $err) { echo "<div class='error'>" . e($err) . "</div>"; } ?>
<div class="box" style="max-width:500px">
  <p>Items: <?= count($items) ?> | Discount: <?= money($discount) ?> | <b>Total: <?= money($total) ?></b></p>
  <form method="post">
    <?= csrf_field() ?>
    <label>Phone</label>
    <input type="text" name="phone" value="<?= isset($_POST['phone']) ? e($_POST['phone']) : '' ?>" required>
    <label>Delivery address</label>
    <textarea name="address" rows="3" required><?= isset($_POST['address']) ? e($_POST['address']) : '' ?></textarea>
    <label>Payment method</label>
    <select name="payment">
      <option value="COD">Cash on Delivery</option>
      <option value="Online (demo)">Online payment (demo)</option>
    </select>
    <br><br><button>Place order</button>
  </form>
</div>
<?php require_once 'includes/footer.php'; ?>
