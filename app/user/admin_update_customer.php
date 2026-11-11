<?php
include 'employees_sidebar.php';

auth("admin");

$admin_id = $_SESSION['user_id'] ?? null;
if ($admin_id === null) {
    temp('error', 'Not logged in!');
    redirect('home.php');
    exit;
}

$userID = $_GET['id'] ?? null;
if (!$userID) {
    temp('info', 'No user ID provided!');
    redirect('admin_crud_customer.php');
    exit;
}

// Fetch user data
$fetch_user = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$fetch_user->execute([$userID]);
$user = $fetch_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    temp('info', 'User not found!');
    redirect('admin_crud_customer.php');
    exit;
}

// Handle form submission
if (is_post() && isset($_POST['update'])) {
    $username = htmlspecialchars(trim($_POST['username']));
    $gmail = filter_var($_POST['gmail'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $role = $_POST['role'];

    if ($password !== '' && $password !== $confirm) {
        temp('error', 'Passwords do not match!');
    } else {
        // Build base SQL and params
        $params = [$username, $gmail, $phone, $role, $userID];
        $sql = "UPDATE users SET username=?, gmail=?, phone=?, role=? WHERE user_id=?";

        // If password provided, extend SQL and params
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, gmail=?, phone=?, password=?, role=? WHERE user_id=?";
            $params = [$username, $gmail, $phone, $hashedPassword, $role, $userID];
        }

        $update_user = $conn->prepare($sql);
        $update_user->execute($params);

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $image_name = time() . '_' . basename($_FILES['profile_image']['name']);
            $upload_dir = __DIR__ . '/../images/';
            $image_path = $upload_dir . $image_name;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $image_path)) {
                $update_image = $conn->prepare("UPDATE users SET profile_image=? WHERE user_id=?");
                $update_image->execute([$image_name, $userID]);

                // Delete old image if exists
                if (!empty($user['profile_image']) && file_exists($upload_dir . $user['profile_image'])) {
                    unlink($upload_dir . $user['profile_image']);
                }
            }
        }

        log_user_action($conn, $admin_id, "update", "Updated user ID: " . $userID);

        temp('info', 'User updated successfully!');
        redirect('admin_crud_customer.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/edit_profile.css">
</head>

<body>
    <section class="side-section">
        <div class="admin-container">
            <form id="editProfileForm" action="" method="POST" enctype="multipart/form-data" class="admin_form">

                <div class="profile-header">
                    <h3>Edit User</h3>
                    <div>
                        <a href="admin_crud_customer.php" style="text-decoration:none;">
                            <button type="button" class="save-trigger" style="background-color:#333;color:#fff;">
                                Back
                            </button>
                        </a>
                        <button type="submit" name="update" class="save-trigger">Update</button>
                    </div>
                </div>

                <?php err('fields'); ?>
                <?php err('db'); ?>

                <div class="form-group">
                    <label>USER USERNAME</label>
                    <div class="input-group">
                        <?php html_text('username', "placeholder='Enter username' required maxlength='100'", $user['username']); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>USER EMAIL</label>
                    <div class="input-group">
                        <?php html_text('gmail', "type='email' required", $user['gmail']); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>USER PHONE NUMBER</label>
                    <div class="input-group">
                        <?php html_text('phone', "placeholder='Enter phone number' required", $user['phone']); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>USER NEW PASSWORD</label>
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
                    <label>ROLE</label>
                    <div class="input-group">
                        <?php
                        $roles = [
                            'admin',
                            'customer'
                        ];
                        // Pre-fill with the user's actual role from DB
                        // html_select('role', $roles, $user['role'], "required");
                        ?>
                        <select name="role" required>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Current Profile Image</label>
                    <div class="image-container">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?= BASE_URL ?>images/<?= htmlspecialchars($user['profile_image']); ?>" width="100">
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
    </section>
</body>

</html>