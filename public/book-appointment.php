<?php
/**
 * Diyetlenio - Randevu Al
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Sadece giriş yapmış danışanlar
if (!$auth->check()) {
    setFlash('error', 'Randevu almak için giriş yapmalısınız.');
    redirect('/login.php');
}

if ($auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/');
}

$dietitianId = (int) ($_GET['dietitian_id'] ?? 0);
$conn = $db->getConnection();

// Diyetisyen bilgilerini getir
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.profile_photo,
           dp.title, dp.consultation_fee, dp.about_me
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id = ? AND u.user_type = 'dietitian' AND u.is_active = 1 AND dp.is_approved = 1
");
$stmt->execute([$dietitianId]);
$dietitian = $stmt->fetch();

if (!$dietitian) {
    setFlash('error', 'Diyetisyen bulunamadı.');
    redirect('/dietitians.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        // Validator ile validasyon
        $validator = new Validator($_POST);
        $validator
            ->required(['appointment_date', 'start_time'])
            ->date('appointment_date');

        // Gelecek tarih kontrolü
        $validator->custom('appointment_date', function($value) {
            return strtotime($value) >= strtotime(date('Y-m-d'));
        }, 'Geçmiş tarihli randevu oluşturamazsınız.');

        if ($validator->fails()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        // Randevu çakışması kontrolü
        if (empty($errors)) {
            $appointmentDate = $_POST['appointment_date'];
            $startTime = $_POST['start_time'];
            $notes = $_POST['notes'] ?? '';
            $stmt = $conn->prepare("
                SELECT id FROM appointments 
                WHERE dietitian_id = ? 
                AND appointment_date = ? 
                AND start_time = ?
                AND status NOT IN ('cancelled')
            ");
            $stmt->execute([$dietitianId, $appointmentDate, $startTime]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu saat için zaten randevu mevcut. Lütfen başka bir saat seçin.';
            }
        }

        // Randevu oluştur
        if (empty($errors)) {
            try {
                $endTime = date('H:i:s', strtotime($startTime) + (45 * 60)); // 45 dakika

                $stmt = $conn->prepare("
                    INSERT INTO appointments (
                        dietitian_id, client_id, appointment_date, start_time, end_time,
                        duration, status, notes, created_at
                    ) VALUES (?, ?, ?, ?, ?, 45, 'scheduled', ?, NOW())
                ");

                $stmt->execute([
                    $dietitianId,
                    $auth->id(),
                    $appointmentDate,
                    $startTime,
                    $endTime,
                    $notes
                ]);

                $appointmentId = $conn->lastInsertId();
                $success = true;

                // Bildirim gönder
                try {
                    $notification = new Notification();
                    $notification->notifyAppointmentCreated($appointmentId);
                } catch (Exception $notifError) {
                    error_log('Notification error: ' . $notifError->getMessage());
                }

                // Ödeme bilgileri sayfasına yönlendir
                setFlash('success', 'Randevunuz başarıyla oluşturuldu! Lütfen ödeme bilgilerini kontrol edin.');
                redirect('/payment-info.php?appointment=' . $appointmentId);

            } catch (Exception $e) {
                error_log('Appointment creation error: ' . $e->getMessage());
                $errors[] = 'Randevu oluşturulurken bir hata oluştu.';
            }
        }
    }
}

// Müsait saatleri getir (basit versiyon - gelecekte availability tablosundan gelecek)
$availableTimes = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

$pageTitle = 'Randevu Al';
include __DIR__ . '/../includes/partials/header.php';
?>
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .container { max-width: 900px; margin-top: 50px; }
        .card-custom { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 3px 15px rgba(0,0,0,0.1); }
        .dietitian-info { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .time-slot { border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .time-slot:hover { border-color: #11998e; background: #e6fffa; }
        .time-slot input[type="radio"] { display: none; }
        .time-slot input[type="radio"]:checked + label { background: #11998e; color: white; border-color: #11998e; }
        .btn-submit { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 40px; border: none; border-radius: 10px; font-weight: 600; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-2"></i>Geri Dön
        </a>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                Randevunuz başarıyla oluşturuldu! <a href="/client/appointments.php">Randevularım</a> sayfasından görüntüleyebilirsiniz.
            </div>
        <?php endif; ?>

        <div class="dietitian-info">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <?php if ($dietitian['profile_photo']): ?>
                                        <?php $p=$dietitian['profile_photo']; $photoUrl='/assets/uploads/' . ltrim($p,'/'); ?>
                                        <img src="<?= clean($photoUrl) ?>" alt="" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-md fa-2x text-success"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col">
                    <h2 class="mb-1"><?= clean($dietitian['full_name']) ?></h2>
                    <p class="mb-1"><?= clean($dietitian['title'] ?? 'Diyetisyen') ?></p>
                    <p class="mb-0"><strong>Ücret:</strong> <?= number_format($dietitian['consultation_fee'], 0) ?> ₺</p>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <h3 class="mb-4">Randevu Detayları</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= clean($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="mb-4">
                    <label class="form-label fw-bold">Randevu Tarihi</label>
                    <input type="date" name="appointment_date" class="form-control" 
                           min="<?= date('Y-m-d') ?>" required 
                           value="<?= clean($_POST['appointment_date'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Randevu Saati</label>
                    <div class="row g-3">
                        <?php foreach ($availableTimes as $time): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="time-slot">
                                    <input type="radio" name="start_time" value="<?= $time ?>" id="time_<?= $time ?>" required>
                                    <label for="time_<?= $time ?>" class="d-block p-2">
                                        <i class="far fa-clock me-1"></i><?= $time ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Notlar (Opsiyonel)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Görüşmek istediğiniz konular..."><?= clean($_POST['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-calendar-check me-2"></i>Randevu Oluştur
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
