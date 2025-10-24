-- Kullanıcılara profil fotoğrafı ekleme
-- Diyetisyenler ve diğer kullanıcılar profil fotoğrafı yükleyebilir

ALTER TABLE users
ADD COLUMN profile_photo VARCHAR(255) NULL AFTER phone,
ADD INDEX idx_profile_photo (profile_photo);

-- Varsayılan avatar için yorum
-- NULL = varsayılan avatar kullanılacak
-- Dosya yolu: /storage/uploads/profiles/user_id_timestamp.jpg
