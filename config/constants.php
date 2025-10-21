<?php
/**
 * Diyetlenio - Sabitler Dosyası
 *
 * Uygulama genelinde kullanılan sabitleri tanımlar.
 */

// Dizin sabitleri
define('ROOT_DIR', dirname(__DIR__));
define('CONFIG_DIR', ROOT_DIR . '/config');
define('CLASSES_DIR', ROOT_DIR . '/classes');
define('INCLUDES_DIR', ROOT_DIR . '/includes');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('ASSETS_DIR', ROOT_DIR . '/assets');
define('UPLOAD_DIR', ASSETS_DIR . '/uploads');
define('STORAGE_DIR', ROOT_DIR . '/storage');
define('LOGS_DIR', ROOT_DIR . '/logs');
define('VIEWS_DIR', ROOT_DIR . '/views');

// URL sabitleri
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
define('BASE_URL', $protocol . '://' . $host);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOAD_URL', ASSETS_URL . '/uploads');

// Kullanıcı tipleri
define('USER_TYPE_ADMIN', 'admin');
define('USER_TYPE_DIETITIAN', 'dietitian');
define('USER_TYPE_CLIENT', 'client');

// Kullanıcı durumları
define('USER_STATUS_INACTIVE', 0);
define('USER_STATUS_ACTIVE', 1);

// Randevu durumları
define('APPOINTMENT_STATUS_SCHEDULED', 'scheduled');
define('APPOINTMENT_STATUS_COMPLETED', 'completed');
define('APPOINTMENT_STATUS_CANCELLED', 'cancelled');
define('APPOINTMENT_STATUS_NO_SHOW', 'no_show');

// Ödeme durumları
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_APPROVED', 'approved');
define('PAYMENT_STATUS_REJECTED', 'rejected');

// Makale durumları
define('ARTICLE_STATUS_DRAFT', 'draft');
define('ARTICLE_STATUS_PENDING', 'pending');
define('ARTICLE_STATUS_APPROVED', 'approved');
define('ARTICLE_STATUS_REJECTED', 'rejected');
define('ARTICLE_STATUS_ARCHIVED', 'archived');

// Tarif durumları
define('RECIPE_STATUS_DRAFT', 'draft');
define('RECIPE_STATUS_PENDING', 'pending');
define('RECIPE_STATUS_APPROVED', 'approved');
define('RECIPE_STATUS_REJECTED', 'rejected');

// Tarif zorluk seviyeleri
define('RECIPE_DIFFICULTY_EASY', 'easy');
define('RECIPE_DIFFICULTY_MEDIUM', 'medium');
define('RECIPE_DIFFICULTY_HARD', 'hard');

// Video oturum tipleri
define('VIDEO_SESSION_REGULAR', 'regular');
define('VIDEO_SESSION_EMERGENCY', 'emergency');

// Acil çağrı durumları
define('EMERGENCY_STATUS_PENDING', 'pending');
define('EMERGENCY_STATUS_ONGOING', 'ongoing');
define('EMERGENCY_STATUS_COMPLETED', 'completed');
define('EMERGENCY_STATUS_CANCELLED', 'cancelled');

// Hata mesajları
define('ERROR_DATABASE', 'Veritabanı bağlantı hatası oluştu.');
define('ERROR_UNAUTHORIZED', 'Bu işlem için yetkiniz bulunmuyor.');
define('ERROR_NOT_FOUND', 'Aradığınız sayfa bulunamadı.');
define('ERROR_VALIDATION', 'Lütfen tüm alanları doğru şekilde doldurun.');
define('ERROR_SERVER', 'Sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.');
define('ERROR_FILE_UPLOAD', 'Dosya yükleme hatası.');
define('ERROR_FILE_TYPE', 'Geçersiz dosya türü.');
define('ERROR_FILE_SIZE', 'Dosya boyutu çok büyük.');

// Başarı mesajları
define('SUCCESS_LOGIN', 'Giriş başarılı. Hoş geldiniz!');
define('SUCCESS_LOGOUT', 'Başarıyla çıkış yaptınız.');
define('SUCCESS_REGISTER', 'Kayıt başarılı. E-posta adresinizi doğrulayın.');
define('SUCCESS_UPDATE', 'Güncelleme başarılı.');
define('SUCCESS_DELETE', 'Silme işlemi başarılı.');
define('SUCCESS_CREATE', 'Oluşturma işlemi başarılı.');
define('SUCCESS_UPLOAD', 'Dosya başarıyla yüklendi.');

// E-posta konuları
define('EMAIL_SUBJECT_WELCOME', 'Diyetlenio\'ya Hoş Geldiniz');
define('EMAIL_SUBJECT_VERIFICATION', 'E-posta Adresinizi Doğrulayın');
define('EMAIL_SUBJECT_PASSWORD_RESET', 'Şifre Sıfırlama Talebi');
define('EMAIL_SUBJECT_APPOINTMENT_CONFIRMATION', 'Randevu Onayı');
define('EMAIL_SUBJECT_APPOINTMENT_REMINDER', 'Randevu Hatırlatma');
define('EMAIL_SUBJECT_APPOINTMENT_CANCELLED', 'Randevu İptali');

// Bildirim tipleri
define('NOTIFICATION_TYPE_INFO', 'info');
define('NOTIFICATION_TYPE_SUCCESS', 'success');
define('NOTIFICATION_TYPE_WARNING', 'warning');
define('NOTIFICATION_TYPE_ERROR', 'error');
define('NOTIFICATION_TYPE_APPOINTMENT', 'appointment');
define('NOTIFICATION_TYPE_MESSAGE', 'message');
define('NOTIFICATION_TYPE_PAYMENT', 'payment');
define('NOTIFICATION_TYPE_REVIEW', 'review');

// Aktivite log tipleri
define('ACTIVITY_LOGIN', 'login');
define('ACTIVITY_LOGOUT', 'logout');
define('ACTIVITY_REGISTER', 'register');
define('ACTIVITY_PROFILE_UPDATE', 'profile_update');
define('ACTIVITY_PASSWORD_CHANGE', 'password_change');
define('ACTIVITY_APPOINTMENT_CREATE', 'appointment_create');
define('ACTIVITY_APPOINTMENT_CANCEL', 'appointment_cancel');
define('ACTIVITY_PAYMENT_CREATE', 'payment_create');
define('ACTIVITY_ARTICLE_CREATE', 'article_create');
define('ACTIVITY_RECIPE_CREATE', 'recipe_create');

// Tarih ve zaman formatları
define('DATE_FORMAT', 'd.m.Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd.m.Y H:i');
define('DATE_FORMAT_DB', 'Y-m-d');
define('TIME_FORMAT_DB', 'H:i:s');
define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');

// Sayfalama
define('PAGINATION_DEFAULT_LIMIT', 20);
define('PAGINATION_MAX_LIMIT', 100);

// Dosya boyutları (bytes)
define('FILE_SIZE_1MB', 1048576);
define('FILE_SIZE_5MB', 5242880);
define('FILE_SIZE_10MB', 10485760);
define('FILE_SIZE_20MB', 20971520);

// Resim boyutları
define('IMAGE_PROFILE_WIDTH', 400);
define('IMAGE_PROFILE_HEIGHT', 400);
define('IMAGE_THUMBNAIL_WIDTH', 300);
define('IMAGE_THUMBNAIL_HEIGHT', 300);
define('IMAGE_LARGE_WIDTH', 1200);
define('IMAGE_LARGE_HEIGHT', 800);

// Cache anahtarları
define('CACHE_PREFIX', 'diyetlenio_');
define('CACHE_KEY_SETTINGS', CACHE_PREFIX . 'settings');
define('CACHE_KEY_MENUS', CACHE_PREFIX . 'menus');
define('CACHE_KEY_CATEGORIES', CACHE_PREFIX . 'categories');

// Regex pattern'leri
define('REGEX_EMAIL', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');
define('REGEX_PHONE', '/^(\+90|0)?5\d{9}$/');
define('REGEX_PASSWORD', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/');
define('REGEX_SLUG', '/^[a-z0-9]+(?:-[a-z0-9]+)*$/');
define('REGEX_IBAN', '/^TR\d{24}$/');

// Uygulama versiyonu
define('APP_VERSION', '1.0.0');
define('APP_BUILD', '2025.10.21');

// Debug modu
define('DEBUG_MODE', $_ENV['APP_DEBUG'] ?? false);
