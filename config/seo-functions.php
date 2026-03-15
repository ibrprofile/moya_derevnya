<?php
/**
 * SEO Helper Functions
 * Функции для генерации SEO-оптимизированного контента
 */

/**
 * Генерирует мета description для поселения
 */
function generateSettlementDescription($name, $status, $yearFounded, $region) {
    $statusText = $status === 'active' ? 'действующий' : 'недействующий';
    $year = $yearFounded ? " (основан в $yearFounded году)" : '';
    
    $description = "$name - $statusText населённый пункт России$year";
    if ($region) {
        $description .= " в $region";
    }
    $description .= ". Информация, история, карта, архивные данные и фотографии.";
    
    return substr($description, 0, 160);
}

/**
 * Генерирует мета keywords для поселения
 */
function generateSettlementKeywords($name, $region, $status) {
    $keywords = [
        $name,
        strtolower($name),
        "$name статья",
        "$name информация",
        "$name история",
        "$name население",
        "$name карта",
        'деревня',
        'село',
        'поселение',
        'население России',
    ];
    
    if ($region) {
        $keywords[] = "$region регион";
        $keywords[] = "$name $region";
    }
    
    if ($status === 'active') {
        $keywords[] = "$name действующее";
    } else {
        $keywords[] = "$name недействующее";
        $keywords[] = "$name заброшено";
    }
    
    return implode(', ', array_unique($keywords));
}

/**
 * Генерирует Schema.org структурированные данные для поселения
 */
function generateSettlementSchema($id, $name, $status, $yearFounded, $latitude, $longitude, $region, $baseUrl = 'https://votmoyaderevnya.ru') {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Place",
        "name" => $name,
        "url" => "$baseUrl/settlement.html?id=$id",
        "description" => generateSettlementDescription($name, $status, $yearFounded, $region),
        "inCountry" => "RU",
    ];
    
    if ($latitude && $longitude) {
        $schema["geo"] = [
            "@type" => "GeoCoordinates",
            "latitude" => (float)$latitude,
            "longitude" => (float)$longitude,
        ];
    }
    
    if ($yearFounded) {
        $schema["foundingDate"] = $yearFounded;
    }
    
    if ($region) {
        $schema["containedIn"] = [
            "@type" => "AdministrativeArea",
            "name" => $region,
        ];
    }
    
    return $schema;
}

/**
 * Генерирует Open Graph метатеги
 */
function generateOpenGraphTags($title, $description, $url, $image = null) {
    $tags = [
        'og:type' => 'article',
        'og:title' => $title,
        'og:description' => substr($description, 0, 160),
        'og:url' => $url,
        'og:site_name' => 'Вот моя деревня',
        'og:locale' => 'ru_RU',
    ];
    
    if ($image) {
        $tags['og:image'] = $image;
        $tags['og:image:type'] = 'image/jpeg';
    }
    
    return $tags;
}

/**
 * Проверяет и оптимизирует текст для SEO
 */
function optimizeTextForSEO($text, $keywordDensity = 0.03) {
    // Удаляет лишние пробелы
    $text = preg_replace('/\s+/', ' ', trim($text));
    
    return $text;
}

/**
 * Проверяет SEO-оптимизацию страницы
 */
function checkPageSEO($title, $description, $h1, $content) {
    $issues = [];
    $warnings = [];
    $success = [];
    
    // Проверка H1
    if (empty($h1)) {
        $issues[] = "H1 отсутствует на странице";
    } elseif (strlen($h1) < 10 || strlen($h1) > 70) {
        $warnings[] = "H1 слишком " . (strlen($h1) < 10 ? "короткий" : "длинный") . " (рекомендуется 10-70 символов)";
    } else {
        $success[] = "H1 в норме";
    }
    
    // Проверка title
    if (strlen($title) < 30) {
        $issues[] = "Title слишком короткий (минимум 30 символов)";
    } elseif (strlen($title) > 60) {
        $warnings[] = "Title слишком длинный (максимум 60 символов)";
    } else {
        $success[] = "Title оптимален";
    }
    
    // Проверка description
    if (strlen($description) < 120) {
        $issues[] = "Description слишком короткий (минимум 120 символов)";
    } elseif (strlen($description) > 160) {
        $warnings[] = "Description слишком длинный (максимум 160 символов)";
    } else {
        $success[] = "Description оптимален";
    }
    
    // Проверка контента
    if (strlen(strip_tags($content)) < 300) {
        $warnings[] = "Контент очень короткий (минимум 300 символов рекомендуется)";
    } else {
        $success[] = "Контент достаточного размера";
    }
    
    return [
        'issues' => $issues,
        'warnings' => $warnings,
        'success' => $success,
        'total_issues' => count($issues),
        'total_warnings' => count($warnings),
    ];
}

/**
 * Генерирует sitemap entries в массив для последующей генерации XML
 */
function generateSitemapEntry($url, $lastmod = null, $changefreq = 'monthly', $priority = '0.7') {
    return [
        'loc' => htmlspecialchars($url, ENT_XML1, 'UTF-8'),
        'lastmod' => $lastmod ? date('Y-m-d', strtotime($lastmod)) : date('Y-m-d'),
        'changefreq' => $changefreq,
        'priority' => $priority,
    ];
}

/**
 * Конвертирует массив в XML для sitemap
 */
function arrayToSitemapXml($entries) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($entries as $entry) {
        $xml .= "    <url>\n";
        $xml .= "        <loc>" . $entry['loc'] . "</loc>\n";
        $xml .= "        <lastmod>" . $entry['lastmod'] . "</lastmod>\n";
        $xml .= "        <changefreq>" . $entry['changefreq'] . "</changefreq>\n";
        $xml .= "        <priority>" . $entry['priority'] . "</priority>\n";
        $xml .= "    </url>\n";
    }
    
    $xml .= '</urlset>';
    
    return $xml;
}

/**
 * Получает slug из названия
 */
function generateSlug($text) {
    $text = transliterate($text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Транслитерирует русский текст в латиницу
 */
function transliterate($text) {
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];
    
    foreach ($map as $cyrillic => $latin) {
        $text = str_replace([$cyrillic, strtoupper($cyrillic)], [$latin, ucfirst($latin)], $text);
    }
    
    return $text;
}

?>
