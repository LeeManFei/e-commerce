<?php
include 'employees_sidebar.php';

auth("admin");

// --- ADD USER ---
if (is_post() && isset($_POST['add'])) {
   $admin_id = $_SESSION['user_id'];

   $username = htmlspecialchars(trim(req('username')));
   $gmail = filter_var(req('gmail'), FILTER_SANITIZE_EMAIL);
   $phone = htmlspecialchars(trim(req('phone')));
   $password = req('password');
   $confirm_password = req('confirm_password');
   $role = req('role');

   if ($password === '' || $confirm_password === '') {
      temp('error', 'Password fields cannot be empty!');
   } elseif ($password !== $confirm_password) {
      temp('error', 'Passwords do not match!');
   } else {
      // Check duplicate email
      $check = $conn->prepare("SELECT 1 FROM users WHERE gmail = ?");
      $check->execute([$gmail]);
      if ($check->fetch()) {
         temp('error', 'Email already registered!');
      } else {
         $hashed = password_hash($password, PASSWORD_DEFAULT);

         // Handle image upload
         $image_new_name = '';
         if (!empty($_FILES['profile_image']['name'])) {
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $image_new_name = uniqid('user_', true) . '.' . $ext;
            $target_file = __DIR__ . '/../images/' . $image_new_name;
            if (!file_exists(__DIR__ . '/../images/')) {
               mkdir(__DIR__ . '/../images/', 0777, true);
            }
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file);
         }

         $add_user = $conn->prepare("INSERT INTO users (username, gmail, phone, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
         $add_user->execute([$username, $gmail, $phone, $hashed, $role, $image_new_name]);

         $user_id = $conn->lastInsertId();

         $log_action = $conn->prepare("INSERT INTO user_actions (user_id, action_type, description) VALUES (?, 'add', ?)");
         $log_action->execute([$admin_id, 'Added new user ID: ' . $user_id]);

         temp('info', 'User added successfully!');
         redirect('admin_crud_customer.php');
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Add User</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/edit_profile.css">
</head>

<body>
   <section class="side-section">
      <div class="admin-container">
         <form id="editProfileForm" action="" method="POST" enctype="multipart/form-data" class="admin_form">

            <div class="profile-header">
               <h3>Add User</h3>
               <div>
                  <a href="admin_crud_customer.php" style="text-decoration:none;">
                     <button type="button" class="save-trigger" style="background-color:#333;color:#fff;">
                        Back
                     </button>
                  </a>
                  <button type="submit" name="add" class="save-trigger">Add</button>
               </div>
            </div>

            <?php err('fields'); ?>
            <?php err('db'); ?>

            <div class="form-group">
               <label>USER USERNAME</label>
               <div class="input-group">
                  <?php html_text('username', "placeholder='Enter username' required maxlength='100'", ''); ?>
               </div>
            </div>

            <div class="form-group">
               <label>USER EMAIL</label>
               <div class="input-group">
                  <?php html_text('gmail', "type='email' required", ''); ?>
               </div>
            </div>

            <div class="form-group">
               <label>USER PHONE NUMBER</label>
               <div class="input-group">
                  <?php html_text('phone', "placeholder='Enter phone number' required", ''); ?>
               </div>
            </div>

            <div class="form-group">
               <label>USER NEW PASSWORD</label>
               <div class="input-group">
                  <?php html_password('password', "placeholder='Enter new password' required"); ?>
               </div>
            </div>

            <div class="form-group">
               <label>CONFIRM PASSWORD</label>
               <div class="input-group">
                  <?php html_password('confirm_password', "placeholder='Confirm password' required"); ?>
               </div>
            </div>

            <div class="form-group">
               <label>ROLE</label>
               <div class="input-group">
                  <?php
                  $roles = [
                     'admin' => 'Admin',
                     'customer' => 'Customer'
                  ];
                  html_select('role', $roles, null, "required");
                  ?>
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