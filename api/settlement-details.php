<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$conn = getDBConnection();
$settlement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    if ($settlement_id <= 0) {
        throw new Exception('Invalid settlement ID');
    }
    
    $stmt = $conn->prepare("
        SELECT s.*, 
               s.custom_status as status_text,
               d.name as district_name, 
               r.name as region_name, 
               r.id as region_id, 
               d.id as district_id
        FROM settlements s
        JOIN districts d ON s.district_id = d.id
        JOIN regions r ON d.region_id = r.id
        WHERE s.id = ?
    ");
    $stmt->execute([$settlement_id]);
    $settlement = $stmt->fetch();
    
    if (!$settlement) {
        throw new Exception('Settlement not found');
    }
    
    // Получаем владельцев
    $stmt = $conn->prepare("SELECT * FROM owners WHERE settlement_id = ?");
    $stmt->execute([$settlement_id]);
    $owners = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM settlement_gallery WHERE settlement_id = ? ORDER BY display_order");
    $stmt->execute([$settlement_id]);
    $gallery = $stmt->fetchAll();
    
    // Получаем фотографии
    $stmt = $conn->prepare("SELECT * FROM photos WHERE settlement_id = ? AND category = 'gallery'");
    $stmt->execute([$settlement_id]);
    $photos_gallery = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM photos WHERE settlement_id = ? AND category = 'attractions'");
    $stmt->execute([$settlement_id]);
    $attractions = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM settlement_population WHERE settlement_id = ? ORDER BY year");
    $stmt->execute([$settlement_id]);
    $population = $stmt->fetchAll();
    
    // Получаем фамилии жителей
    $stmt = $conn->prepare("SELECT * FROM residents WHERE settlement_id = ?");
    $stmt->execute([$settlement_id]);
    $residents = $stmt->fetchAll();
    
    // Получаем участников войн
    $stmt = $conn->prepare("SELECT * FROM war_participants WHERE settlement_id = ?");
    $stmt->execute([$settlement_id]);
    $war_participants = $stmt->fetchAll();
    
    // Получаем интересные факты
    $stmt = $conn->prepare("SELECT * FROM interesting_facts WHERE settlement_id = ?");
    $stmt->execute([$settlement_id]);
    $facts = $stmt->fetchAll();
    
    // Получаем историю
    $stmt = $conn->prepare("SELECT * FROM history WHERE settlement_id = ?");
    $stmt->execute([$settlement_id]);
    $history = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM settlement_sections WHERE settlement_id = ? ORDER BY display_order");
    $stmt->execute([$settlement_id]);
    $sections = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'settlement' => $settlement,
            'owners' => $owners,
            'gallery' => $gallery,
            'attractions' => $attractions,
            'population' => $population,
            'residents' => $residents,
            'war_participants' => $war_participants,
            'facts' => $facts,
            'history' => $history,
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
