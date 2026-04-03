<?php

function mbj_ensure_hotels_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS hotels (
        hotel_id INT AUTO_INCREMENT PRIMARY KEY,
        hotel_image VARCHAR(255) NOT NULL DEFAULT '',
        hotel_name VARCHAR(255) NOT NULL,
        location VARCHAR(255) NOT NULL,
        manager_name VARCHAR(255) NOT NULL,
        contact_no VARCHAR(50) NOT NULL,
        facility TEXT NOT NULL,
        price VARCHAR(100) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException('Unable to create hotels table: ' . mysqli_error($conn));
    }
}

function mbj_hotels_upload_relative_dir()
{
    return 'uploads/hotels/';
}

function mbj_hotels_upload_fs_dir()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'hotels' . DIRECTORY_SEPARATOR;
}

function mbj_ensure_hotels_upload_dir()
{
    $dir = mbj_hotels_upload_fs_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create hotel upload directory.');
    }
}

function mbj_hotel_image_full_path($relativePath)
{
    $prefix = mbj_hotels_upload_relative_dir();
    if ($relativePath === '' || strpos($relativePath, $prefix) !== 0) {
        return '';
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
}

function mbj_remove_hotel_image($relativePath)
{
    $fullPath = mbj_hotel_image_full_path($relativePath);
    if ($fullPath !== '' && file_exists($fullPath)) {
        unlink($fullPath);
    }
}

function mbj_validate_hotel_image($file, $isRequired = true)
{
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $fileName = trim((string) ($file['name'] ?? ''));

    if ($errorCode === UPLOAD_ERR_NO_FILE || $fileName === '') {
        return $isRequired ? 'Please upload a hotel image.' : '';
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return 'Hotel image upload failed.';
    }

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return 'Only JPG, JPEG, PNG, GIF, and WEBP images are allowed.';
    }

    if ((int) ($file['size'] ?? 0) > (5 * 1024 * 1024)) {
        return 'Hotel image size must be less than 5MB.';
    }

    return '';
}

function mbj_save_hotel_image($file)
{
    mbj_ensure_hotels_upload_dir();

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $fileName = 'hotel_' . str_replace('.', '_', uniqid('', true)) . '.' . $extension;
    $targetPath = mbj_hotels_upload_fs_dir() . $fileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to save hotel image.');
    }

    return mbj_hotels_upload_relative_dir() . $fileName;
}

function mbj_get_hotel_by_id($conn, $hotelId)
{
    $hotelId = (int) $hotelId;
    if ($hotelId <= 0) {
        return null;
    }

    $result = mysqli_query($conn, "SELECT * FROM hotels WHERE hotel_id = {$hotelId} LIMIT 1");
    if (!$result) {
        throw new RuntimeException('Unable to fetch hotel details: ' . mysqli_error($conn));
    }

    $hotel = mysqli_fetch_assoc($result);
    return $hotel ?: null;
}

function mbj_get_all_hotels($conn)
{
    $rows = [];
    $result = mysqli_query($conn, "SELECT * FROM hotels ORDER BY hotel_id DESC");

    if (!$result) {
        throw new RuntimeException('Unable to load hotels: ' . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}
