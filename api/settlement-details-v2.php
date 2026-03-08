<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        throw new Exception('Неверный ID населённого пункта');
    }

    // Основные данные
    $stmt = $conn->prepare("
        SELECT s.*, c.name AS category_name, c.id AS category_id
        FROM settlements_v2 s
        JOIN categories c ON s.category_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $settlement = $stmt->fetch();

    if (!$settlement) {
        throw new Exception('Населённый пункт не найден');
    }

    // Хлебные крошки раздела
    $breadcrumb = [];
    $current_id = $settlement['category_id'];
    $max_depth = 20;

    while ($current_id && $max_depth > 0) {
        $stmt = $conn->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
        $stmt->execute([$current_id]);
        $cat = $stmt->fetch();
        if (!$cat) break;
        array_unshift($breadcrumb, $cat);
        $current_id = $cat['parent_id'];
        $max_depth--;
    }

    // Рубрики
    $stmt = $conn->prepare("
        SELECT id, title, content, sort_order
        FROM settlement_rubrics
        WHERE settlement_id = ?
        ORDER BY sort_order, id
    ");
    $stmt->execute([$id]);
    $rubrics = $stmt->fetchAll();

    // Галерея
    $stmt = $conn->prepare("
        SELECT id, image_path, caption, sort_order
        FROM settlement_photos
        WHERE settlement_id = ?
        ORDER BY sort_order, id
    ");
    $stmt->execute([$id]);
    $gallery = $stmt->fetchAll();

    // Динамика населения
    $stmt = $conn->prepare("
        SELECT year, population
        FROM settlement_population
        WHERE settlement_id = ?
        ORDER BY year
    ");
    $stmt->execute([$id]);
    $population = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'settlement' => $settlement,
            'breadcrumb' => $breadcrumb,
            'rubrics' => $rubrics,
            'gallery' => $gallery,
            'population' => $population
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
