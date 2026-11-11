<?php
include_once '_base.php';

if (!isset($_GET['token'])) {
    die("Invalid link");
}

$token = $_GET['token'];

$stm = $conn->prepare("SELECT * FROM users WHERE verification_token = ?");
$stm->execute([$token]);
$user = $stm->fetch();

if (!$user) {
    die("Invalid or expired token");
}

// update user to verified
$conn->prepare("
    UPDATE users 
    SET verified = 1, verification_token = NULL 
    WHERE user_id = ?
")->execute([$user['user_id']]);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verified</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/verify.css">
    <script>
        setTimeout(function() {
            window.location.href = "login_form.php";
        }, 3000);
    </script>
</head>
<body>

<div class="msg-box">
    <h2>Email Verified Successfully ✔️</h2>
    <p>You will be redirected to login page in 3 seconds...</p>
</div>

</body>
</html>
