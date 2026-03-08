<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();
$stan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    if ($stan_id <= 0) {
        throw new Exception('Invalid stan ID');
    }
    
    $stmt = $conn->prepare("
        SELECT s.*, 
               s.custom_status as status_text,
               u.name as uezd_name, 
               g.name as guberniya_name, 
               g.id as guberniya_id, 
               u.id as uezd_id
        FROM stans s
        JOIN uezds u ON s.uezd_id = u.id
        JOIN gubernii g ON u.guberniya_id = g.id
        WHERE s.id = ?
    ");
    $stmt->execute([$stan_id]);
    $stan = $stmt->fetch();
    
    if (!$stan) {
        throw new Exception('Stan not found');
    }
    
    $stmt = $conn->prepare("SELECT * FROM stan_gallery WHERE stan_id = ? ORDER BY display_order");
    $stmt->execute([$stan_id]);
    $gallery = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM stan_population WHERE stan_id = ? ORDER BY year");
    $stmt->execute([$stan_id]);
    $population = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM stan_sections WHERE stan_id = ? ORDER BY display_order");
    $stmt->execute([$stan_id]);
    $sections = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'settlement' => $stan,
            'gallery' => $gallery,
            'population' => $population,
            'sections' => $sections
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
