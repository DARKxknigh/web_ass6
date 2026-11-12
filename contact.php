<?php
require_once 'includes/functions.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_check();
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if ($name == "") { $errors[] = "Name is required."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Enter a valid email."; }
    if (strlen($message) < 5) { $errors[] = "Message is too short."; }

    if (count($errors) == 0) {
        run($conn, "INSERT INTO feedback (name, email, message) VALUES (?, ?, ?)", "sss", [$name, $email, $message]);
        // email to admin (needs mail server on the machine, so errors are hidden)
        @mail(setting($conn, 'admin_email'), "New contact message from " . str_replace(["\r", "\n"], "", $name), $message,
              "From: " . setting($conn, 'admin_email'));
        flash("Thank you! We got your message.");
        redirect("contact.php");
    }
}

$page_title = "Contact";
require_once 'includes/header.php';
?>
<div class="box narrow">
  <h2>Contact us</h2>
  <?php foreach ($errors as $err) { echo "<div class='error'>" . e($err) . "</div>"; } ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Name</label>
    <input type="text" name="name" value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>" required>
    <label>Message</label>
    <textarea name="message" rows="5" required><?= isset($_POST['message']) ? e($_POST['message']) : '' ?></textarea>
    <br><br><button>Send</button>
  </form>
</div>
<?php require_once 'includes/footer.php'; ?>
