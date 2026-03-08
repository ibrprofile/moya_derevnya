<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode([
        'success' => false,
        'error' => 'Все поля обязательны для заполнения'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error' => 'Некорректный email адрес'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = getDBConnection();

try {
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ваше сообщение успешно отправлено!'
    ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при отправке сообщения'
    ], JSON_UNESCAPED_UNICODE);
}
?>
