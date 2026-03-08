<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['files']) || !isset($_POST['settlement_id'])) {
        throw new Exception('Недостаточно данных');
    }

    $settlement_id = intval($_POST['settlement_id']);
    if ($settlement_id <= 0) throw new Exception('Неверный ID');

    $uploadDir = __DIR__ . '/../uploads/gallery/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
    if (!is_writable($uploadDir)) throw new Exception('Нет прав записи');

    $conn = getDBConnection();
    $uploaded = 0;

    $orderStmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM settlement_photos WHERE settlement_id = ?");
    $orderStmt->execute([$settlement_id]);
    $nextOrder = $orderStmt->fetchColumn();

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) continue;
        if ($_FILES['files']['size'][$key] > 10 * 1024 * 1024) continue;

        $ext = strtolower(pathinfo($_FILES['files']['name'][$key], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;

        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
        $path = $uploadDir . $randomName;

        if (move_uploaded_file($tmp_name, $path)) {
            $stmt = $conn->prepare("INSERT INTO settlement_photos (settlement_id, image_path, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$settlement_id, 'uploads/gallery/' . $randomName, $nextOrder]);
            $uploaded++;
            $nextOrder++;
        }
    }

    echo json_encode(['success' => $uploaded > 0, 'uploaded' => $uploaded]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
