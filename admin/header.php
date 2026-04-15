<?php

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$username = $_SESSION['username'];

// Fetch profile image from admin_profile table
$profile_image = '';
$img_result = mysqli_query($conn, "SELECT profile_image FROM admin_profile LIMIT 1");
if ($img_result && mysqli_num_rows($img_result) > 0) {
    $img_row = mysqli_fetch_assoc($img_result);
    $profile_image = $img_row['profile_image'];
}
?>
<!-- Header -->
<div class="header">
    <div class="header-left">
        <button class="toggle-sidebar" onclick="toggleSidebar()">
            <i class="fa fa-bars"></i>
        </button>
        <div class="page-title">Dashboard</div>
    </div>
    <div class="header-right">
        <div class="user-profile">
            <?php if (!empty($profile_image) && file_exists('../' . $profile_image)): ?>
                <div class="user-avatar" style="padding:0; overflow:hidden;">
                    <img src="../<?php echo htmlspecialchars($profile_image); ?>"
                         alt="<?php echo htmlspecialchars($username); ?>"
                         style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                </div>
            <?php else: ?>
                <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
