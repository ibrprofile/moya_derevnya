<?php
// Конфигурация подключения к базе данных
define('DB_HOST', 'localhost');
define('DB_USER', 'u3434430_ru_villages');
define('DB_PASS', 'ru_villages');
define('DB_NAME', 'u3434430_ru_villages');

// Создание подключения
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        die("Ошибка подключения к базе данных: " . $e->getMessage());
    }
}
?>
