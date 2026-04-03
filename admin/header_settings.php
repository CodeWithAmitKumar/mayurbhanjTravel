<?php
session_start();
include '../config.php';

$conn->query("CREATE TABLE IF NOT EXISTS header_settings (
    id INT PRIMARY KEY,
    website_name VARCHAR(255) DEFAULT '',
    logo VARCHAR(255) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    mobile VARCHAR(50) DEFAULT '',
    address TEXT DEFAULT '',
    map_url TEXT DEFAULT '',
    social_instagram VARCHAR(255) DEFAULT '',
    social_youtube VARCHAR(255) DEFAULT '',
    social_twitter VARCHAR(255) DEFAULT '',
    social_facebook VARCHAR(255) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("INSERT IGNORE INTO header_settings (id) VALUES (1)");

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $website_name = mysqli_real_escape_string($conn, $_POST['website_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $map_url = mysqli_real_escape_string($conn, $_POST['map_url']);
    $social_instagram = mysqli_real_escape_string($conn, $_POST['social_instagram']);
    $social_youtube = mysqli_real_escape_string($conn, $_POST['social_youtube']);
    $social_twitter = mysqli_real_escape_string($conn, $_POST['social_twitter']);
    $social_facebook = mysqli_real_escape_string($conn, $_POST['social_facebook']);
    
    $logo = '';
    $update_logo = '';
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $max_size = 1024 * 1024; // 1MB
        if ($_FILES['logo']['size'] > $max_size) {
            $message = 'Logo file size must be less than 1MB';
            $message_type = 'error';
        } else {
            $result = mysqli_query($conn, "SELECT logo FROM header_settings WHERE id = 1");
            if ($result && $row = mysqli_fetch_assoc($result)) {
                if (!empty($row['logo']) && file_exists($upload_dir . $row['logo'])) {
                    unlink($upload_dir . $row['logo']);
                }
            }
            
            $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $logo = 'logo_' . time() . '.' . $file_ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo);
                
                $update_logo = ", logo = '$logo'";
            } else {
                $message = 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp';
                $message_type = 'error';
            }
        }
    }
    
    if (empty($message)) {
        $sql = "UPDATE header_settings SET 
                website_name = '$website_name',
                email = '$email',
                mobile = '$mobile',
                address = '$address',
                map_url = '$map_url',
                social_instagram = '$social_instagram',
                social_youtube = '$social_youtube',
                social_twitter = '$social_twitter',
                social_facebook = '$social_facebook'
                " . ($update_logo ? ", logo = '$logo'" : "") . "
                WHERE id = 1";
        
        if (mysqli_query($conn, $sql)) {
            $message = 'Settings saved successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error saving settings: ' . mysqli_error($conn);
            $message_type = 'error';
            error_log("SQL Error: " . mysqli_error($conn));
            error_log("Query: " . $sql);
        }
    }
}

$result = mysqli_query($conn, "SELECT * FROM header_settings WHERE id = 1");
$settings = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Header Settings - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary-color: #86B817; --primary-dark: #6a9612; --secondary-color: #14141F; --text-dark: #1f2937; --text-light: #6b7280; --white: #ffffff; --bg-light: #f3f4f6; --sidebar-width: 260px; --header-height: 70px; --shadow-sm: 0 1px 3px rgba(0,0,0,0.1); --transition: all 0.3s ease; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Heebo', sans-serif; background: var(--bg-light); min-height: 100vh; overflow-x: hidden; }
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--secondary-color) 0%, #2d2d3a 100%); z-index: 1000; box-shadow: 4px 0 20px rgba(0,0,0,0.15); overflow-y: auto; }
        .sidebar-brand { height: var(--header-height); display: flex; align-items: center; padding: 0 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h1 { color: var(--white); font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .sidebar-brand h1 i { color: var(--primary-color); font-size: 24px; }
        .sidebar-menu { padding: 20px 0; }
        .menu-section { padding: 0 20px; margin-bottom: 10px; }
        .menu-section-title { color: rgba(255,255,255,0.4); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255,255,255,0.7); text-decoration: none; border-radius: 10px; margin: 5px 10px; transition: var(--transition); font-size: 14px; font-weight: 500; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: var(--white); transform: translateX(5px); }
        .menu-item.active { background: var(--primary-color); color: var(--white); box-shadow: 0 4px 15px rgba(134, 184, 23, 0.4); }
        .menu-item i { width: 20px; margin-right: 12px; font-size: 16px; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .header { height: var(--header-height); background: var(--white); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: var(--shadow-sm); position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .toggle-sidebar { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--bg-light); border-radius: 10px; cursor: pointer; transition: var(--transition); border: none; font-size: 18px; color: var(--text-dark); }
        .toggle-sidebar:hover { background: var(--primary-color); color: var(--white); }
        .page-title { font-size: 18px; font-weight: 600; color: var(--text-dark); }
        .header-right { display: flex; align-items: center; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 600; font-size: 16px; cursor: pointer; }
        .content { padding: 30px; }
        .page-header { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); border-radius: 20px; padding: 40px; margin-bottom: 30px; position: relative; overflow: hidden; color: var(--white); }
        .page-header::before { content: ''; position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .page-header h1 { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .page-header p { font-size: 16px; opacity: 0.9; }
        .form-card { background: var(--white); border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 30px; }
        .form-card-header { background: linear-gradient(135deg, var(--secondary-color) 0%, #2d2d3a 100%); padding: 20px 25px; display: flex; align-items: center; gap: 12px; }
        .form-card-header i { color: var(--primary-color); font-size: 20px; }
        .form-card-header h3 { color: var(--white); font-size: 16px; font-weight: 600; }
        .form-card-body { padding: 25px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { position: relative; }
        .form-group.full-width { grid-column: span 2; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-label i { color: var(--primary-color); margin-right: 5px; }
        .form-control { width: 100%; padding: 12px 15px; font-size: 14px; font-family: 'Heebo', sans-serif; border: 2px solid #e5e7eb; border-radius: 10px; background: var(--bg-light); color: var(--text-dark); transition: var(--transition); }
        .form-control:focus { outline: none; border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 4px rgba(134, 184, 23, 0.15); }
        textarea.form-control { min-height: 80px; resize: vertical; }
        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--bg-light); }
        .btn { padding: 14px 32px; font-size: 15px; font-weight: 600; font-family: 'Heebo', sans-serif; border-radius: 12px; cursor: pointer; transition: var(--transition); border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: var(--white); box-shadow: 0 4px 15px rgba(134, 184, 23, 0.4); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(134, 184, 23, 0.5); }
        .btn-secondary { background: var(--bg-light); color: var(--text-dark); padding: 14px 24px; }
        .btn-secondary:hover { background: #e5e7eb; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } .form-grid { grid-template-columns: 1fr; } .form-group.full-width { grid-column: span 1; } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <?php include 'header.php'; ?>
        <div class="content">
            <?php if (!empty($message)): ?>
                <div style="padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; background: <?php echo $message_type == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type == 'success' ? '#155724' : '#721c24'; ?>; border: 1px solid <?php echo $message_type == 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1><i class="fa fa-heading"></i> Header Settings</h1>
                <p>Configure navigation menu and header appearance</p>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="header_settings_form">
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-heading"></i>
                    <h3>Website Info</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-globe"></i> Website Name</label>
                            <input type="text" name="website_name" class="form-control" value="<?php echo isset($settings['website_name']) ? htmlspecialchars($settings['website_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-image"></i> Logo (Max 1MB)</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <?php if (!empty($settings['logo'])): ?>
                                <p style="margin-top: 8px; font-size: 12px; color: var(--text-light);">Current: <?php echo htmlspecialchars($settings['logo']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-phone"></i>
                    <h3>Contact Info</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-envelope"></i> Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo isset($settings['email']) ? htmlspecialchars($settings['email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-mobile-alt"></i> Mobile Number</label>
                            <input type="text" name="mobile" class="form-control" value="<?php echo isset($settings['mobile']) ? htmlspecialchars($settings['mobile']) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-map-marker-alt"></i>
                    <h3>Address</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label"><i class="fa fa-location-arrow"></i> Address</label>
                            <textarea name="address" class="form-control"><?php echo isset($settings['address']) ? htmlspecialchars($settings['address']) : ''; ?></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label"><i class="fa fa-map"></i> Map URL</label>
                            <input type="text" name="map_url" class="form-control" value="<?php echo isset($settings['map_url']) ? htmlspecialchars($settings['map_url']) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-share-alt"></i>
                    <h3>Social Icons</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-instagram"></i> Instagram</label>
                            <input type="text" name="social_instagram" class="form-control" value="<?php echo isset($settings['social_instagram']) ? htmlspecialchars($settings['social_instagram']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-youtube"></i> YouTube</label>
                            <input type="text" name="social_youtube" class="form-control" value="<?php echo isset($settings['social_youtube']) ? htmlspecialchars($settings['social_youtube']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-twitter"></i> Twitter</label>
                            <input type="text" name="social_twitter" class="form-control" value="<?php echo isset($settings['social_twitter']) ? htmlspecialchars($settings['social_twitter']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                            <input type="text" name="social_facebook" class="form-control" value="<?php echo isset($settings['social_facebook']) ? htmlspecialchars($settings['social_facebook']) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="page_setting.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
                <button type="submit" form="header_settings_form" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
            </div>
            </form>
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