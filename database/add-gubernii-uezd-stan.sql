-- Добавление таблиц для губерний, уездов и станов
USE russian_villages;

-- Таблица губерний
CREATE TABLE IF NOT EXISTS gubernii (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица уездов
CREATE TABLE IF NOT EXISTS uezds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guberniya_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guberniya_id) REFERENCES gubernii(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица станов
CREATE TABLE IF NOT EXISTS stans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uezd_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    year_founded VARCHAR(50),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    toponym TEXT,
    period VARCHAR(255),
    status_text TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uezd_id) REFERENCES uezds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Обновляем таблицу settlements для добавления текстового статуса
ALTER TABLE settlements ADD COLUMN status_text TEXT AFTER status;

-- Таблица владельцев для станов
CREATE TABLE IF NOT EXISTS stan_owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица фотографий для станов
CREATE TABLE IF NOT EXISTS stan_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    photo_url VARCHAR(500) NOT NULL,
    category ENUM('gallery', 'attractions') DEFAULT 'gallery',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица динамики населения для станов
CREATE TABLE IF NOT EXISTS stan_population_dynamics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    year INT NOT NULL,
    population INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица галереи для станов и settlements
CREATE TABLE IF NOT EXISTS stan_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settlement_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlement_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица рубрик для станов и settlements
CREATE TABLE IF NOT EXISTS stan_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stan_id INT NOT NULL,
    section_name VARCHAR(255) NOT NULL,
    content TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stan_id) REFERENCES stans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settlement_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlement_id INT NOT NULL,
    section_name VARCHAR(255) NOT NULL,
    content TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
