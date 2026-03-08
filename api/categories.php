<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();

try {
    $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;

    if ($parent_id === null || $parent_id === 0) {
        // Корневые разделы
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.slug, c.description, c.sort_order,
                   (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) AS children_count,
                   (SELECT COUNT(*) FROM settlements_v2 WHERE category_id = c.id) AS settlements_count
            FROM categories c
            WHERE c.parent_id IS NULL
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute();
    } else {
        // Подразделы конкретного раздела
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.slug, c.description, c.sort_order,
                   (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) AS children_count,
                   (SELECT COUNT(*) FROM settlements_v2 WHERE category_id = c.id) AS settlements_count
            FROM categories c
            WHERE c.parent_id = ?
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute([$parent_id]);
    }

    $categories = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
