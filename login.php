<?php
require_once 'includes/functions.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $user = get_one($conn, "SELECT * FROM users WHERE email = ?", "s", [$email]);
    if ($user && password_verify($password, $user['password'])) {
        $vendor = null;
        if ($user['role'] == 'vendor') {
            $vendor = get_one($conn, "SELECT * FROM vendors WHERE user_id = ?", "i", [$user['id']]);
        }
        if ($vendor && $vendor['status'] != 'approved') {
            $error = "Your vendor account is " . $vendor['status'] . ". Please wait for admin approval.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            if ($vendor) {
                $_SESSION['vendor_id'] = $vendor['id'];
            }
            // remember email in a cookie for 30 days
            if (isset($_POST['remember'])) {
                setcookie("remember_email", $email, time() + 60 * 60 * 24 * 30, "/");
            } else {
                setcookie("remember_email", "", time() - 3600, "/");
            }
            if ($user['role'] == 'vendor') {
                redirect("vendor/dashboard.php");
            }
            redirect("index.php");
        }
    } else {
        $error = "Wrong email or password.";
    }
}

$saved_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
$page_title = "Login";
require_once 'includes/header.php';
?>
<div class="box narrow">
  <h2>Login</h2>
  <?php if ($error != "") { echo "<div class='error'>" . e($error) . "</div>"; } ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email</label>
    <input type="email" name="email" value="<?= e($saved_email) ?>" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <label style="font-weight:normal"><input type="checkbox" name="remember" <?= $saved_email ? 'checked' : '' ?>> Remember my email</label>
    <br><button>Login</button>
  </form>
  <p>New here? <a href="signup.php">Create an account</a></p>
  <p><small>Demo: user@test.com / user123 &nbsp; vendor@test.com / vendor123</small></p>
</div>
<?php require_once 'includes/footer.php'; ?>
