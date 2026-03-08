<?php
require_once 'config.php';
checkAdminAuth();
$admin = getCurrentAdmin();
$conn = getDBConnection();

// Получаем список файлов
$files = $conn->query("
    SELECT uf.*, au.username 
    FROM uploaded_files uf 
    LEFT JOIN admin_users au ON uf.uploaded_by = au.id 
    ORDER BY uf.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление файлами</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Загруженные файлы</h1>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Оригинальное имя</th>
                            <th>Тип</th>
                            <th>Размер</th>
                            <th>Загружено</th>
                            <th>Дата</th>
                            <th>Путь</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?php echo $file['id']; ?></td>
                            <td><?php echo htmlspecialchars($file['original_name']); ?></td>
                            <td><?php echo htmlspecialchars($file['file_type']); ?></td>
                            <td><?php echo round($file['file_size'] / 1024, 2); ?> KB</td>
                            <td><?php echo htmlspecialchars($file['username']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($file['created_at'])); ?></td>
                            <td><code><?php echo htmlspecialchars($file['file_path']); ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
