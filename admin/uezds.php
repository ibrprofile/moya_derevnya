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
            $guberniya_id = intval($_POST['guberniya_id'] ?? 0);
            
            if (empty($name)) {
                throw new Exception('Введите название уезда');
            }
            
            if ($guberniya_id <= 0) {
                throw new Exception('Выберите губернию');
            }
            
            $stmt = $conn->prepare("INSERT INTO uezds (name, guberniya_id) VALUES (?, ?)");
            if ($stmt->execute([$name, $guberniya_id])) {
                $success = 'Уезд успешно добавлен';
            } else {
                throw new Exception('Ошибка при добавлении уезда');
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Неверный ID уезда');
            }
            
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM stans WHERE uezd_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                throw new Exception('Невозможно удалить уезд, у него есть станы');
            }
            
            $stmt = $conn->prepare("DELETE FROM uezds WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = 'Уезд успешно удален';
            } else {
                throw new Exception('Ошибка при удалении уезда');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Uezd operation error: " . $e->getMessage());
    }
}

// Получаем список уездов
$uezds = $conn->query("
    SELECT u.*, g.name as guberniya_name, COUNT(s.id) as stans_count 
    FROM uezds u 
    LEFT JOIN gubernii g ON u.guberniya_id = g.id 
    LEFT JOIN stans s ON u.id = s.uezd_id 
    GROUP BY u.id 
    ORDER BY g.name, u.name
")->fetchAll();

// Получаем список губерний для формы
$gubernii = $conn->query("SELECT * FROM gubernii ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление уездами</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Управление уездами</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Добавить новый уезд</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Губерния: *</label>
                            <select name="guberniya_id" required>
                                <option value="">Выберите губернию</option>
                                <?php foreach ($gubernii as $guberniya): ?>
                                    <option value="<?php echo $guberniya['id']; ?>"><?php echo htmlspecialchars($guberniya['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Название уезда: *</label>
                            <input type="text" name="name" required placeholder="Например: Новосильский уезд">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить уезд</button>
                </form>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Губерния</th>
                            <th>Станов</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($uezds)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #999;">
                                Нет уездов. Добавьте первый уезд.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($uezds as $uezd): ?>
                            <tr>
                                <td><?php echo $uezd['id']; ?></td>
                                <td><?php echo htmlspecialchars($uezd['name']); ?></td>
                                <td><?php echo htmlspecialchars($uezd['guberniya_name']); ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $uezd['stans_count']; ?></span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($uezd['created_at'])); ?></td>
                                <td class="actions">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить уезд <?php echo htmlspecialchars($uezd['name']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $uezd['id']; ?>">
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
