<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();
$guberniya_id = isset($_GET['guberniya_id']) ? intval($_GET['guberniya_id']) : 0;

try {
    if ($guberniya_id <= 0) {
        throw new Exception('Invalid guberniya ID');
    }
    
    $stmt = $conn->prepare("SELECT id, name FROM uezds WHERE guberniya_id = ? ORDER BY name");
    $stmt->execute([$guberniya_id]);
    $uezds = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $uezds
    ], JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
