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

// Create admin_profile table if not exists
$table_sql = "CREATE TABLE IF NOT EXISTS admin_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile_no VARCHAR(15) NOT NULL,
    whatsapp_no VARCHAR(15),
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $table_sql);

// Handle profile form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile_no = trim($_POST['mobile_no']);
    $whatsapp_no = trim($_POST['whatsapp_no']);
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    
    // Check if profile exists
    $check_sql = "SELECT * FROM admin_profile WHERE admin_id = $admin_id";
    $check_result = mysqli_query($conn, $check_sql);
    $existing_profile = mysqli_fetch_assoc($check_result);
    
    // Handle image upload
    $profile_image = $existing_profile['profile_image'] ?? '';
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check file size (500KB max)
        if ($file_size > 500000) {
            // Resize image
            $image = null;
            if ($file_ext === 'jpg' || $file_ext === 'jpeg') {
                $image = imagecreatefromjpeg($file_tmp);
            } elseif ($file_ext === 'png') {
                $image = imagecreatefrompng($file_tmp);
            } elseif ($file_ext === 'gif') {
                $image = imagecreatefromgif($file_tmp);
            }
            
            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                $max_size = 800;
                
                if ($width > $max_size || $height > $max_size) {
                    if ($width > $height) {
                        $new_width = $max_size;
                        $new_height = ($height / $width) * $max_size;
                    } else {
                        $new_height = $max_size;
                        $new_width = ($width / $height) * $max_size;
                    }
                } else {
                    $new_width = $width;
                    $new_height = $height;
                }
                
                $resized = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                
                $new_file_name = 'admin_' . $admin_id . '_' . time() . '.' . $file_ext;
                $upload_dir = '../img/profile/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if ($file_ext === 'jpg' || $file_ext === 'jpeg') {
                    imagejpeg($resized, $upload_dir . $new_file_name, 80);
                } elseif ($file_ext === 'png') {
                    imagepng($resized, $upload_dir . $new_file_name, 8);
                } elseif ($file_ext === 'gif') {
                    imagegif($resized, $upload_dir . $new_file_name);
                }
                
                imagedestroy($image);
                imagedestroy($resized);
                
                if ($existing_profile && $existing_profile['profile_image']) {
                    $old_file = '../' . $existing_profile['profile_image'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $profile_image = 'img/profile/' . $new_file_name;
            }
        } else {
            $new_file_name = 'admin_' . $admin_id . '_' . time() . '.' . $file_ext;
            $upload_dir = '../img/profile/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            move_uploaded_file($file_tmp, $upload_dir . $new_file_name);
            
            if ($existing_profile && $existing_profile['profile_image']) {
                $old_file = '../' . $existing_profile['profile_image'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            $profile_image = 'img/profile/' . $new_file_name;
        }
    }
    
    if ($existing_profile) {
        $sql = "UPDATE admin_profile SET 
                name = '$name', 
                email = '$email', 
                mobile_no = '$mobile_no', 
                whatsapp_no = '$whatsapp_no', 
                gender = '$gender', 
                address = '$address'";
        
        if ($profile_image !== $existing_profile['profile_image']) {
            $sql .= ", profile_image = '$profile_image'";
        }
        
        $sql .= " WHERE admin_id = $admin_id";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Profile updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating profile: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    } else {
        $sql = "INSERT INTO admin_profile (admin_id, name, email, mobile_no, whatsapp_no, gender, address, profile_image) 
                VALUES ($admin_id, '$name', '$email', '$mobile_no', '$whatsapp_no', '$gender', '$address', '$profile_image')";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Profile saved successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error saving profile: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// Handle password change from profile page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'All password fields are required!';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New password and confirm password do not match!';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long!';
        $message_type = 'error';
    } else {
        $sql = "SELECT password FROM admins WHERE admin_id = $admin_id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        
        if (password_verify($current_password, $row['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE admins SET password = '$hashed_password', display_pass = '$new_password' WHERE admin_id = $admin_id";
            if (mysqli_query($conn, $update_sql)) {
                $message = 'Password changed successfully!';
                $message_type = 'success';
                $display_password = $new_password;
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

// Get existing profile data
$sql = "SELECT * FROM admin_profile WHERE admin_id = $admin_id";
$result = mysqli_query($conn, $sql);
$profile = mysqli_fetch_assoc($result);

// Get admin display password
$admin_sql = "SELECT display_pass FROM admins WHERE admin_id = $admin_id";
$admin_result = mysqli_query($conn, $admin_sql);
$admin_row = mysqli_fetch_assoc($admin_result);
$display_password = $admin_row['display_pass'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Profile Settings - Mayurbhanj Tourism Planner</title>
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
            margin-bottom: 30px;
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

        .form-card-body {
            padding: 30px;
        }

        /* Profile Image */
        .profile-image-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 40px;
        }

        .profile-image-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            margin-bottom: 15px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--white);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .profile-image-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--bg-light) 0%, #e5e7eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 5px solid var(--white);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .profile-image-placeholder i {
            font-size: 60px;
            color: var(--text-light);
        }

        .image-upload-label {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            color: var(--white);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 4px solid var(--white);
            transition: var(--transition);
            font-size: 18px;
        }

        .image-upload-label:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .image-upload-label input {
            display: none;
        }

        .image-hint {
            font-size: 13px;
            color: var(--text-light);
            background: var(--bg-light);
            padding: 8px 16px;
            border-radius: 20px;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            position: relative;
        }

        .form-group.full-width {
            grid-column: span 2;
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

        .form-control {
            width: 100%;
            padding: 15px 18px;
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

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 18px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
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

        /* Password Section */
        .password-section {
            border-top: 2px solid var(--bg-light);
            margin-top: 30px;
            padding-top: 30px;
        }

        .password-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 18px;
        }

        .form-control.with-icon {
            padding-left: 45px;
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
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .form-grid, .password-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .page-header h1 {
                font-size: 24px;
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
                <h1><i class="fa fa-user-cog"></i> Profile Settings</h1>
                <p>Manage your personal information and account settings</p>
            </div>

            <?php if ($message): ?>
            <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <i class="fa fa <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fa fa-id-card-alt"></i> Personal Information</h3>
                </div>
                <div class="form-card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="profile-image-container">
                            <div class="profile-image-wrapper">
                                <?php if ($profile && $profile['profile_image']): ?>
                                <img src="../<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile Image" class="profile-image" id="preview-image">
                                <?php else: ?>
                                <div class="profile-image-placeholder">
                                    <i class="fa fa-user"></i>
                                </div>
                                <?php endif; ?>
                                <label class="image-upload-label">
                                    <input type="file" name="profile_image" accept="image/*" onchange="previewImage(this)">
                                    <i class="fa fa-camera"></i>
                                </label>
                            </div>
                            <span class="image-hint"><i class="fa fa-info-circle"></i> Max size: 500KB. Supported: JPG, PNG, GIF</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-user"></i> Name <span class="required">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter your full name" value="<?php echo $profile ? htmlspecialchars($profile['name']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-envelope"></i> Email <span class="required">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="Enter your email address" value="<?php echo $profile ? htmlspecialchars($profile['email']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-phone-alt"></i> Mobile No <span class="required">*</span></label>
                                <input type="tel" name="mobile_no" class="form-control" placeholder="Enter mobile number" value="<?php echo $profile ? htmlspecialchars($profile['mobile_no']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-comment-alt"></i> WhatsApp No</label>
                                <input type="tel" name="whatsapp_no" class="form-control" placeholder="Enter WhatsApp number" value="<?php echo $profile ? htmlspecialchars($profile['whatsapp_no']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-venus-mars"></i> Gender <span class="required">*</span></label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($profile && $profile['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($profile && $profile['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($profile && $profile['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label"><i class="fa fa-map-marker-alt"></i> Address</label>
                                <textarea name="address" class="form-control" placeholder="Enter your complete address"><?php echo $profile ? htmlspecialchars($profile['address']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="save_profile" class="btn btn-primary">
                                <i class="fa fa-save"></i> Save Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Change Section -->
            <div class="form-card">
                <div class="form-card-header">
                    <h3><i class="fa fa-lock"></i> Change Password</h3>
                </div>
                <div class="form-card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="password-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-key"></i> Current Password <span class="required">*</span></label>
                                <div class="input-group">
                                    <i class="fa fa-lock input-icon"></i>
                                    <input type="password" name="current_password" class="form-control with-icon" id="current_password" placeholder="Current password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password', 'toggle-current')">
                                        <i class="fa fa-eye" id="toggle-current"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-key"></i> New Password <span class="required">*</span></label>
                                <div class="input-group">
                                    <i class="fa fa-lock input-icon"></i>
                                    <input type="password" name="new_password" class="form-control with-icon" id="new_password" placeholder="New password" required minlength="6">
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password', 'toggle-new')">
                                        <i class="fa fa-eye" id="toggle-new"></i>
                                    </button>
                                </div>
                                <p class="password-hint">Minimum 6 characters</p>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fa fa-lock"></i> Confirm Password <span class="required">*</span></label>
                                <div class="input-group">
                                    <i class="fa fa-lock input-icon"></i>
                                    <input type="password" name="confirm_password" class="form-control with-icon" id="confirm_password" placeholder="Confirm password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggle-confirm')">
                                        <i class="fa fa-eye" id="toggle-confirm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fa fa-key"></i> Change Password
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

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.querySelector('.profile-image-container');
                    let wrapper = container.querySelector('.profile-image-wrapper');
                    let img = wrapper.querySelector('.profile-image');
                    let placeholder = wrapper.querySelector('.profile-image-placeholder');
                    
                    if (!img) {
                        img = document.createElement('img');
                        img.className = 'profile-image';
                        img.id = 'preview-image';
                        if (placeholder) {
                            placeholder.remove();
                        }
                        wrapper.insertBefore(img, wrapper.firstChild);
                    }
                    img.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
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
