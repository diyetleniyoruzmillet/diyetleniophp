<?php
/**
 * Diyetisyen Müsaitlik Yönetimi
 * Haftalık çalışma saatleri ve izin günleri
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$availabilityService = new AvailabilityService($db);
$dietitianId = $auth->user()->getId();

$errors = [];
$success = false;

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        try {
            $schedule = [];

            // Her gün için saatleri al
            for ($day = 1; $day <= 5; $day++) { // Pazartesi-Cuma
                if (isset($_POST["day_{$day}_enabled"])) {
                    $morningStart = $_POST["day_{$day}_morning_start"] ?? '';
                    $morningEnd = $_POST["day_{$day}_morning_end"] ?? '';
                    $afternoonStart = $_POST["day_{$day}_afternoon_start"] ?? '';
                    $afternoonEnd = $_POST["day_{$day}_afternoon_end"] ?? '';

                    // Sabah vardiyası
                    if ($morningStart && $morningEnd) {
                        $schedule[] = [
                            'day_of_week' => $day,
                            'start_time' => $morningStart . ':00',
                            'end_time' => $morningEnd . ':00',
                            'slot_duration' => 45,
                            'is_active' => 1
                        ];
                    }

                    // Öğleden sonra vardiyası
                    if ($afternoonStart && $afternoonEnd) {
                        $schedule[] = [
                            'day_of_week' => $day,
                            'start_time' => $afternoonStart . ':00',
                            'end_time' => $afternoonEnd . ':00',
                            'slot_duration' => 45,
                            'is_active' => 1
                        ];
                    }
                }
            }

            $availabilityService->updateWeeklyAvailability($dietitianId, $schedule);
            $success = true;
            setFlash('success', 'Müsaitlik ayarlarınız güncellendi.');

        } catch (Exception $e) {
            $errors[] = 'Güncelleme sırasında hata oluştu: ' . $e->getMessage();
        }
    }
}

// Mevcut müsaitlikleri getir
$weeklyAvailability = $availabilityService->getWeeklyAvailability($dietitianId);

// Günleri formatla
$days = [
    1 => 'Pazartesi',
    2 => 'Salı',
    3 => 'Çarşamba',
    4 => 'Perşembe',
    5 => 'Cuma'
];

$pageTitle = 'Müsaitlik Ayarları';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<p>Müsaitlik yönetimi sayfası oluşturuldu!</p>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
