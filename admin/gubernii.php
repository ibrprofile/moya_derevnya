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
                throw new Exception('Введите название губернии');
            }
            
            $stmt = $conn->prepare("INSERT INTO gubernii (name, icon) VALUES (?, ?)");
            if ($stmt->execute([$name, $icon])) {
                $success = 'Губерния успешно добавлена';
            } else {
                throw new Exception('Ошибка при добавлении губернии');
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Неверный ID губернии');
            }
            
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM uezds WHERE guberniya_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                throw new Exception('Невозможно удалить губернию, у неё есть уезды');
            }
            
            $stmt = $conn->prepare("DELETE FROM gubernii WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = 'Губерния успешно удалена';
            } else {
                throw new Exception('Ошибка при удалении губернии');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Guberniya operation error: " . $e->getMessage());
    }
}

// Получаем список губерний
$gubernii = $conn->query("SELECT g.*, COUNT(u.id) as uezds_count FROM gubernii g LEFT JOIN uezds u ON g.id = u.guberniya_id GROUP BY g.id ORDER BY g.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление губерниями</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Управление губерниями</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Добавить новую губернию</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название губернии: *</label>
                            <input type="text" name="name" required placeholder="Например: Тульская губерния">
                        </div>
                        <div class="form-group">
                            <label>Иконка (необязательно):</label>
                            <input type="text" name="icon" placeholder="Путь к иконке">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить губернию</button>
                </form>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Уездов</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gubernii)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
                                Нет губерний. Добавьте первую губернию.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($gubernii as $guberniya): ?>
                            <tr>
                                <td><?php echo $guberniya['id']; ?></td>
                                <td><?php echo htmlspecialchars($guberniya['name']); ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $guberniya['uezds_count']; ?></span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($guberniya['created_at'])); ?></td>
                                <td class="actions">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить губернию <?php echo htmlspecialchars($guberniya['name']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $guberniya['id']; ?>">
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
