<?php
include_once '_base.php';


if (is_post()) {
    $gmail = req('gmail');

    // Validate input
    if ($gmail == '') {
        $error = "Email is required.";
    } elseif (!is_email($gmail)) {
        $error = "Invalid Email Format.";
    } else {
        // Check account
        $stm = $conn->prepare("SELECT * FROM users WHERE gmail = ?");
        $stm->execute([$gmail]);
        $user = $stm->fetch();

        if (!$user) {
            $error = "No account found with this email.";
        } elseif ($user['verified'] == 1) {
            $error = "This email is already verified.";
        } else {
            // generate new token
            $token = bin2hex(random_bytes(20));

            // update token
            $conn->prepare("UPDATE users SET verification_token=? WHERE user_id=?")
                 ->execute([$token, $user['user_id']]);

            // send email
            sendVerificationEmail($gmail, $token);

            // TEMP SUCCESS PAGE (3s)
            echo "
                <html><head>
                <meta charset='UTF-8'>
                <title>Email Sent</title>
                <style>
                    body { font-family: Arial;
                         background:#f4f8ff;
                          display:flex;
                           justify-content:center; 
                           align-items:center; 
                           height:100vh; }
                    
                           .box { background:white; padding:30px 40px; border-radius:10px;
                           text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
                    h2 { color:green; margin-bottom:10px; }
                </style>
                <script>
                    setTimeout(() => {
                        window.location.href = 'login_form.php';
                    }, 3000);
                </script>
                </head><body>
                    <div class='box'>
                        <h2>Verification Link Sent ✔️</h2>
                        <p>You will be redirected to login page in 3 seconds...</p>
                        
                    </div>
                </body></html>
            ";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resend Verification</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/resend_verify.css">
    <meta charset="UTF-8">
    
</head>
<body>

<div class="card">
    <h2>Resend Verification Email</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="post">
        <input type="email" name="gmail" placeholder="Enter your email"
       value="<?= htmlspecialchars($_GET['email'] ?? '') ?>" required>

        <button type="submit">Resend Email</button>
    </form>

    <br>
    <a href="login_form.php">← Back to Login</a>
</div>

</body>
</html>
