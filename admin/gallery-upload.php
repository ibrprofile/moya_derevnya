<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['files']) || !isset($_POST['settlement_id'])) {
        throw new Exception('Недостаточно данных для загрузки');
    }
    
    $settlement_id = intval($_POST['settlement_id']);
    
    if ($settlement_id <= 0) {
        throw new Exception('Неверный ID населенного пункта');
    }
    
    $uploadDir = __DIR__ . '/../uploads/gallery/';
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Не удалось создать папку для галереи');
        }
    }
    
    if (!is_writable($uploadDir)) {
        throw new Exception('Нет прав на запись в папку галереи');
    }
    
    $conn = getDBConnection();
    $uploaded = 0;
    $errors = [];
    
    $orderStmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM settlement_gallery WHERE settlement_id = ?");
    $orderStmt->execute([$settlement_id]);
    $nextOrder = $orderStmt->fetch()['next_order'];
    
    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['files']['name'][$key];
        $file_size = $_FILES['files']['size'][$key];
        $file_error = $_FILES['files']['error'][$key];
        
        if ($file_error !== UPLOAD_ERR_OK) {
            $errors[] = "Ошибка загрузки файла {$file_name}";
            continue;
        }
        
        if ($file_size > 10 * 1024 * 1024) {
            $errors[] = "Файл {$file_name} слишком большой (макс 10MB)";
            continue;
        }
        
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Файл {$file_name} не является изображением";
            continue;
        }
        
        // Генерируем случайное имя
        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadPath = $uploadDir . $randomName;
        
        if (move_uploaded_file($tmp_name, $uploadPath)) {
            $stmt = $conn->prepare("INSERT INTO settlement_gallery (settlement_id, image_path, display_order) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$settlement_id, 'uploads/gallery/' . $randomName, $nextOrder])) {
                $uploaded++;
                $nextOrder++;
            } else {
                $errors[] = "Не удалось сохранить {$file_name} в БД";
            }
        } else {
            $errors[] = "Не удалось переместить файл {$file_name}";
        }
    }
    
    if ($uploaded > 0) {
        echo json_encode([
            'success' => true,
            'uploaded' => $uploaded,
            'errors' => $errors
        ]);
    } else {
        throw new Exception('Не удалось загрузить ни одного файла. ' . implode(', ', $errors));
    }
    
} catch (Exception $e) {
    error_log("Gallery upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
