<?php

function mbj_ensure_guides_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS guides (
        guide_id INT AUTO_INCREMENT PRIMARY KEY,
        guide_name VARCHAR(255) NOT NULL,
        guide_image VARCHAR(255) NOT NULL DEFAULT '',
        price VARCHAR(100) NOT NULL,
        guide_address VARCHAR(255) NOT NULL,
        guide_phone_no VARCHAR(50) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException('Unable to create guides table: ' . mysqli_error($conn));
    }
}

function mbj_guides_upload_relative_dir()
{
    return 'uploads/guides/';
}

function mbj_guides_upload_fs_dir()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'guides' . DIRECTORY_SEPARATOR;
}

function mbj_ensure_guides_upload_dir()
{
    $dir = mbj_guides_upload_fs_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create guide upload directory.');
    }
}

function mbj_guide_image_full_path($relativePath)
{
    $prefix = mbj_guides_upload_relative_dir();
    if ($relativePath === '' || strpos($relativePath, $prefix) !== 0) {
        return '';
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
}

function mbj_remove_guide_image($relativePath)
{
    $fullPath = mbj_guide_image_full_path($relativePath);
    if ($fullPath !== '' && file_exists($fullPath)) {
        unlink($fullPath);
    }
}

function mbj_validate_guide_image($file, $isRequired = true)
{
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $fileName = trim((string) ($file['name'] ?? ''));

    if ($errorCode === UPLOAD_ERR_NO_FILE || $fileName === '') {
        return $isRequired ? 'Please upload a guide image.' : '';
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return 'Guide image upload failed.';
    }

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return 'Only JPG, JPEG, PNG, GIF, and WEBP images are allowed.';
    }

    if ((int) ($file['size'] ?? 0) > (5 * 1024 * 1024)) {
        return 'Guide image size must be less than 5MB.';
    }

    return '';
}

function mbj_save_guide_image($file)
{
    mbj_ensure_guides_upload_dir();

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $fileName = 'guide_' . str_replace('.', '_', uniqid('', true)) . '.' . $extension;
    $targetPath = mbj_guides_upload_fs_dir() . $fileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to save guide image.');
    }

    return mbj_guides_upload_relative_dir() . $fileName;
}

function mbj_get_guide_by_id($conn, $guideId)
{
    $guideId = (int) $guideId;
    if ($guideId <= 0) {
        return null;
    }

    $result = mysqli_query($conn, "SELECT * FROM guides WHERE guide_id = {$guideId} LIMIT 1");
    if (!$result) {
        throw new RuntimeException('Unable to fetch guide details: ' . mysqli_error($conn));
    }

    $guide = mysqli_fetch_assoc($result);
    return $guide ?: null;
}

function mbj_get_all_guides($conn)
{
    $rows = [];
    $result = mysqli_query($conn, "SELECT * FROM guides ORDER BY guide_id DESC");

    if (!$result) {
        throw new RuntimeException('Unable to load guides: ' . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}
