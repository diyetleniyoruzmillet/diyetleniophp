<?php
/**
 * Acil Diyetisyen Danışma
 * Acil durumlarda admin'e direkt mesaj gönderme
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();
$errors = [];
$success = false;

// Giriş yapmış kullanıcı varsa bilgilerini al
$loggedInUser = $auth->check() ? $auth->user() : null;

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        try {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $age = (int) ($_POST['age'] ?? 0);
            $gender = $_POST['gender'] ?? null;
            $height = !empty($_POST['height']) ? (float) $_POST['height'] : null;
            $weight = !empty($_POST['weight']) ? (float) $_POST['weight'] : null;
            $healthConditions = trim($_POST['health_conditions'] ?? '');
            $medications = trim($_POST['medications'] ?? '');
            $urgencyLevel = $_POST['urgency_level'] ?? 'medium';
            $message = trim($_POST['message'] ?? '');

            // Validasyon
            if (empty($fullName)) throw new Exception('Ad Soyad zorunludur');
            if (empty($email)) throw new Exception('E-posta zorunludur');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Geçerli bir e-posta adresi girin');
            if (empty($message)) throw new Exception('Mesaj zorunludur');
            if (strlen($message) < 20) throw new Exception('Mesaj en az 20 karakter olmalıdır');

            // Veritabanına kaydet
            $stmt = $conn->prepare("
                INSERT INTO emergency_consultations (
                    user_id, full_name, email, phone, age, gender, height, weight,
                    health_conditions, medications, urgency_level, message, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $userId = $loggedInUser ? $loggedInUser->getId() : null;

            $stmt->execute([
                $userId,
                $fullName,
                $email,
                $phone,
                $age > 0 ? $age : null,
                $gender,
                $height,
                $weight,
                $healthConditions,
                $medications,
                $urgencyLevel,
                $message
            ]);

            $requestId = $conn->lastInsertId();

            // Admin'e e-posta gönder
            try {
                $mailer = new Mailer();
                $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@diyetlenio.com';

                $urgencyLabels = [
                    'low' => 'Düşük',
                    'medium' => 'Orta',
                    'high' => 'Yüksek',
                    'critical' => 'Kritik'
                ];

                $emailBody = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                        .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); padding: 30px; text-align: center; color: white; }
                        .content { padding: 30px; }
                        .urgency-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 10px 0; }
                        .urgency-critical { background: #dc2626; color: white; }
                        .urgency-high { background: #f97316; color: white; }
                        .urgency-medium { background: #f59e0b; color: white; }
                        .urgency-low { background: #10b981; color: white; }
                        .info-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
                        .info-row { margin: 10px 0; }
                        .info-label { font-weight: bold; color: #374151; }
                        .message-box { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
                        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🚨 Acil Diyetisyen Talebi</h1>
                            <p>Yeni bir acil danışma talebi geldi</p>
                        </div>
                        <div class='content'>
                            <p><strong>Talep #" . $requestId . "</strong></p>
                            <span class='urgency-badge urgency-" . $urgencyLevel . "'>
                                Aciliyet: " . $urgencyLabels[$urgencyLevel] . "
                            </span>

                            <div class='info-box'>
                                <h3 style='margin-top: 0;'>👤 Kişi Bilgileri</h3>
                                <div class='info-row'><span class='info-label'>Ad Soyad:</span> {$fullName}</div>
                                <div class='info-row'><span class='info-label'>E-posta:</span> {$email}</div>
                                <div class='info-row'><span class='info-label'>Telefon:</span> " . ($phone ?: '-') . "</div>
                                <div class='info-row'><span class='info-label'>Yaş:</span> " . ($age > 0 ? $age : '-') . "</div>
                                <div class='info-row'><span class='info-label'>Boy:</span> " . ($height ? $height . ' cm' : '-') . "</div>
                                <div class='info-row'><span class='info-label'>Kilo:</span> " . ($weight ? $weight . ' kg' : '-') . "</div>
                            </div>

                            " . ($healthConditions ? "
                            <div class='info-box'>
                                <h3 style='margin-top: 0;'>🏥 Sağlık Durumu</h3>
                                <p>" . nl2br(htmlspecialchars($healthConditions)) . "</p>
                            </div>
                            " : "") . "

                            " . ($medications ? "
                            <div class='info-box'>
                                <h3 style='margin-top: 0;'>💊 Kullanılan İlaçlar</h3>
                                <p>" . nl2br(htmlspecialchars($medications)) . "</p>
                            </div>
                            " : "") . "

                            <div class='message-box'>
                                <h3 style='margin-top: 0;'>📝 Talep Mesajı</h3>
                                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                            </div>

                            <p style='text-align: center;'>
                                <a href='" . BASE_URL . "/admin/emergency-requests.php' class='button'>
                                    Talepleri Görüntüle
                                </a>
                            </p>

                            <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                                <strong>Not:</strong> Bu talep acil olarak işaretlenmiştir. Lütfen en kısa sürede yanıt verin.
                            </p>
                        </div>
                        <div class='footer'>
                            © " . date('Y') . " Diyetlenio. Tüm hakları saklıdır.<br>
                            Talep Tarihi: " . date('d.m.Y H:i') . "
                        </div>
                    </div>
                </body>
                </html>
                ";

                $mailer->send($adminEmail, '🚨 Acil Diyetisyen Talebi - ' . $urgencyLabels[$urgencyLevel], $emailBody);

            } catch (Exception $e) {
                error_log('Emergency request email error: ' . $e->getMessage());
                // E-posta hatası olsa bile form başarılı sayılır
            }

            $success = true;
            setFlash('success', 'Acil talebiniz başarıyla gönderildi! En kısa sürede sizinle iletişime geçeceğiz.');
            redirect('/');

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Acil Diyetisyen Danışma';
$showNavbar = true;
include __DIR__ . '/../includes/partials/header.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
        min-height: 100vh;
        font-family: 'Inter', -apple-system, sans-serif;
    }

    .emergency-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .emergency-header {
        background: white;
        padding: 40px;
        border-radius: 24px 24px 0 0;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .emergency-icon {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 3rem;
        color: white;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
        50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(220, 38, 38, 0); }
    }

    .emergency-header h1 {
        font-size: 2.5rem;
        font-weight: 900;
        background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 10px;
    }

    .emergency-header p {
        color: #6b7280;
        font-size: 1.1rem;
        margin: 0;
    }

    .emergency-form {
        background: white;
        padding: 40px;
        border-radius: 0 0 24px 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }

    .section-title {
        font-size: 1.3rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 10px;
        border-bottom: 3px solid #f3f4f6;
    }

    .section-title i {
        color: #dc2626;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        font-weight: 700;
        color: #374151;
        margin-bottom: 8px;
        display: block;
        font-size: 0.95rem;
    }

    .form-label.required::after {
        content: ' *';
        color: #dc2626;
    }

    .form-control, .form-select {
        padding: 14px 18px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s;
        width: 100%;
    }

    .form-control:focus, .form-select:focus {
        border-color: #dc2626;
        outline: none;
        box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
    }

    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .urgency-selector {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 10px;
    }

    .urgency-option {
        position: relative;
    }

    .urgency-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .urgency-option label {
        display: block;
        padding: 18px;
        border: 3px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        font-weight: 700;
    }

    .urgency-option input:checked + label {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .urgency-low input:checked + label { border-color: #10b981; background: #d1fae5; color: #065f46; }
    .urgency-medium input:checked + label { border-color: #f59e0b; background: #fef3c7; color: #92400e; }
    .urgency-high input:checked + label { border-color: #f97316; background: #ffedd5; color: #9a3412; }
    .urgency-critical input:checked + label { border-color: #dc2626; background: #fee2e2; color: #991b1b; }

    .info-alert {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: start;
        gap: 15px;
    }

    .info-alert i {
        font-size: 1.5rem;
        margin-top: 2px;
    }

    .warning-alert {
        background: #fff3cd;
        color: #92400e;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        border-left: 4px solid #f59e0b;
    }

    .submit-btn {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1.2rem;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(220, 38, 38, 0.4);
    }

    .alert {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #dc2626;
    }

    @media (max-width: 768px) {
        .urgency-selector {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="emergency-container">
    <div class="emergency-header">
        <div class="emergency-icon">
            <i class="fas fa-heartbeat"></i>
        </div>
        <h1>Acil Diyetisyen Danışma</h1>
        <p>Acil durumlarınızda size yardımcı olmak için buradayız</p>
    </div>

    <div class="emergency-form">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php foreach ($errors as $error): ?>
                    <div><?= clean($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="info-alert">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Acil Danışma Hakkında:</strong><br>
                Bu form acil sağlık durumları için değildir. Hayati tehlike durumunda 112'yi arayın.
                Diyetisyen danışmanlığı gerektiren acil durumlarınız için bu formu kullanabilirsiniz.
                Talebiniz direkt olarak admin ekibimize ulaşacak ve en kısa sürede size dönüş yapılacaktır.
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Kişisel Bilgiler -->
            <div class="section-title">
                <i class="fas fa-user"></i>
                Kişisel Bilgiler
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label required">Ad Soyad</label>
                        <input type="text"
                               name="full_name"
                               class="form-control"
                               value="<?= $loggedInUser ? clean($loggedInUser->getFullName()) : '' ?>"
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label required">E-posta</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="<?= $loggedInUser ? clean($loggedInUser->getEmail()) : '' ?>"
                               required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel"
                               name="phone"
                               class="form-control"
                               placeholder="5XX XXX XX XX">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Yaş</label>
                        <input type="number"
                               name="age"
                               class="form-control"
                               min="1"
                               max="120">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Cinsiyet</label>
                        <select name="gender" class="form-select">
                            <option value="">Seçiniz</option>
                            <option value="male">Erkek</option>
                            <option value="female">Kadın</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Boy (cm)</label>
                        <input type="number"
                               name="height"
                               class="form-control"
                               step="0.1"
                               min="50"
                               max="250"
                               placeholder="170">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Kilo (kg)</label>
                        <input type="number"
                               name="weight"
                               class="form-control"
                               step="0.1"
                               min="20"
                               max="300"
                               placeholder="70">
                    </div>
                </div>
            </div>

            <!-- Sağlık Bilgileri -->
            <div class="section-title mt-4">
                <i class="fas fa-notes-medical"></i>
                Sağlık Bilgileri
            </div>

            <div class="form-group">
                <label class="form-label">Sağlık Durumu ve Hastalıklar</label>
                <textarea name="health_conditions"
                          class="form-control"
                          placeholder="Kronik hastalıklar, alerjiler, geçirdiğiniz ameliyatlar vb."></textarea>
                <small class="text-muted">Varsa belirtiniz, yoksa boş bırakabilirsiniz</small>
            </div>

            <div class="form-group">
                <label class="form-label">Kullandığınız İlaçlar</label>
                <textarea name="medications"
                          class="form-control"
                          placeholder="Düzenli kullandığınız ilaçlar, vitaminler, takviyeler"></textarea>
                <small class="text-muted">Varsa belirtiniz, yoksa boş bırakabilirsiniz</small>
            </div>

            <!-- Talep Detayları -->
            <div class="section-title mt-4">
                <i class="fas fa-clipboard-list"></i>
                Talep Detayları
            </div>

            <div class="form-group">
                <label class="form-label">Aciliyet Durumu</label>
                <div class="urgency-selector">
                    <div class="urgency-option urgency-low">
                        <input type="radio" name="urgency_level" value="low" id="urgency-low">
                        <label for="urgency-low">
                            <i class="fas fa-check-circle"></i><br>
                            Düşük<br>
                            <small>Genel danışma</small>
                        </label>
                    </div>
                    <div class="urgency-option urgency-medium">
                        <input type="radio" name="urgency_level" value="medium" id="urgency-medium" checked>
                        <label for="urgency-medium">
                            <i class="fas fa-exclamation-circle"></i><br>
                            Orta<br>
                            <small>Birkaç gün içinde</small>
                        </label>
                    </div>
                    <div class="urgency-option urgency-high">
                        <input type="radio" name="urgency_level" value="high" id="urgency-high">
                        <label for="urgency-high">
                            <i class="fas fa-exclamation-triangle"></i><br>
                            Yüksek<br>
                            <small>Bugün içinde</small>
                        </label>
                    </div>
                    <div class="urgency-option urgency-critical">
                        <input type="radio" name="urgency_level" value="critical" id="urgency-critical">
                        <label for="urgency-critical">
                            <i class="fas fa-heartbeat"></i><br>
                            Kritik<br>
                            <small>Hemen gerekli</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">Talebinizi Açıklayın</label>
                <textarea name="message"
                          class="form-control"
                          required
                          minlength="20"
                          placeholder="Durumunuzu detaylı olarak açıklayın. Ne tür bir yardıma ihtiyacınız var? Şikayetleriniz neler?"
                          style="min-height: 180px;"></textarea>
                <small class="text-muted">Minimum 20 karakter</small>
            </div>

            <div class="warning-alert">
                <i class="fas fa-shield-alt me-2"></i>
                <strong>Gizlilik:</strong> Paylaştığınız tüm bilgiler gizli tutulacak ve sadece yetkilendirilmiş ekip üyelerimiz tarafından görüntülenecektir.
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i>
                Acil Talebimi Gönder
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
