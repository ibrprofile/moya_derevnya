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

    if ($photo_id <= 0) throw new Exception('Неверный ID');

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT image_path FROM settlement_photos WHERE id = ?");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch();

    if (!$photo) throw new Exception('Фото не найдено');

    $filePath = __DIR__ . '/../' . $photo['image_path'];
    if (file_exists($filePath)) unlink($filePath);

    $conn->prepare("DELETE FROM settlement_photos WHERE id = ?")->execute([$photo_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
