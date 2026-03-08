<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (mb_strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $term = '%' . $query . '%';
    $results = [];

    // Поиск по разделам
    $stmt = $conn->prepare("
        SELECT c.id, c.name, 'category' AS type, p.name AS parent_name
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        WHERE c.name LIKE ?
        ORDER BY c.name
        LIMIT 10
    ");
    $stmt->execute([$term]);
    $categories = $stmt->fetchAll();

    foreach ($categories as $cat) {
        $results[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'type' => 'category',
            'meta' => $cat['parent_name'] ? 'Раздел, ' . $cat['parent_name'] : 'Раздел'
        ];
    }

    // Поиск по населённым пунктам
    $stmt = $conn->prepare("
        SELECT s.id, s.name, s.status, s.year_founded, c.name AS category_name
        FROM settlements_v2 s
        JOIN categories c ON s.category_id = c.id
        WHERE s.name LIKE ?
        ORDER BY s.name
        LIMIT 15
    ");
    $stmt->execute([$term]);
    $settlements = $stmt->fetchAll();

    foreach ($settlements as $s) {
        $statusLabel = $s['status'] === 'active' ? 'действующий' : 'недействующий';
        $results[] = [
            'id' => $s['id'],
            'name' => $s['name'],
            'type' => 'settlement',
            'meta' => $s['category_name'] . ' / ' . $statusLabel
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $results
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
