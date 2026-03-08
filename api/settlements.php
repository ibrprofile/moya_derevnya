<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();
$district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;

try {
    if ($district_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM settlements WHERE district_id = ? ORDER BY name");
        $stmt->execute([$district_id]);
    } else {
        $stmt = $conn->query("SELECT * FROM settlements ORDER BY name");
    }
    
    $settlements = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $settlements
    ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
