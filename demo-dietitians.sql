-- Demo Diyetisyen Ekleme SQL Script
-- Bu SQL dosyasını phpMyAdmin veya MySQL client üzerinden çalıştırın

-- 1. Dr. Ayşe Yılmaz - Spor Beslenmesi
INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
VALUES ('Dr. Ayşe Yılmaz', 'ayse.yilmaz@diyetlenio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0532 111 11 11', 'dietitian', 1, NOW());

INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, education, certifications, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at)
VALUES (LAST_INSERT_ID(), 'Diyetisyen, Beslenme Uzmanı', 'Spor Beslenmesi', 'Spor beslenmesi alanında 8 yıllık deneyime sahip uzman diyetisyen.', 'Hacettepe Üniversitesi Beslenme ve Diyetetik', 'Spor Beslenmesi Sertifikası', 8, 500, 4.8, 45, 120, 1, NOW());

-- 2. Mehmet Demir - Klinik Beslenme
INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
VALUES ('Mehmet Demir', 'mehmet.demir@diyetlenio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0533 222 22 22', 'dietitian', 1, NOW());

INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, education, certifications, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at)
VALUES (LAST_INSERT_ID(), 'Uzman Diyetisyen', 'Klinik Beslenme', 'Klinik beslenme ve metabolik hastalıklar konusunda uzman.', 'Ankara Üniversitesi Beslenme ve Diyetetik', 'Klinik Beslenme Uzmanlığı', 12, 600, 4.9, 78, 200, 1, NOW());

-- 3. Zeynep Kaya - Çocuk Beslenmesi
INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
VALUES ('Zeynep Kaya', 'zeynep.kaya@diyetlenio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0534 333 33 33', 'dietitian', 1, NOW());

INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, education, certifications, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at)
VALUES (LAST_INSERT_ID(), 'Diyetisyen', 'Çocuk Beslenmesi', 'Bebek ve çocuk beslenmesi konusunda uzman.', 'Ege Üniversitesi Beslenme ve Diyetetik', 'Çocuk Beslenmesi Sertifikası', 6, 450, 4.7, 52, 90, 1, NOW());

-- 4. Ahmet Öztürk - Obezite
INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
VALUES ('Ahmet Öztürk', 'ahmet.ozturk@diyetlenio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0535 444 44 44', 'dietitian', 1, NOW());

INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, education, certifications, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at)
VALUES (LAST_INSERT_ID(), 'Klinik Diyetisyen', 'Obezite ve Kilo Yönetimi', 'Obezite tedavisi ve sağlıklı kilo kaybı konusunda uzman.', 'İstanbul Üniversitesi Beslenme ve Diyetetik', 'Obezite Tedavisi Sertifikası', 9, 550, 4.8, 65, 150, 1, NOW());

-- 5. Elif Şahin - Vegan
INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
VALUES ('Elif Şahin', 'elif.sahin@diyetlenio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0536 555 55 55', 'dietitian', 1, NOW());

INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, education, certifications, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at)
VALUES (LAST_INSERT_ID(), 'Beslenme Uzmanı', 'Vejetaryen ve Vegan Beslenme', 'Bitkisel beslenme konusunda uzman.', 'Gazi Üniversitesi Beslenme ve Diyetetik', 'Vegan Beslenme Sertifikası', 5, 400, 4.6, 38, 75, 1, NOW());

-- 6. Can Yıldırım - Fonksiyonel
INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
VALUES ('Can Yıldırım', 'can.yildirim@diyetlenio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0537 666 66 66', 'dietitian', 1, NOW());

INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, education, certifications, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at)
VALUES (LAST_INSERT_ID(), 'Uzman Diyetisyen', 'Fonksiyonel Beslenme', 'Fonksiyonel beslenme ve bağırsak sağlığı konusunda uzman.', 'Başkent Üniversitesi Beslenme ve Diyetetik', 'Fonksiyonel Beslenme Sertifikası', 7, 500, 4.7, 42, 95, 1, NOW());

-- NOT: Tüm demo kullanıcıların şifresi: Demo123!
-- Password hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
