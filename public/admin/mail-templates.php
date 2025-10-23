<?php
/**
 * Diyetlenio - Admin Mail Template Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Mail template'lerini tanımla (hardcoded ama düzenlenebilir)
$templates = [
    'password_reset' => [
        'name' => 'Şifre Sıfırlama',
        'subject' => 'Şifre Sıfırlama - Diyetlenio',
        'variables' => ['{FIRST_NAME}', '{RESET_LINK}'],
        'description' => 'Kullanıcı şifre sıfırlama talep ettiğinde gönderilir'
    ],
    'contact_notification' => [
        'name' => 'İletişim Formu Bildirimi',
        'subject' => 'Yeni İletişim Formu Mesajı',
        'variables' => ['{NAME}', '{EMAIL}', '{PHONE}', '{SUBJECT}', '{MESSAGE}'],
        'description' => 'İletişim formundan mesaj geldiğinde admin\'e gönderilir'
    ],
    'appointment_confirmation' => [
        'name' => 'Randevu Onayı',
        'subject' => 'Randevunuz Onaylandı - Diyetlenio',
        'variables' => ['{CLIENT_NAME}', '{DIETITIAN_NAME}', '{DATE}', '{TIME}'],
        'description' => 'Randevu oluşturulduğunda danışana gönderilir'
    ],
    'dietitian_verification' => [
        'name' => 'Diyetisyen Email Doğrulama',
        'subject' => 'Email Doğrulama - Diyetlenio',
        'variables' => ['{FIRST_NAME}', '{VERIFICATION_LINK}'],
        'description' => 'Diyetisyen kayıt olduğunda email doğrulama için gönderilir'
    ],
    'dietitian_approval' => [
        'name' => 'Diyetisyen Onay Bildirimi',
        'subject' => 'Başvurunuz Onaylandı - Diyetlenio',
        'variables' => ['{FIRST_NAME}', '{LOGIN_URL}'],
        'description' => 'Diyetisyen başvurusu onaylandığında gönderilir'
    ],
    'admin_new_dietitian' => [
        'name' => 'Admin - Yeni Diyetisyen Başvurusu',
        'subject' => 'Yeni Diyetisyen Başvurusu',
        'variables' => ['{FULL_NAME}', '{EMAIL}', '{PHONE}', '{TITLE}', '{EXPERIENCE}', '{SPECIALIZATION}', '{ADMIN_PANEL_URL}'],
        'description' => 'Yeni diyetisyen başvurusu geldiğinde admin\'e gönderilir'
    ]
];

$pageTitle = 'Mail Template Yönetimi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-admin.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            min-height: 100vh;
        }

        .page-header-custom {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header-custom h1 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .page-header-custom p {
            color: #64748b;
            font-size: 1rem;
            margin: 0;
        }

        .template-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .template-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            transition: width 0.3s ease;
        }

        .template-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
            border-color: #56ab2f;
        }

        .template-card:hover::before {
            width: 8px;
        }

        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .template-name {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .template-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .template-subject {
            font-size: 0.95rem;
            color: #56ab2f;
            font-weight: 600;
            margin-bottom: 12px;
            padding: 8px 16px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            display: inline-block;
        }

        .template-description {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .variables-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 16px;
            border-radius: 12px;
            margin-top: 16px;
            border: 1px solid #e2e8f0;
        }

        .variables-section-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .variable-tag {
            display: inline-block;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-family: 'Courier New', monospace;
            margin: 4px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
            transition: all 0.2s ease;
        }

        .variable-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-view {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-view:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .alert-info-custom {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            border: none;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .alert-info-custom i {
            font-size: 1.2rem;
        }

        .alert-info-custom strong {
            font-size: 1.05rem;
        }

        .alert-info-custom code {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        .template-count {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .template-count h2 {
            font-size: 3rem;
            font-weight: 800;
            margin: 0;
        }

        .template-count p {
            margin: 8px 0 0 0;
            font-size: 1rem;
            opacity: 0.95;
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 24px;
        }

        .modal-header-custom h5 {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .modal-body-custom {
            padding: 28px;
        }

        .info-group {
            margin-bottom: 24px;
        }

        .info-label {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .info-label i {
            color: #56ab2f;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="page-header-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1>
                            <i class="fas fa-envelope me-3"></i><?= clean($pageTitle) ?>
                        </h1>
                        <p>Mail şablonlarını görüntüleyin ve yönetin</p>
                    </div>
                    <a href="/admin/dashboard.php" class="btn btn-view">
                        <i class="fas fa-arrow-left me-2"></i>Dashboard
                    </a>
                </div>
            </div>

            <!-- Template Count -->
            <div class="template-count">
                <h2><?= count($templates) ?></h2>
                <p><i class="fas fa-envelope me-2"></i>Toplam Mail Şablonu</p>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-info-custom">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Bilgi:</strong> Mail şablonları şu anda kod içinde tanımlıdır.
                Değişiklik yapmak için <code>/classes/Mail.php</code> dosyasını düzenleyin.
                Gelecek versiyonda database tabanlı şablon sistemi eklenecektir.
            </div>

            <!-- Templates List -->
            <div class="row">
                <div class="col-12">
                    <?php foreach ($templates as $key => $template): ?>
                        <div class="template-card">
                            <div class="template-header">
                                <div class="d-flex align-items-center">
                                    <div class="template-icon">
                                        <i class="fas fa-envelope-open-text"></i>
                                    </div>
                                    <div>
                                        <h3 class="template-name mb-0">
                                            <?= clean($template['name']) ?>
                                        </h3>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-view" data-bs-toggle="modal" data-bs-target="#viewModal<?= $key ?>">
                                        <i class="fas fa-eye me-2"></i>Görüntüle
                                    </button>
                                </div>
                            </div>

                            <div class="template-subject">
                                <i class="fas fa-tag me-2"></i><?= clean($template['subject']) ?>
                            </div>

                            <div class="template-description">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= clean($template['description']) ?>
                            </div>

                            <div class="variables-section">
                                <div class="variables-section-title">
                                    <i class="fas fa-code me-2"></i>Kullanılabilir Değişkenler
                                </div>
                                <?php foreach ($template['variables'] as $variable): ?>
                                    <span class="variable-tag"><?= clean($variable) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- View/Edit Modal -->
                        <div class="modal fade" id="viewModal<?= $key ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                                    <div class="modal-header-custom">
                                        <h5 class="modal-title">
                                            <i class="fas fa-envelope-open-text me-2"></i>
                                            <?= clean($template['name']) ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body-custom">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Not:</strong> Bu şablon şu anda kod içinde tanımlıdır.
                                            Değişiklik yapmak için <code>/classes/Mail.php</code> dosyasındaki ilgili metodu düzenleyin.
                                        </div>

                                        <div class="info-group">
                                            <div class="info-label">
                                                <i class="fas fa-tag"></i>
                                                Konu:
                                            </div>
                                            <input type="text" class="form-control" value="<?= clean($template['subject']) ?>" readonly style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px;">
                                        </div>

                                        <div class="info-group">
                                            <div class="info-label">
                                                <i class="fas fa-file-code"></i>
                                                Şablon Konumu:
                                            </div>
                                            <input type="text" class="form-control" value="/classes/Mail.php" readonly style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px; font-family: 'Courier New', monospace;">
                                        </div>

                                        <div class="info-group">
                                            <div class="info-label">
                                                <i class="fas fa-info-circle"></i>
                                                Açıklama:
                                            </div>
                                            <textarea class="form-control" rows="2" readonly style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; padding: 12px;"><?= clean($template['description']) ?></textarea>
                                        </div>

                                        <div class="info-group mb-0">
                                            <div class="info-label">
                                                <i class="fas fa-code"></i>
                                                Kullanılabilir Değişkenler:
                                            </div>
                                            <div class="p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 12px; border: 1px solid #e2e8f0;">
                                                <?php foreach ($template['variables'] as $variable): ?>
                                                    <span class="variable-tag"><?= clean($variable) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="fas fa-lightbulb me-1"></i>
                                                Bu değişkenler email şablonunda kullanılabilir ve çalışma zamanında gerçek değerlerle değiştirilir.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="padding: 20px 28px; border-top: 2px solid #e2e8f0;">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; padding: 10px 24px;">
                                            <i class="fas fa-times me-2"></i>Kapat
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
