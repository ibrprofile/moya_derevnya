<?php
require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'Файл не загружен']);
    exit;
}

$admin = getCurrentAdmin();
$result = uploadFile($_FILES['file'], $admin['id']);

echo json_encode($result);
?>
