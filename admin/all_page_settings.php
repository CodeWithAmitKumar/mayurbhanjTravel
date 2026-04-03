<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>All Page Settings - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #86B817;
            --primary-dark: #6a9612;
            --secondary-color: #14141F;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --bg-light: #f3f4f6;
            --sidebar-width: 260px;
            --header-height: 70px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Heebo', sans-serif;
            background: var(--bg-light);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--secondary-color) 0%, #2d2d3a 100%);
            padding: 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            overflow-y: auto;
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand h1 {
            color: var(--white);
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand h1 i {
            color: var(--primary-color);
            font-size: 24px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-section {
            padding: 0 20px;
            margin-bottom: 10px;
        }

        .menu-section-title {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            margin: 5px 10px;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            transform: translateX(5px);
        }

        .menu-item.active {
            background: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(134, 184, 23, 0.4);
        }

        .menu-item i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .header {
            height: var(--header-height);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .toggle-sidebar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-light);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 18px;
            color: var(--text-dark);
        }

        .toggle-sidebar:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }

        .content {
            padding: 30px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            color: var(--white);
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .settings-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            display: block;
            position: relative;
        }

        .settings-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }

        .settings-card:hover .card-icon {
            background: var(--primary-color);
            color: var(--white);
        }

        .settings-card:hover .card-arrow {
            transform: translateX(5px);
            color: var(--primary-color);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--bg-light) 0%, #e5e7eb 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 30px auto 20px;
            transition: var(--transition);
        }

        .card-icon i {
            font-size: 28px;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .card-content {
            padding: 0 25px 30px;
            text-align: center;
        }

        .card-content h3 {
            color: var(--text-dark);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .card-content p {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
        }

        .card-arrow {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 18px;
            transition: var(--transition);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .settings-grid {
                grid-template-columns: 1fr;
            }
            .page-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">
            <div class="page-header">
                <h1><i class="fa fa-file"></i> Page Settings</h1>
                <p>Select a page to configure its settings</p>
            </div>

            <div class="settings-grid">
                <a href="home.php" class="settings-card">
                    <div class="card-icon">
                        <i class="fa fa-home"></i>
                    </div>
                    <div class="card-content">
                        <h3>Home Page</h3>
                        <p>Configure home page content</p>
                    </div>
                    <div class="card-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </a>

                <a href="about.php" class="settings-card">
                    <div class="card-icon">
                        <i class="fa fa-info-circle"></i>
                    </div>
                    <div class="card-content">
                        <h3>About Page</h3>
                        <p>Configure about page content</p>
                    </div>
                    <div class="card-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </a>

                 <a href="service.php" class="settings-card">
                    <div class="card-icon">
                        <i class="fa fa-cogs"></i>
                    </div>
                    <div class="card-content">
                        <h3>Service Page</h3>
                        <p>Configure service page content</p>
                    </div>
                    <div class="card-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </a>

                <a href="destination.php" class="settings-card">
                    <div class="card-icon">
                        <i class="fa fa-map-marker-alt"></i>
                    </div>
                    <div class="card-content">
                        <h3>Destination Page</h3>
                        <p>Configure destinations</p>
                    </div>
                    <div class="card-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </a>
                <a href="client-review.php" class="settings-card">
                    <div class="card-icon">
                        <i class="fa fa-star"></i>
                    </div>
                    <div class="card-content">
                        <h3>Client Review</h3>
                        <p>Configure client review</p>
                    </div>
                    <div class="card-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </a>

                 <a href="all_destination.php" class="settings-card">
                    <div class="card-icon">
                        <i class="fa fa-map-marker-alt"></i>
                    </div>
                    <div class="card-content">
                        <h3>All Destination</h3>
                        <p>Configure all destination</p>
                    </div>
                    <div class="card-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </a>

                
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth <= 768) {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0)' ? 'translateX(-100%)' : 'translateX(0)';
            }
        }
    </script>
</body>

</html>