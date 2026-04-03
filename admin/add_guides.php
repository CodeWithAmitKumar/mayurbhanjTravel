<?php
session_start();

require_once '../config.php';
require_once '../lib/guide_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$guide = [
    'guide_id' => 0,
    'guide_name' => '',
    'guide_image' => '',
    'price' => '',
    'guide_address' => '',
    'guide_phone_no' => '',
    'is_active' => 1,
];

try {
    mbj_ensure_guides_table($conn);
    mbj_ensure_guides_upload_dir();
} catch (Throwable $throwable) {
    $error = $throwable->getMessage();
}

$requestedGuideId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($error === '' && $requestedGuideId > 0) {
    try {
        $existingGuide = mbj_get_guide_by_id($conn, $requestedGuideId);
        if ($existingGuide) {
            $guide = $existingGuide;
        } else {
            $error = 'The selected guide could not be found.';
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $postedGuideId = isset($_POST['guide_id']) ? (int) $_POST['guide_id'] : 0;
    $guideName = trim((string) ($_POST['guide_name'] ?? ''));
    $price = trim((string) ($_POST['price'] ?? ''));
    $guideAddress = trim((string) ($_POST['guide_address'] ?? ''));
    $guidePhoneNo = trim((string) ($_POST['guide_phone_no'] ?? ''));
    $isActive = isset($_POST['is_active']) && (int) $_POST['is_active'] === 0 ? 0 : 1;
    $file = $_FILES['guide_image'] ?? ['name' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

    $guide = [
        'guide_id' => $postedGuideId,
        'guide_name' => $guideName,
        'guide_image' => $guide['guide_image'] ?? '',
        'price' => $price,
        'guide_address' => $guideAddress,
        'guide_phone_no' => $guidePhoneNo,
        'is_active' => $isActive,
    ];

    try {
        $existingGuide = $postedGuideId > 0 ? mbj_get_guide_by_id($conn, $postedGuideId) : null;
        if ($postedGuideId > 0 && !$existingGuide) {
            throw new RuntimeException('The selected guide could not be found.');
        }

        if ($existingGuide) {
            $guide['guide_image'] = (string) ($existingGuide['guide_image'] ?? '');
        }

        $validationErrors = [];
        if ($guideName === '') {
            $validationErrors[] = 'Guide name is required.';
        }
        if ($price === '') {
            $validationErrors[] = 'Price is required.';
        }
        if ($guideAddress === '') {
            $validationErrors[] = 'Guide address is required.';
        }
        if ($guidePhoneNo === '') {
            $validationErrors[] = 'Guide phone number is required.';
        }

        $fileError = mbj_validate_guide_image($file, $existingGuide === null || empty($existingGuide['guide_image']));
        if ($fileError !== '') {
            $validationErrors[] = $fileError;
        }

        if (!empty($validationErrors)) {
            throw new RuntimeException(implode(' ', $validationErrors));
        }

        $newImagePath = '';
        $imagePath = $existingGuide['guide_image'] ?? '';

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && trim((string) ($file['name'] ?? '')) !== '') {
            $newImagePath = mbj_save_guide_image($file);
            $imagePath = $newImagePath;
        }

        mysqli_begin_transaction($conn);

        try {
            if ($existingGuide) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE guides
                     SET guide_name = ?, guide_image = ?, price = ?, guide_address = ?, guide_phone_no = ?, is_active = ?
                     WHERE guide_id = ?"
                );

                if (!$stmt) {
                    throw new RuntimeException('Unable to prepare guide update statement.');
                }

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssssii',
                    $guideName,
                    $imagePath,
                    $price,
                    $guideAddress,
                    $guidePhoneNo,
                    $isActive,
                    $postedGuideId
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException(mysqli_error($conn));
                }
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO guides (guide_name, guide_image, price, guide_address, guide_phone_no, is_active)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                if (!$stmt) {
                    throw new RuntimeException('Unable to prepare guide insert statement.');
                }

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssssi',
                    $guideName,
                    $imagePath,
                    $price,
                    $guideAddress,
                    $guidePhoneNo,
                    $isActive
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException(mysqli_error($conn));
                }
            }

            mysqli_commit($conn);

            if ($existingGuide && $newImagePath !== '' && $existingGuide['guide_image'] !== '' && $existingGuide['guide_image'] !== $newImagePath) {
                mbj_remove_guide_image($existingGuide['guide_image']);
            }

            header('Location: allguides.php?message=' . ($existingGuide ? 'updated' : 'created'));
            exit();
        } catch (Throwable $throwable) {
            mysqli_rollback($conn);
            if ($newImagePath !== '') {
                mbj_remove_guide_image($newImagePath);
            }
            throw $throwable;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$isEdit = (int) ($guide['guide_id'] ?? 0) > 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo $isEdit ? 'Edit Guide' : 'Add Guide'; ?> - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #86B817;
            --primary-dark: #6a9612;
            --dark: #14141F;
            --text: #1f2937;
            --muted: #6b7280;
            --bg: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
            --danger: #b91c1c;
            --danger-bg: #fee2e2;
            --sidebar-width: 260px;
            --header-height: 70px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Heebo', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--dark), #2d2d3a);
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand h1 {
            color: var(--white);
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand i {
            color: var(--primary);
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-section {
            padding: 0 20px;
            margin-bottom: 10px;
        }

        .menu-section-title {
            color: rgba(255, 255, 255, 0.4);
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
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            margin: 5px 10px;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s ease;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            transform: translateX(5px);
        }

        .menu-item.active {
            background: var(--primary);
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
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .header {
            height: var(--header-height);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
            background: var(--bg);
            border-radius: 10px;
            cursor: pointer;
            border: none;
            font-size: 18px;
            color: var(--text);
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .content {
            padding: 30px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            text-decoration: none;
            margin-bottom: 18px;
        }

        .hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border-radius: 20px;
            padding: 34px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            right: -80px;
            top: -120px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 50%;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }

        .hero p {
            margin: 0;
            opacity: 0.92;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            background: var(--danger-bg);
            color: var(--danger);
        }

        .card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 28px;
        }

        .card-title {
            margin: 0 0 22px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
        }

        .upload-card {
            background: var(--bg);
            border-radius: 18px;
            padding: 18px;
        }

        .upload-box {
            position: relative;
            min-height: 280px;
            border-radius: 16px;
            border: 2px dashed #cbd5e1;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 16px;
            overflow: hidden;
            cursor: pointer;
        }

        .upload-box.has-image {
            border-style: solid;
            border-color: var(--primary);
        }

        .upload-box input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-hint i {
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .upload-hint span {
            display: block;
            color: var(--muted);
            line-height: 1.6;
        }

        .upload-hint.hidden {
            display: none;
        }

        .preview {
            width: 100%;
            height: 280px;
            object-fit: cover;
            border-radius: 14px;
            display: none;
        }

        .preview.active {
            display: block;
        }

        .current-image-note {
            margin-top: 12px;
            color: var(--muted);
            font-size: 13px;
        }

        .fields-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .input,
        .select {
            width: 100%;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 13px 14px;
            font: inherit;
            background: var(--white);
        }

        .input:focus,
        .select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(134, 184, 23, 0.12);
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            padding: 14px 22px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-secondary {
            background: #edf2f7;
            color: var(--text);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
        }

        @media (max-width: 991px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .content {
                padding: 20px;
            }

            .fields-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 24px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">
            <a href="allguides.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to All Guides</a>

            <div class="hero">
                <h1><i class="fa fa-user-tie"></i> <?php echo $isEdit ? 'Edit Guide' : 'Add New Guide'; ?></h1>
                <p>Manage guide name, image, price, address, phone number, and status from one place.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert"><i class="fa fa-exclamation-circle"></i> <?php echo h($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <h2 class="card-title"><i class="fa fa-edit"></i> Guide Details</h2>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="guide_id" value="<?php echo (int) ($guide['guide_id'] ?? 0); ?>">

                    <div class="form-grid">
                        <div class="upload-card">
                            <label class="label">Guide Image</label>
                            <?php $imagePath = (string) ($guide['guide_image'] ?? ''); ?>
                            <div class="upload-box <?php echo $imagePath !== '' ? 'has-image' : ''; ?>">
                                <div class="upload-hint <?php echo $imagePath !== '' ? 'hidden' : ''; ?>" id="uploadHint">
                                    <i class="fa fa-cloud-upload-alt"></i>
                                    <span>Click to upload guide image<br>JPG, PNG, GIF, WEBP up to 5MB</span>
                                </div>
                                <img
                                    src="<?php echo $imagePath !== '' ? '../' . h($imagePath) : ''; ?>"
                                    alt="Guide preview"
                                    id="imagePreview"
                                    class="preview <?php echo $imagePath !== '' ? 'active' : ''; ?>">
                                <input type="file" name="guide_image" accept="image/*" onchange="previewGuideImage(this)">
                            </div>
                            <div class="current-image-note">
                                <?php echo $isEdit ? 'Upload a new image only if you want to replace the current one.' : 'Guide image is required for a new guide.'; ?>
                            </div>
                        </div>

                        <div>
                            <div class="fields-grid">
                                <div class="field">
                                    <label class="label">Guide Name</label>
                                    <input type="text" name="guide_name" class="input" value="<?php echo h($guide['guide_name'] ?? ''); ?>" placeholder="Enter guide name">
                                </div>

                                <div class="field">
                                    <label class="label">Price</label>
                                    <input type="text" name="price" class="input" value="<?php echo h($guide['price'] ?? ''); ?>" placeholder="Example: Rs. 1,500 / day">
                                </div>

                                <div class="field full">
                                    <label class="label">Guide Address</label>
                                    <input type="text" name="guide_address" class="input" value="<?php echo h($guide['guide_address'] ?? ''); ?>" placeholder="Enter guide address">
                                </div>

                                <div class="field">
                                    <label class="label">Guide Phone No</label>
                                    <input type="text" name="guide_phone_no" class="input" value="<?php echo h($guide['guide_phone_no'] ?? ''); ?>" placeholder="Enter guide phone number">
                                </div>

                                <div class="field">
                                    <label class="label">Status</label>
                                    <select name="is_active" class="select">
                                        <option value="1" <?php echo (int) ($guide['is_active'] ?? 1) === 1 ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo (int) ($guide['is_active'] ?? 1) === 0 ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="actions">
                        <a href="allguides.php" class="btn btn-secondary"><i class="fa fa-times"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $isEdit ? 'Update Guide' : 'Save Guide'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewGuideImage(input) {
            const file = input.files[0];
            if (!file) {
                return;
            }

            const preview = document.getElementById('imagePreview');
            const hint = document.getElementById('uploadHint');
            const reader = new FileReader();

            reader.onload = function(event) {
                preview.src = event.target.result;
                preview.classList.add('active');
                input.closest('.upload-box').classList.add('has-image');
                if (hint) {
                    hint.classList.add('hidden');
                }
            };

            reader.readAsDataURL(file);
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth <= 768) {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0)';
            }
        }
    </script>
</body>

</html>
