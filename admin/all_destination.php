<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS all_destinations (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    destinationimage VARCHAR(255) DEFAULT '',
    subheading VARCHAR(255) NOT NULL,
    titel VARCHAR(255) NOT NULL,
    price VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$message = '';
$error = '';
$upload_dir_relative = 'uploads/all-destinations/';
$upload_dir_fs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'all-destinations' . DIRECTORY_SEPARATOR;

if (!is_dir($upload_dir_fs)) {
    mkdir($upload_dir_fs, 0777, true);
}

function destination_image_path($relative_path)
{
    if ($relative_path === '' || strpos($relative_path, 'uploads/all-destinations/') !== 0) {
        return '';
    }
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
}

function remove_destination_image($relative_path)
{
    $full_path = destination_image_path($relative_path);
    if ($full_path !== '' && file_exists($full_path)) {
        unlink($full_path);
    }
}

function validate_destination_file($file)
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

function save_destination_file($file, $upload_dir_fs, $upload_dir_relative)
{
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'destination_' . str_replace('.', '_', uniqid('', true)) . '.' . $extension;
    $target = $upload_dir_fs . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Unable to save uploaded image.');
    }

    return $upload_dir_relative . $filename;
}

function load_all_destinations($conn)
{
    $rows = [];
    $result = mysqli_query($conn, "SELECT * FROM all_destinations ORDER BY sort_order ASC, destination_id ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$existing_destinations = load_all_destinations($conn);
$existing_map = [];
foreach ($existing_destinations as $row) {
    $existing_map[(int) $row['destination_id']] = $row;
}

$rows_to_display = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destination_ids = $_POST['destination_id'] ?? [];
    $subheadings = $_POST['subheading'] ?? [];
    $titels = $_POST['titel'] ?? [];
    $prices = $_POST['price'] ?? [];
    $descriptions = $_POST['description'] ?? [];
    $existing_images = $_POST['existing_image'] ?? [];
    $files = $_FILES['destinationimage'] ?? ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];

    $row_count = max(
        count($destination_ids),
        count($subheadings),
        count($titels),
        count($prices),
        count($descriptions),
        count($existing_images),
        count($files['name'] ?? [])
    );

    $rows_to_save = [];
    $validation_errors = [];

    for ($i = 0; $i < $row_count; $i++) {
        $destination_id = (int) ($destination_ids[$i] ?? 0);
        $subheading = trim((string) ($subheadings[$i] ?? ''));
        $titel = trim((string) ($titels[$i] ?? ''));
        $price = trim((string) ($prices[$i] ?? ''));
        $description = trim((string) ($descriptions[$i] ?? ''));
        $current_image = $destination_id > 0 && isset($existing_map[$destination_id])
            ? (string) ($existing_map[$destination_id]['destinationimage'] ?? '')
            : trim((string) ($existing_images[$i] ?? ''));

        if ($destination_id > 0 && !isset($existing_map[$destination_id])) {
            $destination_id = 0;
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
        $is_blank = $destination_id === 0 && $subheading === '' && $titel === '' && $price === '' && $description === '' && !$has_upload;
        if ($is_blank) {
            continue;
        }

        if ($subheading === '' || $titel === '' || $price === '' || $description === '') {
            $validation_errors[] = 'Please complete all fields for destination #' . ($i + 1) . '.';
        }
        if ($destination_id === 0 && !$has_upload) {
            $validation_errors[] = 'Please upload an image for new destination #' . ($i + 1) . '.';
        }

        $file_error = validate_destination_file($file);
        if ($file_error !== '') {
            $validation_errors[] = 'Destination #' . ($i + 1) . ': ' . $file_error;
        }

        $rows_to_save[] = [
            'destination_id' => $destination_id,
            'destinationimage' => $current_image,
            'subheading' => $subheading,
            'titel' => $titel,
            'price' => $price,
            'description' => $description,
            'file' => $file,
            'has_upload' => $has_upload,
        ];
    }

    if (!empty($validation_errors)) {
        $error = implode(' ', array_unique($validation_errors));
        $rows_to_display = $rows_to_save;
    } else {
        $new_uploads = [];
        $delete_after_commit = [];
        mysqli_begin_transaction($conn);

        try {
            $submitted_ids = [];
            $update_stmt = mysqli_prepare($conn, "UPDATE all_destinations SET destinationimage = ?, subheading = ?, titel = ?, price = ?, description = ?, sort_order = ? WHERE destination_id = ?");
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO all_destinations (destinationimage, subheading, titel, price, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM all_destinations WHERE destination_id = ?");

            if (!$update_stmt || !$insert_stmt || !$delete_stmt) {
                throw new Exception('Unable to prepare database statements.');
            }

            foreach ($rows_to_save as $index => $row) {
                $destination_id = (int) $row['destination_id'];
                $image_path = $row['destinationimage'];

                if ($row['has_upload']) {
                    $new_image_path = save_destination_file($row['file'], $upload_dir_fs, $upload_dir_relative);
                    $new_uploads[] = $new_image_path;
                    if ($image_path !== '' && $image_path !== $new_image_path) {
                        $delete_after_commit[] = $image_path;
                    }
                    $image_path = $new_image_path;
                }

                if ($destination_id > 0) {
                    $submitted_ids[] = $destination_id;
                    $subheading_value = $row['subheading'];
                    $titel_value = $row['titel'];
                    $price_value = $row['price'];
                    $description_value = $row['description'];
                    $sort_order_value = $index;
                    mysqli_stmt_bind_param($update_stmt, 'sssssii', $image_path, $subheading_value, $titel_value, $price_value, $description_value, $sort_order_value, $destination_id);
                    if (!mysqli_stmt_execute($update_stmt)) {
                        throw new Exception(mysqli_error($conn));
                    }
                } else {
                    $subheading_value = $row['subheading'];
                    $titel_value = $row['titel'];
                    $price_value = $row['price'];
                    $description_value = $row['description'];
                    $sort_order_value = $index;
                    mysqli_stmt_bind_param($insert_stmt, 'sssssi', $image_path, $subheading_value, $titel_value, $price_value, $description_value, $sort_order_value);
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
                    if (!empty($existing_row['destinationimage'])) {
                        $delete_after_commit[] = $existing_row['destinationimage'];
                    }
                }
            }

            mysqli_commit($conn);
            foreach (array_unique($delete_after_commit) as $old_image) {
                remove_destination_image($old_image);
            }
            $message = 'All destinations saved successfully!';
            $existing_destinations = load_all_destinations($conn);
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            foreach ($new_uploads as $uploaded_path) {
                remove_destination_image($uploaded_path);
            }
            $error = 'Unable to save destinations. ' . $e->getMessage();
            $rows_to_display = $rows_to_save;
        }
    }
}

if (empty($rows_to_display)) {
    $rows_to_display = !empty($existing_destinations) ? $existing_destinations : [[
        'destination_id' => 0,
        'destinationimage' => '',
        'subheading' => '',
        'titel' => '',
        'price' => '',
        'description' => '',
    ]];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>All Destination Settings</title>
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
            --white: #fff;
            --danger: #ef4444;
            --sidebar-width: 260px;
            --header-height: 70px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Heebo', sans-serif; background: var(--bg); color: var(--text); }
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--dark), #2d2d3a); z-index: 1000; overflow-y: auto; }
        .sidebar-brand { height: var(--header-height); display: flex; align-items: center; padding: 0 25px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-brand h1 { color: #fff; font-size: 20px; display: flex; gap: 10px; align-items: center; }
        .sidebar-brand i { color: var(--primary); }
        .sidebar-menu { padding: 20px 0; }
        .menu-section { padding: 0 20px; margin-bottom: 10px; }
        .menu-section-title { color: rgba(255,255,255,.4); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255,255,255,.7); text-decoration: none; border-radius: 10px; margin: 5px 10px; font-size: 14px; font-weight: 500; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,.1); color: #fff; }
        .menu-item.active { background: var(--primary); box-shadow: 0 4px 15px rgba(134,184,23,.4); }
        .menu-item i { width: 20px; margin-right: 12px; }

        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .header { height: var(--header-height); background: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 1px 3px rgba(0,0,0,.1); position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .toggle-sidebar { width: 40px; height: 40px; border: none; border-radius: 10px; cursor: pointer; background: var(--bg); font-size: 18px; }
        .page-title { font-size: 18px; font-weight: 600; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; }

        .content { padding: 30px; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: var(--muted); text-decoration: none; }
        .hero { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; border-radius: 20px; padding: 34px; margin-bottom: 24px; }
        .hero h1 { margin: 0 0 10px; font-size: 30px; }
        .hero p { margin: 0; opacity: .92; }
        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; }
        .alert.success { background: rgba(34,197,94,.12); color: #15803d; }
        .alert.error { background: rgba(239,68,68,.12); color: #b91c1c; }
        .card { background: #fff; border-radius: 20px; box-shadow: var(--shadow); padding: 26px; }
        .card-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .card-title i { color: var(--primary); }

        .destination-item { background: var(--bg); border-radius: 18px; padding: 20px; margin-bottom: 18px; border: 2px solid transparent; }
        .destination-item:hover { border-color: rgba(134,184,23,.25); }
        .destination-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .destination-number { width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .remove-btn { width: 40px; height: 40px; border: none; border-radius: 50%; background: var(--danger); color: #fff; font-size: 22px; cursor: pointer; }
        .destination-fields { display: grid; grid-template-columns: 220px 1fr 1fr; gap: 18px; align-items: start; }
        .label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .group { margin-bottom: 15px; }
        .input { width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 12px; font: inherit; background: #fff; }
        .input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(134,184,23,.12); }
        textarea.input { min-height: 170px; resize: vertical; }

        .upload { position: relative; aspect-ratio: 1; border: 2px dashed #d1d5db; border-radius: 16px; background: #fff; display: flex; align-items: center; justify-content: center; text-align: center; padding: 16px; overflow: hidden; cursor: pointer; flex-direction: column; }
        .upload.has-image { border-style: solid; border-color: var(--primary); }
        .upload i { font-size: 32px; color: var(--primary); margin-bottom: 8px; }
        .upload span { font-size: 13px; color: var(--muted); line-height: 1.5; }
        .upload input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .hint.hidden { display: none; }
        .preview { display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 14px; }
        .preview.active { display: block; }

        .add-btn, .save-btn { border: none; border-radius: 12px; color: #fff; font: inherit; font-weight: 700; cursor: pointer; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); }
        .add-btn { width: 100%; padding: 15px 18px; font-size: 15px; }
        .save-wrap { display: flex; justify-content: flex-end; margin-top: 24px; }
        .save-btn { padding: 15px 28px; font-size: 16px; }

        @media (max-width: 992px) {
            .destination-fields { grid-template-columns: 1fr; }
            textarea.input { min-height: 120px; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .content { padding: 20px; }
            .hero h1 { font-size: 24px; }
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
                <h1><i class="fa fa-map-marker-alt"></i> All Destination Settings</h1>
                <p>Add multiple destinations with image, subheading, title, price, and description.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert success"><i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert error"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-title"><i class="fa fa-map-marked-alt"></i> Destination Items</div>
                    <div id="destinations-container">
                        <?php foreach ($rows_to_display as $index => $destination): ?>
                            <?php $image = (string) ($destination['destinationimage'] ?? ''); ?>
                            <div class="destination-item">
                                <div class="destination-top">
                                    <div class="destination-number"><?php echo $index + 1; ?></div>
                                    <button type="button" class="remove-btn" onclick="removeDestination(this)" title="Remove">-</button>
                                </div>
                                <div class="destination-fields">
                                    <div>
                                        <label class="label">Destination Image</label>
                                        <div class="upload <?php echo $image !== '' ? 'has-image' : ''; ?>" onclick="openPicker(event, <?php echo $index; ?>)">
                                            <div class="hint <?php echo $image !== '' ? 'hidden' : ''; ?>" id="hint_<?php echo $index; ?>">
                                                <i class="fa fa-cloud-upload-alt"></i>
                                                <span>Click to upload<br>JPG, PNG, GIF, WEBP</span>
                                            </div>
                                            <img src="<?php echo $image !== '' ? '../' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') : ''; ?>" id="preview_<?php echo $index; ?>" class="preview <?php echo $image !== '' ? 'active' : ''; ?>" alt="">
                                            <input type="file" name="destinationimage[]" id="destinationimage_<?php echo $index; ?>" accept="image/*" onchange="previewImage(this, <?php echo $index; ?>)">
                                        </div>
                                        <input type="hidden" name="destination_id[]" value="<?php echo (int) ($destination['destination_id'] ?? 0); ?>">
                                        <input type="hidden" name="existing_image[]" value="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div>
                                        <div class="group">
                                            <label class="label">Subheading</label>
                                            <input type="text" name="subheading[]" class="input" value="<?php echo htmlspecialchars((string) ($destination['subheading'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter subheading">
                                        </div>
                                        <div class="group">
                                            <label class="label">Title</label>
                                            <input type="text" name="titel[]" class="input" value="<?php echo htmlspecialchars((string) ($destination['titel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter title">
                                        </div>
                                        <div class="group">
                                            <label class="label">Price</label>
                                            <input type="text" name="price[]" class="input" value="<?php echo htmlspecialchars((string) ($destination['price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Example: Rs. 24,999">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="label">Description</label>
                                        <textarea name="description[]" class="input" placeholder="Enter destination description"><?php echo htmlspecialchars((string) ($destination['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-btn" onclick="addDestination()"><i class="fa fa-plus"></i> Add Destination</button>
                </div>
                <div class="save-wrap">
                    <button type="submit" class="save-btn"><i class="fa fa-save"></i> Save Destinations</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let destinationCount = <?php echo count($rows_to_display); ?>;

        function openPicker(event, index) {
            if (event.target.tagName.toLowerCase() === 'input') {
                return;
            }
            const input = document.getElementById('destinationimage_' + index);
            if (input) {
                input.click();
            }
        }

        function previewImage(input, index) {
            const file = input.files[0];
            if (!file) {
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('preview_' + index);
                const hint = document.getElementById('hint_' + index);
                const upload = input.closest('.upload');
                preview.src = e.target.result;
                preview.classList.add('active');
                upload.classList.add('has-image');
                if (hint) {
                    hint.classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
        }

        function addDestination() {
            const container = document.getElementById('destinations-container');
            const index = destinationCount;
            container.insertAdjacentHTML('beforeend', `
                <div class="destination-item">
                    <div class="destination-top">
                        <div class="destination-number">${index + 1}</div>
                        <button type="button" class="remove-btn" onclick="removeDestination(this)" title="Remove">-</button>
                    </div>
                    <div class="destination-fields">
                        <div>
                            <label class="label">Destination Image</label>
                            <div class="upload" onclick="openPicker(event, ${index})">
                                <div class="hint" id="hint_${index}">
                                    <i class="fa fa-cloud-upload-alt"></i>
                                    <span>Click to upload<br>JPG, PNG, GIF, WEBP</span>
                                </div>
                                <img src="" id="preview_${index}" class="preview" alt="">
                                <input type="file" name="destinationimage[]" id="destinationimage_${index}" accept="image/*" onchange="previewImage(this, ${index})">
                            </div>
                            <input type="hidden" name="destination_id[]" value="0">
                            <input type="hidden" name="existing_image[]" value="">
                        </div>
                        <div>
                            <div class="group">
                                <label class="label">Subheading</label>
                                <input type="text" name="subheading[]" class="input" placeholder="Enter subheading">
                            </div>
                            <div class="group">
                                <label class="label">Title</label>
                                <input type="text" name="titel[]" class="input" placeholder="Enter title">
                            </div>
                            <div class="group">
                                <label class="label">Price</label>
                                <input type="text" name="price[]" class="input" placeholder="Example: Rs. 24,999">
                            </div>
                        </div>
                        <div>
                            <label class="label">Description</label>
                            <textarea name="description[]" class="input" placeholder="Enter destination description"></textarea>
                        </div>
                    </div>
                </div>
            `);
            destinationCount++;
            updateNumbers();
        }

        function removeDestination(button) {
            const item = button.closest('.destination-item');
            if (item) {
                item.remove();
            }
            updateNumbers();
        }

        function updateNumbers() {
            document.querySelectorAll('.destination-number').forEach((node, index) => {
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
