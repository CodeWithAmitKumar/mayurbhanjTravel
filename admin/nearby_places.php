<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ── Ensure tables exist ─────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS all_nearbyplaces (
    nearby_id      INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    place_name     VARCHAR(255) NOT NULL,
    place_image    VARCHAR(255) DEFAULT '',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// ── Upload config ───────────────────────────────────────────────────────────
$upload_dir_rel = 'uploads/nearby-places/';
$upload_dir_fs  = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'nearby-places' . DIRECTORY_SEPARATOR;
if (!is_dir($upload_dir_fs)) { mkdir($upload_dir_fs, 0777, true); }

// ── Get the nearby place being edited ──────────────────────────────────────
$nearby_id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$destination_id = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : 0;

if ($nearby_id <= 0) {
    header('Location: all_nearbyplaces.php');
    exit();
}

$res  = mysqli_query($conn, "SELECT * FROM all_nearbyplaces WHERE nearby_id = $nearby_id");
$place = $res ? mysqli_fetch_assoc($res) : null;

if (!$place) {
    header('Location: all_nearbyplaces.php?message=not_found');
    exit();
}

// Use destination_id from the record if not in URL
if ($destination_id <= 0) {
    $destination_id = (int)$place['destination_id'];
}

// Load destination name for display
$dest_name = '';
$dest_res  = mysqli_query($conn, "SELECT titel FROM all_destinations WHERE destination_id = $destination_id LIMIT 1");
if ($dest_res) {
    $dest_row  = mysqli_fetch_assoc($dest_res);
    $dest_name = $dest_row ? $dest_row['titel'] : '';
}

$message     = '';
$messageType = 'success';

// ── Handle POST (update) ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = trim((string)($_POST['action'] ?? ''));
    $place_name = trim((string)($_POST['place_name'] ?? ''));

    if ($action === 'update_nearby') {
        if ($place_name === '') {
            $message     = 'Place name is required.';
            $messageType = 'error';
        } else {
            $current_image = (string)$place['place_image'];
            $new_image     = $current_image;

            // Handle new image upload
            if (!empty($_FILES['place_image']['name'])) {
                $file = $_FILES['place_image'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                    $message = 'Only JPG, JPEG, PNG, GIF, WEBP images are allowed.';
                    $messageType = 'error';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $message = 'Image must be less than 5 MB.';
                    $messageType = 'error';
                } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                    $message = 'Image upload failed.';
                    $messageType = 'error';
                } else {
                    $filename = 'nearby_' . str_replace('.','_', uniqid('',true)) . '.' . $ext;
                    $target   = $upload_dir_fs . $filename;
                    if (!move_uploaded_file($file['tmp_name'], $target)) {
                        $message     = 'Could not save uploaded image.';
                        $messageType = 'error';
                    } else {
                        $new_image = $upload_dir_rel . $filename;
                    }
                }
            }

            if ($messageType !== 'error') {
                $stmt = mysqli_prepare($conn, "UPDATE all_nearbyplaces SET place_name = ?, place_image = ? WHERE nearby_id = ?");
                mysqli_stmt_bind_param($stmt, 'ssi', $place_name, $new_image, $nearby_id);
                if (mysqli_stmt_execute($stmt)) {
                    // Delete old image if replaced
                    if ($new_image !== $current_image && $current_image !== '') {
                        $old_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $current_image);
                        if (file_exists($old_path)) { unlink($old_path); }
                    }
                    header("Location: all_nearbyplaces.php?destination_id={$destination_id}&message=updated");
                    exit();
                } else {
                    $message     = mysqli_error($conn);
                    $messageType = 'error';
                }
            }
        }
        // Refresh place data after failed update attempt
        $place['place_name'] = $place_name;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Nearby Place - Mayurbhanj Tourism Planner</title>
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
            --success: #166534;
            --success-bg: #dcfce7;
            --danger: #b91c1c;
            --danger-bg: #fee2e2;
            --sidebar-width: 260px;
            --header-height: 70px;
            --shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Heebo', sans-serif; background: var(--bg); color: var(--text); }

        /* Sidebar */
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--dark), #2d2d3a); z-index: 1000; overflow-y: auto; box-shadow: 4px 0 20px rgba(0,0,0,.15); }
        .sidebar-brand { height: var(--header-height); display: flex; align-items: center; padding: 0 25px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-brand h1 { color: #fff; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .sidebar-brand i { color: var(--primary); }
        .sidebar-menu { padding: 20px 0; }
        .menu-section { padding: 0 20px; margin-bottom: 10px; }
        .menu-section-title { color: rgba(255,255,255,.4); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255,255,255,.7); text-decoration: none; border-radius: 10px; margin: 5px 10px; font-size: 14px; font-weight: 500; transition: .3s ease; }
        .menu-item:hover { background: rgba(255,255,255,.1); color: #fff; transform: translateX(5px); }
        .menu-item.active { background: var(--primary); color: #fff; box-shadow: 0 4px 15px rgba(134,184,23,.4); }
        .menu-item i { width: 20px; margin-right: 12px; font-size: 16px; }
        .menu-item .badge { margin-left: auto; background: rgba(255,255,255,.2); padding: 2px 8px; border-radius: 20px; font-size: 11px; }

        /* Header */
        .header { height: var(--header-height); background: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 1px 3px rgba(0,0,0,.1); position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .toggle-sidebar { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--bg); border-radius: 10px; cursor: pointer; border: none; font-size: 18px; color: var(--text); }
        .page-title { font-size: 18px; font-weight: 600; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; }

        /* Layout */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .content { padding: 30px; }

        /* Breadcrumb / back */
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 500; }
        .back-link:hover { color: var(--primary); }

        /* Page header banner */
        .page-header { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; border-radius: 20px; padding: 34px; margin-bottom: 28px; position: relative; overflow: hidden; }
        .page-header::before { content: ''; position: absolute; width: 320px; height: 320px; right: -90px; top: -120px; background: rgba(255,255,255,.12); border-radius: 50%; }
        .page-header h1 { margin: 0 0 8px; font-size: 28px; position: relative; }
        .page-header p { margin: 0; opacity: .92; position: relative; }
        .dest-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.18); border-radius: 20px; padding: 5px 14px; font-size: 13px; font-weight: 600; margin-top: 12px; }

        /* Alert */
        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: var(--success-bg); color: var(--success); }
        .alert.error   { background: var(--danger-bg);  color: var(--danger); }

        /* Form card */
        .form-card { background: #fff; border-radius: 20px; box-shadow: var(--shadow); padding: 32px; }
        .form-card-title { font-size: 20px; font-weight: 700; margin: 0 0 26px; display: flex; align-items: center; gap: 10px; }
        .form-card-title i { color: var(--primary); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .form-group { margin-bottom: 0; }
        .form-label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 13px 15px; border: 2px solid var(--border); border-radius: 12px; font: inherit; font-size: 14px; background: #fff; transition: border-color .2s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(134,184,23,.12); }

        /* Upload box */
        .upload-wrap { position: relative; }
        .upload-box { border: 2px dashed #d1d5db; border-radius: 16px; background: #f9fafb; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 28px 16px; cursor: pointer; min-height: 170px; transition: border-color .2s, background .2s; }
        .upload-box:hover { border-color: var(--primary); background: #f0f7e2; }
        .upload-box.has-image { border-style: solid; border-color: var(--primary); background: #f0f7e2; }
        .upload-box i.upload-icon { font-size: 34px; color: var(--primary); margin-bottom: 10px; }
        .upload-box span { font-size: 13px; color: var(--muted); line-height: 1.6; }
        .upload-box input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .current-img-wrap { margin-bottom: 14px; }
        .current-img-label { font-size: 12px; color: var(--muted); margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
        .current-img { width: 100%; max-height: 200px; border-radius: 14px; object-fit: cover; border: 2px solid var(--border); }
        .new-preview { width: 100%; max-height: 180px; border-radius: 12px; object-fit: cover; margin-top: 12px; display: none; border: 2px solid var(--primary); }

        /* Actions row */
        .form-actions { display: flex; align-items: center; justify-content: space-between; margin-top: 28px; flex-wrap: wrap; gap: 14px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 13px 24px; border: none; border-radius: 12px; font: inherit; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: .2s; }
        .btn-save   { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
        .btn-save:hover { opacity: .9; transform: translateY(-1px); }
        .btn-cancel { background: #f3f4f6; color: var(--text); }
        .btn-cancel:hover { background: #e5e7eb; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .content { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 22px; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">

            <a href="all_nearbyplaces.php?destination_id=<?php echo $destination_id; ?>" class="back-link">
                <i class="fa fa-arrow-left"></i> Back to Nearby Places
            </a>

            <?php if ($message !== ''): ?>
                <div class="alert <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                    <i class="fa <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h1><i class="fa fa-edit"></i> Edit Nearby Place</h1>
                <p>Update the name or image for this nearby place.</p>
                <?php if ($dest_name !== ''): ?>
                    <div class="dest-badge">
                        <i class="fa fa-map-marker-alt"></i> <?php echo h($dest_name); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-card">
                <div class="form-card-title"><i class="fa fa-map-pin"></i> Place Details</div>

                <form method="POST" enctype="multipart/form-data" id="edit-nearby-form">
                    <input type="hidden" name="action" value="update_nearby">

                    <div class="form-grid">
                        <!-- Place Name -->
                        <div class="form-group">
                            <label class="form-label" for="place_name">
                                Place Name <span style="color:var(--danger)">*</span>
                            </label>
                            <input type="text" id="place_name" name="place_name" class="form-control"
                                   value="<?php echo h($place['place_name']); ?>"
                                   placeholder="e.g. Simlipal National Park" required>
                        </div>

                        <!-- Image upload -->
                        <div class="form-group">
                            <label class="form-label">Place Image</label>

                            <?php if (!empty($place['place_image'])): ?>
                                <div class="current-img-wrap">
                                    <div class="current-img-label">Current Image</div>
                                    <img src="../<?php echo h($place['place_image']); ?>"
                                         alt="<?php echo h($place['place_name']); ?>"
                                         class="current-img" id="current-img">
                                </div>
                            <?php endif; ?>

                            <div class="upload-box <?php echo !empty($place['place_image']) ? '' : ''; ?>"
                                 id="upload-box"
                                 onclick="document.getElementById('place_image').click()">
                                <i class="fa fa-cloud-upload-alt upload-icon"></i>
                                <span>
                                    <?php echo !empty($place['place_image']) ? 'Click to replace image' : 'Click to upload image'; ?><br>
                                    JPG, PNG, GIF, WEBP — max 5 MB
                                </span>
                                <input type="file" name="place_image" id="place_image"
                                       accept="image/*"
                                       onclick="event.stopPropagation()"
                                       onchange="previewNewImg(this)">
                            </div>
                            <img id="new-preview" class="new-preview" src="" alt="New preview">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="all_nearbyplaces.php?destination_id=<?php echo $destination_id; ?>" class="btn btn-cancel">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                        <button type="submit" id="btn-save-nearby" class="btn btn-save">
                            <i class="fa fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        function previewNewImg(input) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('new-preview');
                preview.src = e.target.result;
                preview.style.display = 'block';
                // Dim current image to show it will be replaced
                const curr = document.getElementById('current-img');
                if (curr) { curr.style.opacity = '0.4'; }
                document.getElementById('upload-box').classList.add('has-image');
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
