<?php
include '_base.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';


$message = '';

if (is_post() && isset($_POST['send_reset'])) {
    $gmail = req('gmail');
    $stm = $conn->prepare("SELECT * FROM users WHERE gmail = ?");
    $stm->execute([$gmail]);
    $user = $stm->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(20));
        $conn->prepare("UPDATE users SET reset_token=? WHERE user_id=?")
            ->execute([$token, $user['user_id']]);

        // send email
        global $EMAIL_CONFIG;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $EMAIL_CONFIG['smtp_host'];
$mail->SMTPAuth = true;
$mail->Username = $EMAIL_CONFIG['smtp_user'];
$mail->Password = $EMAIL_CONFIG['smtp_pass'];
$mail->SMTPSecure = 'tls';
$mail->Port = $EMAIL_CONFIG['smtp_port'];

$mail->setFrom($EMAIL_CONFIG['smtp_user'], 'NaBeGrad');
$mail->addAddress($gmail);

$reset_link = $EMAIL_CONFIG['base_url'] . "reset_password.php?token=" . $token;

$mail->isHTML(true);
$mail->Subject = "Reset Password Link from NaBeGrad";
$mail->Body = <<<HTML
        <html>
        <body>
            <h2>Dear Customer,</h2><br>
            <p>Please click this link to reset the password, this link expires in 15 minute</p><br>
            <a href= '$reset_link'
                style="
                display:inline-block;
                padding:12px 20px;
                background:black;
                color:white;
                text-decoration:none;
                font-size:16px;
                border-radius:5px;">
            Reset Password
            </a>
            <br>
            <p> If button doesn't work, use this link:</p>
            <p>$reset_link</p>
        </body>
        </html>
        HTML;
        
$mail->send();

        $message = "Reset link sent to your email.";
    } else {
        $message = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/forgot_reset.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Forgot Password</h2>
            <p>Enter your email to receive a password reset link.</p>
            <?php if($message) echo "<p class='message'>$message</p>"; ?>
            <form method="post">
                <input type="email" name="gmail" placeholder="Enter your email" required>
                <button type="submit" name="send_reset">Send Reset Link</button>
            </form>
            <p><a href="login_form.php">Back to login</a></p>
        </div>
    </div>
</body>
</html>
