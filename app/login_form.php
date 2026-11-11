<?php
include_once '_base.php';


if (is_post() && isset($_POST['login'])) {
    $gmail = req('gmail');
    $password = req('password');

    // Validate email
    if ($gmail == '') {
        $_err['gmail'] = 'Required';
    } elseif (!is_email($gmail)) {
        temp('error', 'Invalid Email Format');
    }

    // Validate password
    if ($password == '') {
        $_err['password'] = 'Required';
    }

    // Attempt login if no validation errors
    if (!$_err) {

        $stm = $conn->prepare("SELECT * FROM users WHERE gmail = ?");
        $stm->execute([$gmail]);
        $user = $stm->fetch();

        if ($user) {

            // Wrong password
            if (!password_verify($password, $user['password'])) {
                temp('error', "Email or password not matched!");
            }
            // Email not verified
            elseif ($user['verified'] == 0) {
                $gmail_escaped = htmlspecialchars($gmail);

                echo "
                <html><head>
                <meta charset='UTF-8'>
                <title>Email Not Verified</title>

                <style>
                    body {
                        font-family: Arial;
                        background: rgba(0,0,0,0.5);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                    }
                    .popup {
                        background: white;
                        padding: 30px 40px;
                        border-radius: 10px;
                        text-align: center;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        width: 400px;
                    }
                    h2 { color: #d9534f; }
                    a { color:#0d6efd; font-weight:bold; }
                </style>

                </head><body>
                    <div class='popup'>
                        <h2>Email Not Verified</h2>
                        <p>Please verify your email before logging in.</p>
                        <br>
                        <a href='resend_verification.php?email=$gmail_escaped'>
                            Resend Verification Email
                        </a>
                    </div>
                </body></html>
                ";
                exit;
            }

            // Successful login
            else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['gmail'];
                $_SESSION['name'] = $user['username'] ?? 'User';
                $_SESSION['user_role'] = strtolower($user['role']);
                $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.jpg';

                temp('info', "Welcome, $_SESSION[name]");

                switch ($_SESSION['user_role']) {
                    case 'admin':
                        redirect(BASE_URL . 'user/employees_home.php');
                        break;
                    case 'customer':
                        redirect(BASE_URL . 'home.php');
                        break;
                    default:
                        $_err['password'] = 'Unauthorized role access';
                }
            }
        }
        // No user found
        else {
            temp('error', 'Email or password not matched!');
        }
    }
}



// ----------------------------------------------------------------------------
// SIGNUP
if (is_post() && isset($_POST['signup'])) {
    $gmail = req('gmail');
    $password = req('password');

    if ($gmail == '') {
        $_err['gmail'] = 'Required';
    } elseif (!is_email($gmail)) {
        $_err['gmail'] = 'Invalid email';
    } else {
        // Check if user exists
        $stm = $conn->prepare("SELECT * FROM users WHERE gmail = ?");
        $stm->execute([$gmail]);
        if ($stm->fetch()) {
            $_err['gmail'] = 'Email already registered';
        }
    }

    if ($password == '') {
        $_err['password'] = 'Required';
    }

    if (!$_err) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // generate verification token
        $token = bin2hex(random_bytes(20));

        // insert user (verified = 0)
        $stm = $conn->prepare("
            INSERT INTO users (gmail, password, role, verified, verification_token)
            VALUES (?, ?, 'customer', 0, ?)
        ");
        $stm->execute([$gmail, $hashed, $token]);

        // send verification email
        sendVerificationEmail($gmail, $token);

        temp('info', 'Account created! Please check your email to verify.');
        redirect(BASE_URL . 'login_form.php');

        echo "<script>alert('Registration successful! Check your email to verify.');</script>";
    }
}




$_title = 'Login';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/login.css">
    <script src="js/login.js" defer></script>
</head>

<body>
    <div class="blur-bg-overlay"></div>
    <div id="temp-msg" style="
        display:none;
        background:#ff4d4d;
        color:white;
        padding:10px 15px;
        border-radius:5px;
        margin-bottom:15px;
        font-size:14px;
        ">
        <?= temp('error') ?? '' ?>
    </div>
    <div class="login-form">
        <a class="close-btn" href="<?= BASE_URL ?>home.php"><i class="fa-solid fa-xmark"></i></a>

        <!-- LOGIN FORM -->
        <div class="form-box login">
            <div class="form-details">
                <h2>Welcome Back</h2>
                <p>Please log in using your personal information to stay connected with us.</p>
            </div>

            <div class="form-content">
                <h2>LOGIN</h2>

                <?php if (!empty($error_msg)): ?>
                    <p class="error-msg" style="color:red;"><?= $error_msg ?></p>
                <?php endif; ?>

                <form method="post">
                    <div class="input-field">
                        <?= html_text('gmail', 'required') ?>
                        <label>Email</label>
                        <?= err('gmail') ?>
                    </div>

                    <div class="input-field">
                        <?= html_password('password', 'required') ?>
                        <label>Password</label>
                        <?= err('password') ?>
                    </div>

                    <a href="forgot_password.php" class="forgot-pass-link">Forgot password?</a>
                    <button type="submit" name="login">Log In</button>
                </form>

                <div class="bottom-link">
                    Don't have an account?
                    <a href="#" id="signup-link">Signup</a>
                </div>
            </div>
        </div>

        <!-- SIGNUP FORM -->
        <div class="form-box signup">
            <div class="form-details">
                <h2>Create Account</h2>
                <p>To become a part of our community, please sign up using your personal information.</p>
            </div>

            <div class="form-content">
                <h2>SIGNUP</h2>
                <form method="post">
                    <div class="input-field">
                        <?= html_text('gmail', 'required') ?>
                        <label>Enter your email</label>
                        <?= err('gmail') ?>
                    </div>

                    <div class="input-field">
                        <?= html_password('password', 'required') ?>
                        <label>Create password</label>
                        <?= err('password') ?>
                    </div>

                    <div class="policy-text">
                        <input type="checkbox" id="policy" required>
                        <label for="policy">
                            I agree to the
                            <a href="#" class="option">Terms & Conditions</a>
                        </label>
                    </div>

                    <button type="submit" name="signup">Sign Up</button>
                </form>

                <div class="bottom-link">
                    Already have an account?
                    <a href="#" id="login-link">Login</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>