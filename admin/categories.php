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

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;

// Добавление раздела
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $add_parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

            if (empty($name)) {
                throw new Exception('Название раздела обязательно');
            }

            $slug = transliterate($name);

            // Проверяем уникальность slug
            $check = $conn->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetchColumn() > 0) {
                $slug .= '-' . time();
            }

            $stmt = $conn->prepare("INSERT INTO categories (parent_id, name, slug, description, sort_order) VALUES (?, ?, ?, ?, ?)");
            $sort = $conn->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories WHERE " . ($add_parent_id ? "parent_id = $add_parent_id" : "parent_id IS NULL"))->fetchColumn();
            $stmt->execute([$add_parent_id, $name, $slug, $description, $sort]);

            $success = 'Раздел добавлен';

        } elseif ($_POST['action'] === 'delete') {
            $delete_id = intval($_POST['delete_id'] ?? 0);
            if ($delete_id > 0) {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$delete_id]);
                $success = 'Раздел удалён';
            }

        } elseif ($_POST['action'] === 'edit') {
            $edit_id = intval($_POST['edit_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($edit_id > 0 && !empty($name)) {
                $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $edit_id]);
                $success = 'Раздел обновлён';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Загрузка текущих разделов
if ($parent_id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order, name");
    $stmt->execute([$parent_id]);

    // Информация о родителе
    $parentStmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $parentStmt->execute([$parent_id]);
    $parentCat = $parentStmt->fetch();
} else {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order, name");
    $stmt->execute();
    $parentCat = null;
}

$categories = $stmt->fetchAll();

// Хлебные крошки
$breadcrumb = [];
if ($parent_id) {
    $current = $parent_id;
    $depth = 0;
    while ($current && $depth < 20) {
        $bc = $conn->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
        $bc->execute([$current]);
        $row = $bc->fetch();
        if (!$row) break;
        array_unshift($breadcrumb, $row);
        $current = $row['parent_id'];
        $depth++;
    }
}

// Загружаем все разделы для выбора родителя
$allCategories = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY name")->fetchAll();

function buildCategoryTree($categories, $parentId = null, $prefix = '') {
    $result = [];
    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parentId) {
            $result[] = ['id' => $cat['id'], 'name' => $prefix . $cat['name']];
            $result = array_merge($result, buildCategoryTree($categories, $cat['id'], $prefix . '-- '));
        }
    }
    return $result;
}

$categoryTree = buildCategoryTree($allCategories);

function transliterate($str) {
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh',
        'з'=>'z','и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts',
        'ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        ' '=>'-', '.'=>'', ','=>''
    ];
    $str = mb_strtolower($str);
    $result = '';
    for ($i = 0; $i < mb_strlen($str); $i++) {
        $char = mb_substr($str, $i, 1);
        $result .= isset($map[$char]) ? $map[$char] : $char;
    }
    return preg_replace('/[^a-z0-9\-]/', '', $result);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление разделами</title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Хлебные крошки -->
            <div style="margin-bottom: 20px; font-size: 14px; color: #7f8c8d;">
                <a href="categories.php" style="color: #3498db; text-decoration: none;">Все разделы</a>
                <?php foreach ($breadcrumb as $bc): ?>
                    <span style="margin: 0 5px;">/</span>
                    <a href="categories.php?parent_id=<?php echo $bc['id']; ?>" style="color: #3498db; text-decoration: none;"><?php echo htmlspecialchars($bc['name']); ?></a>
                <?php endforeach; ?>
            </div>
            
            <h1><?php echo $parentCat ? htmlspecialchars($parentCat['name']) : 'Управление разделами'; ?></h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Форма добавления -->
            <div class="card">
                <h2>Добавить раздел</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Название раздела: *</label>
                            <input type="text" name="name" required placeholder="Например: Тульская область">
                        </div>
                        
                        <div class="form-group">
                            <label>Родительский раздел:</label>
                            <select name="parent_id">
                                <option value="">Корневой раздел</option>
                                <?php foreach ($categoryTree as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($parent_id && $cat['id'] == $parent_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" rows="2" placeholder="Краткое описание раздела"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Добавить</button>
                </form>
            </div>
            
            <!-- Список разделов -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Подразделов</th>
                            <th>Нас. пунктов</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                Разделы не найдены. Добавьте первый раздел выше.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                $childCount = $conn->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
                                $childCount->execute([$cat['id']]);
                                $children = $childCount->fetchColumn();
                                
                                $settlementCount = $conn->prepare("SELECT COUNT(*) FROM settlements_v2 WHERE category_id = ?");
                                $settlementCount->execute([$cat['id']]);
                                $settlements = $settlementCount->fetchColumn();
                                ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td>
                                    <a href="categories.php?parent_id=<?php echo $cat['id']; ?>" style="color: #3498db; text-decoration: none; font-weight: 500;">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </a>
                                </td>
                                <td style="color: #7f8c8d; font-size: 13px;"><?php echo htmlspecialchars(mb_substr($cat['description'] ?? '', 0, 60)); ?></td>
                                <td>
                                    <?php if ($children > 0): ?>
                                        <a href="categories.php?parent_id=<?php echo $cat['id']; ?>" class="badge badge-success"><?php echo $children; ?></a>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $settlements; ?></span>
                                </td>
                                <td class="actions">
                                    <a href="categories.php?parent_id=<?php echo $cat['id']; ?>" class="btn btn-primary btn-small">Открыть</a>
                                    <button type="button" class="btn btn-secondary btn-small" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes(htmlspecialchars($cat['name'])); ?>', '<?php echo addslashes(htmlspecialchars($cat['description'] ?? '')); ?>')">Редакт.</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить раздел и всё содержимое?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="delete_id" value="<?php echo $cat['id']; ?>">
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
    
    <!-- Модальное окно редактирования -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:8px; padding:30px; max-width:500px; width:90%; margin:auto; margin-top:15vh;">
            <h2 style="margin-bottom:20px;">Редактировать раздел</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Название:</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Описание:</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-success">Сохранить</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editCategory(id, name, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>
</html>
