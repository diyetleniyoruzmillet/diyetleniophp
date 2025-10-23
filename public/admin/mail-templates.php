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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .template-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }

        .template-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .template-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }

        .template-subject {
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .template-description {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 16px;
        }

        .variables-list {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
        }

        .variable-tag {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-family: 'Courier New', monospace;
            margin: 4px;
        }

        .btn-modern {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .alert-info-custom {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            border: none;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0 text-white">
                        <i class="fas fa-envelope me-3"></i><?= clean($pageTitle) ?>
                    </h1>
                    <p class="text-white-50 mb-0">Mail şablonlarını görüntüleyin ve düzenleyin</p>
                </div>
                <a href="/admin/dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Dashboard
                </a>
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
                                <div>
                                    <h3 class="template-name">
                                        <i class="fas fa-envelope-open-text me-2 text-primary"></i>
                                        <?= clean($template['name']) ?>
                                    </h3>
                                </div>
                                <div>
                                    <button class="btn btn-modern btn-edit" data-bs-toggle="modal" data-bs-target="#viewModal<?= $key ?>">
                                        <i class="fas fa-eye me-2"></i>Görüntüle
                                    </button>
                                </div>
                            </div>

                            <div class="template-subject">
                                <i class="fas fa-tag me-2"></i>
                                Konu: <?= clean($template['subject']) ?>
                            </div>

                            <div class="template-description">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= clean($template['description']) ?>
                            </div>

                            <div class="variables-list">
                                <small class="text-muted d-block mb-2">
                                    <strong><i class="fas fa-code me-1"></i>Kullanılabilir Değişkenler:</strong>
                                </small>
                                <?php foreach ($template['variables'] as $variable): ?>
                                    <span class="variable-tag"><?= clean($variable) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- View/Edit Modal -->
                        <div class="modal fade" id="viewModal<?= $key ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                        <h5 class="modal-title">
                                            <i class="fas fa-envelope me-2"></i>
                                            <?= clean($template['name']) ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Not:</strong> Bu şablon şu anda kod içinde tanımlıdır.
                                            Değişiklik yapmak için <code>/classes/Mail.php</code> dosyasındaki ilgili metodu düzenleyin.
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-tag me-2"></i>Konu:
                                            </label>
                                            <input type="text" class="form-control" value="<?= clean($template['subject']) ?>" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-file-code me-2"></i>Şablon Konumu:
                                            </label>
                                            <input type="text" class="form-control" value="/classes/Mail.php" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-info-circle me-2"></i>Açıklama:
                                            </label>
                                            <textarea class="form-control" rows="2" readonly><?= clean($template['description']) ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-code me-2"></i>Kullanılabilir Değişkenler:
                                            </label>
                                            <div class="p-3" style="background: #f8fafc; border-radius: 8px;">
                                                <?php foreach ($template['variables'] as $variable): ?>
                                                    <span class="variable-tag"><?= clean($variable) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                Bu değişkenler email şablonunda kullanılabilir ve çalışma zamanında gerçek değerlerle değiştirilir.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
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
