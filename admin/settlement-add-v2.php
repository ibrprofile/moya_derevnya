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

        if (empty($name)) {
            throw new Exception('Название обязательно');
        }

        if ($category_id <= 0) {
            throw new Exception('Выберите раздел');
        }

        $stmt = $conn->prepare("INSERT INTO settlements_v2 (category_id, name, status, custom_status, year_founded, latitude, longitude, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $category_id,
            $name,
            $status,
            $custom_status ?: null,
            $year_founded ?: null,
            $latitude ?: null,
            $longitude ?: null,
            $description ?: null
        ]);

        $settlement_id = $conn->lastInsertId();

        // Сохранение рубрик
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

// Загрузка дерева разделов
$allCategories = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY sort_order, name")->fetchAll();

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
    <title>Добавить населённый пункт</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Добавить населённый пункт</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="settlement-form">
                <div class="card">
                    <h2>Основная информация</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название: *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="Название населённого пункта">
                        </div>
                        
                        <div class="form-group">
                            <label>Раздел: *</label>
                            <select name="category_id" required>
                                <option value="">Выберите раздел</option>
                                <?php foreach ($categoryTree as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
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
                                <option value="active">Действующий</option>
                                <option value="inactive">Недействующий</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Текст статуса (отображается на странице):</label>
                            <input type="text" name="custom_status" value="<?php echo htmlspecialchars($_POST['custom_status'] ?? ''); ?>" placeholder="Например: Деревня, село">
                        </div>
                        
                        <div class="form-group">
                            <label>Год основания:</label>
                            <input type="text" name="year_founded" value="<?php echo htmlspecialchars($_POST['year_founded'] ?? ''); ?>" placeholder="1676">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Широта:</label>
                            <input type="text" name="latitude" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>" placeholder="55.7558">
                        </div>
                        
                        <div class="form-group">
                            <label>Долгота:</label>
                            <input type="text" name="longitude" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>" placeholder="37.6173">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" rows="3" placeholder="Краткое описание"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="card">
                    <h2>Рубрики</h2>
                    <p style="color: #7f8c8d; margin-bottom: 15px; font-size: 14px;">Добавьте разделы с контентом: история, достопримечательности, факты и т.д.</p>
                    <div id="rubrics-container"></div>
                    <button type="button" class="btn btn-secondary" id="add-rubric-btn">+ Добавить рубрику</button>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Сохранить</button>
                    <a href="settlements-v2.php" class="btn btn-secondary">Отмена</a>
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
            language: 'ru'
        };
        
        let rubricCounter = 0;
        
        function addRubric(title, content) {
            const container = document.getElementById('rubrics-container');
            const rid = 'rubric_' + Date.now() + '_' + rubricCounter;
            const textareaId = 'rubric_content_' + rubricCounter;
            rubricCounter++;
            
            const div = document.createElement('div');
            div.className = 'section-item';
            div.innerHTML = `
                <div class="section-header">
                    <input type="text" name="rubrics[${rid}][title]" value="${title || ''}" placeholder="Название рубрики" style="flex: 1; margin-right: 10px;">
                    <button type="button" class="btn btn-danger btn-small remove-rubric">Удалить</button>
                </div>
                <textarea name="rubrics[${rid}][content]" id="${textareaId}">${content || ''}</textarea>
            `;
            container.appendChild(div);
            
            tinymce.init({
                selector: '#' + textareaId,
                ...tinymceConfig
            });
        }
        
        document.getElementById('add-rubric-btn').addEventListener('click', function() {
            addRubric('', '');
        });
        
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
