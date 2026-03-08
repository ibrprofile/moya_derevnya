<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();

try {
    $stmt = $conn->query("SELECT * FROM regions ORDER BY name");
    $regions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $regions
    ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
