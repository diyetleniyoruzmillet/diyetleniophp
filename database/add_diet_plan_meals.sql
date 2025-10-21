-- Diyet planı öğünleri tablosu
CREATE TABLE IF NOT EXISTS diet_plan_meals (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    plan_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Pazartesi, 7=Pazar',
    meal_time ENUM('breakfast', 'snack1', 'lunch', 'snack2', 'dinner', 'snack3') NOT NULL,
    meal_name VARCHAR(255),
    description TEXT,
    calories INT,
    protein DECIMAL(5,2),
    carbs DECIMAL(5,2),
    fat DECIMAL(5,2),
    notes TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES diet_plans(id) ON DELETE CASCADE,
    INDEX idx_plan_day (plan_id, day_of_week),
    INDEX idx_meal_time (meal_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek veri ekle
INSERT INTO diet_plan_meals (plan_id, day_of_week, meal_time, meal_name, description, calories, protein, carbs, fat, notes) VALUES
(1, 1, 'breakfast', 'Kahvaltı', 'Yumurta, peynir, domates, salatalık, zeytin, tam buğday ekmeği', 350, 18, 35, 15, 'Sabah 08:00-09:00 arası'),
(1, 1, 'snack1', 'Kuşluk Atıştırması', 'Meyveli yoğurt', 120, 8, 18, 2, 'Sabah 10:30-11:00 arası'),
(1, 1, 'lunch', 'Öğle Yemeği', 'Izgara tavuk, bulgur pilavı, salata', 450, 35, 48, 12, 'Öğle 12:30-13:30 arası'),
(1, 1, 'snack2', 'İkindi Atıştırması', 'Çiğ badem (10 adet)', 80, 3, 4, 7, 'İkindi 16:00-16:30 arası'),
(1, 1, 'dinner', 'Akşam Yemeği', 'Fırında sebze, kırmızı mercimek çorbası', 380, 15, 52, 10, 'Akşam 19:00-20:00 arası');

-- İstatistikler için view
CREATE OR REPLACE VIEW v_diet_plan_daily_totals AS
SELECT
    plan_id,
    day_of_week,
    SUM(calories) as total_calories,
    SUM(protein) as total_protein,
    SUM(carbs) as total_carbs,
    SUM(fat) as total_fat,
    COUNT(*) as meal_count
FROM diet_plan_meals
GROUP BY plan_id, day_of_week;
