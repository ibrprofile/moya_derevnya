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
        
        $stmt = $conn->prepare("INSERT INTO stans (name, uezd_id, description, year_founded, latitude, longitude, toponym, period, status, custom_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
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
            $custom_status
        ])) {
            $stan_id = $conn->lastInsertId();
            
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
            
            header("Location: stans-edit.php?id=$stan_id&success=1");
            exit;
        } else {
            throw new Exception('Ошибка при добавлении стана');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Stan add error: " . $e->getMessage());
    }
}

try {
    $gubernii = $conn->query("SELECT id, name FROM gubernii ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $gubernii = [];
    $error = 'Ошибка загрузки губерний: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить стан</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Добавить стан</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="stan-form">
                <div class="card">
                    <h2>Основная информация</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название: *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Губерния: *</label>
                            <select id="guberniya-select" required>
                                <option value="">Выберите губернию</option>
                                <?php foreach ($gubernii as $guberniya): ?>
                                    <option value="<?php echo $guberniya['id']; ?>"><?php echo htmlspecialchars($guberniya['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Уезд: *</label>
                            <select name="uezd_id" id="uezd-select" required disabled>
                                <option value="">Сначала выберите губернию</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Краткое описание:</label>
                        <textarea name="description" id="description-editor" rows="5"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Год основания:</label>
                            <input type="text" name="year_founded" value="<?php echo htmlspecialchars($_POST['year_founded'] ?? ''); ?>" placeholder="1850">
                        </div>
                        
                        <div class="form-group">
                            <label>Период:</label>
                            <input type="text" name="period" value="<?php echo htmlspecialchars($_POST['period'] ?? ''); ?>" placeholder="до 1920">
                        </div>
                        
                        <div class="form-group">
                            <label>Статус:</label>
                            <select name="status">
                                <option value="active">Действующий</option>
                                <option value="inactive">Недействующий</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Текст статуса (отображается на странице):</label>
                        <input type="text" name="custom_status" value="<?php echo htmlspecialchars($_POST['custom_status'] ?? ''); ?>" placeholder="Например: Исторический стан">
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
                        <label>Топоним:</label>
                        <textarea name="toponym" rows="3"><?php echo htmlspecialchars($_POST['toponym'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="card">
                    <h2>Рубрики (дополнительные разделы)</h2>
                    <p style="color: #666; margin-bottom: 15px;">Добавьте собственные разделы с контентом для стана</p>
                    <div id="sections-container"></div>
                    <button type="button" class="btn btn-secondary" id="add-section-btn">+ Добавить рубрику</button>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Сохранить стан</button>
                    <a href="stans.php" class="btn btn-secondary">Отмена</a>
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
            language: 'ru'
        };
        
        tinymce.init({
            selector: '#description-editor',
            ...tinymceConfig,
            height: 200
        });
        
        document.getElementById('guberniya-select').addEventListener('change', function() {
            const guberniyaId = this.value;
            const uezdSelect = document.getElementById('uezd-select');
            
            if (!guberniyaId) {
                uezdSelect.innerHTML = '<option value="">Сначала выберите губернию</option>';
                uezdSelect.disabled = true;
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
                })
                .catch(error => {
                    console.error('[v0] Uezd load error:', error);
                    uezdSelect.innerHTML = '<option value="">Ошибка загрузки уездов</option>';
                });
        });
        
        let sectionCounter = 0;
        
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
        
        document.getElementById('stan-form').addEventListener('submit', function(e) {
            tinymce.triggerSave();
        });
    </script>
</body>
</html>
