<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();

try {
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

    if ($category_id <= 0) {
        throw new Exception('Неверный ID раздела');
    }

    $stmt = $conn->prepare("
        SELECT id, name, status, custom_status, year_founded
        FROM settlements_v2
        WHERE category_id = ?
        ORDER BY name
    ");
    $stmt->execute([$category_id]);
    $settlements = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $settlements
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
