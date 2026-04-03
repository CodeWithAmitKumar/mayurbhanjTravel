<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];

$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'destination_page'");
if (mysqli_num_rows($table_check) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS destination_page (
        id INT AUTO_INCREMENT PRIMARY KEY,
        destinationbanner VARCHAR(255) DEFAULT NULL,
        pageheading VARCHAR(255) DEFAULT NULL,
        main_heading VARCHAR(255) DEFAULT NULL,
        image1 VARCHAR(255) DEFAULT NULL,
        image1_name VARCHAR(255) DEFAULT NULL,
        image2 VARCHAR(255) DEFAULT NULL,
        image2_name VARCHAR(255) DEFAULT NULL,
        image3 VARCHAR(255) DEFAULT NULL,
        image3_name VARCHAR(255) DEFAULT NULL,
        image4 VARCHAR(255) DEFAULT NULL,
        image4_name VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
}

$destination = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM destination_page LIMIT 1"));

$message = '';

if (isset($_POST['submit'])) {
    $pageheading = mysqli_real_escape_string($conn, $_POST['pageheading']);
    $main_heading = mysqli_real_escape_string($conn, $_POST['main_heading']);
    
    $destinationbanner = $destination['destinationbanner'] ?? '';
    $image1 = $destination['image1'] ?? '';
    $image1_name = $destination['image1_name'] ?? '';
    $image2 = $destination['image2'] ?? '';
    $image2_name = $destination['image2_name'] ?? '';
    $image3 = $destination['image3'] ?? '';
    $image3_name = $destination['image3_name'] ?? '';
    $image4 = $destination['image4'] ?? '';
    $image4_name = $destination['image4_name'] ?? '';
    
    $upload_dir = '../uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (!empty($_FILES['destinationbanner']['name'])) {
        if (!empty($destination['destinationbanner'])) {
            $old_banner = '../' . $destination['destinationbanner'];
            if (file_exists($old_banner)) {
                unlink($old_banner);
            }
        }
        $banner_ext = pathinfo($_FILES['destinationbanner']['name'], PATHINFO_EXTENSION);
        $banner_new = 'destinationbanner_' . time() . '.' . $banner_ext;
        move_uploaded_file($_FILES['destinationbanner']['tmp_name'], $upload_dir . $banner_new);
        $destinationbanner = 'uploads/' . $banner_new;
    }
    
    $image_fields = ['image1', 'image2', 'image3', 'image4'];
    
    foreach ($image_fields as $index => $field) {
        if (!empty($_FILES[$field]['name'])) {
            if (!empty($destination[$field])) {
                $old_image = '../' . $destination[$field];
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
            }
            $img_ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            $img_new = $field . '_' . time() . '.' . $img_ext;
            move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $img_new);
            
            if ($field === 'image1') $image1 = 'uploads/' . $img_new;
            if ($field === 'image2') $image2 = 'uploads/' . $img_new;
            if ($field === 'image3') $image3 = 'uploads/' . $img_new;
            if ($field === 'image4') $image4 = 'uploads/' . $img_new;
        }
        
        $name_field = $field . '_name';
        if (isset($_POST[$name_field])) {
            if ($field === 'image1') $image1_name = mysqli_real_escape_string($conn, $_POST[$name_field]);
            if ($field === 'image2') $image2_name = mysqli_real_escape_string($conn, $_POST[$name_field]);
            if ($field === 'image3') $image3_name = mysqli_real_escape_string($conn, $_POST[$name_field]);
            if ($field === 'image4') $image4_name = mysqli_real_escape_string($conn, $_POST[$name_field]);
        }
    }
    
    if ($destination) {
        $sql = "UPDATE destination_page SET 
            destinationbanner = '$destinationbanner',
            pageheading = '$pageheading',
            main_heading = '$main_heading',
            image1 = '$image1',
            image1_name = '$image1_name',
            image2 = '$image2',
            image2_name = '$image2_name',
            image3 = '$image3',
            image3_name = '$image3_name',
            image4 = '$image4',
            image4_name = '$image4_name'
            WHERE id = " . $destination['id'];
    } else {
        $sql = "INSERT INTO destination_page (destinationbanner, pageheading, main_heading, image1, image1_name, image2, image2_name, image3, image3_name, image4, image4_name) 
                VALUES ('$destinationbanner', '$pageheading', '$main_heading', '$image1', '$image1_name', '$image2', '$image2_name', '$image3', '$image3_name', '$image4', '$image4_name')";
    }
    
    if (mysqli_query($conn, $sql)) {
        $message = '<div class="alert alert-success">Destination page updated successfully!</div>';
        $destination = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM destination_page LIMIT 1"));
    } else {
        $message = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Destination Page Settings - Mayurbhanj Tourism Planner</title>
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

        .form-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 40px;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--bg-light);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--bg-light);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Heebo', sans-serif;
            transition: var(--transition);
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(134, 184, 23, 0.1);
        }

        .form-control-file {
            width: 100%;
            padding: 12px;
            border: 2px dashed var(--bg-light);
            border-radius: 12px;
            background: var(--bg-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .form-control-file:hover {
            border-color: var(--primary-color);
        }

        .image-preview {
            margin-top: 15px;
            position: relative;
            display: inline-block;
        }

        .image-preview img {
            max-width: 300px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .image-grid .image-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .image-name-display {
            margin-top: 10px;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 14px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Heebo', sans-serif;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(134, 184, 23, 0.4);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .form-card {
                padding: 25px;
            }
            .page-header h1 {
                font-size: 24px;
            }
            .image-preview img {
                max-width: 100%;
            }
            .image-grid {
                grid-template-columns: 1fr;
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
                <h1><i class="fa fa-map-marker"></i> Destination Page Settings</h1>
                <p>Configure your destination page content</p>
            </div>

            <?php echo $message; ?>

            <div class="form-card">
                <h2 class="form-title">Destination Page Content</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Destination Banner Image</label>
                        <input type="file" name="destinationbanner" class="form-control-file" accept="image/*">
                        <?php if (!empty($destination['destinationbanner'])): ?>
                        <div class="image-preview">
                            <img src="../<?php echo $destination['destinationbanner']; ?>" alt="Destination Banner">
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Page Heading</label>
                        <input type="text" name="pageheading" class="form-control" value="<?php echo isset($destination['pageheading']) ? htmlspecialchars($destination['pageheading']) : ''; ?>" placeholder="Enter page heading">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Main Heading</label>
                        <input type="text" name="main_heading" class="form-control" value="<?php echo isset($destination['main_heading']) ? htmlspecialchars($destination['main_heading']) : ''; ?>" placeholder="Enter main heading">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Destination Images (4 Images)</label>
                        <div class="image-grid">
                            <div>
                                <label class="form-label">Image 1</label>
                                <input type="text" name="image1_name" class="form-control" value="<?php echo isset($destination['image1_name']) ? htmlspecialchars($destination['image1_name']) : ''; ?>" placeholder="Enter image name">
                                <input type="file" name="image1" class="form-control-file" accept="image/*">
                                <?php if (!empty($destination['image1'])): ?>
                                <div class="image-preview">
                                    <img src="../<?php echo $destination['image1']; ?>" alt="Image 1">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="form-label">Image 2</label>
                                <input type="text" name="image2_name" class="form-control" value="<?php echo isset($destination['image2_name']) ? htmlspecialchars($destination['image2_name']) : ''; ?>" placeholder="Enter image name">
                                <input type="file" name="image2" class="form-control-file" accept="image/*">
                                <?php if (!empty($destination['image2'])): ?>
                                <div class="image-preview">
                                    <img src="../<?php echo $destination['image2']; ?>" alt="Image 2">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="form-label">Image 3</label>
                                <input type="text" name="image3_name" class="form-control" value="<?php echo isset($destination['image3_name']) ? htmlspecialchars($destination['image3_name']) : ''; ?>" placeholder="Enter image name">
                                <input type="file" name="image3" class="form-control-file" accept="image/*">
                                <?php if (!empty($destination['image3'])): ?>
                                <div class="image-preview">
                                    <img src="../<?php echo $destination['image3']; ?>" alt="Image 3">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="form-label">Image 4</label>
                                <input type="text" name="image4_name" class="form-control" value="<?php echo isset($destination['image4_name']) ? htmlspecialchars($destination['image4_name']) : ''; ?>" placeholder="Enter image name">
                                <input type="file" name="image4" class="form-control-file" accept="image/*">
                                <?php if (!empty($destination['image4'])): ?>
                                <div class="image-preview">
                                    <img src="../<?php echo $destination['image4']; ?>" alt="Image 4">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="btn-submit">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                </form>
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