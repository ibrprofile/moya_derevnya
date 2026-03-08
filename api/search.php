<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $searchTerm = "%{$query}%";
    
    // Поиск по регионам
    $stmt = $conn->prepare("SELECT id, name, 'region' as type FROM regions WHERE name LIKE ? LIMIT 5");
    $stmt->execute([$searchTerm]);
    $regions = $stmt->fetchAll();
    
    // Поиск по районам
    $stmt = $conn->prepare("
        SELECT d.id, d.name, 'district' as type, r.name as region_name 
        FROM districts d 
        JOIN regions r ON d.region_id = r.id 
        WHERE d.name LIKE ? 
        LIMIT 5
    ");
    $stmt->execute([$searchTerm]);
    $districts = $stmt->fetchAll();
    
    // Поиск по населенным пунктам
    $stmt = $conn->prepare("
        SELECT s.id, s.name, 'settlement' as type, d.name as district_name, r.name as region_name 
        FROM settlements s 
        JOIN districts d ON s.district_id = d.id 
        JOIN regions r ON d.region_id = r.id 
        WHERE s.name LIKE ? 
        LIMIT 5
    ");
    $stmt->execute([$searchTerm]);
    $settlements = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT id, name, 'guberniya' as type FROM gubernii WHERE name LIKE ? LIMIT 5");
    $stmt->execute([$searchTerm]);
    $gubernii = $stmt->fetchAll();
    
    $stmt = $conn->prepare("
        SELECT u.id, u.name, 'uezd' as type, g.name as guberniya_name 
        FROM uezds u 
        JOIN gubernii g ON u.guberniya_id = g.id 
        WHERE u.name LIKE ? 
        LIMIT 5
    ");
    $stmt->execute([$searchTerm]);
    $uezds = $stmt->fetchAll();
    
    $stmt = $conn->prepare("
        SELECT s.id, s.name, 'stan' as type, u.name as uezd_name, g.name as guberniya_name 
        FROM stans s 
        JOIN uezds u ON s.uezd_id = u.id 
        JOIN gubernii g ON u.guberniya_id = g.id 
        WHERE s.name LIKE ? 
        LIMIT 5
    ");
    $stmt->execute([$searchTerm]);
    $stans = $stmt->fetchAll();
    
    $results = array_merge($regions, $districts, $settlements, $gubernii, $uezds, $stans);
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
