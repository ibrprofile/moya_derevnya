<?php
require_once 'config.php';
checkAdminAuth();
$admin = getCurrentAdmin();
$conn = getDBConnection();

$stats = [];

try {
    $stats['categories'] = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (Exception $e) { $stats['categories'] = 0; }

try {
    $stats['settlements'] = $conn->query("SELECT COUNT(*) FROM settlements_v2")->fetchColumn();
} catch (Exception $e) { $stats['settlements'] = 0; }

try {
    $stats['rubrics'] = $conn->query("SELECT COUNT(*) FROM settlement_rubrics")->fetchColumn();
} catch (Exception $e) { $stats['rubrics'] = 0; }

try {
    $stats['photos'] = $conn->query("SELECT COUNT(*) FROM settlement_photos")->fetchColumn();
} catch (Exception $e) { $stats['photos'] = 0; }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Добро пожаловать, <?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?>!</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="font-size:36px; color:#3498db;">&#128193;</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['categories']; ?></h3>
                        <p>Разделов</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="font-size:36px; color:#27ae60;">&#127968;</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['settlements']; ?></h3>
                        <p>Населённых пунктов</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="font-size:36px; color:#e67e22;">&#128196;</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['rubrics']; ?></h3>
                        <p>Рубрик</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="font-size:36px; color:#9b59b6;">&#128247;</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['photos']; ?></h3>
                        <p>Фотографий</p>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h2>Быстрые действия</h2>
                <div class="action-buttons">
                    <a href="categories.php" class="btn btn-primary">Управление разделами</a>
                    <a href="settlement-add-v2.php" class="btn btn-success">Добавить населённый пункт</a>
                    <a href="settlements-v2.php" class="btn btn-secondary">Все населённые пункты</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
