<?php
/**
 * Şifre Sıfırlama ve Test Kullanıcıları Oluşturma
 */

require_once __DIR__ . '/includes/bootstrap.php';
$conn = $db->getConnection();

echo "=== ŞİFRE SIFIRLAMA VE TEST KULLANICILARI ===\n\n";

// 1. Admin şifresini sıfırla
$password_hash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
$stmt->execute([$password_hash, 'admin@diyetlenio.com']);
echo "✅ admin@diyetlenio.com şifresi: admin123\n\n";

// 2. Test Admin
$email = 'testadmin@diyetlenio.com';
$check = $conn->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if (!$check->fetch()) {
    $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, user_type, is_active) VALUES (?, ?, ?, ?, 1)');
    $stmt->execute(['Test Admin', $email, password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
    echo "✅ Yeni: $email | Şifre: admin123\n\n";
}

// 3. Test Diyetisyen
$email = 'testdiyetisyen@diyetlenio.com';
$check = $conn->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if (!$check->fetch()) {
    $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, user_type, phone, is_active) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->execute(['Test Diyetisyen', $email, password_hash('diyetisyen123', PASSWORD_BCRYPT), 'dietitian', '5551234567']);
    $user_id = $conn->lastInsertId();

    $stmt = $conn->prepare('INSERT INTO dietitian_profiles (user_id, title, specialization, is_approved, consultation_fee, experience_years) VALUES (?, ?, ?, 1, 500, 5)');
    $stmt->execute([$user_id, 'Uzman Diyetisyen', 'Genel Beslenme']);
    echo "✅ Yeni: $email | Şifre: diyetisyen123\n\n";
}

// 4. Test Danışan
$email = 'testdanisman@diyetlenio.com';
$check = $conn->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if (!$check->fetch()) {
    $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, user_type, phone, is_active) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->execute(['Test Danışan', $email, password_hash('danisman123', PASSWORD_BCRYPT), 'client', '5559876543']);
    $user_id = $conn->lastInsertId();

    $stmt = $conn->prepare('INSERT INTO client_profiles (user_id, gender, height, target_weight, activity_level) VALUES (?, ?, 170, 70, ?)');
    $stmt->execute([$user_id, 'male', 'moderate']);
    echo "✅ Yeni: $email | Şifre: danisman123\n\n";
}

echo "=== TAMAMLANDI ===\n";
echo "\nTÜM HESAPLAR:\n\n";
echo "👑 ADMIN:\n";
echo "   Email: admin@diyetlenio.com\n";
echo "   Şifre: admin123\n\n";
echo "   Email: testadmin@diyetlenio.com\n";
echo "   Şifre: admin123\n\n";

echo "👨‍⚕️ DİYETİSYEN:\n";
echo "   Email: testdiyetisyen@diyetlenio.com\n";
echo "   Şifre: diyetisyen123\n\n";

echo "👥 DANIŞAN:\n";
echo "   Email: testdanisman@diyetlenio.com\n";
echo "   Şifre: danisman123\n\n";
?>
