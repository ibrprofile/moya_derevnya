<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();

try {
    $category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($category_id <= 0) {
        throw new Exception('Неверный ID раздела');
    }

    $breadcrumb = [];
    $current_id = $category_id;
    $max_depth = 20; // защита от бесконечного цикла

    while ($current_id && $max_depth > 0) {
        $stmt = $conn->prepare("SELECT id, name, slug, parent_id FROM categories WHERE id = ?");
        $stmt->execute([$current_id]);
        $cat = $stmt->fetch();

        if (!$cat) break;

        array_unshift($breadcrumb, $cat);
        $current_id = $cat['parent_id'];
        $max_depth--;
    }

    echo json_encode([
        'success' => true,
        'data' => $breadcrumb
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
