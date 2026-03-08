<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $photo_id = intval($data['photo_id'] ?? 0);
    
    if ($photo_id <= 0) {
        throw new Exception('Неверный ID фотографии');
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT image_path FROM settlement_gallery WHERE id = ?");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        throw new Exception('Фото не найдено в базе данных');
    }
    
    $filePath = __DIR__ . '/../' . $photo['image_path'];
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            error_log("Failed to delete file: " . $filePath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM settlement_gallery WHERE id = ?");
    if (!$stmt->execute([$photo_id])) {
        throw new Exception('Не удалось удалить фото из базы данных');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Gallery delete error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
