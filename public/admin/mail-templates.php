<?php
/**
 * Diyetlenio - Admin Mail Template Yönetimi (Database-based)
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$emailTemplate = new EmailTemplate($db);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'create':
            $variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
            $variables = array_map('trim', $variables);

            $data = [
                'template_key' => $_POST['template_key'],
                'template_name' => $_POST['template_name'],
                'subject' => $_POST['subject'],
                'body_html' => $_POST['body_html'],
                'body_text' => $_POST['body_text'] ?? null,
                'description' => $_POST['description'] ?? null,
                'variables' => $variables,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            if ($emailTemplate->keyExists($data['template_key'])) {
                echo json_encode(['success' => false, 'message' => 'Bu şablon anahtarı zaten kullanılıyor!']);
            } else {
                $id = $emailTemplate->create($data);
                if ($id) {
                    echo json_encode(['success' => true, 'message' => 'Şablon başarıyla oluşturuldu!', 'id' => $id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Şablon oluşturulamadı!']);
                }
            }
            exit;

        case 'update':
            $variables = isset($_POST['variables']) ? explode(',', $_POST['variables']) : [];
            $variables = array_map('trim', $variables);

            $data = [
                'template_key' => $_POST['template_key'],
                'template_name' => $_POST['template_name'],
                'subject' => $_POST['subject'],
                'body_html' => $_POST['body_html'],
                'body_text' => $_POST['body_text'] ?? null,
                'description' => $_POST['description'] ?? null,
                'variables' => $variables,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            if ($emailTemplate->keyExists($data['template_key'], $_POST['id'])) {
                echo json_encode(['success' => false, 'message' => 'Bu şablon anahtarı zaten kullanılıyor!']);
            } else {
                $result = $emailTemplate->update($_POST['id'], $data);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Şablon başarıyla güncellendi!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Şablon güncellenemedi!']);
                }
            }
            exit;

        case 'delete':
            $result = $emailTemplate->delete($_POST['id']);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Şablon başarıyla silindi!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Şablon silinemedi!']);
            }
            exit;

        case 'toggle':
            $result = $emailTemplate->toggleActive($_POST['id']);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Şablon durumu güncellendi!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Durum güncellenemedi!']);
            }
            exit;

        case 'get':
            $template = $emailTemplate->getById($_POST['id']);
            if ($template) {
                echo json_encode(['success' => true, 'template' => $template]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Şablon bulunamadı!']);
            }
            exit;
    }
}

// Get all templates
$templates = $emailTemplate->getAll();
$stats = $emailTemplate->getStats();

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 10px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card p {
            color: #64748b;
            font-weight: 600;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .template-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }

        .template-card:hover::before {
            width: 8px;
        }

        .template-card.inactive {
            opacity: 0.6;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
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

        .variable-tag {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-family: 'Courier New', monospace;
            margin: 4px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s ease;
            margin: 0 4px;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-toggle {
            background: #10b981;
            color: white;
            border: none;
        }

        .btn-toggle:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 24px;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .code-editor {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            min-height: 300px;
        }

        .preview-frame {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            background: #f8fafc;
            min-height: 400px;
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
                        <p class="text-muted">Database tabanlı mail şablon sistemi</p>
                    </div>
                    <div>
                        <button class="btn btn-gradient me-2" onclick="openCreateModal()">
                            <i class="fas fa-plus me-2"></i>Yeni Şablon
                        </button>
                        <a href="/admin/dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-envelope fa-2x" style="color: #667eea;"></i>
                    <h3><?= $stats['total'] ?></h3>
                    <p>Toplam Şablon</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle fa-2x" style="color: #10b981;"></i>
                    <h3><?= $stats['active'] ?></h3>
                    <p>Aktif Şablon</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-times-circle fa-2x" style="color: #ef4444;"></i>
                    <h3><?= $stats['inactive'] ?></h3>
                    <p>Pasif Şablon</p>
                </div>
            </div>

            <!-- Templates List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($templates)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Henüz hiç mail şablonu eklenmemiş. Yukarıdaki "Yeni Şablon" butonuna tıklayarak ilk şablonunuzu oluşturun.
                        </div>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <div class="template-card <?= $template['is_active'] ? '' : 'inactive' ?>">
                                <div class="template-header">
                                    <div class="d-flex align-items-center">
                                        <div class="template-icon">
                                            <i class="fas fa-envelope-open-text"></i>
                                        </div>
                                        <div>
                                            <h3 class="template-name mb-0">
                                                <?= clean($template['template_name']) ?>
                                            </h3>
                                            <small class="text-muted">
                                                <i class="fas fa-key me-1"></i><?= clean($template['template_key']) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge <?= $template['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <i class="fas fa-<?= $template['is_active'] ? 'check' : 'times' ?>"></i>
                                            <?= $template['is_active'] ? 'Aktif' : 'Pasif' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="template-subject">
                                    <i class="fas fa-tag me-2"></i><?= clean($template['subject']) ?>
                                </div>

                                <?php if ($template['description']): ?>
                                    <div class="template-description">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <?= clean($template['description']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($template['variables'])): ?>
                                    <div class="variables-section">
                                        <div class="fw-bold mb-2">
                                            <i class="fas fa-code me-2"></i>Kullanılabilir Değişkenler
                                        </div>
                                        <?php foreach ($template['variables'] as $variable): ?>
                                            <span class="variable-tag"><?= clean($variable) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3 text-end">
                                    <button class="btn btn-action btn-gradient" onclick="previewTemplate(<?= $template['id'] ?>)">
                                        <i class="fas fa-eye me-1"></i>Önizle
                                    </button>
                                    <button class="btn btn-action btn-edit" onclick="editTemplate(<?= $template['id'] ?>)">
                                        <i class="fas fa-edit me-1"></i>Düzenle
                                    </button>
                                    <button class="btn btn-action btn-toggle" onclick="toggleTemplate(<?= $template['id'] ?>)">
                                        <i class="fas fa-toggle-<?= $template['is_active'] ? 'on' : 'off' ?> me-1"></i>
                                        <?= $template['is_active'] ? 'Devre Dışı Bırak' : 'Aktif Et' ?>
                                    </button>
                                    <button class="btn btn-action btn-delete" onclick="deleteTemplate(<?= $template['id'] ?>, '<?= clean($template['template_name']) ?>')">
                                        <i class="fas fa-trash me-1"></i>Sil
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-header-custom">
                    <h5 class="modal-title">
                        <i class="fas fa-envelope me-2"></i>
                        <span id="modalTitle">Yeni Şablon Oluştur</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="templateForm">
                        <input type="hidden" id="templateId" name="id">
                        <input type="hidden" id="formAction" name="action" value="create">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="templateKey" class="form-label">Şablon Anahtarı *</label>
                                <input type="text" class="form-control" id="templateKey" name="template_key" required
                                       placeholder="ornek: password_reset">
                                <small class="text-muted">Benzersiz olmalı, küçük harf ve alt çizgi kullanın</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="templateName" class="form-label">Şablon Adı *</label>
                                <input type="text" class="form-control" id="templateName" name="template_name" required
                                       placeholder="örnek: Şifre Sıfırlama">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Konu *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required
                                   placeholder="Email konu satırı">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="2"
                                      placeholder="Bu şablonun ne için kullanıldığını açıklayın"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="variables" class="form-label">Değişkenler</label>
                            <input type="text" class="form-control" id="variables" name="variables"
                                   placeholder="FIRST_NAME, EMAIL, RESET_LINK (virgülle ayırın)">
                            <small class="text-muted">Şablonda kullanılacak değişkenler (büyük harf, virgülle ayırın)</small>
                        </div>

                        <div class="mb-3">
                            <label for="bodyHtml" class="form-label">HTML İçerik *</label>
                            <textarea class="form-control code-editor" id="bodyHtml" name="body_html" required
                                      placeholder="HTML email içeriği buraya..."></textarea>
                            <small class="text-muted">Değişkenler için {VARIABLE_NAME} formatını kullanın</small>
                        </div>

                        <div class="mb-3">
                            <label for="bodyText" class="form-label">Düz Metin İçerik</label>
                            <textarea class="form-control" id="bodyText" name="body_text" rows="5"
                                      placeholder="Opsiyonel: Düz metin versiyonu"></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                <label class="form-check-label" for="isActive">
                                    <strong>Aktif</strong> - Şablon kullanıma hazır
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>İptal
                    </button>
                    <button type="button" class="btn btn-gradient" onclick="saveTemplate()">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-header-custom">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Şablon Önizleme
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="previewContent" class="preview-frame"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
        const previewModalEl = new bootstrap.Modal(document.getElementById('previewModal'));

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Yeni Şablon Oluştur';
            document.getElementById('templateForm').reset();
            document.getElementById('templateId').value = '';
            document.getElementById('formAction').value = 'create';
            document.getElementById('isActive').checked = true;
            templateModal.show();
        }

        function editTemplate(id) {
            // Fetch template data
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const t = data.template;
                    document.getElementById('modalTitle').textContent = 'Şablonu Düzenle';
                    document.getElementById('templateId').value = t.id;
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('templateKey').value = t.template_key;
                    document.getElementById('templateName').value = t.template_name;
                    document.getElementById('subject').value = t.subject;
                    document.getElementById('bodyHtml').value = t.body_html;
                    document.getElementById('bodyText').value = t.body_text || '';
                    document.getElementById('description').value = t.description || '';
                    document.getElementById('variables').value = t.variables ? t.variables.join(', ') : '';
                    document.getElementById('isActive').checked = t.is_active == 1;
                    templateModal.show();
                } else {
                    alert('Şablon yüklenemedi: ' + data.message);
                }
            });
        }

        function saveTemplate() {
            const form = document.getElementById('templateForm');
            const formData = new FormData(form);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    templateModal.hide();
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                alert('Bir hata oluştu: ' + error);
            });
        }

        function deleteTemplate(id, name) {
            if (!confirm(`"${name}" şablonunu silmek istediğinizden emin misiniz?`)) {
                return;
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            });
        }

        function toggleTemplate(id) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=toggle&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            });
        }

        function previewTemplate(id) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const t = data.template;
                    document.getElementById('previewContent').innerHTML = t.body_html;
                    previewModalEl.show();
                } else {
                    alert('Önizleme yüklenemedi: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
