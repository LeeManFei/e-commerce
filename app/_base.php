<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

define(
    'BASE_URL',
    (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') .
    $_SERVER['SERVER_NAME'] .
    '/PHPASS/app/'   // project root folder
);

session_start();

// Load configuration from environment or a secure config file
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'e-commerce';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

try {
    $conn = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Enable exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch associative arrays
        PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements
    ]);
} catch (PDOException $e) {
    error_log('Connection failed: ' . $e->getMessage());
    exit('Database connection failed.');
}

// ============================================================================
// General Page Functions
// ============================================================================

// Is GET request?
function is_get()
{
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

// Is POST request?
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// Obtain GET parameter
function get($key, $value = null)
{
    $value = $_GET[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain POST parameter
function post($key, $value = null)
{
    $value = $_POST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain REQUEST (GET and POST) parameter
function req($key, $value = null)
{
    $value = $_REQUEST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Set or get temporary session variable
function temp($key, $value = null)
{
    if ($value !== null) {
        $_SESSION["temp_$key"] = $value;
    } else {
        $value = $_SESSION["temp_$key"] ?? null;
        unset($_SESSION["temp_$key"]);
        return $value;
    }
}

// Redirect to URL
function redirect($url = null)
{
    $url ??= $_SERVER['REQUEST_URI'];
    header("Location: $url");
    exit();
}

// ============================================================================
// HTML Helpers
// ============================================================================

// Encode HTML special characters
function encode($value){
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Function to sanitize input
function test_input($data)
{
    return htmlspecialchars(stripslashes(trim(($data))));
}

// Validate email
function is_email($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Generate <input type='text'>
function html_text($key, $attr = '', $value = '')
{
    $value = encode($value); // optional sanitize
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='password'>
function html_password($key, $attr = '') 
{
    echo "<input type='password' id='$key' name='$key' value='' $attr>";
}


// Generate <input type='search'>
function html_search($key, $value = '', $attr = '')
{
    $value = encode($value);
    echo "<input type='search' id='$key' name='$key' value='$value' $attr>";
}

// Generate <select>
function html_select($key, $items, $default = '- Select One -', $attr = '', $selected_value = '') 
{
    $selected_value = encode($selected_value);
    echo "<select id='$key' name='$key' $attr>";
    if ($default !== null) {
        echo "<option value=''>$default</option>";
    }
    foreach ($items as $id => $text) {
        $state = $id == $selected_value ? 'selected' : '';
        echo "<option value='$id' $state>$text</option>";
    }
    echo '</select>';
}

// Generate <input type='number'>
function html_number($key, $value = '', $min = '', $max = '', $step = '', $attr = '') 
{
    $value = encode($value);
    echo "<input type='number' id='$key' name='$key' value='$value' min='$min' max='$max' step='$step' $attr>";
}

// Generate <textarea>
function html_textarea($key, $attr = ''){
    $value = encode($GLOBALS[$key] ?? '');
    echo "<textarea id='$key' name='$key' $attr>$value</textarea>";
}

// Generate SINGLE <input type='checkbox'>
function html_checkbox($key, $label = '', $attr = ''){
    $value = encode($GLOBALS[$key] ?? '');
    $status = $value == 1 ? 'checked' : '';
    echo "<label><input type='checkbox' id='$key' name='$key' value='1' $status $attr>$label</label>";
}

// Generate <input type='radio'> list
function html_radios($key, $items, $br = false)
{
    $value = encode($GLOBALS[$key] ?? '');
    echo '<div>';
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'checked' : '';
        echo "<label><input type='radio' id='{$key}_$id' name='$key' value='$id' $state>$text</label>";
        if ($br) {
            echo '<br>';
        }
    }
    echo '</div>';
}

// Generate <input type='file'>
function html_file($key, $accept = '', $attr = '')
{
    echo "<input type='file' id='$key' name='$key' accept='$accept' $attr>";
}

// Generate table headers <th>
function table_headers($fields, $sort, $dir, $href = '')
{
    foreach ($fields as $k => $v) {
        $d = 'asc'; // Default direction
        $c = '';    // Default class

        if ($k == $sort) {
            $d = $dir == 'asc' ? 'desc' : 'asc';
            $c = $dir;
        }

        echo "<th><a href='?sort=$k&dir=$d&$href' class='$c'>$v</a></th>";
    }
}

// ============================================================================
// Error Handlings
// ============================================================================

// Global error array
$_err = [];

// Generate <span class='err'>
function err($key)
{
    global $_err;
    if ($_err[$key] ?? false) {
        echo "<span class='err'>$_err[$key]</span>";
    } else {
        echo '<span></span>';
    }
}

// ============================================================================
// Security
// ============================================================================

// Global user object
$user_id = $_SESSION['user_id'] ?? null;

// Login user
function login($user, $url = '/')
{
    $_SESSION['user_id'] = $user;
    redirect($url);
}

// Logout user
function logout($url = '/')
{
    unset($_SESSION['user_id']);
    session_destroy();
    redirect($url);
}

// Authorization
function auth(...$roles)
{
    if (isset($_SESSION['user_role'])) {
        $role = $_SESSION['user_role'];
        if ($roles) {
            if (in_array($role, $roles)) {
                return; // OK
            }
        } else {
            return; // OK
        }
    }

    redirect(BASE_URL . 'login_form.php'); // or login_form.php
}



// ============================================================================
// Shopping Cart
// ============================================================================

// Get cart_id for the user or create if not exist
function get_cart_id($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart)
        return $cart['cart_id'];

    // Create a new cart if none exists
    $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    return $conn->lastInsertId();
}

// Get all items in cart
function get_cart_items($conn, $user_id)
{
    $cart_id = get_cart_id($conn, $user_id);

    $stmt = $conn->prepare("
        SELECT ci.cart_item_id, ci.product_id, ci.quantity,
               p.name, p.price, p.product_image
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cart_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Count total items in the cart
function count_cart_items($conn, $user_id)
{
    if (!$user_id)
        return 0;
    $cart_id = get_cart_id($conn, $user_id);
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cart_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total_items'] ?? 0;
}

// ============================================================================
// Global Constants and Variables
// ============================================================================
$gcategory = [
    "Flowers" => "Flowers",
    "GraduationGown" => "GraduationGown",
    "GraduationScroll" => "GraduationScroll",
    "Dolls" => "Dolls",
    "Hats" => "Hats",
];

function log_user_action(PDO $conn, int $user_id, string $action_type, string $description): bool {
    try {
        $stmt = $conn->prepare("
            INSERT INTO user_actions (user_id, action_type, description) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$user_id, $action_type, $description]);
    } catch (PDOException $e) {
        // Optional: log error somewhere
        error_log("Failed to log action: " . $e->getMessage());
        return false;
    }
}


$EMAIL_CONFIG = [
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_user' => getenv('SMTP_USER') ?: '',
    'smtp_pass' => getenv('SMTP_PASS') ?: '',
    'smtp_port' => (int) (getenv('SMTP_PORT') ?: 587),
    'base_url'  => BASE_URL
];


function sendVerificationEmail($gmail, $token) {
    global $EMAIL_CONFIG;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $EMAIL_CONFIG['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $EMAIL_CONFIG['smtp_user'];
        $mail->Password   = $EMAIL_CONFIG['smtp_pass'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = $EMAIL_CONFIG['smtp_port'];

        $mail->setFrom($EMAIL_CONFIG['smtp_user'], 'NaBeGrad');
        $mail->addAddress($gmail);

        $verify_link = $EMAIL_CONFIG['base_url'] . "verify.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Email Verification from NaBeGrad";
        $mail->Body = <<<HTML
        <html>
        <body>
            <h2>Dear Customer,</h2><br>
            <p>Please click this link to complete the email verification, this link expires in 15 minute</p><br>
            <a href= '$verify_link'
                style="
                display:inline-block;
                padding:10px 16px;
                background:black;
                color:white;
                text-decoration:none;
                font-size:12px;
                border-radius:5px;">
            Verify Email
            </a>
            <br>
            <p> If button doesn't work, use this link:</p>
            <p>$verify_link</p>
        </body>
        </html>
        HTML;

        $mail->send();
    } catch (Exception $e) {
        echo "Email error: " . $mail->ErrorInfo;
    }
}

function sendResetPassword($gmail, $token) {
    global $EMAIL_CONFIG;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $EMAIL_CONFIG['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $EMAIL_CONFIG['smtp_user'];
        $mail->Password   = $EMAIL_CONFIG['smtp_pass'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = $EMAIL_CONFIG['smtp_port'];

        $mail->setFrom($EMAIL_CONFIG['smtp_user'], 'NaBeGrad');
        $mail->addAddress($gmail);

        $reset_link = $EMAIL_CONFIG['base_url'] . "reset_password.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Password Reset Link from NaBeGrad";
        $mail->Body = <<<HTML
        <html>
        <body>
            <h2>Dear Customer,</h2><br>
            <p>Please click this link to reset the password, this link expires in 15 minute</p><br>
            <a href= '$reset_link'
                style="
                display:inline-block;
                padding:10px 16px;
                background:black;
                color:white;
                text-decoration:none;
                font-size:12px;
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
    } catch (Exception $e) {
        echo "Email error: " . $mail->ErrorInfo;
    }
}


