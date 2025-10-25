<?php
/**
 * Diyetlenio - Gelişmiş Randevu Alma
 * Dinamik müsait saat sistemi ile
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
    SELECT u.id, u.full_name, u.profile_photo, u.email,
           dp.title, dp.consultation_fee, dp.about_me, dp.specialization
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

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        try {
            $date = $_POST['appointment_date'] ?? '';
            $time = $_POST['start_time'] ?? '';
            $notes = $_POST['notes'] ?? '';

            // Validasyon
            if (empty($date) || empty($time)) {
                throw new Exception('Tarih ve saat seçmelisiniz');
            }

            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                throw new Exception('Geçmiş tarihli randevu oluşturamazsınız');
            }

            // Müsait mi kontrol et
            $availabilityService = new AvailabilityService($db);
            $availableSlots = $availabilityService->getAvailableSlots($dietitianId, $date);

            if (!in_array($time, $availableSlots)) {
                throw new Exception('Seçilen saat artık müsait değil. Lütfen başka bir saat seçin.');
            }

            // Randevu oluştur
            $endTime = date('H:i:s', strtotime($time) + (45 * 60));

            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    dietitian_id, client_id, appointment_date, start_time, end_time,
                    duration, status, is_online, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, 45, 'scheduled', 1, ?, NOW())
            ");

            $stmt->execute([
                $dietitianId,
                $auth->id(),
                $date,
                $time,
                $endTime,
                $notes
            ]);

            $appointmentId = $conn->lastInsertId();

            // E-posta bildirimi gönder
            try {
                $mailer = new Mailer();
                $client = $auth->user();

                // Danışana onay e-postası
                $mailer->sendAppointmentConfirmation($client->getEmail(), [
                    'client_name' => $client->getFullName(),
                    'dietitian_name' => $dietitian['full_name'],
                    'appointment_date' => date('d.m.Y', strtotime($date)),
                    'start_time' => date('H:i', strtotime($time)),
                    'appointment_url' => BASE_URL . '/client/appointments.php'
                ]);

                // Diyetisyene bildirim e-postası
                $mailer->sendAppointmentConfirmation($dietitian['email'], [
                    'client_name' => $client->getFullName(),
                    'dietitian_name' => $dietitian['full_name'],
                    'appointment_date' => date('d.m.Y', strtotime($date)),
                    'start_time' => date('H:i', strtotime($time)),
                    'appointment_url' => BASE_URL . '/dietitian/appointments.php'
                ]);

                // Hatırlatma kayıtları oluştur (24 saat ve 1 saat öncesi)
                $appointmentDateTime = strtotime($date . ' ' . $time);

                // 24 saat öncesi hatırlatma
                $reminder24h = date('Y-m-d H:i:s', $appointmentDateTime - (24 * 3600));
                $stmt = $conn->prepare("
                    INSERT INTO appointment_reminders
                    (appointment_id, reminder_type, scheduled_for, status)
                    VALUES (?, 'email', ?, 'pending')
                ");
                $stmt->execute([$appointmentId, $reminder24h]);

                // 1 saat öncesi hatırlatma
                $reminder1h = date('Y-m-d H:i:s', $appointmentDateTime - 3600);
                $stmt->execute([$appointmentId, $reminder1h]);

            } catch (Exception $e) {
                error_log('Email/Reminder error: ' . $e->getMessage());
                // E-posta hatası randevu oluşumunu engellemez
            }

            setFlash('success', 'Randevunuz başarıyla oluşturuldu! E-posta onayı gönderildi.');
            redirect('/client/appointments.php');

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Randevu Al';
include __DIR__ . '/../includes/partials/header.php';
?>

<style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Inter', sans-serif; }

    .booking-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 20px;
    }

    .back-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.3s;
        margin-bottom: 20px;
    }

    .back-btn:hover {
        background: rgba(255,255,255,0.3);
        color: white;
        transform: translateX(-5px);
    }

    .dietitian-header {
        background: white;
        padding: 40px;
        border-radius: 24px;
        margin-bottom: 30px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }

    .dietitian-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #f3f4f6;
    }

    .dietitian-avatar-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
    }

    .booking-card {
        background: white;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 25px;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        color: #667eea;
    }

    .date-input {
        width: 100%;
        padding: 18px 20px;
        border: 3px solid #e5e7eb;
        border-radius: 16px;
        font-size: 1.1rem;
        font-weight: 600;
        transition: all 0.3s;
    }

    .date-input:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
    }

    .slots-container {
        margin-top: 30px;
    }

    .slots-loading {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .spinner {
        border: 4px solid #f3f4f6;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .slots-empty {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .slots-empty i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 20px;
    }

    .slots-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 15px;
    }

    .slot-btn {
        padding: 20px;
        border: 3px solid #e5e7eb;
        border-radius: 16px;
        background: white;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .slot-btn:hover {
        border-color: #667eea;
        background: #f3f4f6;
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.2);
    }

    .slot-btn.selected {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(102,126,234,0.4);
    }

    .slot-btn i {
        font-size: 1.3rem;
    }

    .notes-textarea {
        width: 100%;
        padding: 18px 20px;
        border: 3px solid #e5e7eb;
        border-radius: 16px;
        font-size: 1rem;
        font-family: inherit;
        resize: vertical;
        min-height: 120px;
        transition: all 0.3s;
    }

    .notes-textarea:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
    }

    .submit-btn {
        width: 100%;
        padding: 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 16px;
        font-size: 1.2rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .submit-btn:hover:not(:disabled) {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(16,185,129,0.4);
    }

    .submit-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .alert {
        padding: 20px 25px;
        border-radius: 16px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }

    .alert-danger {
        background: #fef2f2;
        color: #dc2626;
        border: 2px solid #fca5a5;
    }
</style>

<div class="booking-container">
    <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Geri Dön
    </a>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <?php foreach ($errors as $error): ?>
                    <div><?= clean($error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Dietitian Header -->
    <div class="dietitian-header">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if ($dietitian['profile_photo']): ?>
                    <?php $photoUrl = '/assets/uploads/' . ltrim($dietitian['profile_photo'], '/'); ?>
                    <img src="<?= clean($photoUrl) ?>" alt="<?= clean($dietitian['full_name']) ?>" class="dietitian-avatar">
                <?php else: ?>
                    <div class="dietitian-avatar-placeholder">
                        <i class="fas fa-user-md"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col">
                <h1 class="mb-2" style="font-weight: 900;"><?= clean($dietitian['full_name']) ?></h1>
                <p class="text-muted mb-2" style="font-size: 1.1rem;"><?= clean($dietitian['title'] ?? 'Diyetisyen') ?></p>
                <?php if ($dietitian['specialization']): ?>
                    <div class="mb-2">
                        <?php foreach (array_slice(explode(',', $dietitian['specialization']), 0, 3) as $spec): ?>
                            <span class="badge bg-light text-dark me-2" style="font-size: 0.9rem; padding: 6px 12px;">
                                <?= clean(trim($spec)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p class="mb-0" style="font-size: 1.1rem;">
                    <strong>İlk Görüşme:</strong> <span class="text-success fw-bold">Ücretsiz</span> |
                    <strong>Takip:</strong> <?= number_format($dietitian['consultation_fee'], 0) ?> ₺
                </p>
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="booking-card">
        <form method="POST" id="bookingForm">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="start_time" id="selectedTime">

            <!-- Tarih Seçimi -->
            <div class="mb-4">
                <h3 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Randevu Tarihi
                </h3>
                <input type="date"
                       name="appointment_date"
                       id="appointmentDate"
                       class="date-input"
                       min="<?= date('Y-m-d') ?>"
                       max="<?= date('Y-m-d', strtotime('+90 days')) ?>"
                       required>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle"></i> En fazla 90 gün sonrasına randevu alabilirsiniz
                </small>
            </div>

            <!-- Saat Seçimi -->
            <div class="slots-container">
                <h3 class="section-title">
                    <i class="fas fa-clock"></i>
                    Müsait Saatler
                </h3>

                <div id="slotsArea">
                    <div class="slots-empty">
                        <i class="fas fa-calendar-day"></i>
                        <h4>Lütfen önce tarih seçin</h4>
                        <p>Seçtiğiniz tarihe göre müsait saatler burada görünecektir</p>
                    </div>
                </div>
            </div>

            <!-- Notlar -->
            <div class="mb-4 mt-4">
                <h3 class="section-title">
                    <i class="fas fa-sticky-note"></i>
                    Görüşmek İstediğiniz Konular <small class="text-muted">(Opsiyonel)</small>
                </h3>
                <textarea name="notes"
                          class="notes-textarea"
                          placeholder="Özel durumlarınız, hedefleriniz, sorularınız varsa yazabilirsiniz..."></textarea>
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn" id="submitBtn" disabled>
                <i class="fas fa-calendar-check"></i>
                Randevuyu Onayla
            </button>
        </form>
    </div>
</div>

<script>
const dietitianId = <?= $dietitianId ?>;
const dateInput = document.getElementById('appointmentDate');
const slotsArea = document.getElementById('slotsArea');
const submitBtn = document.getElementById('submitBtn');
const selectedTimeInput = document.getElementById('selectedTime');
let selectedSlot = null;

// Tarih değiştiğinde saatleri getir
dateInput.addEventListener('change', function() {
    const date = this.value;
    if (!date) return;

    loadAvailableSlots(date);
});

// Müsait saatleri AJAX ile getir
async function loadAvailableSlots(date) {
    slotsArea.innerHTML = `
        <div class="slots-loading">
            <div class="spinner"></div>
            <p style="font-size: 1.1rem; font-weight: 600;">Müsait saatler yükleniyor...</p>
        </div>
    `;

    selectedSlot = null;
    selectedTimeInput.value = '';
    submitBtn.disabled = true;

    try {
        const response = await fetch(`/api/get-available-slots.php?dietitian_id=${dietitianId}&date=${date}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Saatler yüklenemedi');
        }

        const slots = data.slots;

        if (slots.length === 0) {
            slotsArea.innerHTML = `
                <div class="slots-empty">
                    <i class="fas fa-calendar-times"></i>
                    <h4>Bu tarihte müsait saat yok</h4>
                    <p>Lütfen başka bir tarih seçin</p>
                </div>
            `;
            return;
        }

        // Slot butonlarını oluştur
        let slotsHTML = '<div class="slots-grid">';
        slots.forEach(slot => {
            slotsHTML += `
                <button type="button" class="slot-btn" data-time="${slot.time}">
                    <i class="far fa-clock"></i>
                    <strong>${slot.display}</strong>
                </button>
            `;
        });
        slotsHTML += '</div>';

        slotsArea.innerHTML = slotsHTML;

        // Slot click eventleri
        document.querySelectorAll('.slot-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Önceki seçimi kaldır
                document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));

                // Yeni seçim
                this.classList.add('selected');
                selectedSlot = this.dataset.time;
                selectedTimeInput.value = selectedSlot;

                // Submit butonu aktif
                submitBtn.disabled = false;
            });
        });

    } catch (error) {
        console.error('Error loading slots:', error);
        slotsArea.innerHTML = `
            <div class="slots-empty">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                <h4>Hata</h4>
                <p>${error.message}</p>
                <button type="button" onclick="loadAvailableSlots('${date}')"
                        style="margin-top: 15px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Tekrar Dene
                </button>
            </div>
        `;
    }
}

// Form submit kontrolü
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    if (!selectedSlot) {
        e.preventDefault();
        alert('Lütfen bir saat seçin');
        return false;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Randevu oluşturuluyor...';
});
</script>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
