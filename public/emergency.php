<?php
/**
 * Diyetlenio - Acil Nöbetçi Diyetisyen
 * 24/7 Acil beslenme danışmanlığı hizmeti
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        // Form verilerini al
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $urgencyLevel = $_POST['urgency_level'] ?? '';
        $situation = trim($_POST['situation'] ?? '');
        $medicalConditions = trim($_POST['medical_conditions'] ?? '');
        $currentSymptoms = trim($_POST['current_symptoms'] ?? '');

        // Validasyon
        $validator = new Validator($_POST);
        $validator
            ->required(['full_name', 'phone', 'urgency_level', 'situation'])
            ->min('full_name', 3)
            ->max('full_name', 100)
            ->phone('phone')
            ->min('situation', 20)
            ->max('situation', 1000)
            ->in('urgency_level', ['low', 'medium', 'high', 'critical']);

        if (!empty($email)) {
            $validator->email('email');
        }

        if ($validator->fails()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        // Rate limiting - 1 başvuru / 10 dakika
        if (empty($errors)) {
            try {
                $rateLimiter = new RateLimiter($db);
                $identifier = $_SERVER['REMOTE_ADDR'];

                if ($rateLimiter->tooManyAttempts('emergency_request', $identifier, 1, 10)) {
                    $errors[] = 'Çok fazla acil başvuru yaptınız. Lütfen 10 dakika bekleyin veya doğrudan arayın: 0850 123 45 67';
                } else {
                    $rateLimiter->hit(hash('sha256', 'emergency_request|' . $identifier), 10);
                }
            } catch (Exception $e) {
                error_log('Rate limiter error: ' . $e->getMessage());
            }
        }

        // Kayıt işlemi
        if (empty($errors)) {
            try {
                $conn = $db->getConnection();

                $stmt = $conn->prepare("
                    INSERT INTO emergency_calls (
                        full_name, phone, email, urgency_level,
                        situation, medical_conditions, current_symptoms,
                        status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");

                $stmt->execute([
                    $fullName,
                    $phone,
                    $email ?: null,
                    $urgencyLevel,
                    $situation,
                    $medicalConditions ?: null,
                    $currentSymptoms ?: null
                ]);

                $success = true;

                // Admin'e email bildirimi gönder (opsiyonel)
                try {
                    Mail::notifyAdminEmergencyCall([
                        'full_name' => $fullName,
                        'phone' => $phone,
                        'urgency_level' => $urgencyLevel,
                        'situation' => substr($situation, 0, 100) . '...'
                    ]);
                } catch (Exception $mailError) {
                    error_log('Emergency email notification error: ' . $mailError->getMessage());
                }

            } catch (Exception $e) {
                error_log('Emergency call error: ' . $e->getMessage());
                $errors[] = 'Başvurunuz kaydedilirken bir hata oluştu. Lütfen doğrudan arayın: 0850 123 45 67';
            }
        }
    }
}

$pageTitle = 'Acil Nöbetçi Diyetisyen';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <meta name="description" content="24/7 Acil beslenme danışmanlığı hizmeti. Acil durumlarda nöbetçi diyetisyenimize hemen ulaşın.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --emergency-red: #dc2626;
            --emergency-orange: #ea580c;
            --emergency-yellow: #ca8a04;
            --primary: #0ea5e9;
            --dark: #0f172a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        .nav-link.active {
            color: var(--emergency-red) !important;
            font-weight: 700;
        }

        /* Emergency Hero */
        .emergency-hero {
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
            color: white;
            padding: 4rem 0 3rem;
            position: relative;
            overflow: hidden;
        }

        .emergency-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .emergency-hero .container {
            position: relative;
            z-index: 1;
        }

        .emergency-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .emergency-badge i {
            animation: pulse-emergency 1.5s infinite;
        }

        @keyframes pulse-emergency {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .emergency-hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .emergency-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .emergency-phone {
            background: white;
            color: var(--emergency-red);
            padding: 1.5rem 2.5rem;
            border-radius: 20px;
            font-size: 2rem;
            font-weight: 800;
            margin-top: 2rem;
            display: inline-block;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }

        .emergency-phone:hover {
            transform: scale(1.05);
        }

        /* Emergency Form Card */
        .emergency-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 3rem;
            margin-top: -3rem;
            position: relative;
            z-index: 2;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--emergency-red);
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        .urgency-select {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .urgency-select option[value="critical"] {
            color: #dc2626;
        }

        .urgency-select option[value="high"] {
            color: #ea580c;
        }

        .urgency-select option[value="medium"] {
            color: #ca8a04;
        }

        .urgency-select option[value="low"] {
            color: #16a34a;
        }

        .btn-emergency {
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
            border: none;
            color: white;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
        }

        .btn-emergency:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(220, 38, 38, 0.6);
            color: white;
        }

        /* Info Cards */
        .info-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 5px solid #ca8a04;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card h5 {
            font-weight: 700;
            color: #92400e;
            margin-bottom: 1rem;
        }

        .info-card ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .info-card li {
            color: #78350f;
            margin-bottom: 0.5rem;
        }

        /* Success Message */
        .success-animation {
            text-align: center;
            animation: fadeInUp 0.6s ease;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: bounceIn 0.6s ease;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .success-icon i {
            font-size: 3rem;
            color: white;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Response Time */
        .response-time {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-top: 2rem;
        }

        .response-time h4 {
            color: #1e40af;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .response-time p {
            color: #1e3a8a;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Emergency Hero -->
    <div class="emergency-hero">
        <div class="container text-center">
            <div class="emergency-badge">
                <i class="fas fa-siren-on"></i>
                <span>7/24 ACİL HİZMET</span>
            </div>
            <h1>Acil Nöbetçi Diyetisyen</h1>
            <p>Acil beslenme danışmanlığına ihtiyacınız mı var?<br>Nöbetçi diyetisyenimiz size yardımcı olmak için burada.</p>
            <a href="tel:+908501234567" class="emergency-phone">
                <i class="fas fa-phone-alt me-2"></i>0850 123 45 67
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($success): ?>
                    <!-- Success Message -->
                    <div class="emergency-card">
                        <div class="success-animation">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h3 class="mb-3">Başvurunuz Alındı!</h3>
                            <p class="lead mb-4">Nöbetçi diyetisyenimiz en kısa sürede sizinle iletişime geçecektir.</p>

                            <div class="response-time">
                                <h4><i class="fas fa-clock me-2"></i>Tahmini Yanıt Süresi</h4>
                                <p>Aciliyete göre 5-30 dakika içinde dönüş yapılacaktır.</p>
                            </div>

                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Lütfen telefonunuzu açık tutun.</strong> Diyetisyenimiz sizi arayacaktır.
                            </div>

                            <a href="/" class="btn btn-primary mt-3">
                                <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Emergency Form -->
                    <div class="emergency-card">
                        <h2 class="text-center mb-4">
                            <i class="fas fa-file-medical me-2 text-danger"></i>
                            Acil Başvuru Formu
                        </h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <strong><i class="fas fa-exclamation-circle me-2"></i>Hata:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= clean($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="info-card">
                            <h5><i class="fas fa-info-circle me-2"></i>Hangi Durumlarda Acil Başvuru Yapabilirsiniz?</h5>
                            <ul>
                                <li>Ani gıda alerjisi veya besin zehirlenmesi şüphesi</li>
                                <li>Diyabet krizi (ani kan şekeri düşmesi/yükselmesi)</li>
                                <li>Hamilelik döneminde beslenme acilleri</li>
                                <li>Yoğun spor sonrası beslenme sorunu</li>
                                <li>Acil diyet değişikliği gerektiren durumlar</li>
                            </ul>
                        </div>

                        <form method="POST" action="/emergency.php">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                            <div class="mb-3">
                                <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?= clean($_POST['full_name'] ?? '') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefon <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control"
                                           value="<?= clean($_POST['phone'] ?? '') ?>" required
                                           placeholder="0555 123 45 67">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email (Opsiyonel)</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?= clean($_POST['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Aciliyet Durumu <span class="text-danger">*</span></label>
                                <select name="urgency_level" class="form-select urgency-select" required>
                                    <option value="">Seçiniz</option>
                                    <option value="critical" <?= ($_POST['urgency_level'] ?? '') === 'critical' ? 'selected' : '' ?>>
                                        🔴 KRİTİK - Acil müdahale gerekiyor
                                    </option>
                                    <option value="high" <?= ($_POST['urgency_level'] ?? '') === 'high' ? 'selected' : '' ?>>
                                        🟠 YÜKSEK - En kısa sürede görüşme gerekiyor
                                    </option>
                                    <option value="medium" <?= ($_POST['urgency_level'] ?? '') === 'medium' ? 'selected' : '' ?>>
                                        🟡 ORTA - Bugün içinde görüşme gerekiyor
                                    </option>
                                    <option value="low" <?= ($_POST['urgency_level'] ?? '') === 'low' ? 'selected' : '' ?>>
                                        🟢 DÜŞÜK - Bilgi almak istiyorum
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Durum Açıklaması <span class="text-danger">*</span></label>
                                <textarea name="situation" class="form-control" rows="4" required
                                          placeholder="Lütfen durumunuzu detaylı bir şekilde açıklayın..."><?= clean($_POST['situation'] ?? '') ?></textarea>
                                <small class="text-muted">En az 20 karakter</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mevcut Sağlık Durumu / Hastalıklar (Opsiyonel)</label>
                                <textarea name="medical_conditions" class="form-control" rows="2"
                                          placeholder="Diyabet, kalp hastalığı, tansiyon, alerji vb."><?= clean($_POST['medical_conditions'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Şu Anki Belirtiler (Opsiyonel)</label>
                                <textarea name="current_symptoms" class="form-control" rows="2"
                                          placeholder="Bulantı, baş ağrısı, titreme, terleme vb."><?= clean($_POST['current_symptoms'] ?? '') ?></textarea>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Önemli:</strong> Hayati tehlike durumunda lütfen <strong>112</strong> numaralı telefonu arayın!
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-emergency">
                                    <i class="fas fa-paper-plane me-2"></i>Acil Başvuru Gönder
                                </button>
                            </div>
                        </form>

                        <div class="response-time mt-4">
                            <h4><i class="fas fa-clock me-2"></i>Yanıt Süremiz</h4>
                            <p>Kritik durumlar: 5-10 dakika | Yüksek öncelik: 10-20 dakika | Orta/Düşük: 20-30 dakika</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="height: 4rem;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
