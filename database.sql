-- ============================================
-- DİYETLENIO - Veritabanı Şeması
-- Versiyon: 1.0
-- Tarih: 2025-10-21
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

USE diyetlenio;

-- ============================================
-- 1. KULLANICILAR VE PROFİLLER
-- ============================================

-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('admin', 'dietitian', 'client') NOT NULL,
    profile_photo VARCHAR(255),
    is_active TINYINT(1) DEFAULT 0,
    is_email_verified TINYINT(1) DEFAULT 0,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diyetisyen profilleri
CREATE TABLE dietitian_profiles (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    title VARCHAR(100),
    specialization TEXT,
    experience_years INT,
    about_me TEXT,
    education TEXT,
    certificates TEXT,
    diploma_file VARCHAR(255),
    certificate_files TEXT,
    iban VARCHAR(34),
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 0,
    approval_date DATETIME,
    rejection_reason TEXT,
    rating_avg DECIMAL(3,2) DEFAULT 0,
    rating_count INT DEFAULT 0,
    total_clients INT DEFAULT 0,
    total_sessions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_approved (is_approved),
    INDEX idx_rating (rating_avg)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. RANDEVU SİSTEMİ
-- ============================================

-- Diyetisyen müsaitlik takvimi
CREATE TABLE availability (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dietitian_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Pazar, 1=Pazartesi, ..., 6=Cumartesi',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dietitian_day (dietitian_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Randevular
CREATE TABLE appointments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dietitian_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration INT DEFAULT 45 COMMENT 'Dakika cinsinden',
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    is_first_session TINYINT(1) DEFAULT 0,
    is_paid TINYINT(1) DEFAULT 0,
    payment_amount DECIMAL(10,2),
    notes TEXT,
    cancellation_reason TEXT,
    cancelled_by INT UNSIGNED,
    cancelled_at DATETIME,
    reminder_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dietitian (dietitian_id),
    INDEX idx_client (client_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. ÖDEME SİSTEMİ
-- ============================================

-- Ödemeler
CREATE TABLE payments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    receipt_file VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT UNSIGNED,
    approved_at DATETIME,
    rejection_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_appointment (appointment_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. VİDEO GÖRÜŞME
-- ============================================

-- Video oturumları
CREATE TABLE video_sessions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    room_id VARCHAR(255) UNIQUE NOT NULL,
    session_type ENUM('regular', 'emergency') DEFAULT 'regular',
    started_at DATETIME,
    ended_at DATETIME,
    duration INT COMMENT 'Dakika cinsinden',
    recording_file VARCHAR(255),
    recording_size INT COMMENT 'Byte cinsinden',
    quality_rating TINYINT,
    connection_issues TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    INDEX idx_appointment (appointment_id),
    INDEX idx_room (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acil nöbetçi talepleri
CREATE TABLE emergency_calls (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED,
    status ENUM('pending', 'ongoing', 'completed', 'cancelled') DEFAULT 'pending',
    room_id VARCHAR(255) UNIQUE,
    started_at DATETIME,
    ended_at DATETIME,
    duration INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. MESAJLAŞMA
-- ============================================

-- Mesajlar
CREATE TABLE messages (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_appointment (appointment_id),
    INDEX idx_unread (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. DANIŞAN TAKİP
-- ============================================

-- Diyet planları
CREATE TABLE diet_plans (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    start_date DATE,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kilo takibi
CREATE TABLE weight_tracking (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED,
    weight DECIMAL(5,2) NOT NULL COMMENT 'Kilogram',
    measurement_date DATE NOT NULL,
    notes TEXT,
    entered_by ENUM('client', 'dietitian') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_date (measurement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diyetisyen notları
CREATE TABLE client_notes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED NOT NULL,
    note TEXT NOT NULL,
    is_private TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client_dietitian (client_id, dietitian_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. BLOG VE MAKALE SİSTEMİ
-- ============================================

-- Makale kategorileri
CREATE TABLE article_categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id INT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES article_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makaleler
CREATE TABLE articles (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    author_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(255),
    status ENUM('draft', 'pending', 'approved', 'rejected', 'archived') DEFAULT 'draft',
    rejection_reason TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    views_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured),
    INDEX idx_published (published_at),
    FULLTEXT idx_search (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makale-kategori ilişkisi
CREATE TABLE article_category_relations (
    article_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (article_id, category_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Etiketler
CREATE TABLE article_tags (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makale-etiket ilişkisi
CREATE TABLE article_tag_relations (
    article_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES article_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makale yorumları
CREATE TABLE article_comments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    article_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    parent_comment_id INT UNSIGNED,
    comment TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES article_comments(id) ON DELETE CASCADE,
    INDEX idx_article (article_id),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makale beğenileri
CREATE TABLE article_likes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    article_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (article_id, user_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. TARİF SİSTEMİ
-- ============================================

-- Tarif kategorileri
CREATE TABLE recipe_categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id INT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES recipe_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarifler
CREATE TABLE recipes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    author_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    featured_image VARCHAR(255),
    prep_time INT COMMENT 'Dakika',
    cook_time INT COMMENT 'Dakika',
    total_time INT COMMENT 'Dakika',
    servings INT,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    calories_per_serving INT,
    protein DECIMAL(5,2),
    carbs DECIMAL(5,2),
    fat DECIMAL(5,2),
    fiber DECIMAL(5,2),
    tips TEXT,
    storage_info TEXT,
    status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    rejection_reason TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    views_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    rating_avg DECIMAL(3,2) DEFAULT 0,
    rating_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_rating (rating_avg),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif malzemeleri
CREATE TABLE recipe_ingredients (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    ingredient_name VARCHAR(255) NOT NULL,
    quantity VARCHAR(50),
    unit VARCHAR(50),
    sort_order INT DEFAULT 0,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif yapılış adımları
CREATE TABLE recipe_steps (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    step_number INT NOT NULL,
    instruction TEXT NOT NULL,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    INDEX idx_recipe (recipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif-kategori ilişkisi
CREATE TABLE recipe_category_relations (
    recipe_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (recipe_id, category_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES recipe_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diyet etiketleri
CREATE TABLE recipe_diet_tags (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif-diyet etiketi ilişkisi
CREATE TABLE recipe_diet_relations (
    recipe_id INT UNSIGNED NOT NULL,
    diet_tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (recipe_id, diet_tag_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (diet_tag_id) REFERENCES recipe_diet_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif puanları
CREATE TABLE recipe_ratings (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (recipe_id, user_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif favorileri
CREATE TABLE recipe_favorites (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (recipe_id, user_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarif yorumları
CREATE TABLE recipe_comments (
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

-- ============================================
-- 9. CMS VE SAYFA YÖNETİMİ
-- ============================================

-- Sayfalar
CREATE TABLE pages (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menü yapısı
CREATE TABLE menus (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id INT UNSIGNED,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    page_id INT UNSIGNED,
    target VARCHAR(20) DEFAULT '_self',
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Slider/Banner
CREATE TABLE sliders (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    subtitle TEXT,
    image VARCHAR(255) NOT NULL,
    button_text VARCHAR(100),
    button_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site ayarları
CREATE TABLE site_settings (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. PUAN VE YORUMLAR
-- ============================================

-- Diyetisyen değerlendirmeleri
CREATE TABLE reviews (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dietitian_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (appointment_id),
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    INDEX idx_dietitian (dietitian_id),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. BİLDİRİMLER
-- ============================================

-- Bildirimler
CREATE TABLE notifications (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. AKTİVİTE LOGLARI
-- ============================================

-- Sistem logları
CREATE TABLE activity_logs (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- İLK VERİLERİ EKLE
-- ============================================

-- Admin kullanıcısı
INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified) 
VALUES (
    'admin@diyetlenio.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- şifre: Admin123!
    'Sistem Yöneticisi',
    '05001234567',
    'admin',
    1,
    1
);

-- Temel site ayarları
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Diyetlenio', 'text', 'Site adı'),
('site_description', 'Diyetisyenler ve danışanları bir araya getiren platform', 'textarea', 'Site açıklaması'),
('contact_email', 'info@diyetlenio.com', 'email', 'İletişim e-posta adresi'),
('contact_phone', '0850 123 4567', 'text', 'İletişim telefonu'),
('facebook_url', 'https://facebook.com/diyetlenio', 'url', 'Facebook sayfası'),
('instagram_url', 'https://instagram.com/diyetlenio', 'url', 'Instagram sayfası'),
('twitter_url', 'https://twitter.com/diyetlenio', 'url', 'Twitter sayfası'),
('appointment_duration', '45', 'number', 'Randevu süresi (dakika)'),
('cancellation_hours', '2', 'number', 'İptal süresi (saat)'),
('reminder_hours', '1', 'number', 'Hatırlatma süresi (saat)');

-- Temel diyet etiketleri
INSERT INTO recipe_diet_tags (name, slug, color) VALUES
('Vejetaryen', 'vejetaryen', '#4CAF50'),
('Vegan', 'vegan', '#8BC34A'),
('Glutensiz', 'glutensiz', '#FF9800'),
('Laktozsuz', 'laktozsuz', '#03A9F4'),
('Keto', 'keto', '#9C27B0'),
('Düşük Kalorili', 'dusuk-kalorili', '#F44336'),
('Yüksek Proteinli', 'yuksek-proteinli', '#2196F3'),
('Şekersiz', 'sekersiz', '#E91E63');

-- Temel makale kategorileri
INSERT INTO article_categories (name, slug, description, sort_order) VALUES
('Beslenme Temelleri', 'beslenme-temelleri', 'Sağlıklı beslenmenin temel prensipleri', 1),
('Kilo Yönetimi', 'kilo-yonetimi', 'Kilo verme ve kilo alma rehberleri', 2),
('Sporcu Beslenmesi', 'sporcu-beslenmesi', 'Sporculara özel beslenme tavsiyeleri', 3),
('Hastalık ve Diyet', 'hastalik-ve-diyet', 'Hastalıklara özel diyet önerileri', 4),
('Yaşam Tarzı', 'yasam-tarzi', 'Sağlıklı yaşam için öneriler', 5);

-- Temel tarif kategorileri
INSERT INTO recipe_categories (name, slug, icon, sort_order) VALUES
('Kahvaltılıklar', 'kahvaltiliklar', 'fa-coffee', 1),
('Çorbalar', 'corbalar', 'fa-bowl-food', 2),
('Ana Yemekler', 'ana-yemekler', 'fa-plate-wheat', 3),
('Salatalar', 'salatalar', 'fa-salad', 4),
('Atıştırmalıklar', 'atistirmaliklar', 'fa-cookie-bite', 5),
('Tatlılar', 'tatlilar', 'fa-cake-candles', 6),
('İçecekler', 'icecekler', 'fa-mug-hot', 7);

-- Temel sayfalar
INSERT INTO pages (title, slug, content, is_active) VALUES
('Hakkımızda', 'hakkimizda', '<h1>Diyetlenio Hakkında</h1><p>Diyetisyenler ve danışanları bir araya getiren platformuz.</p>', 1),
('İletişim', 'iletisim', '<h1>İletişim</h1><p>Bizimle iletişime geçin.</p>', 1),
('Gizlilik Politikası', 'gizlilik-politikasi', '<h1>Gizlilik Politikası</h1><p>Kişisel verilerinizin korunması...</p>', 1),
('Kullanım Şartları', 'kullanim-sartlari', '<h1>Kullanım Şartları</h1><p>Platform kullanım koşulları...</p>', 1);

-- Temel menü yapısı
INSERT INTO menus (title, url, target, sort_order, is_active) VALUES
('Ana Sayfa', '/', '_self', 1, 1),
('Diyetisyenler', '/dietitians', '_self', 2, 1),
('Blog', '/blog', '_self', 3, 1),
('Tarifler', '/recipes', '_self', 4, 1),
('Hakkımızda', '/hakkimizda', '_self', 5, 1),
('İletişim', '/iletisim', '_self', 6, 1);

-- ============================================
-- TRİGGER'LAR
-- ============================================

-- Makale beğeni sayısını güncelle
DELIMITER $
CREATE TRIGGER update_article_likes_count 
AFTER INSERT ON article_likes
FOR EACH ROW
BEGIN
    UPDATE articles 
    SET likes_count = (SELECT COUNT(*) FROM article_likes WHERE article_id = NEW.article_id)
    WHERE id = NEW.article_id;
END$
DELIMITER ;

-- Tarif puan ortalamasını güncelle
DELIMITER $
CREATE TRIGGER update_recipe_rating 
AFTER INSERT ON recipe_ratings
FOR EACH ROW
BEGIN
    UPDATE recipes 
    SET 
        rating_avg = (SELECT AVG(rating) FROM recipe_ratings WHERE recipe_id = NEW.recipe_id),
        rating_count = (SELECT COUNT(*) FROM recipe_ratings WHERE recipe_id = NEW.recipe_id)
    WHERE id = NEW.recipe_id;
END$
DELIMITER ;

-- Diyetisyen puan ortalamasını güncelle
DELIMITER $
CREATE TRIGGER update_dietitian_rating 
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE dietitian_profiles 
    SET 
        rating_avg = (SELECT AVG(rating) FROM reviews WHERE dietitian_id = NEW.dietitian_id AND is_approved = 1),
        rating_count = (SELECT COUNT(*) FROM reviews WHERE dietitian_id = NEW.dietitian_id AND is_approved = 1)
    WHERE user_id = NEW.dietitian_id;
END$
DELIMITER ;

-- ============================================
-- VERİTABANI KURULUMU TAMAMLANDI
-- ============================================

SELECT 'Diyetlenio veritabanı başarıyla oluşturuldu!' AS Message;
