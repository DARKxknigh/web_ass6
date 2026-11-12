<?php
require_once 'includes/functions.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] == 'vendor' ? 'vendor' : 'customer';
    $shop = trim($_POST['shop_name']);

    if ($name == "") { $errors[] = "Name is required."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Enter a valid email."; }
    if (strlen($password) < 6) { $errors[] = "Password must be at least 6 characters."; }
    if ($role == 'vendor' && $shop == "") { $errors[] = "Shop name is required for vendors."; }
    if (get_one($conn, "SELECT id FROM users WHERE email = ?", "s", [$email])) {
        $errors[] = "This email is already registered.";
    }

    if (count($errors) == 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        run($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", "ssss", [$name, $email, $hash, $role]);
        $uid = mysqli_insert_id($conn);
        if ($role == 'vendor') {
            run($conn, "INSERT INTO vendors (user_id, name, status) VALUES (?, ?, 'pending')", "is", [$uid, $shop]);
            flash("Account created. You can sell after the admin approves your shop.");
        } else {
            flash("Account created. Please login.");
        }
        redirect("login.php");
    }
}

$page_title = "Sign Up";
require_once 'includes/header.php';
?>
<div class="box narrow">
  <h2>Create account</h2>
  <?php foreach ($errors as $err) { echo "<div class='error'>" . e($err) . "</div>"; } ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Name</label>
    <input type="text" name="name" value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <label>I want to</label>
    <select name="role">
      <option value="customer">Buy products</option>
      <option value="vendor">Sell products (vendor)</option>
    </select>
    <label>Shop name (vendors only)</label>
    <input type="text" name="shop_name">
    <br><br><button>Sign up</button>
  </form>
  <p>Already have an account? <a href="login.php">Login</a></p>
</div>
<?php require_once 'includes/footer.php'; ?>
