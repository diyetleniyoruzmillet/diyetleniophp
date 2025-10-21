<?php
/**
 * Diyetlenio - Ödeme Dekontu Yükle
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece client erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Randevu ID
$appointmentId = (int) ($_GET['appointment_id'] ?? 0);

// Randevu kontrolü
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as dietitian_name, dp.consultation_fee
    FROM appointments a
    INNER JOIN users u ON a.dietitian_id = u.id
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE a.id = ? AND a.client_id = ?
");
$stmt->execute([$appointmentId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/client/appointments.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        // Dosya yükleme
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['receipt'];
            $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedTypes)) {
                $errors[] = 'Geçersiz dosya tipi. İzin verilenler: JPG, PNG, PDF';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'Dosya boyutu çok büyük. Maksimum 5MB';
            } else {
                $filename = 'payment_' . $appointmentId . '_' . time() . '.' . $ext;
                $uploadPath = ROOT_DIR . '/assets/uploads/payments/';
                
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                    // Ödeme kaydı oluştur
                    try {
                        $stmt = $conn->prepare("
                            INSERT INTO payments (
                                appointment_id, client_id, dietitian_id, amount,
                                receipt_file, status, created_at
                            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                        ");

                        $stmt->execute([
                            $appointmentId,
                            $userId,
                            $appointment['dietitian_id'],
                            $appointment['consultation_fee'],
                            $filename
                        ]);

                        // Randevuyu is_paid = 1 yap
                        $updateStmt = $conn->prepare("UPDATE appointments SET is_paid = 1 WHERE id = ?");
                        $updateStmt->execute([$appointmentId]);

                        $success = true;
                        setFlash('success', 'Ödeme dekontunuz başarıyla yüklendi. Onay bekliyor.');
                    } catch (Exception $e) {
                        error_log('Payment upload error: ' . $e->getMessage());
                        $errors[] = 'Ödeme kaydı oluşturulurken hata oluştu.';
                    }
                } else {
                    $errors[] = 'Dosya yüklenirken hata oluştu.';
                }
            }
        } else {
            $errors[] = 'Lütfen ödeme dekontunu yükleyin.';
        }
    }
}

$pageTitle = 'Ödeme Dekontu Yükle';
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
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .container { max-width: 700px; margin-top: 50px; }
        .card-custom { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 3px 15px rgba(0,0,0,0.1); }
        .upload-area { border: 3px dashed #11998e; border-radius: 15px; padding: 40px; text-align: center; background: #e6fffa; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { background: #b2f5ea; }
        .btn-submit { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 40px; border: none; border-radius: 10px; font-weight: 600; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/client/appointments.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-2"></i>Geri Dön
        </a>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                Ödeme dekontunuz başarıyla yüklendi! Onaylandığında bildirim alacaksınız.
            </div>
            <div class="text-center">
                <a href="/client/appointments.php" class="btn btn-success">Randevularıma Dön</a>
            </div>
        <?php else: ?>
            <div class="card-custom">
                <h3 class="mb-4">Ödeme Dekontu Yükle</h3>

                <div class="mb-4">
                    <p><strong>Diyetisyen:</strong> <?= clean($appointment['dietitian_name']) ?></p>
                    <p><strong>Randevu Tarihi:</strong> <?= formatDate($appointment['appointment_date']) ?> <?= substr($appointment['start_time'], 0, 5) ?></p>
                    <p><strong>Tutar:</strong> <span class="text-success fs-4"><?= number_format($appointment['consultation_fee'], 2) ?> ₺</span></p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?= clean($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="upload-area mb-4" onclick="document.getElementById('receipt').click()">
                        <i class="fas fa-cloud-upload-alt fa-4x text-success mb-3"></i>
                        <p class="mb-0"><strong>Dekont Dosyasını Seç</strong></p>
                        <p class="text-muted">JPG, PNG veya PDF (Maks. 5MB)</p>
                        <input type="file" name="receipt" id="receipt" accept=".jpg,.jpeg,.png,.pdf" style="display: none;" required onchange="showFileName(this)">
                        <p id="filename" class="mt-2 text-success fw-bold"></p>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-upload me-2"></i>Dekontu Yükle
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('filename').textContent = input.files[0].name;
            }
        }
    </script>
</body>
</html>
