<?php
/**
 * Diyetlenio - Diyetisyen Müsaitlik Ayarları
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Müsaitlik kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_availability'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        try {
            // Önce mevcut müsaitlikleri sil
            $stmt = $conn->prepare("DELETE FROM dietitian_availability WHERE dietitian_id = ?");
            $stmt->execute([$userId]);

            // Yeni müsaitlikleri ekle
            foreach ($_POST['availability'] as $dayOfWeek => $slots) {
                foreach ($slots as $slot) {
                    if (!empty($slot['start_time']) && !empty($slot['end_time'])) {
                        $stmt = $conn->prepare("
                            INSERT INTO dietitian_availability (dietitian_id, day_of_week, start_time, end_time)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$userId, $dayOfWeek, $slot['start_time'], $slot['end_time']]);
                    }
                }
            }

            setFlash('success', 'Müsaitlik ayarlarınız kaydedildi.');
            redirect('/dietitian/availability.php');
        } catch (Exception $e) {
            $error = 'Kayıt sırasında bir hata oluştu.';
        }
    }
}

// Mevcut müsaitlikleri çek
$stmt = $conn->prepare("
    SELECT * FROM dietitian_availability
    WHERE dietitian_id = ?
    ORDER BY day_of_week, start_time
");
$stmt->execute([$userId]);
$availabilities = $stmt->fetchAll();

// Günlere göre grupla
$availabilityByDay = [];
foreach ($availabilities as $av) {
    $availabilityByDay[$av['day_of_week']][] = $av;
}

$pageTitle = 'Müsaitlik Ayarları';
$days = [
    1 => 'Pazartesi',
    2 => 'Salı',
    3 => 'Çarşamba',
    4 => 'Perşembe',
    5 => 'Cuma',
    6 => 'Cumartesi',
    7 => 'Pazar'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        .content-wrapper { padding: 30px; }
        .day-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .time-slot {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid #28a745;
        }
        .btn-add-slot {
            border: 2px dashed #28a745;
            color: #28a745;
            background: white;
        }
        .btn-add-slot:hover {
            background: #e7f5ed;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/dietitian/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link active" href="/dietitian/availability.php">
                            <i class="fas fa-clock me-2"></i>Müsaitlik
                        </a>
                        <a class="nav-link" href="/dietitian/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planları
                        </a>
                        <a class="nav-link" href="/dietitian/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link" href="/dietitian/profile.php">
                            <i class="fas fa-user me-2"></i>Profilim
                        </a>
                        <hr class="text-white-50 my-3">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                        <a class="nav-link" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <h2 class="mb-4">Müsaitlik Ayarları</h2>

                    <?php if (hasFlash()): ?>
                        <?php if ($msg = getFlash('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($msg = getFlash('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-10">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Bilgi:</strong> Haftalık çalışma saatlerinizi belirleyin.
                                Danışanlar sadece bu saatler içinde randevu alabilecektir.
                            </div>

                            <form method="POST" id="availabilityForm">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                <?php foreach ($days as $dayNum => $dayName): ?>
                                    <div class="day-card">
                                        <h5 class="mb-3">
                                            <i class="fas fa-calendar-day text-success me-2"></i>
                                            <?= $dayName ?>
                                        </h5>

                                        <div class="time-slots-container" id="day<?= $dayNum ?>">
                                            <?php
                                            $daySlots = $availabilityByDay[$dayNum] ?? [['start_time' => '', 'end_time' => '']];
                                            if (count($daySlots) === 0) {
                                                $daySlots = [['start_time' => '', 'end_time' => '']];
                                            }
                                            ?>

                                            <?php foreach ($daySlots as $index => $slot): ?>
                                                <div class="time-slot mb-3">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-5">
                                                            <label class="form-label small">Başlangıç Saati</label>
                                                            <input
                                                                type="time"
                                                                name="availability[<?= $dayNum ?>][<?= $index ?>][start_time]"
                                                                class="form-control"
                                                                value="<?= $slot['start_time'] ?>"
                                                            >
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label class="form-label small">Bitiş Saati</label>
                                                            <input
                                                                type="time"
                                                                name="availability[<?= $dayNum ?>][<?= $index ?>][end_time]"
                                                                class="form-control"
                                                                value="<?= $slot['end_time'] ?>"
                                                            >
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small d-block">&nbsp;</label>
                                                            <button
                                                                type="button"
                                                                class="btn btn-danger btn-sm w-100 remove-slot"
                                                                onclick="removeSlot(this)"
                                                            >
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-add-slot w-100"
                                            onclick="addSlot(<?= $dayNum ?>)"
                                        >
                                            <i class="fas fa-plus me-2"></i>Saat Aralığı Ekle
                                        </button>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="/dietitian/dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Geri Dön
                                    </a>
                                    <button type="submit" name="save_availability" class="btn btn-success btn-lg">
                                        <i class="fas fa-save me-2"></i>Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let slotCounters = {
            <?php foreach ($days as $dayNum => $dayName): ?>
            <?= $dayNum ?>: <?= count($availabilityByDay[$dayNum] ?? [['start_time' => '', 'end_time' => '']]) ?>,
            <?php endforeach; ?>
        };

        function addSlot(dayNum) {
            const container = document.getElementById('day' + dayNum);
            const index = slotCounters[dayNum]++;

            const slotHtml = `
                <div class="time-slot mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small">Başlangıç Saati</label>
                            <input
                                type="time"
                                name="availability[${dayNum}][${index}][start_time]"
                                class="form-control"
                            >
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">Bitiş Saati</label>
                            <input
                                type="time"
                                name="availability[${dayNum}][${index}][end_time]"
                                class="form-control"
                            >
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small d-block">&nbsp;</label>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm w-100 remove-slot"
                                onclick="removeSlot(this)"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', slotHtml);
        }

        function removeSlot(button) {
            const slot = button.closest('.time-slot');
            slot.remove();
        }
    </script>
</body>
</html>
