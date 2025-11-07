<?php
/**
 * Dietitian Availability Management
 * Müsaitlik takvimi yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Dietitian kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    header('Location: /login.php');
    exit;
}

$user = $auth->user();
$conn = $db->getConnection();
$dietitianId = $auth->id();

// Kaydetme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_availability'])) {
    $availabilityData = $_POST['availability'] ?? [];

    // Mevcut müsaitlikleri temizle
    $stmt = $conn->prepare("DELETE FROM dietitian_availability WHERE dietitian_id = ?");
    $stmt->execute([$dietitianId]);

    // Yeni müsaitlikleri kaydet
    foreach ($availabilityData as $day => $times) {
        if (isset($times['enabled']) && $times['enabled'] === '1') {
            $startTime = $times['start_time'] ?? '09:00';
            $endTime = $times['end_time'] ?? '17:00';

            $stmt = $conn->prepare("
                INSERT INTO dietitian_availability (dietitian_id, day_of_week, start_time, end_time, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$dietitianId, $day, $startTime, $endTime]);
        }
    }

    $success = "Müsaitlik takviminiz başarıyla güncellendi!";
}

// Mevcut müsaitlikleri çek
$stmt = $conn->prepare("
    SELECT * FROM dietitian_availability
    WHERE dietitian_id = ?
    ORDER BY
        FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
");
$stmt->execute([$dietitianId]);
$availabilities = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

// Günleri Türkçeleştir
$daysMap = [
    'monday' => 'Pazartesi',
    'tuesday' => 'Salı',
    'wednesday' => 'Çarşamba',
    'thursday' => 'Perşembe',
    'friday' => 'Cuma',
    'saturday' => 'Cumartesi',
    'sunday' => 'Pazar'
];

$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

$pageTitle = 'Müsaitlik Takvimi';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    body { background: #f8fafc; }
    .container { max-width: 900px; margin: 100px auto 50px; padding: 0 2rem; }

    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 1.1rem;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        font-weight: 600;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: #d1fae5;
        color: #059669;
        border-left: 4px solid #10b981;
    }

    .info-card {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .info-card i {
        font-size: 2rem;
    }

    .availability-form {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .day-row {
        border-bottom: 2px solid #f1f5f9;
        padding: 1.5rem 0;
        transition: all 0.2s;
    }

    .day-row:last-child {
        border-bottom: none;
    }

    .day-row:hover {
        background: #f8fafc;
        padding-left: 1rem;
        padding-right: 1rem;
        margin-left: -1rem;
        margin-right: -1rem;
        border-radius: 12px;
    }

    .day-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .day-toggle {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
    }

    .toggle-switch {
        position: relative;
        width: 50px;
        height: 26px;
        background: #e2e8f0;
        border-radius: 50px;
        transition: all 0.3s;
    }

    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        top: 3px;
        left: 3px;
        transition: all 0.3s;
    }

    input[type="checkbox"]:checked + .toggle-switch {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    input[type="checkbox"]:checked + .toggle-switch::after {
        left: 27px;
    }

    input[type="checkbox"] {
        display: none;
    }

    .day-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
        flex: 1;
    }

    .time-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: all 0.3s;
    }

    .time-inputs.active {
        max-height: 200px;
        opacity: 1;
        margin-top: 1rem;
    }

    .time-group label {
        display: block;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .time-input {
        width: 100%;
        padding: 0.875rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        color: #0f172a;
        transition: all 0.2s;
    }

    .time-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    .btn-save {
        width: 100%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 2rem;
        transition: all 0.3s;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .btn-cancel {
        width: 100%;
        background: #f1f5f9;
        color: #64748b;
        border: none;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    @media (max-width: 768px) {
        .page-title {
            font-size: 2rem;
        }

        .time-inputs {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-calendar-check"></i>
            Müsaitlik Takvimi
        </h1>
        <p class="page-subtitle">Haftalık çalışma saatlerinizi ayarlayın</p>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <?= $success ?>
    </div>
    <?php endif; ?>

    <div class="info-card">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Bilgi:</strong> Müsaitlik saatlerinizi günlük olarak belirleyin.
            Danışanlar sadece müsait olduğunuz saatlerde randevu alabilecektir.
        </div>
    </div>

    <form method="POST" class="availability-form">
        <?php foreach ($days as $day):
            $dayData = $availabilities[$day][0] ?? null;
            $isEnabled = !empty($dayData);
            $startTime = $dayData['start_time'] ?? '09:00';
            $endTime = $dayData['end_time'] ?? '17:00';
        ?>
        <div class="day-row">
            <div class="day-header">
                <label class="day-toggle">
                    <input type="checkbox"
                           name="availability[<?= $day ?>][enabled]"
                           value="1"
                           onchange="toggleTimeInputs(this, '<?= $day ?>')"
                           <?= $isEnabled ? 'checked' : '' ?>>
                    <span class="toggle-switch"></span>
                </label>
                <div class="day-name"><?= $daysMap[$day] ?></div>
            </div>

            <div class="time-inputs <?= $isEnabled ? 'active' : '' ?>" id="times-<?= $day ?>">
                <div class="time-group">
                    <label for="start-<?= $day ?>">Başlangıç Saati</label>
                    <input type="time"
                           id="start-<?= $day ?>"
                           name="availability[<?= $day ?>][start_time]"
                           class="time-input"
                           value="<?= $startTime ?>"
                           <?= !$isEnabled ? 'disabled' : '' ?>>
                </div>
                <div class="time-group">
                    <label for="end-<?= $day ?>">Bitiş Saati</label>
                    <input type="time"
                           id="end-<?= $day ?>"
                           name="availability[<?= $day ?>][end_time]"
                           class="time-input"
                           value="<?= $endTime ?>"
                           <?= !$isEnabled ? 'disabled' : '' ?>>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" name="save_availability" class="btn-save">
            <i class="fas fa-save me-2"></i>
            Kaydet
        </button>
        <a href="/dietitian/dashboard.php" class="btn-cancel">
            <i class="fas fa-arrow-left me-1"></i>
            İptal Et
        </a>
    </form>
</div>

<script>
function toggleTimeInputs(checkbox, day) {
    const timesDiv = document.getElementById('times-' + day);
    const inputs = timesDiv.querySelectorAll('input');

    if (checkbox.checked) {
        timesDiv.classList.add('active');
        inputs.forEach(input => input.disabled = false);
    } else {
        timesDiv.classList.remove('active');
        inputs.forEach(input => input.disabled = true);
    }
}
</script>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
