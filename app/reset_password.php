<?php
include '_base.php';

if (!isset($_GET['token'])) die("Invalid link");

$token = $_GET['token'];
$stm = $conn->prepare("SELECT * FROM users WHERE reset_token = ?");
$stm->execute([$token]);
$user = $stm->fetch();
if (!$user) die("Invalid or expired token");

$error = '';
$success = '';

if (is_post() && isset($_POST['reset_password'])) {
    $password = req('password');
    $repeat = req('repeat_password');

    if ($password == '' || $repeat == '') $error = "Both fields required";
    elseif ($password !== $repeat) $error = "Passwords do not match";
    else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $conn->prepare("UPDATE users SET password=?, reset_token=NULL WHERE user_id=?")
            ->execute([$hashed, $user['user_id']]);
        $success = "Password successfully reset!<br>
        <p>You will be redirected to login page in 3 seconds...</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/forgot_reset.css">

    <?php if ($success): ?>
        <meta http-equiv="refresh" content="3;url=<?= BASE_URL ?>login_form.php">
    <?php endif; ?>
</head>

<body>
    <div class="container">
        <div class="card">
            <h2>Reset Password</h2>
            <?php if($error) echo "<p class='error'>$error</p>"; ?>
            <?php if($success) echo "<p class='success'>$success</p>"; ?>
            <?php if(!$success): ?>
            <form method="post">
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="repeat_password" placeholder="Confirm Password" required>
                <button type="submit" name="reset_password">Reset Password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
