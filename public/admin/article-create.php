<?php
/**
 * Diyetlenio - Admin Makale Ekleme/Düzenleme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$errors = [];
$article = null;
$isEdit = false;

// Düzenleme modu kontrolü
if (isset($_GET['id'])) {
    $articleId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch();

    if ($article) {
        $isEdit = true;
    } else {
        setFlash('error', 'Makale bulunamadı.');
        redirect('/admin/articles.php');
    }
}

// Form gönderildi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        $validator = new Validator($_POST);
        $validator
            ->required(['title', 'content', 'category'])
            ->min('title', 3)
            ->max('title', 200)
            ->min('content', 50)
            ->in('category', ['beslenme', 'saglik', 'diyet', 'yasam']);

        if ($validator->fails()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        if (empty($errors)) {
            $title = sanitizeString($_POST['title'], 200);
            $content = $_POST['content']; // HTML içerik
            $category = sanitizeString($_POST['category'], 50);
            $excerpt = sanitizeString($_POST['excerpt'] ?? '', 500);
            $metaDescription = sanitizeString($_POST['meta_description'] ?? '', 160);
            $isPublished = isset($_POST['is_published']) ? 1 : 0;
            $authorId = $auth->user()->getId();

            // Slug oluştur
            $slug = sanitizeString(
                strtolower(
                    preg_replace('/[^a-z0-9]+/i', '-',
                    str_replace(['ı', 'ş', 'ğ', 'ü', 'ö', 'ç', 'İ', 'Ş', 'Ğ', 'Ü', 'Ö', 'Ç'],
                                ['i', 's', 'g', 'u', 'o', 'c', 'i', 's', 'g', 'u', 'o', 'c'],
                                $title))
                ),
                200
            );
            $slug = trim($slug, '-');

            try {
                if ($isEdit) {
                    // Güncelleme
                    $stmt = $conn->prepare("
                        UPDATE articles
                        SET title = ?,
                            slug = ?,
                            content = ?,
                            excerpt = ?,
                            category = ?,
                            meta_description = ?,
                            is_published = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $title, $slug, $content, $excerpt,
                        $category, $metaDescription, $isPublished, $articleId
                    ]);

                    setFlash('success', 'Makale başarıyla güncellendi.');
                } else {
                    // Yeni ekleme
                    $stmt = $conn->prepare("
                        INSERT INTO articles (title, slug, content, excerpt, category, meta_description, is_published, author_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $title, $slug, $content, $excerpt,
                        $category, $metaDescription, $isPublished, $authorId
                    ]);

                    setFlash('success', 'Makale başarıyla eklendi.');
                }

                redirect('/admin/articles.php');

            } catch (Exception $e) {
                error_log('Article save error: ' . $e->getMessage());
                $errors[] = 'Makale kaydedilirken bir hata oluştu.';
            }
        }
    }
}

$pageTitle = $isEdit ? 'Makale Düzenle' : 'Yeni Makale';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../includes/admin-styles.php'; ?>
    <style>
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .char-counter {
            font-size: 0.875rem;
            color: #6c757d;
            float: right;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?= $pageTitle ?></h2>
                        <a href="/admin/articles.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Geri Dön
                        </a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= clean($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                        <div class="form-section">
                            <h5 class="mb-4">Temel Bilgiler</h5>

                            <div class="mb-3">
                                <label for="title" class="form-label">Başlık <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="<?= clean($article['title'] ?? $_POST['title'] ?? '') ?>"
                                       maxlength="200" required>
                                <small class="text-muted">Makale başlığı (3-200 karakter)</small>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Kategori Seçin</option>
                                    <?php
                                    $categories = [
                                        'beslenme' => 'Beslenme',
                                        'saglik' => 'Sağlık',
                                        'diyet' => 'Diyet',
                                        'yasam' => 'Yaşam'
                                    ];
                                    $selectedCategory = $article['category'] ?? $_POST['category'] ?? '';
                                    foreach ($categories as $value => $label):
                                    ?>
                                        <option value="<?= $value ?>" <?= $selectedCategory === $value ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="excerpt" class="form-label">Özet</label>
                                <textarea class="form-control" id="excerpt" name="excerpt" rows="3"
                                          maxlength="500"><?= clean($article['excerpt'] ?? $_POST['excerpt'] ?? '') ?></textarea>
                                <small class="text-muted">Makale özeti (maksimum 500 karakter)</small>
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">İçerik <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="15"
                                          required><?= clean($article['content'] ?? $_POST['content'] ?? '') ?></textarea>
                                <small class="text-muted">Makale içeriği (minimum 50 karakter, HTML kullanabilirsiniz)</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-4">SEO Ayarları</h5>

                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Açıklama</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2"
                                          maxlength="160"><?= clean($article['meta_description'] ?? $_POST['meta_description'] ?? '') ?></textarea>
                                <small class="text-muted">Arama motorları için açıklama (maksimum 160 karakter)</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-4">Yayın Ayarları</h5>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published"
                                       <?= ($article['is_published'] ?? $_POST['is_published'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_published">
                                    Makaleyi Yayınla
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                            </button>
                            <a href="/admin/articles.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
