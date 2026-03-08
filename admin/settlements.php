<?php
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
    $whereClause = " WHERE s.name LIKE ? OR r.name LIKE ? OR d.name LIKE ?";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam, $searchParam];
}

// Подсчет общего количества
$countQuery = "SELECT COUNT(*) as total FROM settlements s 
               LEFT JOIN districts d ON s.district_id = d.id 
               LEFT JOIN regions r ON d.region_id = r.id" . $whereClause;
$stmt = $conn->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Получаем список населенных пунктов с пагинацией
$query = "SELECT s.*, d.name as district_name, r.name as region_name 
          FROM settlements s 
          LEFT JOIN districts d ON s.district_id = d.id 
          LEFT JOIN regions r ON d.region_id = r.id" . 
          $whereClause . " ORDER BY s.name LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$settlements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Населенные пункты</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Населенные пункты</h1>
            
            <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <a href="settlements-add.php" class="btn btn-primary">+ Добавить населенный пункт</a>
                
                <!-- Добавлен поиск -->
                <form method="GET" style="flex: 1; max-width: 400px; display: flex; gap: 10px;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Поиск по названию..." style="flex: 1;">
                    <button type="submit" class="btn btn-secondary">Найти</button>
                    <?php if (!empty($search)): ?>
                        <a href="settlements.php" class="btn btn-secondary">Сбросить</a>
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
                            <th>Регион</th>
                            <th>Район</th>
                            <th>Год основания</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($settlements)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                                <?php echo empty($search) ? 'Нет населенных пунктов' : 'Ничего не найдено'; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($settlements as $settlement): ?>
                            <tr>
                                <td><?php echo $settlement['id']; ?></td>
                                <td><?php echo htmlspecialchars($settlement['name']); ?></td>
                                <td><?php echo htmlspecialchars($settlement['region_name']); ?></td>
                                <td><?php echo htmlspecialchars($settlement['district_name']); ?></td>
                                <td><?php echo htmlspecialchars($settlement['year_founded']); ?></td>
                                <td>
                                    <span class="badge <?php echo $settlement['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo $settlement['status'] === 'active' ? 'Действующий' : 'Недействующий'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="settlements-edit.php?id=<?php echo $settlement['id']; ?>" class="btn btn-primary btn-small">Редактировать</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Добавлена пагинация -->
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
