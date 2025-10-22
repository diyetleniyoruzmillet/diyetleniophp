-- Diyetisyen profili tablosuna IBAN alanı zaten var
-- Ekstra: Ödeme sistemini IBAN transfer'e uygun hale getir

-- NOTE: This migration might fail if columns already exist
-- That's OK - migration runner handles "already exists" errors

-- Site settings'e default IBAN ekle (eğer yoksa)
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('company_iban', 'TR00 0000 0000 0000 0000 0000 00', 'text', 'Şirket IBAN numarası'),
('bank_name', 'Ziraat Bankası', 'text', 'Banka adı'),
('account_holder', 'Diyetlenio Ltd. Şti.', 'text', 'Hesap sahibi adı'),
('payment_instructions', 'Lütfen randevu ücretinizi belirtilen IBAN numarasına yatırın ve dekontunu yükleyin.', 'textarea', 'Ödeme talimatları');

-- View: Diyetisyen IBAN bilgileri
CREATE OR REPLACE VIEW v_dietitian_payment_info AS
SELECT 
    u.id,
    u.full_name,
    dp.iban,
    dp.consultation_fee,
    COALESCE(dp.iban, (SELECT setting_value FROM site_settings WHERE setting_key = 'company_iban')) as payment_iban
FROM users u
INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
WHERE u.user_type = 'dietitian';
