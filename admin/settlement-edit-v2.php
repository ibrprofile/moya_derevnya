<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();
$admin = getCurrentAdmin();
$conn = getDBConnection();

$settlement_id = intval($_GET['id'] ?? 0);
$success = isset($_GET['success']) ? 'Сохранено' : '';
$error = '';

if ($settlement_id <= 0) {
    header('Location: settlements-v2.php');
    exit;
}

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $custom_status = trim($_POST['custom_status'] ?? '');
        $year_founded = trim($_POST['year_founded'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) throw new Exception('Название обязательно');
        if ($category_id <= 0) throw new Exception('Выберите раздел');

        $stmt = $conn->prepare("UPDATE settlements_v2 SET category_id=?, name=?, status=?, custom_status=?, year_founded=?, latitude=?, longitude=?, description=? WHERE id=?");
        $stmt->execute([
            $category_id, $name, $status, $custom_status ?: null,
            $year_founded ?: null, $latitude ?: null, $longitude ?: null,
            $description ?: null, $settlement_id
        ]);

        // Пересохранить рубрики
        $conn->prepare("DELETE FROM settlement_rubrics WHERE settlement_id = ?")->execute([$settlement_id]);
        if (isset($_POST['rubrics']) && is_array($_POST['rubrics'])) {
            $order = 0;
            foreach ($_POST['rubrics'] as $rubric) {
                $r_title = trim($rubric['title'] ?? '');
                $r_content = $rubric['content'] ?? '';
                if (!empty($r_title)) {
                    $stmt = $conn->prepare("INSERT INTO settlement_rubrics (settlement_id, title, content, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$settlement_id, $r_title, $r_content, $order]);
                    $order++;
                }
            }
        }

        header("Location: settlement-edit-v2.php?id=$settlement_id&success=1");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Загрузка данных
try {
    $stmt = $conn->prepare("SELECT * FROM settlements_v2 WHERE id = ?");
    $stmt->execute([$settlement_id]);
    $settlement = $stmt->fetch();

    if (!$settlement) {
        header('Location: settlements-v2.php');
        exit;
    }

    // Рубрики
    $stmt = $conn->prepare("SELECT * FROM settlement_rubrics WHERE settlement_id = ? ORDER BY sort_order, id");
    $stmt->execute([$settlement_id]);
    $rubrics = $stmt->fetchAll();

    // Галерея
    $stmt = $conn->prepare("SELECT * FROM settlement_photos WHERE settlement_id = ? ORDER BY sort_order, id");
    $stmt->execute([$settlement_id]);
    $gallery = $stmt->fetchAll();

    // Дерево разделов
    $allCategories = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY sort_order, name")->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}

function buildTree($categories, $parentId = null, $prefix = '') {
    $result = [];
    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parentId) {
            $result[] = ['id' => $cat['id'], 'name' => $prefix . $cat['name']];
            $result = array_merge($result, buildTree($categories, $cat['id'], $prefix . '— '));
        }
    }
    return $result;
}

$categoryTree = buildTree($allCategories);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать: <?php echo htmlspecialchars($settlement['name']); ?></title>
    <link rel="stylesheet" href="css/admin-style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Редактировать: <?php echo htmlspecialchars($settlement['name']); ?></h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="settlement-form">
                <div class="card">
                    <h2>Основная информация</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название: *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($settlement['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Раздел: *</label>
                            <select name="category_id" required>
                                <option value="">Выберите раздел</option>
                                <?php foreach ($categoryTree as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $settlement['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Статус:</label>
                            <select name="status">
                                <option value="active" <?php echo $settlement['status'] === 'active' ? 'selected' : ''; ?>>Действующий</option>
                                <option value="inactive" <?php echo $settlement['status'] === 'inactive' ? 'selected' : ''; ?>>Недействующий</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Текст статуса:</label>
                            <input type="text" name="custom_status" value="<?php echo htmlspecialchars($settlement['custom_status'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Год основания:</label>
                            <input type="text" name="year_founded" value="<?php echo htmlspecialchars($settlement['year_founded'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Широта:</label>
                            <input type="text" name="latitude" value="<?php echo htmlspecialchars($settlement['latitude'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Долгота:</label>
                            <input type="text" name="longitude" value="<?php echo htmlspecialchars($settlement['longitude'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($settlement['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Галерея -->
                <div class="card">
                    <h2>Галерея</h2>
                    <div id="gallery-container" class="gallery-grid">
                        <?php foreach ($gallery as $photo): ?>
                        <div class="gallery-item" data-id="<?php echo $photo['id']; ?>">
                            <img src="../<?php echo htmlspecialchars($photo['image_path']); ?>" alt="<?php echo htmlspecialchars($photo['caption'] ?? ''); ?>">
                            <input type="text" value="<?php echo htmlspecialchars($photo['caption'] ?? ''); ?>" placeholder="Описание" readonly>
                            <button type="button" class="btn btn-danger btn-small remove-gallery">Удалить</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('gallery-upload').click()">+ Загрузить фото</button>
                    <input type="file" id="gallery-upload" multiple accept="image/*" style="display: none;">
                </div>
                
                <!-- Рубрики -->
                <div class="card">
                    <h2>Рубрики</h2>
                    <p style="color: #7f8c8d; margin-bottom: 15px; font-size: 14px;">Разделы со своим содержимым (история, факты, культура и т.д.)</p>
                    <div id="rubrics-container">
                        <?php foreach ($rubrics as $idx => $rubric): ?>
                        <div class="section-item" data-rubric-id="<?php echo $rubric['id']; ?>">
                            <div class="section-header">
                                <input type="text" name="rubrics[existing_<?php echo $rubric['id']; ?>][title]" value="<?php echo htmlspecialchars($rubric['title']); ?>" placeholder="Название рубрики" style="flex: 1; margin-right: 10px;">
                                <button type="button" class="btn btn-danger btn-small remove-rubric">Удалить</button>
                            </div>
                            <textarea name="rubrics[existing_<?php echo $rubric['id']; ?>][content]" class="tinymce-editor" id="rubric_existing_<?php echo $rubric['id']; ?>"><?php echo htmlspecialchars($rubric['content']); ?></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add-rubric-btn">+ Добавить рубрику</button>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Сохранить изменения</button>
                    <a href="settlements-v2.php" class="btn btn-secondary">Назад к списку</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        const tinymceConfig = {
            height: 350,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link media | table | code | fullscreen',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
            language: 'ru',
            images_upload_handler: function (blobInfo, success, failure) {
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                fetch('upload-file.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) success('../' + data.url);
                        else failure('Ошибка: ' + (data.error || ''));
                    })
                    .catch(() => failure('Ошибка загрузки'));
            }
        };
        
        // Инициализация TinyMCE для существующих редакторов
        tinymce.init({ selector: '.tinymce-editor', ...tinymceConfig });
        
        // Загрузка фото в галерею
        document.getElementById('gallery-upload').addEventListener('change', function(e) {
            const files = e.target.files;
            if (!files.length) return;
            
            const formData = new FormData();
            for (let f of files) formData.append('files[]', f);
            formData.append('settlement_id', <?php echo $settlement_id; ?>);
            
            fetch('gallery-upload-v2.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Ошибка: ' + (data.error || ''));
                })
                .catch(() => alert('Ошибка загрузки'));
        });
        
        // Удаление фото
        document.getElementById('gallery-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-gallery')) {
                const item = e.target.closest('.gallery-item');
                const photoId = item.dataset.id;
                if (confirm('Удалить фото?')) {
                    fetch('gallery-delete-v2.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ photo_id: photoId })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) item.remove();
                        else alert('Ошибка удаления');
                    });
                }
            }
        });
        
        // Добавление рубрик
        let rubricCounter = <?php echo count($rubrics); ?>;
        
        document.getElementById('add-rubric-btn').addEventListener('click', function() {
            const container = document.getElementById('rubrics-container');
            const rid = 'new_' + Date.now() + '_' + rubricCounter;
            const textareaId = 'rubric_new_' + rubricCounter;
            rubricCounter++;
            
            const div = document.createElement('div');
            div.className = 'section-item';
            div.innerHTML = `
                <div class="section-header">
                    <input type="text" name="rubrics[${rid}][title]" placeholder="Название рубрики" style="flex: 1; margin-right: 10px;">
                    <button type="button" class="btn btn-danger btn-small remove-rubric">Удалить</button>
                </div>
                <textarea name="rubrics[${rid}][content]" id="${textareaId}"></textarea>
            `;
            container.appendChild(div);
            tinymce.init({ selector: '#' + textareaId, ...tinymceConfig });
        });
        
        // Удаление рубрик
        document.getElementById('rubrics-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-rubric')) {
                const item = e.target.closest('.section-item');
                const textarea = item.querySelector('textarea');
                if (textarea && textarea.id) {
                    const editor = tinymce.get(textarea.id);
                    if (editor) editor.remove();
                }
                item.remove();
            }
        });
        
        document.getElementById('settlement-form').addEventListener('submit', function() {
            tinymce.triggerSave();
        });
    </script>
</body>
</html>
