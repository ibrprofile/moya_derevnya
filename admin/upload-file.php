<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['file'])) {
        throw new Exception('Файл не загружен');
    }
    
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер',
            UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой',
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Ошибка записи на диск',
            UPLOAD_ERR_EXTENSION => 'Загрузка остановлена расширением'
        ];
        throw new Exception($errors[$file['error']] ?? 'Ошибка загрузки файла');
    }
    
    $uploadDir = __DIR__ . '/../uploads/';
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Не удалось создать папку uploads');
        }
    }
    
    if (!is_writable($uploadDir)) {
        throw new Exception('Нет прав на запись в папку uploads');
    }
    
    // Получаем расширение файла
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $allowedTypes = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'rtf',
        'zip', 'rar', '7z',
        'mp3', 'mp4', 'avi', 'mov'
    ];
    
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('Недопустимый тип файла: .' . $extension);
    }
    
    $maxSize = 20 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Файл слишком большой (макс 20MB)');
    }
    
    // Генерируем случайное имя файла
    $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
    $uploadPath = $uploadDir . $randomName;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Не удалось переместить загруженный файл');
    }
    
    $conn = getDBConnection();
    $admin = getCurrentAdmin();
    
    $stmt = $conn->prepare("INSERT INTO uploaded_files (original_name, stored_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $file['name'],
        $randomName,
        'uploads/' . $randomName,
        $file['type'],
        $file['size'],
        $admin['id']
    ]);
    
    echo json_encode([
        'success' => true,
        'url' => 'uploads/' . $randomName,
        'filename' => $file['name'],
        'file_id' => $conn->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("File upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
