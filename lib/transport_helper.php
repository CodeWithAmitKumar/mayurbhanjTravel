<?php

function mbj_ensure_transport_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS transports (
        transport_id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_image VARCHAR(255) NOT NULL DEFAULT '',
        driver_image VARCHAR(255) NOT NULL DEFAULT '',
        vehicle_details TEXT NOT NULL,
        price VARCHAR(100) NOT NULL,
        driver_details TEXT NOT NULL,
        driver_phone_no VARCHAR(50) NOT NULL,
        driver_address VARCHAR(255) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException('Unable to create transports table: ' . mysqli_error($conn));
    }
}

function mbj_transport_upload_relative_dir()
{
    return 'uploads/transports/';
}

function mbj_transport_upload_fs_dir()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'transports' . DIRECTORY_SEPARATOR;
}

function mbj_ensure_transport_upload_dir()
{
    $dir = mbj_transport_upload_fs_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create transport upload directory.');
    }
}

function mbj_transport_image_full_path($relativePath)
{
    $prefix = mbj_transport_upload_relative_dir();
    if ($relativePath === '' || strpos($relativePath, $prefix) !== 0) {
        return '';
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
}

function mbj_remove_transport_image($relativePath)
{
    $fullPath = mbj_transport_image_full_path($relativePath);
    if ($fullPath !== '' && file_exists($fullPath)) {
        unlink($fullPath);
    }
}

function mbj_validate_transport_image($file, $label, $isRequired = true)
{
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $fileName = trim((string) ($file['name'] ?? ''));

    if ($errorCode === UPLOAD_ERR_NO_FILE || $fileName === '') {
        return $isRequired ? 'Please upload ' . $label . '.' : '';
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return ucfirst($label) . ' upload failed.';
    }

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return ucfirst($label) . ' must be JPG, JPEG, PNG, GIF, or WEBP.';
    }

    if ((int) ($file['size'] ?? 0) > (5 * 1024 * 1024)) {
        return ucfirst($label) . ' size must be less than 5MB.';
    }

    return '';
}

function mbj_save_transport_image($file, $prefix)
{
    mbj_ensure_transport_upload_dir();

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $fileName = $prefix . '_' . str_replace('.', '_', uniqid('', true)) . '.' . $extension;
    $targetPath = mbj_transport_upload_fs_dir() . $fileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to save uploaded image.');
    }

    return mbj_transport_upload_relative_dir() . $fileName;
}

function mbj_get_transport_by_id($conn, $transportId)
{
    $transportId = (int) $transportId;
    if ($transportId <= 0) {
        return null;
    }

    $result = mysqli_query($conn, "SELECT * FROM transports WHERE transport_id = {$transportId} LIMIT 1");
    if (!$result) {
        throw new RuntimeException('Unable to fetch transport details: ' . mysqli_error($conn));
    }

    $transport = mysqli_fetch_assoc($result);
    return $transport ?: null;
}

function mbj_get_all_transports($conn)
{
    $rows = [];
    $result = mysqli_query($conn, "SELECT * FROM transports ORDER BY transport_id DESC");

    if (!$result) {
        throw new RuntimeException('Unable to load transports: ' . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}
