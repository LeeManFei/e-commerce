<?php
include_once '_base.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {

    $name = trim($_POST['_name'] ?? '');
    $email = trim($_POST['_email'] ?? '');
    $message = trim($_POST['_message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error = "All fields are required.";
    } elseif (!is_email($email)) {
        $error = "Please enter a valid email address.";
    } else {

        try {
            $mail = new PHPMailer(true);

            // SMTP setup
            $mail->isSMTP();
            $mail->Host       = $EMAIL_CONFIG['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $EMAIL_CONFIG['smtp_user'];
            $mail->Password   = $EMAIL_CONFIG['smtp_pass'];
            $mail->SMTPSecure = 'tls';
            $mail->Port       = $EMAIL_CONFIG['smtp_port'];

            // Gmail rules: sender must be smtp_user
            $mail->setFrom($EMAIL_CONFIG['smtp_user'], "Customer Contact");
            $mail->addReplyTo($email, $name);        // customer email
            $mail->addAddress($EMAIL_CONFIG['smtp_user']); // send to yourself

            // Email content
            $mail->isHTML(true);
            $mail->Subject = "New Contact Message from $name";
            $mail->Body = "
                <h3>New Contact Message</h3>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Message:</strong><br>" . nl2br(encode($message)) . "</p>
            ";

            $mail->send();
            $success = "Your message has been sent successfully!";

        } catch (Exception $e) {
            $error = "Error sending message: " . $mail->ErrorInfo;
        }
    }
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/contact_us.css">

<div id="whole">
    <div id="contact_us_div">

        <?php if ($success): ?>
            <div class="success"><?= encode($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= encode($error) ?></div>
        <?php endif; ?>

        <h2 style="color:white;">Contact Us</h2>
        <p>If you have questions, feel free to contact our developer.</p>

        <div id="contact_container">

            <!-- Contact Form -->
            <div id="contact_form">
                <form action="" method="POST">
                    <input type="text" name="_name" placeholder="Your Name" required>
                    <input type="text" name="_email" placeholder="Your Email" required>
                    <textarea id="_message" name="_message" placeholder="Your Message..." required></textarea>
                    <button type="submit" name="send_message">Send Message</button>
                </form>
            </div>

            <!-- Google Map -->
            <div id="contact_map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3786.4405319882894!2d101.72400367473057!3d3.2139481967612156!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc386bb5535b83%3A0x2d08b6c5b6fd0155!2sTAR%20UMT%20Cyber%20Centre%20(CITC)!5e1!3m2!1szh-CN!2smy!4v1764912504808!5m2!1szh-CN!2smy" 
                    width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

        </div>
    </div>
</div>

