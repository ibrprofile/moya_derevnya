-- Добавление поля custom_status для текстового статуса
ALTER TABLE settlements ADD COLUMN IF NOT EXISTS custom_status VARCHAR(255) DEFAULT NULL;

-- Добавление таблиц для галереи населенных пунктов
CREATE TABLE IF NOT EXISTS settlement_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlement_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление таблиц для рубрик населенных пунктов
CREATE TABLE IF NOT EXISTS settlement_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlement_id INT NOT NULL,
    section_name VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    content LONGTEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление таблицы для динамики населения (корректное название)
CREATE TABLE IF NOT EXISTS settlement_population (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlement_id INT NOT NULL,
    year INT NOT NULL,
    population INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление таблиц для галереи станов
CREATE TABLE IF NOT EXISTS stan_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление таблиц для рубрик станов
CREATE TABLE IF NOT EXISTS stan_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    section_name VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    content LONGTEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление таблицы для динамики населения станов
CREATE TABLE IF NOT EXISTS stan_population (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    year INT NOT NULL,
    population INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
