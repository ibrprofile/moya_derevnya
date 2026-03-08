let currentParentId = null;

document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const parentId = params.get('parent_id');

    if (parentId) {
        currentParentId = parseInt(parentId, 10);
        loadBreadcrumb(currentParentId);
    }

    loadCategories(currentParentId);
    loadSettlements(currentParentId);
    setupSearch();
    setupMobileMenu();
});

// Загрузка разделов
function loadCategories(parentId) {
    let url = '/api/categories.php';
    if (parentId) url += '?parent_id=' + parentId;

    fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (result) {
            if (result.success) {
                displayCategories(result.data);
            }
        })
        .catch(function (err) {
            console.error('Ошибка загрузки разделов:', err);
        });
}

// Загрузка НП раздела
function loadSettlements(categoryId) {
    var section = document.getElementById('settlementsSection');
    if (!categoryId) {
        section.style.display = 'none';
        return;
    }

    fetch('/api/category-settlements.php?category_id=' + categoryId)
        .then(function (r) { return r.json(); })
        .then(function (result) {
            if (result.success && result.data.length > 0) {
                displaySettlements(result.data);
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        })
        .catch(function () {
            section.style.display = 'none';
        });
}

// Хлебные крошки
function loadBreadcrumb(categoryId) {
    fetch('/api/category-breadcrumb.php?id=' + categoryId)
        .then(function (r) { return r.json(); })
        .then(function (result) {
            if (result.success) {
                displayBreadcrumb(result.data);
            }
        });
}

function displayBreadcrumb(items) {
    var section = document.getElementById('breadcrumbSection');
    var nav = document.getElementById('breadcrumb');

    if (!items || items.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    var html = '<a href="/catalog.html" class="breadcrumb-link">Каталог</a>';

    items.forEach(function (item, idx) {
        html += '<span class="breadcrumb-sep">/</span>';
        if (idx < items.length - 1) {
            html += '<a href="/catalog.html?parent_id=' + item.id + '" class="breadcrumb-link">' + escapeHtml(item.name) + '</a>';
        } else {
            html += '<span class="breadcrumb-current">' + escapeHtml(item.name) + '</span>';
        }
    });

    nav.innerHTML = html;
}

// Отображение разделов
function displayCategories(categories) {
    var grid = document.getElementById('categoriesGrid');
    var title = document.getElementById('categoriesTitle');
    var section = document.getElementById('categoriesSection');

    if (!categories || categories.length === 0) {
        if (!currentParentId) {
            grid.innerHTML = '<p style="text-align:center; color:#6b7280; grid-column: 1/-1;">Разделы пока не добавлены</p>';
        } else {
            section.style.display = 'none';
        }
        return;
    }

    section.style.display = 'block';
    title.textContent = currentParentId ? 'Подразделы' : 'Разделы';
    grid.innerHTML = '';

    categories.forEach(function (cat, index) {
        var card = document.createElement('div');
        card.className = 'region-card';
        card.style.animationDelay = (index * 0.04) + 's';

        var countText = '';
        if (parseInt(cat.children_count) > 0) {
            countText = '<span class="card-count">' + cat.children_count + ' подр.</span>';
        }
        if (parseInt(cat.settlements_count) > 0) {
            countText += '<span class="card-count card-count-green">' + cat.settlements_count + ' н.п.</span>';
        }

        card.innerHTML =
            '<div class="region-icon"><i class="fas fa-folder"></i></div>' +
            '<div class="region-info">' +
                '<div class="region-name">' + escapeHtml(cat.name) + '</div>' +
                (countText ? '<div class="region-counts">' + countText + '</div>' : '') +
            '</div>';

        card.addEventListener('click', function () {
            window.location.href = '/catalog.html?parent_id=' + cat.id;
        });

        grid.appendChild(card);
    });
}

// Отображение населённых пунктов
function displaySettlements(settlements) {
    var grid = document.getElementById('settlementsGrid');
    grid.innerHTML = '';

    settlements.forEach(function (s, index) {
        var item = document.createElement('a');
        item.className = 'settlement-card';
        item.href = '/settlement.html?id=' + s.id;
        item.style.animationDelay = (index * 0.04) + 's';

        var statusClass = s.status === 'active' ? 'badge-active' : 'badge-inactive';
        var statusText = s.status === 'active' ? 'Действующий' : 'Недействующий';
        if (s.custom_status) statusText = s.custom_status;

        item.innerHTML =
            '<div class="settlement-card-name">' + escapeHtml(s.name) + '</div>' +
            '<div class="settlement-card-meta">' +
                '<span class="settlement-badge ' + statusClass + '">' + statusText + '</span>' +
                (s.year_founded ? '<span class="settlement-year">осн. ' + escapeHtml(s.year_founded) + '</span>' : '') +
            '</div>';

        grid.appendChild(item);
    });
}

// Поиск
function setupSearch() {
    var input = document.getElementById('searchInput');
    var results = document.getElementById('searchResults');
    var timeout;

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        var q = this.value.trim();

        if (q.length < 2) {
            results.classList.remove('active');
            return;
        }

        timeout = setTimeout(function () {
            fetch('/api/search-v2.php?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    if (result.success) {
                        displaySearchResults(result.data);
                    }
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.remove('active');
        }
    });
}

function displaySearchResults(data) {
    var container = document.getElementById('searchResults');
    container.innerHTML = '';

    if (data.length === 0) {
        container.innerHTML = '<div class="search-result-item"><div class="search-result-name">Ничего не найдено</div></div>';
        container.classList.add('active');
        return;
    }

    data.forEach(function (item) {
        var div = document.createElement('div');
        div.className = 'search-result-item';
        div.innerHTML =
            '<div class="search-result-name">' + escapeHtml(item.name) + '</div>' +
            '<div class="search-result-meta">' + escapeHtml(item.meta) + '</div>';

        div.addEventListener('click', function () {
            if (item.type === 'settlement') {
                window.location.href = '/settlement.html?id=' + item.id;
            } else if (item.type === 'category') {
                window.location.href = '/catalog.html?parent_id=' + item.id;
            }
            container.classList.remove('active');
        });

        container.appendChild(div);
    });

    container.classList.add('active');
}

// Мобильное меню
function setupMobileMenu() {
    var btn = document.getElementById('mobileMenuBtn');
    var menu = document.getElementById('mobileMenu');
    if (btn && menu) {
        btn.addEventListener('click', function () {
            menu.classList.toggle('active');
            var icon = btn.querySelector('i');
            if (menu.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
    }
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
