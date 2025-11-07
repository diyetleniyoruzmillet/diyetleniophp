<?php
/**
 * Create Appointment API
 * Randevu oluşturma endpoint'i
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Auth kontrolü - Client olmalı
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Randevu oluşturmak için danışan olarak giriş yapmalısınız.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Sadece POST istekleri kabul edilir.'
    ]);
    exit;
}

$conn = $db->getConnection();
$clientId = $auth->id();

// Form verilerini al
$dietitianId = (int) ($_POST['dietitian_id'] ?? 0);
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$timeSlot = trim($_POST['time_slot'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validasyon
$errors = [];

if (!$dietitianId) {
    $errors[] = 'Diyetisyen seçilmedi.';
}

if (empty($appointmentDate)) {
    $errors[] = 'Randevu tarihi seçilmedi.';
} else {
    $date = DateTime::createFromFormat('Y-m-d', $appointmentDate);
    if (!$date || $date->format('Y-m-d') !== $appointmentDate) {
        $errors[] = 'Geçersiz tarih formatı.';
    } elseif ($date < new DateTime('today')) {
        $errors[] = 'Geçmiş bir tarihe randevu oluşturamazsınız.';
    }
}

if (empty($timeSlot)) {
    $errors[] = 'Saat seçilmedi.';
} else {
    if (!preg_match('/^\d{2}:\d{2}$/', $timeSlot)) {
        $errors[] = 'Geçersiz saat formatı.';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Lütfen tüm alanları doldurun.',
        'errors' => $errors
    ]);
    exit;
}

try {
    // Diyetisyeni kontrol et
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, dp.is_approved
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE u.id = ? AND u.user_type = 'dietitian' AND u.is_active = 1
    ");
    $stmt->execute([$dietitianId]);
    $dietitian = $stmt->fetch();

    if (!$dietitian) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Diyetisyen bulunamadı.'
        ]);
        exit;
    }

    if (!$dietitian['is_approved']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Bu diyetisyen henüz onaylanmamış.'
        ]);
        exit;
    }

    // Diyetisyenin o gün müsait olup olmadığını kontrol et
    $dayOfWeek = strtolower(date('l', strtotime($appointmentDate))); // monday, tuesday, etc.

    $stmt = $conn->prepare("
        SELECT * FROM dietitian_availability
        WHERE dietitian_id = ?
        AND day_of_week = ?
        AND is_active = 1
        AND ? BETWEEN start_time AND end_time
    ");
    $stmt->execute([$dietitianId, $dayOfWeek, $timeSlot]);
    $availability = $stmt->fetch();

    if (!$availability) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Diyetisyen seçtiğiniz gün ve saatte müsait değil.'
        ]);
        exit;
    }

    // Aynı saatte başka randevu var mı kontrol et
    $endTime = date('H:i:s', strtotime($timeSlot) + (45 * 60)); // 45 dakika ekle

    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM appointments
        WHERE dietitian_id = ?
        AND appointment_date = ?
        AND status IN ('scheduled', 'confirmed')
        AND (
            (start_time <= ? AND end_time > ?)
            OR (start_time < ? AND end_time >= ?)
        )
    ");
    $stmt->execute([$dietitianId, $appointmentDate, $timeSlot, $timeSlot, $endTime, $endTime]);
    $conflict = $stmt->fetch();

    if ($conflict['count'] > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Bu saat diliminde zaten bir randevu var. Lütfen başka bir saat seçin.'
        ]);
        exit;
    }

    // Danışanın aynı gün başka randevusu var mı kontrol et
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM appointments
        WHERE client_id = ?
        AND appointment_date = ?
        AND status IN ('scheduled', 'confirmed')
    ");
    $stmt->execute([$clientId, $appointmentDate]);
    $clientConflict = $stmt->fetch();

    if ($clientConflict['count'] > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Bu tarihte zaten bir randevunuz var.'
        ]);
        exit;
    }

    // Randevuyu oluştur
    $stmt = $conn->prepare("
        INSERT INTO appointments (
            client_id,
            dietitian_id,
            appointment_date,
            start_time,
            end_time,
            duration,
            notes,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 45, ?, 'scheduled', NOW())
    ");

    $stmt->execute([
        $clientId,
        $dietitianId,
        $appointmentDate,
        $timeSlot,
        $endTime,
        $notes
    ]);

    $appointmentId = $conn->lastInsertId();

    // Başarılı response
    echo json_encode([
        'success' => true,
        'message' => 'Randevunuz başarıyla oluşturuldu!',
        'appointment_id' => $appointmentId,
        'redirect' => '/client/dashboard.php'
    ]);

} catch (Exception $e) {
    error_log('Appointment creation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Randevu oluşturulurken bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}
?>
