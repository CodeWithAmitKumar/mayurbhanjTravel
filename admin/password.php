<?php
session_start();
include '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$username = $_SESSION['username'];

// Handle password change
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'All fields are required!';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New password and confirm password do not match!';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long!';
        $message_type = 'error';
    } else {
        // Get current password from database
        $sql = "SELECT password FROM admins WHERE admin_id = $admin_id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        
        // Verify current password
        if (password_verify($current_password, $row['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_sql = "UPDATE admins SET password = '$hashed_password', display_pass = '$new_password' WHERE admin_id = $admin_id";
            if (mysqli_query($conn, $update_sql)) {
                $message = 'Password changed successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating password. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = 'Current password is incorrect!';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Change Password - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
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
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
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

        /* Sidebar Styles */
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

        .menu-item .badge {
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Header Styles */
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

        /* Content Area */
        .content {
            padding: 30px;
        }

        /* Page Header */
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

        /* Form Card */
        .form-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-card-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2d2d3a 100%);
            padding: 25px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-card-header h3 {
            color: var(--white);
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-header h3 i {
            color: var(--primary-color);
        }

        .form-card-header .lock-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-card-header .lock-icon i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .form-card-body {
            padding: 40px;
        }

        /* Form Group */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label i {
            color: var(--primary-color);
            margin-right: 5px;
        }

        .form-label .required {
            color: #ef4444;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 15px 18px 15px 45px;
            font-size: 15px;
            font-family: 'Heebo', sans-serif;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: var(--bg-light);
            color: var(--text-dark);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(134, 184, 23, 0.15);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 18px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 18px;
            padding: 0;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-hint {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-hint i {
            color: #f59e0b;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid var(--bg-light);
        }

        .btn {
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Heebo', sans-serif;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(134, 184, 23, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(134, 184, 23, 0.5);
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* Alert Messages */
        .alert {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.05) 100%);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Security Tips */
        .security-tips {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .security-tips h4 {
            color: #3b82f6;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .security-tips ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .security-tips li {
            color: var(--text-dark);
            font-size: 13px;
            padding: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .security-tips li i {
            color: #3b82f6;
            font-size: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .page-header h1 {
                font-size: 24px;
            }
            .form-card-body {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <!-- Content -->
        <div class="content">
            <div class="page-header">
                <h1><i class="fa fa-lock"></i> Change Password</h1>
                <p>Update your account password to keep it secure</p>
            </div>

            <?php if ($message): ?>
            <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <i class="fa fa <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fa fa-shield-alt"></i> Security Settings</h3>
                    <div class="lock-icon">
                        <i class="fa fa-lock"></i>
                    </div>
                </div>
                <div class="form-card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="security-tips">
                            <h4><i class="fa fa-info-circle"></i> Password Requirements</h4>
                            <ul>
                                <li><i class="fa fa-check"></i> At least 6 characters long</li>
                                <li><i class="fa fa-check"></i> Use a mix of letters and numbers</li>
                                <li><i class="fa fa-check"></i> Avoid using personal information</li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-key"></i> Current Password <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fa fa-lock input-icon"></i>
                                <input type="password" name="current_password" class="form-control" id="current_password" placeholder="Enter current password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('current_password', 'toggle-current')">
                                    <i class="fa fa-eye" id="toggle-current"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-key"></i> New Password <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fa fa-lock input-icon"></i>
                                <input type="password" name="new_password" class="form-control" id="new_password" placeholder="Enter new password" required minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password', 'toggle-new')">
                                    <i class="fa fa-eye" id="toggle-new"></i>
                                </button>
                            </div>
                            <p class="password-hint"><i class="fa fa-exclamation-circle"></i> Minimum 6 characters</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-lock"></i> Confirm New Password <span class="required">*</span></label>
                            <div class="input-group">
                                <i class="fa fa-lock input-icon"></i>
                                <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirm new password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggle-confirm')">
                                    <i class="fa fa-eye" id="toggle-confirm"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="welcome.php" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
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

        function togglePassword(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            if (input.type === 'password') {
                input.type = 'text';
                toggle.className = 'fa fa-eye-slash';
            } else {
                input.type = 'password';
                toggle.className = 'fa fa-eye';
            }
        }
    </script>
</body>

</html>
