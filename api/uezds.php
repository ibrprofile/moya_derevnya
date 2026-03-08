<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();
$guberniya_id = isset($_GET['guberniya_id']) ? intval($_GET['guberniya_id']) : 0;

try {
    if ($guberniya_id > 0) {
        $stmt = $conn->prepare("SELECT id, name FROM uezds WHERE guberniya_id = ? ORDER BY name");
        $stmt->execute([$guberniya_id]);
    } else {
        $stmt = $conn->query("SELECT id, name, guberniya_id FROM uezds ORDER BY name");
    }
    
    $uezds = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $uezds
    ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
