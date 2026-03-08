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
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

$whereClause = ' WHERE 1=1';
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (s.name LIKE ? OR c.name LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($category_filter > 0) {
    $whereClause .= " AND s.category_id = ?";
    $params[] = $category_filter;
}

$countQuery = "SELECT COUNT(*) as total FROM settlements_v2 s 
               JOIN categories c ON s.category_id = c.id" . $whereClause;
$stmt = $conn->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

$query = "SELECT s.*, c.name as category_name 
          FROM settlements_v2 s 
          JOIN categories c ON s.category_id = c.id" . 
          $whereClause . " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$settlements = $stmt->fetchAll();

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    if ($del_id > 0) {
        $conn->prepare("DELETE FROM settlements_v2 WHERE id = ?")->execute([$del_id]);
        header("Location: settlements-v2.php?deleted=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Населённые пункты</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Населённые пункты</h1>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Населённый пункт удалён</div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <a href="settlement-add-v2.php" class="btn btn-success">+ Добавить</a>
                
                <form method="GET" style="flex: 1; min-width: 200px; max-width: 400px; display: flex; gap: 10px;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Поиск..." style="flex: 1;">
                    <button type="submit" class="btn btn-secondary">Найти</button>
                    <?php if (!empty($search) || $category_filter): ?>
                        <a href="settlements-v2.php" class="btn btn-secondary">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <p style="color: #7f8c8d; margin-bottom: 15px; font-size: 14px;">Всего записей: <?php echo $totalRecords; ?></p>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Раздел</th>
                            <th>Год осн.</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($settlements)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                <?php echo empty($search) ? 'Нет населённых пунктов' : 'Ничего не найдено'; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($settlements as $s): ?>
                            <tr>
                                <td><?php echo $s['id']; ?></td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($s['name']); ?></td>
                                <td style="color: #7f8c8d; font-size: 13px;"><?php echo htmlspecialchars($s['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['year_founded'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $s['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo $s['status'] === 'active' ? 'Действующий' : 'Недействующий'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="settlement-edit-v2.php?id=<?php echo $s['id']; ?>" class="btn btn-primary btn-small">Редакт.</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить населённый пункт?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Удалить</button>
                                    </form>
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
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-small">&larr; Назад</a>
                <?php endif; ?>
                
                <span style="margin: 0 15px; color: #7f8c8d;">
                    Страница <?php echo $page; ?> из <?php echo $totalPages; ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-small">Вперёд &rarr;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
