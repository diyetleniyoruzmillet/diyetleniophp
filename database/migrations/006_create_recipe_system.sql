-- Migration: 006_create_recipe_system.sql
-- Description: Create recipe system tables
-- Created: 2025-10-26
-- Depends on: 001_create_users_table.sql

-- Tarif kategorileri
CREATE TABLE IF NOT EXISTS recipe_categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id INT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icon VARCHAR(100),
    sort_order INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES recipe_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarifler
CREATE TABLE IF NOT EXISTS recipes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    author_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    featured_image VARCHAR(255),
    prep_time INT UNSIGNED COMMENT 'Dakika',
    cook_time INT UNSIGNED COMMENT 'Dakika',
    total_time INT UNSIGNED COMMENT 'Dakika',
    servings INT UNSIGNED,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    calories_per_serving INT UNSIGNED,
    protein DECIMAL(5,2),
    carbs DECIMAL(5,2),
    fat DECIMAL(5,2),
    fiber DECIMAL(5,2),
    tips TEXT,
    storage_info TEXT,
    status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    rejection_reason TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    views_count INT UNSIGNED DEFAULT 0,
    likes_count INT UNSIGNED DEFAULT 0,
    rating_avg DECIMAL(3,2) DEFAULT 0,
    rating_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_rating (rating_avg),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif malzemeleri
CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    ingredient_name VARCHAR(255) NOT NULL,
    quantity VARCHAR(50),
    unit VARCHAR(50),
    sort_order INT UNSIGNED DEFAULT 0,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif yapılış adımları
CREATE TABLE IF NOT EXISTS recipe_steps (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    step_number INT UNSIGNED NOT NULL,
    instruction TEXT NOT NULL,
    image VARCHAR(255),
    sort_order INT UNSIGNED DEFAULT 0,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif-kategori ilişkisi
CREATE TABLE IF NOT EXISTS recipe_category_relations (
    recipe_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (recipe_id, category_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES recipe_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diyet etiketleri
CREATE TABLE IF NOT EXISTS recipe_diet_tags (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif-diyet etiketi ilişkisi
CREATE TABLE IF NOT EXISTS recipe_diet_relations (
    recipe_id INT UNSIGNED NOT NULL,
    diet_tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (recipe_id, diet_tag_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (diet_tag_id) REFERENCES recipe_diet_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif puanları
CREATE TABLE IF NOT EXISTS recipe_ratings (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (recipe_id, user_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif favorileri
CREATE TABLE IF NOT EXISTS recipe_favorites (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (recipe_id, user_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif yorumları
CREATE TABLE IF NOT EXISTS recipe_comments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
