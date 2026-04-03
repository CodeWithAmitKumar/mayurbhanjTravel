<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h1><i class="fa fa-map-marker-alt"></i> MBJ Travel</h1>
    </div>
    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-section-title">Main</div>
            <a href="welcome.php" class="menu-item <?php echo $currentPage === 'welcome' ? 'active' : ''; ?>">
                <i class="fa fa-home"></i>
                Dashboard
            </a>
            <!-- <a href="#" class="menu-item">
                <i class="fa fa-tachometer-alt"></i>
                Overview
            </a> -->
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Page Settings</div>
            <a href="page_setting.php" class="menu-item <?php echo $currentPage === 'page_setting' ? 'active' : ''; ?>">
                <i class="fa fa-cogs"></i>
                Website Settings
            </a>
          
            <a href="emailsetting.php" class="menu-item <?php echo $currentPage === 'emailsetting' ? 'active' : ''; ?>">
                <i class="fa fa-envelope"></i>
                Email Settings
            </a>
            <a href="all_queries.php" class="menu-item">
                <i class="fa fa-envelope"></i>
                Queries
                <span class="badge">3</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fa fa-users"></i>
                Bookings
            </a>
        </div>
        <div class="menu-section">
            <div class="menu-section-title">Settings</div>
            <a href="profile.php" class="menu-item">
                <i class="fa fa-user-cog"></i>
                Profile
            </a>
            <a href="logout.php" class="menu-item">
                <i class="fa fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</div>
