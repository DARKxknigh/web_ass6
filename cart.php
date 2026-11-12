<?php
require_once 'includes/functions.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action == 'add') {
        $pid = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $vid = isset($_POST['variation_id']) ? (int)$_POST['variation_id'] : 0;
        $qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;
        $p = get_one($conn, "SELECT * FROM products WHERE id = ?", "i", [$pid]);
        if (!$p) {
            redirect("index.php");
        }
        $count = get_one($conn, "SELECT COUNT(*) AS c FROM product_variations WHERE product_id = ?", "i", [$pid]);
        $stock = $p['stock'];
        if ($count['c'] > 0) {
            // product has options, user must select one
            $v = get_one($conn, "SELECT * FROM product_variations WHERE id = ? AND product_id = ?", "ii", [$vid, $pid]);
            if (!$v) {
                flash("Please select an option.");
                redirect(product_url($p));
            }
            $stock = $v['stock'];
        } else {
            $vid = 0;
        }
        $key = $pid . "_" . $vid;
        $old = isset($_SESSION['cart'][$key]) ? $_SESSION['cart'][$key] : 0;
        if ($old + $qty > $stock) {
            flash("Only $stock in stock.");
        } else {
            $_SESSION['cart'][$key] = $old + $qty;
            flash("Added to cart.");
        }
    }

    if ($action == 'update') {
        foreach ($_POST['qty'] as $key => $q) {
            $q = (int)$q;
            if ($q <= 0) {
                unset($_SESSION['cart'][$key]);
            } elseif (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key] = $q;
            }
        }
        flash("Cart updated.");
    }

    if ($action == 'remove') {
        unset($_SESSION['cart'][$_POST['key']]);
    }

    if ($action == 'coupon') {
        $code = strtoupper(trim($_POST['code']));
        $c = get_one($conn, "SELECT * FROM coupons WHERE code = ? AND expiry >= CURDATE()", "s", [$code]);
        if ($c) {
            $_SESSION['coupon'] = $code;
            flash("Coupon applied.");
        } else {
            unset($_SESSION['coupon']);
            flash("Invalid or expired coupon.");
        }
    }
    redirect("cart.php");
}

$page_title = "Cart";
require_once 'includes/header.php';
$items = cart_items($conn);
$subtotal = cart_subtotal($items);
$discount = coupon_discount($conn, $subtotal);
$total = $subtotal - $discount;
?>
<h1>Shopping Cart</h1>
<?php if (count($items) == 0) { ?>
  <div class="box">Your cart is empty. <a href="search.php">Start shopping</a></div>
<?php } else { ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <div class="table-wrap"><table>
      <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr>
      <?php foreach ($items as $i) { ?>
      <tr>
        <td><?= e($i['product']['name']) ?><br><small><?= e($i['variation']) ?></small></td>
        <td><?= money($i['product']['price']) ?></td>
        <td><input type="number" name="qty[<?= e($i['key']) ?>]" value="<?= $i['qty'] ?>" min="0" max="<?= $i['stock'] ?>" style="width:70px"></td>
        <td><?= money($i['total']) ?></td>
        <td></td>
      </tr>
      <?php } ?>
    </table></div>
    <button>Update quantities</button>
  </form>

  <p>
  <?php foreach ($items as $i) {
      echo action_form(['action' => 'remove', 'key' => $i['key']], "Remove " . substr($i['product']['name'], 0, 15), "btn-small btn-red") . " ";
  } ?>
  </p>

  <div class="box" style="max-width:420px">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="coupon">
      <label>Coupon code</label>
      <input type="text" name="code" value="<?= isset($_SESSION['coupon']) ? e($_SESSION['coupon']) : '' ?>">
      <br><br><button>Apply coupon</button>
    </form>
    <hr>
    <p>Subtotal: <?= money($subtotal) ?></p>
    <p>Discount: - <?= money($discount) ?></p>
    <h3>Total: <?= money($total) ?></h3>
    <a class="btn" href="checkout.php">Proceed to checkout</a>
  </div>
<?php } ?>
<?php require_once 'includes/footer.php'; ?>
