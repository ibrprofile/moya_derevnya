<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();
$admin = getCurrentAdmin();
$conn = getDBConnection();

$success = isset($_GET['success']) ? 'Стан успешно сохранен' : '';
$error = '';
$stan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($stan_id <= 0) {
    header('Location: stans.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = getPostValue('name');
        $uezd_id = intval($_POST['uezd_id'] ?? 0);
        $description = getPostValue('description');
        $year_founded = getPostValue('year_founded');
        $latitude = getPostValue('latitude');
        $longitude = getPostValue('longitude');
        $toponym = getPostValue('toponym');
        $period = getPostValue('period');
        $status = $_POST['status'] ?? 'active';
        $custom_status = getPostValue('custom_status');
        
        if (empty($name)) {
            throw new Exception('Название обязательно для заполнения');
        }
        
        if ($uezd_id <= 0) {
            throw new Exception('Выберите уезд');
        }
        
        $stmt = $conn->prepare("UPDATE stans SET name = ?, uezd_id = ?, description = ?, year_founded = ?, latitude = ?, longitude = ?, toponym = ?, period = ?, status = ?, custom_status = ? WHERE id = ?");
        
        if ($stmt->execute([
            $name, 
            $uezd_id, 
            $description, 
            $year_founded ?: null, 
            $latitude ?: null, 
            $longitude ?: null, 
            $toponym, 
            $period, 
            $status,
            $custom_status,
            $stan_id
        ])) {
            $conn->query("DELETE FROM stan_sections WHERE stan_id = $stan_id");
            
            if (isset($_POST['sections']) && is_array($_POST['sections'])) {
                $display_order = 0;
                foreach ($_POST['sections'] as $section) {
                    $section_name = trim($section['name'] ?? '');
                    $section_content = $section['content'] ?? '';
                    
                    if (!empty($section_name)) {
                        $stmt = $conn->prepare("INSERT INTO stan_sections (stan_id, section_name, content, display_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$stan_id, $section_name, $section_content, $display_order]);
                        $display_order++;
                    }
                }
            }
            
            $success = 'Стан успешно обновлен';
        } else {
            throw new Exception('Ошибка при обновлении стана');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Stan update error: " . $e->getMessage());
    }
}

try {
    $stmt = $conn->prepare("
        SELECT s.*, u.guberniya_id
        FROM stans s
        JOIN uezds u ON s.uezd_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$stan_id]);
    $stan = $stmt->fetch();
    
    if (!$stan) {
        throw new Exception('Стан не найден');
    }
    
    $gubernii = $conn->query("SELECT id, name FROM gubernii ORDER BY name")->fetchAll();
    
    $stmt = $conn->prepare("SELECT id, name FROM uezds WHERE guberniya_id = ? ORDER BY name");
    $stmt->execute([$stan['guberniya_id']]);
    $uezds = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM stan_sections WHERE stan_id = ? ORDER BY display_order");
    $stmt->execute([$stan_id]);
    $sections = $stmt->fetchAll();
    
    $stmt = $conn->prepare("SELECT * FROM stan_gallery WHERE stan_id = ? ORDER BY display_order");
    $stmt->execute([$stan_id]);
    $gallery = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Ошибка загрузки данных: ' . $e->getMessage();
    $stan = null;
}

if (!$stan) {
    header('Location: stans.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать стан - <?php echo htmlspecialchars($stan['name']); ?></title>
    <link rel="stylesheet" href="css/admin-style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Редактировать стан: <?php echo htmlspecialchars($stan['name']); ?></h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="stan-form" enctype="multipart/form-data">
                <div class="card">
                    <h2>Основная информация</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название: *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($stan['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Губерния: *</label>
                            <select id="guberniya-select" required>
                                <?php foreach ($gubernii as $guberniya): ?>
                                    <option value="<?php echo $guberniya['id']; ?>" <?php echo $guberniya['id'] == $stan['guberniya_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($guberniya['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Уезд: *</label>
                            <select name="uezd_id" id="uezd-select" required>
                                <?php foreach ($uezds as $uezd): ?>
                                    <option value="<?php echo $uezd['id']; ?>" <?php echo $uezd['id'] == $stan['uezd_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($uezd['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Краткое описание:</label>
                        <textarea name="description" id="description-editor" rows="5"><?php echo htmlspecialchars($stan['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Год основания:</label>
                            <input type="text" name="year_founded" value="<?php echo htmlspecialchars($stan['year_founded'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Период:</label>
                            <input type="text" name="period" value="<?php echo htmlspecialchars($stan['period'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Статус:</label>
                            <select name="status">
                                <option value="active" <?php echo $stan['status'] === 'active' ? 'selected' : ''; ?>>Действующий</option>
                                <option value="inactive" <?php echo $stan['status'] === 'inactive' ? 'selected' : ''; ?>>Недействующий</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Текст статуса (отображается на странице):</label>
                        <input type="text" name="custom_status" value="<?php echo htmlspecialchars($stan['custom_status'] ?? ''); ?>" placeholder="Например: Исторический стан">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Широта:</label>
                            <input type="text" name="latitude" value="<?php echo htmlspecialchars($stan['latitude'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Долгота:</label>
                            <input type="text" name="longitude" value="<?php echo htmlspecialchars($stan['longitude'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Топоним:</label>
                        <textarea name="toponym" rows="3"><?php echo htmlspecialchars($stan['toponym'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="card">
                    <h2>Рубрики (дополнительные разделы)</h2>
                    <p style="color: #666; margin-bottom: 15px;">Управление разделами с контентом для стана</p>
                    <div id="sections-container">
                        <?php foreach ($sections as $index => $section): ?>
                        <div class="section-item" data-section-id="<?php echo $section['id']; ?>">
                            <div class="section-header">
                                <input type="text" name="sections[<?php echo $section['id']; ?>][name]" value="<?php echo htmlspecialchars($section['section_name']); ?>" placeholder="Название рубрики" style="flex: 1; margin-right: 10px;">
                                <button type="button" class="btn btn-danger btn-small remove-section">Удалить</button>
                            </div>
                            <textarea name="sections[<?php echo $section['id']; ?>][content]" class="tinymce-editor" id="section_content_<?php echo $index; ?>"><?php echo htmlspecialchars($section['content'] ?? ''); ?></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add-section-btn">+ Добавить рубрику</button>
                </div>
                
                <div class="card">
                    <h2>Галерея</h2>
                    <p style="color: #666; margin-bottom: 15px;">Загрузите фотографии для галереи стана</p>
                    
                    <div class="form-group">
                        <label>Загрузить изображения:</label>
                        <input type="file" id="gallery-upload" accept="image/*" multiple>
                        <button type="button" class="btn btn-primary" id="upload-gallery-btn" style="margin-top: 10px;">Загрузить фото</button>
                    </div>
                    
                    <div id="gallery-preview" class="gallery-grid" style="margin-top: 20px;">
                        <?php foreach ($gallery as $photo): ?>
                        <div class="gallery-item" data-photo-id="<?php echo $photo['id']; ?>">
                            <img src="../<?php echo htmlspecialchars($photo['image_path']); ?>" alt="<?php echo htmlspecialchars($photo['caption'] ?? ''); ?>">
                            <button type="button" class="btn btn-danger btn-small delete-photo" data-photo-id="<?php echo $photo['id']; ?>">Удалить</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Сохранить изменения</button>
                    <a href="stans.php" class="btn btn-secondary">Назад к списку</a>
                    <a href="../settlement.html?id=<?php echo $stan_id; ?>&type=stan" class="btn btn-outline" target="_blank">Просмотр на сайте</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        const stanId = <?php echo $stan_id; ?>;
        const tinymceConfig = {
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link media | table | code | fullscreen',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            language: 'ru'
        };
        
        tinymce.init({
            selector: '#description-editor',
            ...tinymceConfig,
            height: 200
        });
        
        document.querySelectorAll('.tinymce-editor').forEach((el, index) => {
            tinymce.init({
                selector: `#${el.id}`,
                ...tinymceConfig
            });
        });
        
        document.getElementById('guberniya-select').addEventListener('change', function() {
            const guberniyaId = this.value;
            const uezdSelect = document.getElementById('uezd-select');
            
            if (!guberniyaId) {
                return;
            }
            
            uezdSelect.disabled = true;
            uezdSelect.innerHTML = '<option value="">Загрузка...</option>';
            
            fetch(`../api/uezds-by-guberniya.php?guberniya_id=${guberniyaId}`)
                .then(response => response.json())
                .then(data => {
                    uezdSelect.innerHTML = '';
                    
                    if (data.data && data.data.length > 0) {
                        uezdSelect.innerHTML = '<option value="">Выберите уезд</option>';
                        data.data.forEach(uezd => {
                            const option = document.createElement('option');
                            option.value = uezd.id;
                            option.textContent = uezd.name;
                            uezdSelect.appendChild(option);
                        });
                        uezdSelect.disabled = false;
                    } else {
                        uezdSelect.innerHTML = '<option value="">Уезды не найдены</option>';
                    }
                });
        });
        
        let sectionCounter = <?php echo count($sections); ?>;
        
        function addSection() {
            const container = document.getElementById('sections-container');
            const sectionId = 'new_' + Date.now() + '_' + sectionCounter++;
            const textareaId = 'section_content_' + sectionCounter;
            
            const sectionDiv = document.createElement('div');
            sectionDiv.className = 'section-item';
            sectionDiv.setAttribute('data-section-id', sectionId);
            sectionDiv.innerHTML = `
                <div class="section-header">
                    <input type="text" name="sections[${sectionId}][name]" placeholder="Название рубрики" style="flex: 1; margin-right: 10px;">
                    <button type="button" class="btn btn-danger btn-small remove-section">Удалить</button>
                </div>
                <textarea name="sections[${sectionId}][content]" class="tinymce-editor" id="${textareaId}"></textarea>
            `;
            container.appendChild(sectionDiv);
            
            tinymce.init({
                selector: `#${textareaId}`,
                ...tinymceConfig
            });
        }
        
        document.getElementById('add-section-btn').addEventListener('click', addSection);
        
        document.getElementById('sections-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-section')) {
                const item = e.target.closest('.section-item');
                const textarea = item.querySelector('textarea');
                
                if (textarea && textarea.id) {
                    const editor = tinymce.get(textarea.id);
                    if (editor) {
                        editor.remove();
                    }
                }
                
                item.remove();
            }
        });
        
        document.getElementById('upload-gallery-btn').addEventListener('click', function() {
            const fileInput = document.getElementById('gallery-upload');
            const files = fileInput.files;
            
            if (files.length === 0) {
                alert('Выберите файлы для загрузки');
                return;
            }
            
            const formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            formData.append('stan_id', stanId);
            
            fetch('gallery-upload.php?type=stan', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Фотографии успешно загружены');
                    location.reload();
                } else {
                    alert('Ошибка загрузки: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('[v0] Upload error:', error);
                alert('Ошибка загрузки файлов');
            });
        });
        
        document.getElementById('gallery-preview').addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-photo')) {
                const photoId = e.target.getAttribute('data-photo-id');
                
                if (confirm('Удалить это фото?')) {
                    fetch('gallery-delete.php?type=stan', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `photo_id=${photoId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            e.target.closest('.gallery-item').remove();
                        } else {
                            alert('Ошибка удаления: ' + (data.error || 'Неизвестная ошибка'));
                        }
                    });
                }
            }
        });
        
        document.getElementById('stan-form').addEventListener('submit', function(e) {
            tinymce.triggerSave();
        });
    </script>
</body>
</html>
