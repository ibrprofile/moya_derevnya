var settlementData = null;
var yandexMap = null;

document.addEventListener('DOMContentLoaded', function () {
    var params = new URLSearchParams(window.location.search);
    var id = params.get('id');

    if (id) {
        loadSettlement(parseInt(id, 10));
    } else {
        window.location.href = '/catalog.html';
    }

    setupMobileMenu();
});

function loadSettlement(id) {
    fetch('/api/settlement-details-v2.php?id=' + id)
        .then(function (r) { return r.json(); })
        .then(function (result) {
            if (result.success) {
                settlementData = result.data;
                renderSettlement();
            } else {
                alert('Ошибка загрузки данных');
                window.location.href = '/catalog.html';
            }
        })
        .catch(function (err) {
            console.error('Ошибка:', err);
            alert('Ошибка загрузки данных');
        });
}

function renderSettlement() {
    var s = settlementData.settlement;
    var breadcrumb = settlementData.breadcrumb;
    var rubrics = settlementData.rubrics;
    var gallery = settlementData.gallery;
    var population = settlementData.population;

    // SEO: Обновляем Meta Tags динамически
    updateSEOMetaTags(s, breadcrumb);

    // Заголовок
    document.title = s.name + ' - Малочисленные населенные пункты РФ';
    document.getElementById('settlementName').textContent = s.name;

    // Бейдж статуса
    var badge = document.getElementById('settlementStatusBadge');
    if (s.status === 'active') {
        badge.textContent = 'Действующий';
        badge.className = 'settlement-status status-active';
    } else {
        badge.textContent = 'Недействующий';
        badge.className = 'settlement-status status-inactive';
    }

    // Инфо-карточка
    document.getElementById('settlementStatus').textContent = s.custom_status || (s.status === 'active' ? 'Действующий' : 'Недействующий');
    document.getElementById('settlementYear').textContent = s.year_founded || 'Неизвестно';

    // Раздел
    var catLink = document.getElementById('settlementCategory');
    if (breadcrumb && breadcrumb.length > 0) {
        var crumbHtml = '';
        breadcrumb.forEach(function (c, i) {
            if (i > 0) crumbHtml += ' / ';
            crumbHtml += '<a href="/catalog.html?parent_id=' + c.id + '" class="info-link">' + escapeHtml(c.name) + '</a>';
        });
        catLink.innerHTML = crumbHtml;
    } else {
        catLink.textContent = s.category_name || 'Не указан';
    }

    // Хлебные крошки (верхние)
    renderBreadcrumb(breadcrumb, s.name);

    // Рубрики
    renderRubrics(rubrics);

    // Галерея
    renderGallery(gallery);

    // Население
    renderPopulation(population);

    // Карта
    if (s.latitude && s.longitude) {
        initMap(parseFloat(s.latitude), parseFloat(s.longitude), s.name);
    } else {
        document.getElementById('yandexMap').innerHTML = '<p style="padding:20px; text-align:center; color:#6b7280;">Координаты не указаны</p>';
    }
}

// SEO функция для обновления метатегов
function updateSEOMetaTags(settlement, breadcrumb) {
    var settlementName = settlement.name || 'Населённый пункт';
    var status = settlement.status === 'active' ? 'действующий' : 'недействующий';
    var yearInfo = settlement.year_founded ? ' (основан в ' + settlement.year_founded + ' г.)' : '';
    var breadcrumbPath = breadcrumb && breadcrumb.length > 0 ? breadcrumb.map(function(c) { return c.name; }).join(', ') : '';
    
    // Meta Description
    var description = settlementName + ' - ' + status + ' населённый пункт России' + yearInfo + '. Информация, история, карта, архивные данные и фотографии.';
    var metaDescription = document.querySelector('meta[name="description"]');
    if (metaDescription) metaDescription.setAttribute('content', description);
    
    // Meta Keywords
    var keywords = settlementName + ', ' + settlementName.toLowerCase() + ', население, деревня, село, история, ' + status + ', архив, карта';
    if (breadcrumbPath) keywords += ', ' + breadcrumbPath;
    var metaKeywords = document.querySelector('meta[name="keywords"]');
    if (!metaKeywords) {
        metaKeywords = document.createElement('meta');
        metaKeywords.setAttribute('name', 'keywords');
        document.head.appendChild(metaKeywords);
    }
    metaKeywords.setAttribute('content', keywords);
    
    // Canonical URL
    var canonicalUrl = 'https://votmoyaderevnya.ru/settlement.html?id=' + settlement.id;
    var canonicalTag = document.getElementById('canonicalTag');
    canonicalTag.setAttribute('href', canonicalUrl);
    
    // hreflang
    var hreflangTag = document.getElementById('hreflangTag');
    hreflangTag.setAttribute('href', canonicalUrl);
    
    // Open Graph Tags
    document.getElementById('ogUrl').setAttribute('content', canonicalUrl);
    document.getElementById('ogTitle').setAttribute('content', settlementName + ' — информация о населённом пункте');
    document.getElementById('ogDescription').setAttribute('content', description);
    
    // Twitter Tags
    document.getElementById('twitterTitle').setAttribute('content', settlementName);
    document.getElementById('twitterDescription').setAttribute('content', description.substring(0, 160));
    
    // Schema.org structured data
    var schemaData = {
        "@context": "https://schema.org",
        "@type": "Place",
        "name": settlementName,
        "description": description,
        "url": canonicalUrl,
        "status": status,
        "inCountry": "RU"
    };
    
    if (settlement.latitude && settlement.longitude) {
        schemaData.geo = {
            "@type": "GeoCoordinates",
            "latitude": settlement.latitude,
            "longitude": settlement.longitude
        };
    }
    
    if (breadcrumb && breadcrumb.length > 0) {
        schemaData.address = {
            "@type": "PostalAddress",
            "addressCountry": "RU",
            "areaServed": breadcrumb.map(function(b) { return b.name; })
        };
    }
    
    var schemaScript = document.getElementById('settlementSchema');
    schemaScript.textContent = JSON.stringify(schemaData, null, 2);
}

function renderBreadcrumb(items, settlementName) {
    var section = document.getElementById('breadcrumbSection');
    var nav = document.getElementById('breadcrumb');

    var html = '<a href="/" class="breadcrumb-link">Главная</a>';
    html += '<span class="breadcrumb-sep">/</span>';
    html += '<a href="/catalog.html" class="breadcrumb-link">Каталог</a>';

    if (items && items.length > 0) {
        items.forEach(function (c) {
            html += '<span class="breadcrumb-sep">/</span>';
            html += '<a href="/catalog.html?parent_id=' + c.id + '" class="breadcrumb-link">' + escapeHtml(c.name) + '</a>';
        });
    }

    html += '<span class="breadcrumb-sep">/</span>';
    html += '<span class="breadcrumb-current">' + escapeHtml(settlementName) + '</span>';

    nav.innerHTML = html;
    section.style.display = 'block';
}

function renderRubrics(rubrics) {
    var container = document.getElementById('rubricsButtons');
    container.innerHTML = '';

    if (!rubrics || rubrics.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'grid';

    rubrics.forEach(function (rubric) {
        var btn = document.createElement('button');
        btn.className = 'btn btn-primary rubric-btn';
        btn.textContent = rubric.title;

        btn.addEventListener('click', function () {
            showRubricModal(rubric.title, rubric.content);
        });

        container.appendChild(btn);
    });
}

function showRubricModal(title, content) {
    document.getElementById('rubricModalTitle').textContent = title;
    document.getElementById('rubricModalContent').innerHTML = content || '<p>Содержимое отсутствует</p>';
    openModal('rubricModal');
}

function renderGallery(photos) {
    var wrapper = document.getElementById('galleryWrapper');
    var grid = document.getElementById('galleryGrid');

    if (!photos || photos.length === 0) {
        wrapper.style.display = 'none';
        return;
    }

    wrapper.style.display = 'block';
    grid.innerHTML = '';

    photos.forEach(function (photo, index) {
        var item = document.createElement('div');
        item.className = 'gallery-item';
        item.style.animationDelay = (index * 0.08) + 's';

        var src = '/' + photo.image_path;
        var alt = photo.caption || 'Фото';

        item.innerHTML = '<img src="' + src + '" alt="' + escapeHtml(alt) + '" loading="lazy" onerror="this.parentElement.style.display=\'none\'">';
        grid.appendChild(item);
    });
}

function formatPopulation(n) {
    n = parseInt(n, 10);
    if (isNaN(n)) return n;
    if (n >= 1000000) return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
    return n;
}

function renderPopulation(data) {
    var wrapper = document.getElementById('populationWrapper');
    var chart = document.getElementById('populationChart');

    if (!data || data.length === 0) {
        wrapper.style.display = 'none';
        return;
    }

    wrapper.style.display = 'block';
    var maxPop = 0;
    data.forEach(function (d) {
        var v = parseInt(d.population, 10);
        if (v > maxPop) maxPop = v;
    });

    var barsHtml = '';
    data.forEach(function (item, index) {
        var val = parseInt(item.population, 10);
        var height = Math.max((val / maxPop) * 100, 4);
        barsHtml +=
            '<div class="chart-bar" style="height:' + height + '%; animation-delay:' + (index * 0.08) + 's;" title="' + item.year + ': ' + val.toLocaleString('ru-RU') + '">' +
                '<div class="chart-bar-label">' + formatPopulation(val) + '</div>' +
                '<div class="chart-bar-year">' + item.year + '</div>' +
            '</div>';
    });

    chart.innerHTML = '<div class="chart-bars">' + barsHtml + '</div>';
}

function initMap(lat, lon, name) {
    if (typeof ymaps === 'undefined') {
        document.getElementById('yandexMap').innerHTML = '<p style="padding:20px; text-align:center; color:#6b7280;">Карта недоступна</p>';
        return;
    }

    ymaps.ready(function () {
        yandexMap = new ymaps.Map('yandexMap', {
            center: [lat, lon],
            zoom: 13
        });

        var placemark = new ymaps.Placemark([lat, lon], {
            balloonContent: name
        });

        yandexMap.geoObjects.add(placemark);
    });
}

function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Закрытие модалки по клику на фон
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
        e.target.classList.remove('active');
    }
});

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
