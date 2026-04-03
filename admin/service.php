<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $page_heading = mysqli_real_escape_string($conn, $_POST['page_heading']);
    
    $existing_logos = isset($_POST['existing_logo']) ? $_POST['existing_logo'] : [];
    $delete_logo = isset($_POST['delete_logo']) ? $_POST['delete_logo'] : [];
    
    $delete_services = mysqli_query($conn, "DELETE FROM services");
    
    $service_names = $_POST['service_name'];
    $service_descs = $_POST['service_desc'];
    $service_logos = $_FILES['service_logo'];
    
    $upload_dir = '../uploads/services/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    for ($i = 0; $i < count($service_names); $i++) {
        $service_name = mysqli_real_escape_string($conn, $service_names[$i]);
        $service_desc = mysqli_real_escape_string($conn, $service_descs[$i]);
        
        $service_logo = '';
        $delete_this = isset($delete_logo[$i]) && $delete_logo[$i] == 1;
        
        if ($delete_this) {
            if (isset($existing_logos[$i]) && !empty($existing_logos[$i]) && file_exists('../' . $existing_logos[$i])) {
                unlink('../' . $existing_logos[$i]);
            }
        } elseif (isset($existing_logos[$i]) && !empty($existing_logos[$i]) && (!isset($service_logos['name'][$i]) || $service_logos['name'][$i] == '')) {
            $service_logo = $existing_logos[$i];
        }
        
        if (isset($service_logos['name'][$i]) && $service_logos['name'][$i] != '') {
            if (isset($existing_logos[$i]) && !empty($existing_logos[$i]) && file_exists('../' . $existing_logos[$i])) {
                unlink('../' . $existing_logos[$i]);
            }
            
            $file_ext = strtolower(pathinfo($service_logos['name'][$i], PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'svg', 'webp');
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = 'service_' . time() . '_' . $i . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($service_logos['tmp_name'][$i], $target_file)) {
                    $service_logo = 'uploads/services/' . $new_filename;
                }
            }
        }
        
        if ($service_name != '') {
            $insert = "INSERT INTO services (page_heading, service_name, service_desc, service_logo, sort_order) 
                       VALUES ('$page_heading', '$service_name', '$service_desc', '$service_logo', $i)";
            mysqli_query($conn, $insert);
        }
    }
    
    $message = 'Services saved successfully!';
}

$result = mysqli_query($conn, "SELECT * FROM services ORDER BY sort_order ASC");
$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

if (empty($services)) {
    $services[] = ['id' => '', 'service_name' => '', 'service_desc' => '', 'service_logo' => ''];
}

$heading_result = mysqli_query($conn, "SELECT page_heading FROM services LIMIT 1");
$current_heading = '';
if ($heading_result && mysqli_num_rows($heading_result) > 0) {
    $current_heading = mysqli_fetch_assoc($heading_result)['page_heading'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Service Settings - Mayurbhanj Tourism Planner</title>
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
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
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
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-title i {
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: var(--transition);
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(134, 184, 23, 0.1);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .service-item {
            background: var(--bg-light);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            position: relative;
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .service-item:hover {
            border-color: rgba(134, 184, 23, 0.3);
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .service-number {
            background: var(--primary-color);
            color: var(--white);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .remove-service {
            background: #ef4444;
            color: var(--white);
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .remove-service:hover {
            background: #dc2626;
        }

        .service-fields {
            display: grid;
            grid-template-columns: 200px 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 992px) {
            .service-fields {
                grid-template-columns: 1fr;
            }
        }

        .logo-upload {
            width: 100%;
            aspect-ratio: 1;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--white);
            position: relative;
            overflow: hidden;
        }

        .logo-upload:hover {
            border-color: var(--primary-color);
            background: rgba(134, 184, 23, 0.05);
        }

        .logo-upload i {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .logo-upload span {
            font-size: 12px;
            color: var(--text-light);
            text-align: center;
        }

        .logo-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .logo-preview {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 12px;
            object-fit: cover;
            display: none;
        }

        .logo-preview.active {
            display: block;
        }

        .logo-upload.has-logo {
            border-style: solid;
            border-color: var(--primary-color);
        }

        .btn-add {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            width: 100%;
            justify-content: center;
            margin-top: 20px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(134, 184, 23, 0.4);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(134, 184, 23, 0.4);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .btn-back:hover {
            color: var(--primary-color);
        }

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
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">
            <a href="all_page_settings.php" class="btn-back">
                <i class="fa fa-arrow-left"></i> Back to All Pages
            </a>

            <div class="page-header">
                <h1><i class="fa fa-concierge-bell"></i> Service Settings</h1>
                <p>Manage your services section</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-card">
                    <div class="form-title">
                        <i class="fa fa-heading"></i> Page Heading
                    </div>
                    <div class="form-group">
                        <label class="form-label">Heading</label>
                        <input type="text" name="page_heading" class="form-input" value="<?php echo htmlspecialchars($current_heading); ?>" placeholder="Enter page heading">
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-title">
                        <i class="fa fa-list"></i> Services
                    </div>
                    
                    <div id="services-container">
                        <?php foreach ($services as $index => $service): ?>
                            <div class="service-item" data-index="<?php echo $index; ?>">
                                <div class="service-header">
                                    <div class="service-number"><?php echo $index + 1; ?></div>
                                    <?php if ($index > 0): ?>
                                        <button type="button" class="remove-service" onclick="removeService(this)">
                                            <i class="fa fa-trash"></i> Remove
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="service-fields">
                                    <div class="form-group">
                                        <label class="form-label">Service Logo</label>
                                        <div class="logo-upload <?php echo !empty($service['service_logo']) ? 'has-logo' : ''; ?>" onclick="document.getElementById('logo_<?php echo $index; ?>').click()">
                                            <?php if (!empty($service['service_logo'])): ?>
                                                <img src="../<?php echo $service['service_logo']; ?>" class="logo-preview active" id="preview_<?php echo $index; ?>">
                                                <i class="fa fa-cloud-upload-alt" style="display:none;"></i>
                                                <span style="display:none;">Click to upload<br>JPG, PNG, SVG</span>
                                            <?php else: ?>
                                                <i class="fa fa-cloud-upload-alt"></i>
                                                <span>Click to upload<br>JPG, PNG, SVG</span>
                                                <img src="" class="logo-preview" id="preview_<?php echo $index; ?>">
                                            <?php endif; ?>
                                            <input type="file" name="service_logo[]" id="logo_<?php echo $index; ?>" accept="image/*" onchange="previewLogo(this, <?php echo $index; ?>)">
                                        </div>
                                        <?php if (!empty($service['service_logo'])): ?>
                                        <input type="hidden" name="existing_logo[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($service['service_logo']); ?>">
                                        <input type="hidden" name="delete_logo[<?php echo $index; ?>]" value="0">
                                        <input type="checkbox" name="delete_logo[<?php echo $index; ?>]" value="1" onchange="toggleDeleteLogo(this, <?php echo $index; ?>)"> 
                                        <span class="ms-2">Delete logo</span>
                                        <?php else: ?>
                                        <input type="hidden" name="existing_logo[<?php echo $index; ?>]" value="">
                                        <input type="hidden" name="delete_logo[<?php echo $index; ?>]" value="0">
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Service Name</label>
                                        <input type="text" name="service_name[]" class="form-input" value="<?php echo htmlspecialchars($service['service_name']); ?>" placeholder="Enter service name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Service Description</label>
                                        <textarea name="service_desc[]" class="form-input form-textarea" placeholder="Enter service description"><?php echo htmlspecialchars($service['service_desc']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn-add" onclick="addService()">
                        <i class="fa fa-plus"></i> Add Service
                    </button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fa fa-save"></i> Save Services
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let serviceCount = <?php echo count($services); ?>;

        function addService() {
            const container = document.getElementById('services-container');
            const newIndex = serviceCount;
            
            const serviceHtml = `
                <div class="service-item" data-index="${newIndex}">
                    <div class="service-header">
                        <div class="service-number">${newIndex + 1}</div>
                        <button type="button" class="remove-service" onclick="removeService(this)">
                            <i class="fa fa-trash"></i> Remove
                        </button>
                    </div>
                    <div class="service-fields">
                        <div class="form-group">
                            <label class="form-label">Service Logo</label>
                            <div class="logo-upload" onclick="document.getElementById('logo_${newIndex}').click()">
                                <i class="fa fa-cloud-upload-alt"></i>
                                <span>Click to upload<br>JPG, PNG, SVG</span>
                                <img src="" class="logo-preview" id="preview_${newIndex}">
                                <input type="file" name="service_logo[]" id="logo_${newIndex}" accept="image/*" onchange="previewLogo(this, ${newIndex})">
                            </div>
                            <input type="hidden" name="existing_logo[${newIndex}]" value="">
                            <input type="hidden" name="delete_logo[${newIndex}]" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Service Name</label>
                            <input type="text" name="service_name[]" class="form-input" placeholder="Enter service name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Service Description</label>
                            <textarea name="service_desc[]" class="form-input form-textarea" placeholder="Enter service description"></textarea>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', serviceHtml);
            serviceCount++;
            updateServiceNumbers();
        }

        function removeService(button) {
            const serviceItem = button.closest('.service-item');
            serviceItem.remove();
            updateServiceNumbers();
        }

        function updateServiceNumbers() {
            const services = document.querySelectorAll('.service-item');
            services.forEach((service, index) => {
                service.querySelector('.service-number').textContent = index + 1;
            });
        }

        function previewLogo(input, index) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview_' + index);
                    const uploadDiv = input.closest('.logo-upload');
                    
                    preview.src = e.target.result;
                    preview.classList.add('active');
                    uploadDiv.classList.add('has-logo');
                    
                    const icon = uploadDiv.querySelector('i');
                    const span = uploadDiv.querySelector('span');
                    if (icon) icon.style.display = 'none';
                    if (span) span.style.display = 'none';
                    
                    const deleteLabel = uploadDiv.parentElement.querySelector('label:has(input[type="checkbox"])');
                    if (deleteLabel) deleteLabel.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        }

        function toggleDeleteLogo(checkbox, index) {
            const uploadDiv = document.getElementById('logo_' + index).closest('.logo-upload');
            const preview = uploadDiv.querySelector('.logo-preview');
            if (checkbox.checked) {
                preview.style.opacity = '0.5';
            } else {
                preview.style.opacity = '1';
            }
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth <= 768) {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0)' ? 'translateX(-100%)' : 'translateX(0)';
            }
        }
    </script>
</body>

</html>
