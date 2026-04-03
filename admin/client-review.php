<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS client_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT '',
    tourtype VARCHAR(255) NOT NULL,
    short_desc TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$message = '';
$error = '';
$upload_dir_relative = 'uploads/clentreview/';
$upload_dir_fs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'clentreview' . DIRECTORY_SEPARATOR;

if (!is_dir($upload_dir_fs)) {
    mkdir($upload_dir_fs, 0777, true);
}

function review_image_path($relative_path)
{
    if ($relative_path === '' || strpos($relative_path, 'uploads/clentreview/') !== 0) {
        return '';
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
}

function remove_review_image($relative_path)
{
    $full_path = review_image_path($relative_path);
    if ($full_path !== '' && file_exists($full_path)) {
        unlink($full_path);
    }
}

function validate_review_file($file)
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Image upload failed.';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return 'Only JPG, JPEG, PNG, GIF, and WEBP images are allowed.';
    }

    if (($file['size'] ?? 0) > (5 * 1024 * 1024)) {
        return 'Image size must be less than 5MB.';
    }

    return '';
}

function save_review_file($file, $upload_dir_fs, $upload_dir_relative)
{
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'client_review_' . str_replace('.', '_', uniqid('', true)) . '.' . $extension;
    $target = $upload_dir_fs . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Unable to save uploaded image.');
    }

    return $upload_dir_relative . $filename;
}

function load_reviews($conn)
{
    $rows = [];
    $result = mysqli_query($conn, "SELECT * FROM client_reviews ORDER BY sort_order ASC, id ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$existing_reviews = load_reviews($conn);
$existing_map = [];
foreach ($existing_reviews as $row) {
    $existing_map[(int) $row['id']] = $row;
}

$rows_to_display = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_ids = $_POST['review_id'] ?? [];
    $client_names = $_POST['client_name'] ?? [];
    $tour_types = $_POST['tourtype'] ?? [];
    $short_descs = $_POST['short_desc'] ?? [];
    $existing_images = $_POST['existing_image'] ?? [];
    $files = $_FILES['client_image'] ?? ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];

    $row_count = max(
        count($review_ids),
        count($client_names),
        count($tour_types),
        count($short_descs),
        count($existing_images),
        count($files['name'] ?? [])
    );

    $rows_to_save = [];
    $validation_errors = [];

    for ($i = 0; $i < $row_count; $i++) {
        $review_id = (int) ($review_ids[$i] ?? 0);
        $client_name = trim((string) ($client_names[$i] ?? ''));
        $tour_type = trim((string) ($tour_types[$i] ?? ''));
        $short_desc = trim((string) ($short_descs[$i] ?? ''));
        $current_image = $review_id > 0 && isset($existing_map[$review_id])
            ? (string) ($existing_map[$review_id]['image'] ?? '')
            : trim((string) ($existing_images[$i] ?? ''));

        if ($review_id > 0 && !isset($existing_map[$review_id])) {
            $review_id = 0;
            $current_image = '';
        }

        $file = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];

        $has_upload = ($file['name'] !== '' && $file['error'] !== UPLOAD_ERR_NO_FILE);
        $is_blank = $review_id === 0 && $client_name === '' && $tour_type === '' && $short_desc === '' && !$has_upload;

        if ($is_blank) {
            continue;
        }

        if ($client_name === '' || $tour_type === '' || $short_desc === '') {
            $validation_errors[] = 'Please complete all fields for review #' . ($i + 1) . '.';
        }

        if ($review_id === 0 && !$has_upload) {
            $validation_errors[] = 'Please upload an image for new review #' . ($i + 1) . '.';
        }

        $file_error = validate_review_file($file);
        if ($file_error !== '') {
            $validation_errors[] = 'Review #' . ($i + 1) . ': ' . $file_error;
        }

        $rows_to_save[] = [
            'id' => $review_id,
            'client_name' => $client_name,
            'tourtype' => $tour_type,
            'short_desc' => $short_desc,
            'current_image' => $current_image,
            'file' => $file,
            'has_upload' => $has_upload,
        ];
    }

    if (!empty($validation_errors)) {
        $error = implode(' ', array_unique($validation_errors));
        foreach ($rows_to_save as $row) {
            $rows_to_display[] = [
                'id' => $row['id'],
                'client_name' => $row['client_name'],
                'tourtype' => $row['tourtype'],
                'short_desc' => $row['short_desc'],
                'image' => $row['current_image'],
            ];
        }
    } else {
        $new_uploads = [];
        $delete_after_commit = [];
        mysqli_begin_transaction($conn);

        try {
            $submitted_ids = [];
            $update_stmt = mysqli_prepare($conn, "UPDATE client_reviews SET client_name = ?, image = ?, tourtype = ?, short_desc = ?, sort_order = ? WHERE id = ?");
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO client_reviews (client_name, image, tourtype, short_desc, sort_order) VALUES (?, ?, ?, ?, ?)");
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM client_reviews WHERE id = ?");

            if (!$update_stmt || !$insert_stmt || !$delete_stmt) {
                throw new Exception('Unable to prepare database statements.');
            }

            foreach ($rows_to_save as $index => $row) {
                $review_id = (int) $row['id'];
                $image_path = $row['current_image'];

                if ($row['has_upload']) {
                    $new_image_path = save_review_file($row['file'], $upload_dir_fs, $upload_dir_relative);
                    $new_uploads[] = $new_image_path;
                    if ($image_path !== '' && $image_path !== $new_image_path) {
                        $delete_after_commit[] = $image_path;
                    }
                    $image_path = $new_image_path;
                }

                if ($review_id > 0) {
                    $submitted_ids[] = $review_id;
                    $client_name_value = $row['client_name'];
                    $tour_type_value = $row['tourtype'];
                    $short_desc_value = $row['short_desc'];
                    $sort_order_value = $index;
                    $review_id_value = $review_id;
                    mysqli_stmt_bind_param($update_stmt, 'ssssii', $client_name_value, $image_path, $tour_type_value, $short_desc_value, $sort_order_value, $review_id_value);
                    if (!mysqli_stmt_execute($update_stmt)) {
                        throw new Exception(mysqli_error($conn));
                    }
                } else {
                    $client_name_value = $row['client_name'];
                    $tour_type_value = $row['tourtype'];
                    $short_desc_value = $row['short_desc'];
                    $sort_order_value = $index;
                    mysqli_stmt_bind_param($insert_stmt, 'ssssi', $client_name_value, $image_path, $tour_type_value, $short_desc_value, $sort_order_value);
                    if (!mysqli_stmt_execute($insert_stmt)) {
                        throw new Exception(mysqli_error($conn));
                    }
                }
            }

            foreach ($existing_map as $existing_id => $existing_row) {
                if (!in_array($existing_id, $submitted_ids, true)) {
                    mysqli_stmt_bind_param($delete_stmt, 'i', $existing_id);
                    if (!mysqli_stmt_execute($delete_stmt)) {
                        throw new Exception(mysqli_error($conn));
                    }
                    if (!empty($existing_row['image'])) {
                        $delete_after_commit[] = $existing_row['image'];
                    }
                }
            }

            mysqli_commit($conn);
            foreach (array_unique($delete_after_commit) as $old_image) {
                remove_review_image($old_image);
            }

            $message = 'Client reviews saved successfully!';
            $existing_reviews = load_reviews($conn);
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            foreach ($new_uploads as $uploaded_path) {
                remove_review_image($uploaded_path);
            }

            $error = 'Unable to save client reviews. ' . $e->getMessage();
            foreach ($rows_to_save as $row) {
                $rows_to_display[] = [
                    'id' => $row['id'],
                    'client_name' => $row['client_name'],
                    'tourtype' => $row['tourtype'],
                    'short_desc' => $row['short_desc'],
                    'image' => $row['current_image'],
                ];
            }
        }
    }
}

if (empty($rows_to_display)) {
    $rows_to_display = !empty($existing_reviews) ? $existing_reviews : [[
        'id' => 0,
        'client_name' => '',
        'tourtype' => '',
        'short_desc' => '',
        'image' => '',
    ]];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Client Review Settings - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
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
            --danger: #ef4444;
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

        .back-link {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 20px;
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 20px;
            padding: 36px;
            color: var(--white);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .hero h1 {
            font-size: 32px;
            margin-bottom: 10px;
            position: relative;
        }

        .hero p {
            position: relative;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 28px;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 22px;
        }

        .card-title i {
            color: var(--primary-color);
        }

        .review-item {
            background: var(--bg-light);
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 18px;
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .review-item:hover {
            border-color: rgba(134,184,23,.25);
        }

        .review-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
        }

        .review-number {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .remove-btn,
        .add-btn,
        .save-btn {
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
        }

        .remove-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--danger);
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .remove-btn:hover {
            background: #dc2626;
        }

        .fields {
            display: grid;
            grid-template-columns: 220px 1fr 1fr;
            gap: 18px;
        }

        .group {
            margin-bottom: 16px;
        }

        .label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            transition: var(--transition);
        }

        .input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(134,184,23,.1);
        }

        textarea.input {
            min-height: 120px;
            resize: vertical;
        }

        .upload {
            position: relative;
            aspect-ratio: 1;
            border: 2px dashed #d1d5db;
            border-radius: 16px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 18px;
            overflow: hidden;
            cursor: pointer;
            transition: var(--transition);
        }

        .upload:hover {
            border-color: var(--primary-color);
            background: rgba(134, 184, 23, 0.05);
        }

        .upload.has-image {
            border-style: solid;
            border-color: var(--primary-color);
        }

        .upload i {
            font-size: 34px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .upload span {
            font-size: 13px;
            color: var(--text-light);
            line-height: 1.5;
        }

        .upload input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .hint.hidden {
            display: none;
        }

        .preview {
            display: none;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 14px;
        }

        .preview.active {
            display: block;
        }

        .add-btn,
        .save-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #fff;
            border-radius: 12px;
            font-weight: 600;
        }

        .add-btn {
            width: 100%;
            padding: 15px 18px;
            margin-top: 8px;
            font-size: 15px;
        }

        .save-wrap {
            display: flex;
            justify-content: flex-end;
            margin-top: 28px;
        }

        .save-btn {
            padding: 15px 34px;
            font-size: 16px;
        }

        .add-btn:hover,
        .save-btn:hover,
        .remove-btn:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 992px) {
            .fields {
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

            .hero h1 {
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
            <a href="all_page_settings.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to All Pages</a>

            <div class="hero">
                <h1><i class="fa fa-star"></i> Client Review Settings</h1>
                <p>Add multiple client reviews with image, client name, tour type, and short description.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-title"><i class="fa fa-comments"></i> Client Reviews</div>
                    <div id="reviews-container">
                        <?php foreach ($rows_to_display as $index => $review): ?>
                            <?php $image = (string) ($review['image'] ?? ''); ?>
                            <div class="review-item">
                                <div class="review-top">
                                    <div class="review-number"><?php echo $index + 1; ?></div>
                                    <button type="button" class="remove-btn" onclick="removeReview(this)"><i class="fa fa-minus"></i> Remove</button>
                                </div>
                                <div class="fields">
                                    <div class="group">
                                        <label class="label">Client Image</label>
                                        <div class="upload <?php echo $image !== '' ? 'has-image' : ''; ?>" onclick="openPicker(event, <?php echo $index; ?>)">
                                            <div class="hint <?php echo $image !== '' ? 'hidden' : ''; ?>" id="hint_<?php echo $index; ?>">
                                                <i class="fa fa-cloud-upload-alt"></i>
                                                <span>Click to upload<br>JPG, PNG, GIF, WEBP</span>
                                            </div>
                                            <img src="<?php echo $image !== '' ? '../' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') : ''; ?>" id="preview_<?php echo $index; ?>" class="preview <?php echo $image !== '' ? 'active' : ''; ?>" alt="">
                                            <input type="file" name="client_image[]" id="client_image_<?php echo $index; ?>" accept="image/*" onchange="previewImage(this, <?php echo $index; ?>)">
                                        </div>
                                        <input type="hidden" name="review_id[]" value="<?php echo (int) ($review['id'] ?? 0); ?>">
                                        <input type="hidden" name="existing_image[]" value="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div>
                                        <div class="group">
                                            <label class="label">Client Name</label>
                                            <input type="text" name="client_name[]" class="input" value="<?php echo htmlspecialchars((string) ($review['client_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter client name">
                                        </div>
                                        <div class="group">
                                            <label class="label">Tour Type</label>
                                            <input type="text" name="tourtype[]" class="input" value="<?php echo htmlspecialchars((string) ($review['tourtype'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Example: Family Tour">
                                        </div>
                                    </div>
                                    <div class="group">
                                        <label class="label">Short Description</label>
                                        <textarea name="short_desc[]" class="input" placeholder="Write a short review"><?php echo htmlspecialchars((string) ($review['short_desc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-btn" onclick="addReview()"><i class="fa fa-plus"></i> Add Review</button>
                </div>
                <div class="save-wrap">
                    <button type="submit" class="save-btn"><i class="fa fa-save"></i> Save Reviews</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let reviewCount = <?php echo count($rows_to_display); ?>;

        function openPicker(event, index) {
            if (event.target.tagName.toLowerCase() === 'input') return;
            const input = document.getElementById('client_image_' + index);
            if (input) input.click();
        }

        function previewImage(input, index) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('preview_' + index);
                const hint = document.getElementById('hint_' + index);
                const upload = input.closest('.upload');
                preview.src = e.target.result;
                preview.classList.add('active');
                upload.classList.add('has-image');
                if (hint) hint.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        function addReview() {
            const container = document.getElementById('reviews-container');
            const index = reviewCount;
            container.insertAdjacentHTML('beforeend', `
                <div class="review-item">
                    <div class="review-top">
                        <div class="review-number">${index + 1}</div>
                        <button type="button" class="remove-btn" onclick="removeReview(this)"><i class="fa fa-minus"></i> Remove</button>
                    </div>
                    <div class="fields">
                        <div class="group">
                            <label class="label">Client Image</label>
                            <div class="upload" onclick="openPicker(event, ${index})">
                                <div class="hint" id="hint_${index}">
                                    <i class="fa fa-cloud-upload-alt"></i>
                                    <span>Click to upload<br>JPG, PNG, GIF, WEBP</span>
                                </div>
                                <img src="" id="preview_${index}" class="preview" alt="">
                                <input type="file" name="client_image[]" id="client_image_${index}" accept="image/*" onchange="previewImage(this, ${index})">
                            </div>
                            <input type="hidden" name="review_id[]" value="0">
                            <input type="hidden" name="existing_image[]" value="">
                        </div>
                        <div>
                            <div class="group">
                                <label class="label">Client Name</label>
                                <input type="text" name="client_name[]" class="input" placeholder="Enter client name">
                            </div>
                            <div class="group">
                                <label class="label">Tour Type</label>
                                <input type="text" name="tourtype[]" class="input" placeholder="Example: Family Tour">
                            </div>
                        </div>
                        <div class="group">
                            <label class="label">Short Description</label>
                            <textarea name="short_desc[]" class="input" placeholder="Write a short review"></textarea>
                        </div>
                    </div>
                </div>
            `);
            reviewCount++;
            updateNumbers();
        }

        function removeReview(button) {
            const item = button.closest('.review-item');
            if (item) item.remove();
            updateNumbers();
        }

        function updateNumbers() {
            document.querySelectorAll('.review-item .review-number').forEach((node, index) => {
                node.textContent = index + 1;
            });
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
