<?php
/**
 * API: Belirli bir tarih için müsait saatleri getir
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

try {
    $dietitianId = (int) ($_GET['dietitian_id'] ?? 0);
    $date = $_GET['date'] ?? '';

    if (!$dietitianId || !$date) {
        throw new Exception('Diyetisyen ID ve tarih gerekli');
    }

    // Tarih formatı kontrolü
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Geçersiz tarih formatı (Y-m-d bekleniyor)');
    }

    // Geçmiş tarih kontrolü
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        throw new Exception('Geçmiş tarihli randevu oluşturamazsınız');
    }

    $availabilityService = new AvailabilityService($db);
    $slots = $availabilityService->getAvailableSlots($dietitianId, $date);

    // Saatleri güzel formatta döndür
    $formattedSlots = array_map(function($slot) {
        return [
            'time' => $slot,
            'display' => date('H:i', strtotime($slot))
        ];
    }, $slots);

    echo json_encode([
        'success' => true,
        'date' => $date,
        'slots' => $formattedSlots,
        'count' => count($formattedSlots)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
