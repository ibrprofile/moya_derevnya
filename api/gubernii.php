<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();

try {
    $stmt = $conn->query("SELECT id, name, icon FROM gubernii ORDER BY name");
    $gubernii = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $gubernii
    ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
