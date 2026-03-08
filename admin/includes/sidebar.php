<aside class="sidebar">
    <nav>
        <ul>
            <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Главная</a></li>
            
            <li style="margin-top: 10px; padding: 10px 20px;">
                <strong style="color: #bdc3c7; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Структура</strong>
            </li>
            <li><a href="categories.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['categories.php', 'category-edit.php']) ? 'active' : ''; ?>">Разделы</a></li>
            <li><a href="settlements-v2.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['settlements-v2.php', 'settlement-add-v2.php', 'settlement-edit-v2.php']) ? 'active' : ''; ?>">Населённые пункты</a></li>
            
            <li style="margin-top: 10px; padding: 10px 20px;">
                <strong style="color: #bdc3c7; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Система</strong>
            </li>
            <li><a href="files.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'files.php' ? 'active' : ''; ?>">Файлы</a></li>
        </ul>
    </nav>
</aside>
