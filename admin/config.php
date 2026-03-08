<?php
session_start();

// Конфигурация для админ-панели
define('ADMIN_SESSION_KEY', 'admin_user_id');
define('ADMIN_USERNAME_KEY', 'admin_username');

require_once '../config/database.php';

// Проверка авторизации
function checkAdminAuth() {
    if (!isset($_SESSION[ADMIN_SESSION_KEY])) {
        header('Location: login.php');
        exit;
    }
}

// Получение текущего администратора
function getCurrentAdmin() {
    if (!isset($_SESSION[ADMIN_SESSION_KEY])) {
        return null;
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, full_name FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION[ADMIN_SESSION_KEY]]);
    return $stmt->fetch();
}

// Функция для загрузки файлов
function uploadFile($file, $admin_id) {
    $upload_dir = __DIR__ . '/../uploads/';
    
    // Создаем папку, если не существует
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Проверка на ошибки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Ошибка при загрузке файла'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $stored_name = bin2hex(random_bytes(16)) . '.' . $file_extension;
    $file_path = $upload_dir . $stored_name;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO uploaded_files (original_name, stored_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $file['name'],
                $stored_name,
                'uploads/' . $stored_name,
                $file['type'],
                $file['size'],
                $admin_id
            ]);
            
            return [
                'success' => true,
                'file_id' => $conn->lastInsertId(),
                'url' => 'uploads/' . $stored_name
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Ошибка при сохранении в БД: ' . $e->getMessage()];
        }
    }
    
    return ['success' => false, 'error' => 'Не удалось переместить файл'];
}

// Функция безопасного получения POST данных
function getPostValue($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}
?>
