<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();
$admin = getCurrentAdmin();
$conn = getDBConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            
            if (empty($name)) {
                throw new Exception('Введите название региона');
            }
            
            $stmt = $conn->prepare("INSERT INTO regions (name, icon) VALUES (?, ?)");
            if ($stmt->execute([$name, $icon])) {
                $success = 'Регион успешно добавлен';
            } else {
                throw new Exception('Ошибка при добавлении региона');
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Неверный ID региона');
            }
            
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM districts WHERE region_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                throw new Exception('Невозможно удалить регион, у него есть районы');
            }
            
            $stmt = $conn->prepare("DELETE FROM regions WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = 'Регион успешно удален';
            } else {
                throw new Exception('Ошибка при удалении региона');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Region operation error: " . $e->getMessage());
    }
}

// Получаем список регионов
$regions = $conn->query("SELECT r.*, COUNT(d.id) as districts_count FROM regions r LEFT JOIN districts d ON r.id = d.region_id GROUP BY r.id ORDER BY r.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление регионами</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Управление регионами</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Добавить новый регион</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название региона: *</label>
                            <input type="text" name="name" required placeholder="Например: Тульская область">
                        </div>
                        <div class="form-group">
                            <label>Иконка (необязательно):</label>
                            <input type="text" name="icon" placeholder="Путь к иконке">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить регион</button>
                </form>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Районов</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($regions)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
                                Нет регионов. Добавьте первый регион.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($regions as $region): ?>
                            <tr>
                                <td><?php echo $region['id']; ?></td>
                                <td><?php echo htmlspecialchars($region['name']); ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $region['districts_count']; ?></span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($region['created_at'])); ?></td>
                                <td class="actions">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить регион <?php echo htmlspecialchars($region['name']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $region['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
