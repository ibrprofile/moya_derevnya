<?php
// sitemap-settlements.php - динамическая карта сайта для населённых пунктов

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Подключаем БД
require_once __DIR__ . '/config/database.php';

// Создаём подключение к БД
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('{"error": "Database connection failed"}');
}

$conn->set_charset("utf8mb4");

// XML header
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// Получаем все населённые пункты для sitemap
$query = "SELECT id, name, updated_at FROM settlements ORDER BY id ASC LIMIT 50000";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = intval($row['id']);
        $name = htmlspecialchars($row['name'], ENT_XML1, 'UTF-8');
        $updated = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
        
        // Определяем priority в зависимости от популярности
        $priority = '0.7';
        
        echo "    <url>\n";
        echo "        <loc>https://votmoyaderevnya.ru/settlement.html?id=" . $id . "</loc>\n";
        echo "        <lastmod>" . $updated . "</lastmod>\n";
        echo "        <changefreq>monthly</changefreq>\n";
        echo "        <priority>" . $priority . "</priority>\n";
        echo "    </url>\n";
    }
}

echo '</urlset>';

$conn->close();
?>
