<?php
include 'employees_sidebar.php';
auth("admin");

// --- Handle banner actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            // ADD NEW BANNER
            case 'add':
                if (!empty($_FILES['bannerImage']['tmp_name'])) {
                    $uploadDir = '../images/banners/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = time() . '_' . basename($_FILES['bannerImage']['name']);
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['bannerImage']['tmp_name'], $filePath)) {
                        $stmt = $conn->prepare("INSERT INTO banners (image_path, status, user_id) VALUES (?, 'on', ?)");
                        $stmt->execute([$fileName, $_SESSION['user_id'] ?? null]);
                        temp('info', 'Banner added successfully!');
                    } else {
                        temp('info', 'Failed to upload image.');
                    }
                }
                break;

            // UPDATE DISPLAY STATUS
            case 'update':
                $banner_id = $_POST['banner_id'];
                $status = isset($_POST['status']) ? 'on' : 'off';
                $stmt = $conn->prepare("UPDATE banners SET status = ? WHERE banner_id = ?");
                $stmt->execute([$status, $banner_id]);
                temp('info', 'Banner status updated!');
                break;

            // DISPLAY ALL
            case 'displayAll':
                $conn->exec("UPDATE banners SET status = 'on'");
                temp('info', 'All banners set to display.');
                break;

            // HIDE ALL
            case 'hideAll':
                $conn->exec("UPDATE banners SET status = 'off'");
                temp('info', 'All banners hidden.');
                break;

            // DELETE BANNER
            case 'delete':
                $banner_id = $_POST['banner_id'];
                $stmt = $conn->prepare("DELETE FROM banners WHERE banner_id = ?");
                $stmt->execute([$banner_id]);
                temp('info', 'Banner deleted successfully!');
                break;
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database Error: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

// --- Fetch all banners for display ---
try {
    $stmt = $conn->prepare("SELECT banner_id, image_path, status FROM banners ORDER BY banner_id DESC");
    $stmt->execute();
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching banners: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Banner Management</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/admin_banner.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
</head>

<body>
    <section class="side-section">
        <div class="section">
            <div class="banner-header">
                <h2>Banner Management</h2>
                <div id="info"><?= temp('info') ?></div>

                <div class="title-container">
                    <form method="post" class="displayHideContainer">
                        <button type="submit" name="action" value="displayAll" class="displayHideBtn">Display
                            All</button>
                        <button type="submit" name="action" value="hideAll" class="displayHideBtn">Hide All</button>
                    </form>
                    <button class="popup-trigger"><i class="fa-solid fa-plus"></i> Add New Banner</button>
                </div>

                <!-- Add New Banner Popup -->
                <div id="popup-overlay" class="popup-overlay">
                    <div class="banner-form">
                        <div class="banner-container">
                            <form method="post" enctype="multipart/form-data" class="banner_form">
                                <div class="close-container">
                                    <span class="close-btn">&times;</span>
                                </div>
                                <h3>Add New Banner</h3>

                                <input type="hidden" name="action" value="add">

                                <!-- Upload box -->
                                <label class="custum-file-upload" for="popupFile">
                                    <div class="icon text-icon-wrapper" id="uploadIcon">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                    </div>
                                    <div class="text text-icon-wrapper" id="uploadText">
                                        <span>Click to upload image</span>
                                    </div>
                                    <img id="imagePreview" src="#" alt="Image Preview"
                                        style="display:none; object-fit:cover; width:100%; height:100%; border-radius:8px;" />
                                    <input type="file" name="bannerImage" id="popupFile" required>
                                </label>

                                <input type="submit" class="add-btn" value="Add Banner">
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banner Table -->
            <table class="banner-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($banners)): ?>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><img src="<?= BASE_URL ?>images/banners/<?= htmlspecialchars($banner['image_path']); ?>"
                                        class="banner-img" alt="Banner"></td>
                                <td>
                                    <form method="post" class="displayUpdate">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="banner_id" value="<?= $banner['banner_id']; ?>">
                                        <div class="checklist-container">
                                            <input type="checkbox" class="check" name="status" value="on"
                                                id="display-<?= $banner['banner_id']; ?>" <?= $banner['status'] === 'on' ? 'checked' : ''; ?>>
                                            <label for="display-<?= $banner['banner_id']; ?>">Display Banner</label>
                                        </div>
                                        <button type="submit" class="update-btn">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Delete this banner?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="banner_id" value="<?= $banner['banner_id']; ?>">
                                        <button type="submit" class="delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">No banners found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Popup and preview JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function () {
            const $popupTrigger = $('.popup-trigger');
            const $popupOverlay = $('#popup-overlay');
            const $closeBtn = $('.banner-form .close-btn');

            $popupTrigger.on('click', () => $popupOverlay.css('display', 'flex'));
            $closeBtn.on('click', () => $popupOverlay.css('display', 'none'));
            $(window).on('click', (e) => { if ($(e.target).is($popupOverlay)) $popupOverlay.css('display', 'none'); });

            $('#popupFile').on('change', function (e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        $('#imagePreview').attr('src', ev.target.result).show();
                        $('#uploadIcon, #uploadText').hide();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').hide();
                    $('#uploadIcon, #uploadText').show();
                }
            });
        });
    </script>
</body>

</html>