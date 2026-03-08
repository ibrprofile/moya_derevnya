<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();
$admin = getCurrentAdmin();
$conn = getDBConnection();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');

$whereClause = '';
$params = [];
if (!empty($search)) {
    $whereClause = " WHERE s.name LIKE ? OR g.name LIKE ? OR u.name LIKE ?";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam, $searchParam];
}

// Подсчет общего количества
$countQuery = "SELECT COUNT(*) as total FROM stans s 
               LEFT JOIN uezds u ON s.uezd_id = u.id 
               LEFT JOIN gubernii g ON u.guberniya_id = g.id" . $whereClause;
$stmt = $conn->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Получаем список станов с пагинацией
$query = "SELECT s.*, u.name as uezd_name, g.name as guberniya_name 
          FROM stans s 
          LEFT JOIN uezds u ON s.uezd_id = u.id 
          LEFT JOIN gubernii g ON u.guberniya_id = g.id" . 
          $whereClause . " ORDER BY s.name LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$stans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Станы</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Станы</h1>
            
            <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <a href="stans-add.php" class="btn btn-primary">+ Добавить стан</a>
                
                <form method="GET" style="flex: 1; max-width: 400px; display: flex; gap: 10px;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Поиск по названию..." style="flex: 1;">
                    <button type="submit" class="btn btn-secondary">Найти</button>
                    <?php if (!empty($search)): ?>
                        <a href="stans.php" class="btn btn-secondary">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (!empty($search)): ?>
                <p style="color: #666; margin-bottom: 15px;">Найдено записей: <?php echo $totalRecords; ?></p>
            <?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Губерния</th>
                            <th>Уезд</th>
                            <th>Год основания</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stans)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                                <?php echo empty($search) ? 'Нет станов' : 'Ничего не найдено'; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($stans as $stan): ?>
                            <tr>
                                <td><?php echo $stan['id']; ?></td>
                                <td><?php echo htmlspecialchars($stan['name']); ?></td>
                                <td><?php echo htmlspecialchars($stan['guberniya_name']); ?></td>
                                <td><?php echo htmlspecialchars($stan['uezd_name']); ?></td>
                                <td><?php echo htmlspecialchars($stan['year_founded']); ?></td>
                                <td>
                                    <span class="badge <?php echo $stan['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo $stan['status'] === 'active' ? 'Действующий' : 'Недействующий'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="stans-edit.php?id=<?php echo $stan['id']; ?>" class="btn btn-primary btn-small">Редактировать</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-small">← Назад</a>
                <?php endif; ?>
                
                <span style="margin: 0 15px; color: #666;">
                    Страница <?php echo $page; ?> из <?php echo $totalPages; ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-small">Вперед →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
