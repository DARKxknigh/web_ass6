<?php
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = get_one($conn, "SELECT p.*, c.name AS category_name, v.name AS vendor_name, v.status AS vendor_status
                           FROM products p
                           LEFT JOIN categories c ON c.id = p.category_id
                           JOIN vendors v ON v.id = p.vendor_id
                           WHERE p.id = ?", "i", [$id]);
if (!$product || $product['vendor_status'] != 'approved') {
    http_response_code(404);
    $page_title = "Not found";
    require_once 'includes/header.php';
    echo "<div class='box'>Product not found. <a href='index.php'>Go home</a></div>";
    require_once 'includes/footer.php';
    exit;
}

// handle review and question forms
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    require_customer();
    if (isset($_POST['review'])) {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        if ($rating >= 1 && $rating <= 5) {
            run($conn, "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)",
                "iiis", [$id, $_SESSION['user_id'], $rating, $comment]);
            flash("Thanks for your review!");
        } else {
            flash("Please choose a rating from 1 to 5.");
        }
    }
    if (isset($_POST['question'])) {
        $q = trim($_POST['question_text']);
        if ($q != "") {
            run($conn, "INSERT INTO questions (product_id, user_id, question) VALUES (?, ?, ?)",
                "iis", [$id, $_SESSION['user_id'], $q]);
            flash("Your question was sent to the seller.");
        }
    }
    redirect(product_url($product));
}

// save in recently viewed list
if (!isset($_SESSION['recent'])) {
    $_SESSION['recent'] = [];
}
$_SESSION['recent'] = array_diff($_SESSION['recent'], [$id]);
array_unshift($_SESSION['recent'], $id);
$_SESSION['recent'] = array_slice($_SESSION['recent'], 0, 4);

$variations = get_all($conn, "SELECT * FROM product_variations WHERE product_id = ?", "i", [$id]);
$reviews = get_all($conn, "SELECT r.*, u.name FROM reviews r JOIN users u ON u.id = r.user_id
                           WHERE r.product_id = ? ORDER BY r.id DESC", "i", [$id]);
$questions = get_all($conn, "SELECT q.*, u.name FROM questions q JOIN users u ON u.id = q.user_id
                             WHERE q.product_id = ? ORDER BY q.id DESC", "i", [$id]);
$avg = 0;
if (count($reviews) > 0) {
    $sum = 0;
    foreach ($reviews as $r) {
        $sum += $r['rating'];
    }
    $avg = $sum / count($reviews);
}

$page_title = $product['name'];
$page_desc = substr($product['description'], 0, 150);
require_once 'includes/header.php';
?>

<div class="box product-page">
  <div><img src="<?= image_url($product['image']) ?>" alt="<?= e($product['name']) ?>"></div>
  <div>
    <h1><?= e($product['name']) ?></h1>
    <div class="stars"><?= stars($avg) ?> <small>(<?= count($reviews) ?> reviews)</small></div>
    <p class="price" style="font-size:22px"><?= money($product['price']) ?></p>
    <p><?= nl2br(e($product['description'])) ?></p>
    <p>Category: <?= e($product['category_name']) ?> | Sold by: <?= e($product['vendor_name']) ?></p>
    <p>Stock: <?= $product['stock'] > 0 ? $product['stock'] . " available" : "<b style='color:red'>Out of stock</b>" ?></p>

    <?php if ($product['stock'] > 0) { ?>
    <form method="post" action="cart.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?= $id ?>">
      <?php if (count($variations) > 0) { ?>
        <label>Choose option</label>
        <select name="variation_id" required>
          <option value="">-- select --</option>
          <?php foreach ($variations as $v) { ?>
            <option value="<?= $v['id'] ?>" <?= $v['stock'] < 1 ? 'disabled' : '' ?>>
              <?= e($v['attribute']) ?>: <?= e($v['value']) ?> (<?= $v['stock'] ?> left)
            </option>
          <?php } ?>
        </select>
      <?php } else { ?>
        <input type="hidden" name="variation_id" value="0">
      <?php } ?>
      <label>Quantity</label>
      <input type="number" name="qty" value="1" min="1" max="<?= $product['stock'] ?>" style="width:100px">
      <br><br>
      <button><?= t('add_cart') ?></button>
    </form>
    <?php } ?>

    <?php if (isset($_SESSION['user_id'])) { ?>
      <br>
      <form method="post" action="wishlist.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= $id ?>">
        <button class="btn-small">♡ Add to wishlist</button>
      </form>
    <?php } ?>
  </div>
</div>

<div class="box">
  <h2>Reviews</h2>
  <?php if (count($reviews) == 0) { echo "<p>No reviews yet.</p>"; } ?>
  <?php foreach ($reviews as $r) { ?>
    <p><b><?= e($r['name']) ?></b> <span class="stars"><?= stars($r['rating']) ?></span><br>
    <?= e($r['comment']) ?></p>
    <hr>
  <?php } ?>

  <?php if (isset($_SESSION['user_id'])) { ?>
    <h3>Write a review</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="review" value="1">
      <label>Rating</label>
      <select name="rating" style="width:150px">
        <option value="5">5 - Excellent</option><option value="4">4 - Good</option>
        <option value="3">3 - Okay</option><option value="2">2 - Bad</option><option value="1">1 - Very bad</option>
      </select>
      <label>Comment</label>
      <textarea name="comment" rows="3"></textarea>
      <br><br><button>Submit review</button>
    </form>
  <?php } else { ?>
    <p><a href="login.php">Login</a> to write a review.</p>
  <?php } ?>
</div>

<div class="box">
  <h2>Questions and Answers</h2>
  <?php if (count($questions) == 0) { echo "<p>No questions yet.</p>"; } ?>
  <?php foreach ($questions as $q) { ?>
    <p><b>Q (<?= e($q['name']) ?>):</b> <?= e($q['question']) ?><br>
    <?php if ($q['answer']) { ?><b>A:</b> <?= e($q['answer']) ?><?php } else { ?><i>Waiting for seller's answer</i><?php } ?></p>
  <?php } ?>
  <?php if (isset($_SESSION['user_id'])) { ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="question" value="1">
      <label>Ask a question</label>
      <textarea name="question_text" rows="2" required></textarea>
      <br><br><button>Ask</button>
    </form>
  <?php } ?>
</div>

<?php require_once 'includes/footer.php'; ?>
