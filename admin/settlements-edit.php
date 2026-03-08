<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
checkAdminAuth();
$admin = getCurrentAdmin();
$conn = getDBConnection();

$settlement_id = intval($_GET['id'] ?? 0);
$success = isset($_GET['success']) ? 'Населенный пункт успешно сохранен' : '';
$error = '';

if ($settlement_id <= 0) {
    header('Location: settlements.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = getPostValue('name');
        $district_id = intval($_POST['district_id'] ?? 0);
        $description = getPostValue('description');
        $year_founded = getPostValue('year_founded');
        $latitude = getPostValue('latitude');
        $longitude = getPostValue('longitude');
        $toponym = getPostValue('toponym');
        $period = getPostValue('period');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            throw new Exception('Название обязательно для заполнения');
        }
        
        if ($district_id <= 0) {
            throw new Exception('Выберите район');
        }
        
        $stmt = $conn->prepare("UPDATE settlements SET name=?, district_id=?, description=?, year_founded=?, latitude=?, longitude=?, toponym=?, period=?, status=? WHERE id=?");
        
        if ($stmt->execute([
            $name,
            $district_id,
            $description,
            $year_founded ?: null,
            $latitude ?: null,
            $longitude ?: null,
            $toponym,
            $period,
            $status,
            $settlement_id
        ])) {
            $conn->prepare("DELETE FROM settlement_sections WHERE settlement_id = ?")->execute([$settlement_id]);
            
            if (isset($_POST['sections']) && is_array($_POST['sections'])) {
                $display_order = 0;
                foreach ($_POST['sections'] as $section) {
                    $section_name = trim($section['name'] ?? '');
                    $section_content = $section['content'] ?? '';
                    
                    if (!empty($section_name)) {
                        $stmt = $conn->prepare("INSERT INTO settlement_sections (settlement_id, section_name, content, display_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$settlement_id, $section_name, $section_content, $display_order]);
                        $display_order++;
                    }
                }
            }
            
            header("Location: settlements-edit.php?id=$settlement_id&success=1");
            exit;
        } else {
            throw new Exception('Ошибка при обновлении населенного пункта');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Settlement edit error: " . $e->getMessage());
    }
}

try {
    $stmt = $conn->prepare("SELECT s.*, d.region_id FROM settlements s LEFT JOIN districts d ON s.district_id = d.id WHERE s.id = ?");
    $stmt->execute([$settlement_id]);
    $settlement = $stmt->fetch();
    
    if (!$settlement) {
        header('Location: settlements.php');
        exit;
    }
    
    // Получаем рубрики
    $stmt = $conn->prepare("SELECT * FROM settlement_sections WHERE settlement_id = ? ORDER BY display_order");
    $stmt->execute([$settlement_id]);
    $sections = $stmt->fetchAll();
    
    // Получаем галерею
    $stmt = $conn->prepare("SELECT * FROM settlement_gallery WHERE settlement_id = ? ORDER BY display_order");
    $stmt->execute([$settlement_id]);
    $gallery = $stmt->fetchAll();
    
    // Получаем регионы
    $regions = $conn->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();
    
    // Получаем районы выбранного региона
    $stmt = $conn->prepare("SELECT id, name FROM districts WHERE region_id = ? ORDER BY name");
    $stmt->execute([$settlement['region_id']]);
    $districts = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Ошибка загрузки данных: ' . $e->getMessage();
    error_log("Settlement load error: " . $e->getMessage());
}
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
                            <label>Регион: *</label>
                            <select id="region-select" required>
                                <option value="">Выберите регион</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo $region['id']; ?>" <?php echo $region['id'] == $settlement['region_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($region['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Район: *</label>
                            <select name="district_id" id="district-select" required>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>" <?php echo $district['id'] == $settlement['district_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($district['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Краткое описание:</label>
                        <textarea name="description" id="description-editor" rows="5"><?php echo htmlspecialchars($settlement['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Год основания:</label>
                            <input type="text" name="year_founded" value="<?php echo htmlspecialchars($settlement['year_founded']); ?>" placeholder="1850">
                        </div>
                        
                        <div class="form-group">
                            <label>Период:</label>
                            <input type="text" name="period" value="<?php echo htmlspecialchars($settlement['period']); ?>" placeholder="до 1920">
                        </div>
                        
                        <div class="form-group">
                            <label>Статус:</label>
                            <select name="status">
                                <option value="active" <?php echo $settlement['status'] === 'active' ? 'selected' : ''; ?>>Действующий</option>
                                <option value="inactive" <?php echo $settlement['status'] === 'inactive' ? 'selected' : ''; ?>>Недействующий</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Широта:</label>
                            <input type="text" name="latitude" value="<?php echo htmlspecialchars($settlement['latitude']); ?>" placeholder="55.7558">
                        </div>
                        
                        <div class="form-group">
                            <label>Долгота:</label>
                            <input type="text" name="longitude" value="<?php echo htmlspecialchars($settlement['longitude']); ?>" placeholder="37.6173">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Топоним:</label>
                        <textarea name="toponym" rows="3"><?php echo htmlspecialchars($settlement['toponym']); ?></textarea>
                    </div>
                </div>
                
                <div class="card">
                    <h2>Галерея фотографий</h2>
                    <p style="color: #666; margin-bottom: 15px;">Загружайте фотографии для галереи населенного пункта</p>
                    
                    <div id="gallery-container" class="gallery-grid">
                        <?php foreach ($gallery as $photo): ?>
                        <div class="gallery-item" data-id="<?php echo $photo['id']; ?>">
                            <img src="../<?php echo htmlspecialchars($photo['image_path']); ?>" alt="Gallery">
                            <input type="text" value="<?php echo htmlspecialchars($photo['caption']); ?>" placeholder="Описание фото" readonly>
                            <button type="button" class="btn btn-danger btn-small remove-gallery">Удалить</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('gallery-upload').click()">+ Добавить фото в галерею</button>
                    <input type="file" id="gallery-upload" multiple accept="image/*" style="display: none;">
                </div>
                
                <div class="card">
                    <h2>Рубрики (дополнительные разделы)</h2>
                    <p style="color: #666; margin-bottom: 15px;">Управляйте разделами страницы населенного пункта</p>
                    <div id="sections-container">
                        <?php foreach ($sections as $index => $section): ?>
                        <div class="section-item" data-section-id="existing_<?php echo $section['id']; ?>">
                            <div class="section-header">
                                <input type="text" name="sections[existing_<?php echo $section['id']; ?>][name]" value="<?php echo htmlspecialchars($section['section_name']); ?>" placeholder="Название рубрики" style="flex: 1; margin-right: 10px;">
                                <button type="button" class="btn btn-danger btn-small remove-section">Удалить</button>
                            </div>
                            <textarea name="sections[existing_<?php echo $section['id']; ?>][content]" class="tinymce-editor" id="section_<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['content']); ?></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add-section-btn">+ Добавить рубрику</button>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Сохранить изменения</button>
                    <a href="settlements.php" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
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
            language: 'ru',
            images_upload_handler: function (blobInfo, success, failure) {
                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                
                fetch('upload-file.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        success('../' + data.url);
                    } else {
                        failure('Ошибка загрузки: ' + (data.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(error => {
                    console.error('[v0] Upload error:', error);
                    failure('Ошибка загрузки изображения');
                });
            },
            file_picker_callback: function(callback, value, meta) {
                if (meta.filetype === 'file') {
                    const input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', '*/*');
                    
                    input.onchange = function() {
                        const file = this.files[0];
                        const formData = new FormData();
                        formData.append('file', file);
                        
                        fetch('upload-file.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                callback('../' + data.url, { text: file.name });
                            }
                        })
                        .catch(error => {
                            console.error('[v0] File upload error:', error);
                        });
                    };
                    input.click();
                }
            }
        };
        
        tinymce.init({
            selector: '#description-editor',
            ...tinymceConfig,
            height: 200
        });
        
        tinymce.init({
            selector: '.tinymce-editor',
            ...tinymceConfig
        });
        
        document.getElementById('gallery-upload').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length === 0) return;
            
            const formData = new FormData();
            for (let file of files) {
                formData.append('files[]', file);
            }
            formData.append('settlement_id', <?php echo $settlement_id; ?>);
            
            fetch('gallery-upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось загрузить фото'));
                }
            })
            .catch(error => {
                console.error('[v0] Gallery upload error:', error);
                alert('Ошибка загрузки фотографий');
            });
        });
        
        document.getElementById('gallery-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-gallery')) {
                const item = e.target.closest('.gallery-item');
                const photoId = item.dataset.id;
                
                if (confirm('Удалить это фото?')) {
                    fetch('gallery-delete.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({photo_id: photoId})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            item.remove();
                        } else {
                            alert('Ошибка удаления');
                        }
                    })
                    .catch(error => {
                        console.error('[v0] Delete error:', error);
                        alert('Ошибка удаления фото');
                    });
                }
            }
        });
        
        document.getElementById('region-select').addEventListener('change', function() {
            const regionId = this.value;
            const districtSelect = document.getElementById('district-select');
            
            if (!regionId) {
                districtSelect.innerHTML = '<option value="">Сначала выберите регион</option>';
                districtSelect.disabled = true;
                return;
            }
            
            districtSelect.disabled = true;
            districtSelect.innerHTML = '<option value="">Загрузка...</option>';
            
            fetch(`../api/districts-by-region.php?region_id=${regionId}`)
                .then(response => response.json())
                .then(data => {
                    districtSelect.innerHTML = '';
                    
                    if (data.data && data.data.length > 0) {
                        districtSelect.innerHTML = '<option value="">Выберите район</option>';
                        data.data.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.name;
                            districtSelect.appendChild(option);
                        });
                        districtSelect.disabled = false;
                    } else {
                        districtSelect.innerHTML = '<option value="">Районы не найдены</option>';
                    }
                })
                .catch(error => {
                    console.error('[v0] District load error:', error);
                    districtSelect.innerHTML = '<option value="">Ошибка загрузки районов</option>';
                });
        });
        
        let sectionCounter = <?php echo count($sections); ?>;
        
        document.getElementById('add-section-btn').addEventListener('click', function() {
            const container = document.getElementById('sections-container');
            const sectionId = 'new_' + Date.now() + '_' + sectionCounter;
            const textareaId = 'section_new_' + sectionCounter;
            sectionCounter++;
            
            const sectionDiv = document.createElement('div');
            sectionDiv.className = 'section-item';
            sectionDiv.setAttribute('data-section-id', sectionId);
            sectionDiv.innerHTML = `
                <div class="section-header">
                    <input type="text" name="sections[${sectionId}][name]" placeholder="Название рубрики (например: История, Достопримечательности)" style="flex: 1; margin-right: 10px;">
                    <button type="button" class="btn btn-danger btn-small remove-section">Удалить</button>
                </div>
                <textarea name="sections[${sectionId}][content]" class="tinymce-editor" id="${textareaId}"></textarea>
            `;
            container.appendChild(sectionDiv);
            
            tinymce.init({
                selector: `#${textareaId}`,
                ...tinymceConfig
            });
        });
        
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
        
        document.getElementById('settlement-form').addEventListener('submit', function(e) {
            tinymce.triggerSave();
        });
    </script>
</body>
</html>
