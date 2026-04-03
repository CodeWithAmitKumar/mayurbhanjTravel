<?php

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$username = $_SESSION['username'];
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
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        </div>
    </div>
</div>
