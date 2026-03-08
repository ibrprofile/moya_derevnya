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
        $district_id = intval($_POST['district_id'] ?? 0);
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
        
        if ($district_id <= 0) {
            throw new Exception('Выберите район');
        }
        
        $stmt = $conn->prepare("INSERT INTO settlements (name, district_id, description, year_founded, latitude, longitude, toponym, period, status, custom_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
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
            $custom_status
        ])) {
            $settlement_id = $conn->lastInsertId();
            
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
            throw new Exception('Ошибка при добавлении населенного пункта');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Settlement add error: " . $e->getMessage());
    }
}

try {
    $regions = $conn->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $regions = [];
    $error = 'Ошибка загрузки регионов: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить населенный пункт</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <h1>Добавить населенный пункт</h1>
            
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
                            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Регион: *</label>
                            <select id="region-select" required>
                                <option value="">Выберите регион</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?php echo $region['id']; ?>"><?php echo htmlspecialchars($region['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Район: *</label>
                            <select name="district_id" id="district-select" required disabled>
                                <option value="">Сначала выберите регион</option>
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
                    
                    <!-- Добавлено поле для текстового статуса -->
                    <div class="form-group">
                        <label>Текст статуса (отображается на странице):</label>
                        <input type="text" name="custom_status" value="<?php echo htmlspecialchars($_POST['custom_status'] ?? ''); ?>" placeholder="Например: Исторический населенный пункт">
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
                    <p style="color: #666; margin-bottom: 15px;">Добавьте собственные разделы с контентом для населенного пункта (история, достопримечательности, культура и т.д.)</p>
                    <div id="sections-container"></div>
                    <button type="button" class="btn btn-secondary" id="add-section-btn">+ Добавить рубрику</button>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Сохранить населенный пункт</button>
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
            language: 'ru'
        };
        
        tinymce.init({
            selector: '#description-editor',
            ...tinymceConfig,
            height: 200
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
        
        document.getElementById('settlement-form').addEventListener('submit', function(e) {
            tinymce.triggerSave();
        });
    </script>
</body>
</html>
