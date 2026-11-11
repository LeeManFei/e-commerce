<?php
include_once '../_base.php';

auth('customer', 'admin');

$adminID = $_SESSION['user_id'];

if (!$adminID) {
    temp('error', 'Not logged in!');
    redirect(BASE_URL . 'home.php');
    exit;
}

// Fetch user data
$fetch_user = $conn->prepare("SELECT * FROM `users` WHERE user_id = ?");
$fetch_user->execute([$adminID]);
$user = $fetch_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    temp('info', 'User not found!');
    redirect(BASE_URL . 'home.php');
    exit;
}

// Handle form submission
if (isset($_POST['update'])) {
    $name = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $gmail = filter_var($_POST['gmail'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // If password field is filled, validate confirmation
    if ($password !== '' && $password !== $confirm) {
        temp('error', 'Passwords do not match!');
    } else {
        if ($password !== '') {
            // Hash new password only if provided
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $update_user = $conn->prepare("
                UPDATE `users`
                SET username = ?, gmail = ?, phone = ?, password = ?
                WHERE user_id = ?
            ");
            $update_user->execute([$name, $gmail, $phone, $hashedPassword, $adminID]);
        } else {
            // Update without touching password
            $update_user = $conn->prepare("
                UPDATE `users`
                SET username = ?, gmail = ?, phone = ?
                WHERE user_id = ?
            ");
            $update_user->execute([$name, $gmail, $phone, $adminID]);
        }

        // Handle profile image upload (unchanged)
        if (!empty($_FILES['profile_image']['name'])) {
            $image_name = time() . '_' . basename($_FILES['profile_image']['name']);
            $upload_dir = '../images/';
            $image_path = $upload_dir . $image_name;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $image_path)) {
                $update_image = $conn->prepare("UPDATE `users` SET profile_image = ? WHERE user_id = ?");
                $update_image->execute([$image_name, $adminID]);

                if (!empty($user['profile_image']) && file_exists($upload_dir . $user['profile_image'])) {
                    unlink($upload_dir . $user['profile_image']);
                }
            }
        }

        temp('info', 'Profile updated successfully!');
        redirect(BASE_URL . 'user/edit_profile.php');
        exit;
    }
}

// Decide which layout to include based on role
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        include 'employees_sidebar.php';
    } elseif ($_SESSION['user_role'] === 'customer') {
        include '../header.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/edit_profile.css">
</head>

<body>
    <section class="side-section">
        <div class="admin-form">
            <div class="admin-container">
                <form id="editProfileForm" action="" method="POST" enctype="multipart/form-data" class="admin_form">

                    <div class="profile-header">
                        <h3>Edit Profile</h3>
                        <button type="submit" name="update" class="save-trigger"></i>
                            Save</button>
                    </div>

                    <div class="form-group">
                        <label>YOUR NAME</label>
                        <div class="input-group">
                            <?php html_text('username', "placeholder='Enter username' required maxlength='100'", $user['username']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>YOUR EMAIL</label>
                        <div class="input-group">
                            <?php html_text('gmail', "type='email' required", $user['gmail']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>YOUR PHONE NUMBER</label>
                        <div class="input-group">
                            <?php html_text('phone', "placeholder='Enter phone number' required", $user['phone'] ?? ''); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>NEW PASSWORD</label>
                        <div class="input-group">
                            <?php html_password('password', "placeholder='Enter new password'"); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>CONFIRM PASSWORD</label>
                        <div class="input-group">
                            <?php html_password('confirm_password', "placeholder='Confirm password'"); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Current Profile Image</label>
                        <div class="image-container">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img style="padding:10px;" src="../images/<?= htmlspecialchars($user['profile_image']); ?>"
                                    alt="Profile Image" width="100">
                            <?php else: ?>
                                <p>No image uploaded</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>PROFILE IMAGE</label>
                        <div class="input-group">
                            <?php html_file('profile_image', 'image/*'); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        const form = document.querySelector('.admin_form');
        form.addEventListener('submit', (e) => {
            const pass = form.querySelector('input[name="password"]').value;
            const confirm = form.querySelector('input[name="confirm_password"]').value;
            if (pass !== confirm) {
                alert("Passwords do not match!");
                e.preventDefault();
            }
        });
    </script>
</body>

</html>