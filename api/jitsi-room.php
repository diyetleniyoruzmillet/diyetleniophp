<?php
/**
 * API: Jitsi Meet room bilgilerini oluştur
 * Ücretsiz, sınırsız video görüşme
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

// Auth kontrolü
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $appointmentId = (int) ($_GET['appointment_id'] ?? 0);

    if (!$appointmentId) {
        throw new Exception('Appointment ID gerekli');
    }

    // Randevu kontrolü
    $conn = $db->getConnection();
    $stmt = $conn->prepare("
        SELECT a.*,
               u1.full_name as client_name,
               u2.full_name as dietitian_name
        FROM appointments a
        LEFT JOIN users u1 ON a.client_id = u1.id
        LEFT JOIN users u2 ON a.dietitian_id = u2.id
        WHERE a.id = ? AND (a.client_id = ? OR a.dietitian_id = ?)
    ");
    $stmt->execute([$appointmentId, $auth->id(), $auth->id()]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        throw new Exception('Randevu bulunamadı veya erişim yetkiniz yok');
    }

    // Randevu saati kontrolü (30 dakika önce başlatılabilir)
    $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
    $now = time();
    $thirtyMinsBefore = $appointmentDateTime - (30 * 60);

    if ($now < $thirtyMinsBefore) {
        throw new Exception('Randevu henüz başlamadı. Randevu saatinden 30 dakika önce katılabilirsiniz.');
    }

    // Benzersiz room adı oluştur (güvenli)
    $roomName = 'Diyetlenio_' . $appointmentId . '_' . substr(md5($appointmentId . '_' . $appointment['appointment_date']), 0, 8);

    // Kullanıcı bilgileri
    $userType = $auth->user()->getUserType();
    $displayName = $auth->user()->getFullName();

    if ($userType === 'dietitian') {
        $displayName = 'Dyt. ' . $displayName;
    }

    // Video session kaydı
    $stmt = $conn->prepare("
        INSERT INTO video_sessions (appointment_id, room_id, status, created_at)
        VALUES (?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE
            room_id = VALUES(room_id),
            status = 'active',
            updated_at = NOW()
    ");
    $stmt->execute([$appointmentId, $roomName]);

    // Success response
    echo json_encode([
        'success' => true,
        'room_name' => $roomName,
        'display_name' => $displayName,
        'domain' => 'meet.jit.si',
        'subject' => 'Diyetlenio Randevu #' . $appointmentId,
        'appointment' => [
            'id' => $appointmentId,
            'date' => $appointment['appointment_date'],
            'time' => $appointment['start_time'],
            'duration' => $appointment['duration'] ?? 45,
            'client_name' => $appointment['client_name'],
            'dietitian_name' => $appointment['dietitian_name']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
