<?php
/**
 * Diyetlenio - Müsaitlik Takvimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || !in_array($auth->user()->getUserType(), ['dietitian', 'admin'])) {
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

$success = false;
$errors = [];

// Müsait saatleri kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        try {
            // Önce mevcut müsaitlikleri sil
            $conn->prepare("DELETE FROM availability WHERE dietitian_id = ?")->execute([$userId]);

            // Yeni müsaitlikleri ekle
            foreach ($_POST['availability'] ?? [] as $day => $slots) {
                foreach ($slots as $slot) {
                    if (!empty($slot['start']) && !empty($slot['end'])) {
                        $stmt = $conn->prepare("
                            INSERT INTO availability (dietitian_id, day_of_week, start_time, end_time, is_active)
                            VALUES (?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([$userId, $day, $slot['start'], $slot['end']]);
                    }
                }
            }
            $success = true;
        } catch (Exception $e) {
            error_log('Availability save error: ' . $e->getMessage());
            $errors[] = 'Kayıt sırasında hata oluştu.';
        }
    }
}

// Mevcut müsaitlikleri çek
$stmt = $conn->prepare("SELECT * FROM availability WHERE dietitian_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$userId]);
$availability = $stmt->fetchAll();

$days = [
    0 => 'Pazar', 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba',
    4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi'
];

$pageTitle = 'Müsaitlik Takvimi';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<h2 class="mb-4">Müsaitlik Takvimi</h2>

<?php if ($success): ?>
    <div class="alert alert-success">Müsaitlik takviminiz güncellendi!</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?= clean($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <?php foreach ($days as $dayNum => $dayName): ?>
                <div class="mb-4 border-bottom pb-3">
                    <h5><?= $dayName ?></h5>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small">Başlangıç</label>
                            <input type="time" name="availability[<?= $dayNum ?>][0][start]" class="form-control" value="09:00">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">Bitiş</label>
                            <input type="time" name="availability[<?= $dayNum ?>][0][end]" class="form-control" value="17:00">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-2"></i>Kaydet
            </button>
        </form>
    </div>
</div>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
