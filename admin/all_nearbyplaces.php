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

$conn->query("CREATE TABLE IF NOT EXISTS all_destinations (
    destination_id  INT AUTO_INCREMENT PRIMARY KEY,
    destinationimage VARCHAR(255) DEFAULT '',
    subheading      VARCHAR(255) NOT NULL,
    titel           VARCHAR(255) NOT NULL,
    price           VARCHAR(100) NOT NULL,
    description     TEXT DEFAULT NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// ── Upload dir ──────────────────────────────────────────────────────────────
$upload_dir_rel = 'uploads/nearby-places/';
$upload_dir_fs  = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'nearby-places' . DIRECTORY_SEPARATOR;
if (!is_dir($upload_dir_fs)) { mkdir($upload_dir_fs, 0777, true); }

// ── Load destinations ───────────────────────────────────────────────────────
$destinations = [];
$res = mysqli_query($conn, "SELECT destination_id, titel FROM all_destinations ORDER BY sort_order ASC, destination_id ASC");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $destinations[] = $row; } }

$message = '';
$messageType = 'success';
$selected_dest = isset($_GET['destination_id']) ? (int)$_GET['destination_id'] : 0;

// ── Handle POST actions ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    // ── ADD ──
    if ($action === 'add_nearby') {
        $dest_id   = (int)($_POST['destination_id'] ?? 0);
        $place_name = trim((string)($_POST['place_name'] ?? ''));
        $selected_dest = $dest_id;

        if ($dest_id <= 0 || $place_name === '') {
            $message = 'Please select a destination and enter a place name.';
            $messageType = 'error';
        } else {
            $image_path = '';
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
                    $filename   = 'nearby_' . str_replace('.','_',uniqid('',true)) . '.' . $ext;
                    $target     = $upload_dir_fs . $filename;
                    if (!move_uploaded_file($file['tmp_name'], $target)) {
                        $message = 'Could not save uploaded image.';
                        $messageType = 'error';
                    } else {
                        $image_path = $upload_dir_rel . $filename;
                    }
                }
            }

            if ($messageType !== 'error') {
                $stmt = mysqli_prepare($conn, "INSERT INTO all_nearbyplaces (destination_id, place_name, place_image) VALUES (?,?,?)");
                mysqli_stmt_bind_param($stmt, 'iss', $dest_id, $place_name, $image_path);
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: all_nearbyplaces.php?destination_id={$dest_id}&message=created");
                    exit();
                } else {
                    $message = mysqli_error($conn);
                    $messageType = 'error';
                }
            }
        }
    }

    // ── DELETE ──
    if ($action === 'delete_nearby') {
        $nearby_id = (int)($_POST['nearby_id'] ?? 0);
        $dest_id   = (int)($_POST['destination_id'] ?? 0);
        $selected_dest = $dest_id;

        $row_res = mysqli_query($conn, "SELECT place_image FROM all_nearbyplaces WHERE nearby_id = $nearby_id");
        $old_row = $row_res ? mysqli_fetch_assoc($row_res) : null;

        $stmt = mysqli_prepare($conn, "DELETE FROM all_nearbyplaces WHERE nearby_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $nearby_id);
        if (mysqli_stmt_execute($stmt)) {
            if ($old_row && $old_row['place_image'] !== '') {
                $img_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $old_row['place_image']);
                if (file_exists($img_path)) { unlink($img_path); }
            }
            header("Location: all_nearbyplaces.php?destination_id={$dest_id}&message=deleted");
            exit();
        } else {
            $message = mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// ── Flash messages via GET ──────────────────────────────────────────────────
$flashMap = [
    'created' => ['Nearby place added successfully.', 'success'],
    'updated' => ['Nearby place updated successfully.', 'success'],
    'deleted' => ['Nearby place deleted successfully.', 'success'],
];
if ($message === '' && isset($_GET['message']) && isset($flashMap[$_GET['message']])) {
    [$message, $messageType] = $flashMap[$_GET['message']];
}

// ── Load nearby places for selected destination ─────────────────────────────
$nearby_places = [];
if ($selected_dest > 0) {
    $res2 = mysqli_query($conn, "SELECT * FROM all_nearbyplaces WHERE destination_id = $selected_dest ORDER BY nearby_id ASC");
    if ($res2) { while ($r = mysqli_fetch_assoc($res2)) { $nearby_places[] = $r; } }
}

$total_nearby = count($nearby_places);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Nearby Places - Mayurbhanj Tourism Planner</title>
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
            --warning: #9a3412;
            --warning-bg: #ffedd5;
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
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 500; }
        .back-link:hover { color: var(--primary); }

        /* Page header */
        .page-header { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; border-radius: 20px; padding: 34px; margin-bottom: 24px; position: relative; overflow: hidden; }
        .page-header::before { content: ''; position: absolute; width: 320px; height: 320px; right: -90px; top: -120px; background: rgba(255,255,255,.12); border-radius: 50%; }
        .page-header h1 { margin: 0 0 10px; font-size: 30px; }
        .page-header p { margin: 0; opacity: .92; }

        /* Alert */
        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: var(--success-bg); color: var(--success); }
        .alert.error   { background: var(--danger-bg);  color: var(--danger); }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 18px; padding: 24px; box-shadow: var(--shadow); }
        .stat-label { color: var(--muted); font-size: 13px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .stat-value { font-size: 32px; font-weight: 700; }

        /* Form card */
        .form-card { background: #fff; border-radius: 20px; box-shadow: var(--shadow); padding: 28px; margin-bottom: 24px; }
        .form-card-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-card-title i { color: var(--primary); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: start; }
        .form-group { margin-bottom: 0; }
        .form-label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 14px; border: 2px solid var(--border); border-radius: 12px; font: inherit; background: #fff; font-size: 14px; transition: border-color .2s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(134,184,23,.12); }
        select.form-control { cursor: pointer; }

        /* Image upload */
        .upload-box { border: 2px dashed #d1d5db; border-radius: 16px; background: #f9fafb; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 24px 16px; cursor: pointer; position: relative; min-height: 140px; transition: border-color .2s; }
        .upload-box:hover { border-color: var(--primary); }
        .upload-box input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-box i { font-size: 30px; color: var(--primary); margin-bottom: 8px; }
        .upload-box span { font-size: 13px; color: var(--muted); line-height: 1.5; }
        .upload-preview { max-height: 110px; border-radius: 10px; margin-top: 10px; display: none; object-fit: cover; }

        /* Destination selector section */
        .dest-section { background: #fff; border-radius: 20px; box-shadow: var(--shadow); padding: 28px; margin-bottom: 24px; }
        .dest-section h2 { margin: 0 0 18px; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .dest-section h2 i { color: var(--primary); }
        .dest-select-wrap { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; }
        .dest-select-wrap .form-group { flex: 1; min-width: 220px; margin-bottom: 0; }
        .btn-filter { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border: none; border-radius: 12px; background: var(--primary); color: #fff; font: inherit; font-size: 14px; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .btn-filter:hover { background: var(--primary-dark); }

        /* Table */
        .table-card { background: #fff; border-radius: 20px; box-shadow: var(--shadow); overflow: hidden; }
        .table-header { padding: 22px 28px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .table-header h2 { margin: 0; font-size: 20px; font-weight: 700; }
        .table-note { color: var(--muted); font-size: 14px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border); vertical-align: middle; }
        th { background: #fafafa; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .6px; font-weight: 600; }
        td { font-size: 14px; }
        .thumb { width: 70px; height: 70px; border-radius: 12px; object-fit: cover; background: #e5e7eb; display: block; }
        .no-img { width: 70px; height: 70px; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 22px; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: none; border-radius: 10px; padding: 9px 14px; font: inherit; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; transition: .2s; }
        .btn-edit   { background: #e8f0fe; color: #1d4ed8; }
        .btn-delete { background: var(--danger-bg); color: var(--danger); }
        .btn-edit:hover   { background: #d1e0fc; }
        .btn-delete:hover { background: #fecaca; }
        .btn-submit { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; padding: 13px 26px; font-size: 15px; border-radius: 12px; margin-top: 6px; }
        .action-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .inline-form { margin: 0; }

        /* Add form row */
        .add-form-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end; }

        /* Empty state */
        .empty-state { padding: 60px 24px; text-align: center; color: var(--muted); }
        .empty-state i { font-size: 44px; color: var(--primary); margin-bottom: 14px; display: block; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .content { padding: 20px; }
            .page-header h1 { font-size: 22px; }
            .form-grid, .add-form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">
            <a href="all_page_settings.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to All Pages</a>

            <?php if ($message !== ''): ?>
                <div class="alert <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                    <i class="fa <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h1><i class="fa fa-map-marker-alt"></i> Nearby Places Management</h1>
                <p>Select a destination, then add and manage nearby places with images.</p>
            </div>

            <!-- Step 1: Select Destination -->
            <div class="dest-section">
                <h2><i class="fa fa-map-marked-alt"></i> Step 1 — Select a Destination</h2>
                <form method="GET" action="all_nearbyplaces.php">
                    <div class="dest-select-wrap">
                        <div class="form-group">
                            <label class="form-label" for="destination_id">Destination</label>
                            <select name="destination_id" id="destination_id" class="form-control">
                                <option value="">— Select Destination —</option>
                                <?php foreach ($destinations as $dest): ?>
                                    <option value="<?php echo (int)$dest['destination_id']; ?>"
                                        <?php echo (int)$dest['destination_id'] === $selected_dest ? 'selected' : ''; ?>>
                                        <?php echo h($dest['titel']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" id="btn-load-dest" class="btn-filter">
                            <i class="fa fa-search"></i> Load Places
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($selected_dest > 0): ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Nearby Places</div>
                    <div class="stat-value"><?php echo $total_nearby; ?></div>
                </div>
            </div>

            <!-- Step 2: Add Nearby Place -->
            <div class="form-card">
                <div class="form-card-title"><i class="fa fa-plus-circle"></i> Add Nearby Place</div>
                <form method="POST" enctype="multipart/form-data" id="add-nearby-form">
                    <input type="hidden" name="action" value="add_nearby">
                    <input type="hidden" name="destination_id" value="<?php echo $selected_dest; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="place_name">Place Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" id="place_name" name="place_name" class="form-control" placeholder="e.g. Simlipal National Park" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Place Image</label>
                            <div class="upload-box" id="upload-box" onclick="document.getElementById('place_image').click()">
                                <i class="fa fa-cloud-upload-alt"></i>
                                <span>Click to upload<br>JPG, PNG, GIF, WEBP — max 5 MB</span>
                                <img id="img-preview" class="upload-preview" src="" alt="Preview">
                                <input type="file" name="place_image" id="place_image" accept="image/*" onclick="event.stopPropagation()" onchange="previewImg(this)">
                            </div>
                        </div>
                    </div>
                    <div style="margin-top:18px">
                        <button type="submit" id="btn-add-nearby" class="btn btn-submit"><i class="fa fa-plus"></i> Add Nearby Place</button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="table-card">
                <div class="table-header">
                    <h2>Nearby Places</h2>
                    <div class="table-note">Manage all nearby places for the selected destination.</div>
                </div>

                <?php if (!empty($nearby_places)): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Place Name</th>
                                <th>Added On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nearby_places as $idx => $place): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td>
                                    <?php if (!empty($place['place_image'])): ?>
                                        <img src="../<?php echo h($place['place_image']); ?>" alt="<?php echo h($place['place_name']); ?>" class="thumb">
                                    <?php else: ?>
                                        <div class="no-img"><i class="fa fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo h($place['place_name']); ?></strong></td>
                                <td style="color:var(--muted); font-size:13px"><?php echo h(date('d M Y', strtotime($place['created_at']))); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="nearby_places.php?id=<?php echo (int)$place['nearby_id']; ?>&destination_id=<?php echo $selected_dest; ?>" class="btn btn-edit" id="edit-nearby-<?php echo (int)$place['nearby_id']; ?>">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Delete this nearby place permanently?');">
                                            <input type="hidden" name="action" value="delete_nearby">
                                            <input type="hidden" name="nearby_id" value="<?php echo (int)$place['nearby_id']; ?>">
                                            <input type="hidden" name="destination_id" value="<?php echo $selected_dest; ?>">
                                            <button type="submit" class="btn btn-delete" id="del-nearby-<?php echo (int)$place['nearby_id']; ?>">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa fa-map-marker-alt"></i>
                        <p>No nearby places added yet for this destination. Use the form above to add the first one.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
                <div class="empty-state" style="background:#fff; border-radius:20px; box-shadow:var(--shadow);">
                    <i class="fa fa-map-marked-alt"></i>
                    <p>Please select a destination above to view and manage its nearby places.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function previewImg(input) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('img-preview');
                preview.src = e.target.result;
                preview.style.display = 'block';
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
