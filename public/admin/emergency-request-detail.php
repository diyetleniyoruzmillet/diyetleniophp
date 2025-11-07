<?php
/**
 * Admin - Emergency Request Detail
 * Acil talep detayı ve yanıtlama
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    header('Location: /login.php');
    exit;
}

$request_id = $_GET['id'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = $db->getConnection();

    $response_message = trim($_POST['response_message'] ?? '');
    $new_status = $_POST['status'] ?? 'responded';
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    $stmt = $conn->prepare("
        UPDATE emergency_consultations
        SET response_message = :response,
            status = :status,
            admin_notes = :notes,
            responded_by = :admin_id,
            responded_at = NOW(),
            updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        'response' => $response_message,
        'status' => $new_status,
        'notes' => $admin_notes,
        'admin_id' => $auth->user()['id'],
        'id' => $request_id
    ]);

    $_SESSION['success_message'] = 'Yanıt başarıyla kaydedildi';
    header('Location: /admin/emergency-requests.php');
    exit;
}

$conn = $db->getConnection();

// Talebi çek
$stmt = $conn->prepare("
    SELECT ec.*,
           u.full_name as user_full_name,
           u.email as user_email,
           u.phone as user_phone,
           resp.full_name as responder_name
    FROM emergency_consultations ec
    LEFT JOIN users u ON ec.user_id = u.id
    LEFT JOIN users resp ON ec.responded_by = resp.id
    WHERE ec.id = :id
");

$stmt->execute(['id' => $request_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: /admin/emergency-requests.php');
    exit;
}

$pageTitle = 'Acil Talep Detayı #' . $request_id;
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    .admin-container {
        max-width: 1000px;
        margin: 100px auto 50px;
        padding: 0 2rem;
    }

    .back-link {
        color: #64748b;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
        font-weight: 600;
    }

    .back-link:hover {
        color: #0f172a;
    }

    .detail-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }

    .detail-header {
        border-bottom: 3px solid #f1f5f9;
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .detail-title {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .badges {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-critical { background: #fee2e2; color: #dc2626; }
    .badge-high { background: #fed7aa; color: #ea580c; }
    .badge-medium { background: #fef3c7; color: #d97706; }
    .badge-low { background: #dbeafe; color: #2563eb; }
    .badge-pending { background: #fef3c7; color: #d97706; }
    .badge-in-progress { background: #dbeafe; color: #2563eb; }
    .badge-responded { background: #d1fae5; color: #059669; }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin: 1.5rem 0;
    }

    .info-item {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 12px;
    }

    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1rem;
        color: #0f172a;
        font-weight: 600;
    }

    .message-box {
        background: #fefce8;
        border: 2px solid #fde047;
        border-radius: 12px;
        padding: 1.5rem;
        margin: 1.5rem 0;
    }

    .message-box h4 {
        font-size: 0.875rem;
        color: #854d0e;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    .message-content {
        color: #0f172a;
        line-height: 1.8;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control, .form-select {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #10b981;
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    textarea.form-control {
        min-height: 150px;
        resize: vertical;
    }

    .btn {
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 1rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .btn-secondary {
        background: #64748b;
        color: white;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #475569;
    }
</style>

<div class="admin-container">
    <a href="/admin/emergency-requests.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Taleplere Dön
    </a>

    <div class="detail-card">
        <div class="detail-header">
            <div class="detail-title">
                <i class="fas fa-ambulance me-3"></i>
                Talep #<?= $request['id'] ?>
            </div>
            <small style="color: #64748b;">
                <i class="far fa-clock me-1"></i>
                <?= date('d F Y, H:i', strtotime($request['created_at'])) ?>
            </small>
            <div class="badges">
                <span class="badge badge-<?= $request['urgency_level'] ?>">
                    <?= strtoupper($request['urgency_level']) ?> ACİLİYET
                </span>
                <span class="badge badge-<?= $request['status'] ?>">
                    <?= strtoupper(str_replace('_', ' ', $request['status'])) ?>
                </span>
            </div>
        </div>

        <!-- Kullanıcı Bilgileri -->
        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fas fa-user me-2"></i>Kullanıcı Bilgileri
        </h3>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Ad Soyad</div>
                <div class="info-value"><?= clean($request['full_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">E-posta</div>
                <div class="info-value">
                    <a href="mailto:<?= $request['email'] ?>" style="color: #10b981;">
                        <?= clean($request['email']) ?>
                    </a>
                </div>
            </div>
            <?php if ($request['phone']): ?>
            <div class="info-item">
                <div class="info-label">Telefon</div>
                <div class="info-value">
                    <a href="tel:<?= $request['phone'] ?>" style="color: #10b981;">
                        <?= clean($request['phone']) ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($request['age']): ?>
            <div class="info-item">
                <div class="info-label">Yaş</div>
                <div class="info-value"><?= $request['age'] ?></div>
            </div>
            <?php endif; ?>
            <?php if ($request['gender']): ?>
            <div class="info-item">
                <div class="info-label">Cinsiyet</div>
                <div class="info-value">
                    <?php
                    $genders = ['male' => 'Erkek', 'female' => 'Kadın', 'other' => 'Diğer'];
                    echo $genders[$request['gender']] ?? $request['gender'];
                    ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($request['height']): ?>
            <div class="info-item">
                <div class="info-label">Boy</div>
                <div class="info-value"><?= $request['height'] ?> cm</div>
            </div>
            <?php endif; ?>
            <?php if ($request['weight']): ?>
            <div class="info-item">
                <div class="info-label">Kilo</div>
                <div class="info-value"><?= $request['weight'] ?> kg</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sağlık Bilgileri -->
        <?php if ($request['health_conditions'] || $request['medications']): ?>
        <h3 style="font-size: 1.25rem; font-weight: 700; margin: 2rem 0 1rem;">
            <i class="fas fa-heartbeat me-2"></i>Sağlık Bilgileri
        </h3>

        <?php if ($request['health_conditions']): ?>
        <div class="info-item" style="margin-bottom: 1rem;">
            <div class="info-label">Sağlık Durumu</div>
            <div class="info-value"><?= nl2br(clean($request['health_conditions'])) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($request['medications']): ?>
        <div class="info-item">
            <div class="info-label">İlaçlar</div>
            <div class="info-value"><?= nl2br(clean($request['medications'])) ?></div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Talep Mesajı -->
        <div class="message-box">
            <h4><i class="fas fa-comment-medical me-2"></i>Talep Mesajı</h4>
            <div class="message-content">
                <?= nl2br(clean($request['message'])) ?>
            </div>
        </div>

        <!-- Mevcut Yanıt -->
        <?php if ($request['response_message']): ?>
        <div class="message-box" style="background: #f0fdf4; border-color: #10b981;">
            <h4 style="color: #059669;">
                <i class="fas fa-reply me-2"></i>
                Verilen Yanıt (<?= clean($request['responder_name']) ?>)
            </h4>
            <div class="message-content">
                <?= nl2br(clean($request['response_message'])) ?>
            </div>
            <small style="color: #64748b; display: block; margin-top: 1rem;">
                <i class="far fa-clock me-1"></i>
                <?= date('d F Y, H:i', strtotime($request['responded_at'])) ?>
            </small>
        </div>
        <?php endif; ?>

        <?php if ($request['admin_notes']): ?>
        <div class="info-item">
            <div class="info-label">Admin Notları</div>
            <div class="info-value"><?= nl2br(clean($request['admin_notes'])) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Yanıt Formu -->
    <div class="detail-card">
        <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">
            <i class="fas fa-reply me-2"></i>
            Yanıt Ver
        </h3>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">
                    Durumu Güncelle
                </label>
                <select name="status" class="form-select">
                    <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                    <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>İşlemde</option>
                    <option value="responded" <?= $request['status'] === 'responded' ? 'selected' : '' ?>>Yanıtlandı</option>
                    <option value="closed" <?= $request['status'] === 'closed' ? 'selected' : '' ?>>Kapatıldı</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Yanıt Mesajı <span style="color: #ef4444;">*</span>
                </label>
                <textarea name="response_message"
                          class="form-control"
                          required
                          placeholder="Kullanıcıya gönderilecek yanıt mesajınızı yazın..."><?= clean($request['response_message'] ?? '') ?></textarea>
                <small style="color: #64748b;">Bu mesaj kullanıcıya e-posta ile gönderilecektir.</small>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Admin Notları (Opsiyonel)
                </label>
                <textarea name="admin_notes"
                          class="form-control"
                          placeholder="Sadece admin panelinde görünecek notlarınızı buraya ekleyin..."><?= clean($request['admin_notes'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>
                    Yanıtı Kaydet & Gönder
                </button>
                <a href="/admin/emergency-requests.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>
                    İptal
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
