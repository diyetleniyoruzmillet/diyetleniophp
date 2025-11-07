<?php
/**
 * Emergency Request API
 * Acil destek taleplerini kaydeder
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Form verilerini al
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = $_POST['gender'] ?? null;
    $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $health_conditions = trim($_POST['health_conditions'] ?? '');
    $medications = trim($_POST['medications'] ?? '');
    $urgency_level = $_POST['urgency_level'] ?? 'medium';
    $message = trim($_POST['message'] ?? '');

    // Validasyon
    $errors = [];

    if (empty($full_name)) {
        $errors[] = 'Ad soyad gereklidir';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi gereklidir';
    }

    if (empty($message) || strlen($message) < 20) {
        $errors[] = 'Talep mesajı en az 20 karakter olmalıdır';
    }

    if (!in_array($urgency_level, ['low', 'medium', 'high', 'critical'])) {
        $urgency_level = 'medium';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        exit;
    }

    // Veritabanına kaydet
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        INSERT INTO emergency_consultations (
            user_id, full_name, email, phone, age, gender,
            height, weight, health_conditions, medications,
            urgency_level, message, status, created_at
        ) VALUES (
            :user_id, :full_name, :email, :phone, :age, :gender,
            :height, :weight, :health_conditions, :medications,
            :urgency_level, :message, 'pending', NOW()
        )
    ");

    $user_id = $auth->check() ? $auth->user()['id'] : null;

    $stmt->execute([
        'user_id' => $user_id,
        'full_name' => $full_name,
        'email' => $email,
        'phone' => $phone ?: null,
        'age' => $age,
        'gender' => $gender ?: null,
        'height' => $height,
        'weight' => $weight,
        'health_conditions' => $health_conditions ?: null,
        'medications' => $medications ?: null,
        'urgency_level' => $urgency_level,
        'message' => $message
    ]);

    $request_id = $conn->lastInsertId();

    // Başarılı yanıt
    echo json_encode([
        'success' => true,
        'message' => 'Talebiniz başarıyla alındı',
        'request_id' => $request_id
    ]);

} catch (Exception $e) {
    error_log('Emergency request error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}
