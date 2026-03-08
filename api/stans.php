<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();
$uezd_id = isset($_GET['uezd_id']) ? intval($_GET['uezd_id']) : 0;

try {
    if ($uezd_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, status FROM stans WHERE uezd_id = ? ORDER BY name");
        $stmt->execute([$uezd_id]);
    } else {
        $stmt = $conn->query("SELECT id, name, status, uezd_id FROM stans ORDER BY name");
    }
    
    $stans = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $stans
    ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
