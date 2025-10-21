<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$auth->requireAdmin();
$conn = $db->getConnection();

// CRUD işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $stmt = $conn->prepare("
                INSERT INTO pages (title, slug, content, meta_title, meta_description, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['title'],
                createSlug($_POST['title']),
                $_POST['content'],
                $_POST['meta_title'],
                $_POST['meta_description'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
            setFlash('success', 'Sayfa oluşturuldu!');
        } elseif ($_POST['action'] === 'update') {
            $stmt = $conn->prepare("
                UPDATE pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['content'],
                $_POST['meta_title'],
                $_POST['meta_description'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['id']
            ]);
            setFlash('success', 'Sayfa güncellendi!');
        } elseif ($_POST['action'] === 'delete') {
            $conn->prepare("DELETE FROM pages WHERE id = ?")->execute([$_POST['id']]);
            setFlash('success', 'Sayfa silindi!');
        }
        redirect('/admin/cms-pages.php');
    }
}

$pages = $conn->query("SELECT * FROM pages ORDER BY created_at DESC")->fetchAll();
$editPage = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editPage = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sayfa Yönetimi - Diyetlenio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-dark text-white p-3">
                <h4>CMS</h4>
                <hr>
                <a href="/admin/cms-pages.php" class="btn btn-light w-100 mb-2">Sayfalar</a>
                <a href="/admin/cms-menus.php" class="btn btn-outline-light w-100 mb-2">Menüler</a>
                <a href="/admin/cms-sliders.php" class="btn btn-outline-light w-100 mb-2">Sliderlar</a>
                <a href="/admin/site-settings.php" class="btn btn-outline-light w-100 mb-2">Site Ayarları</a>
                <a href="/admin/dashboard.php" class="btn btn-outline-light w-100 mt-4">Dashboard</a>
            </div>

            <main class="col-md-10 p-4">
                <h1>Sayfa Yönetimi</h1>

                <?php if ($flash = getFlash('success')): ?>
                    <div class="alert alert-success"><?= $flash ?></div>
                <?php endif; ?>

                <!-- Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5><?= $editPage ? 'Sayfa Düzenle' : 'Yeni Sayfa Ekle' ?></h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="<?= $editPage ? 'update' : 'create' ?>">
                            <?php if ($editPage): ?>
                                <input type="hidden" name="id" value="<?= $editPage['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label>Sayfa Başlığı</label>
                                <input type="text" name="title" class="form-control" required value="<?= clean($editPage['title'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label>İçerik</label>
                                <textarea name="content" class="form-control" rows="10" required><?= clean($editPage['content'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Meta Title (SEO)</label>
                                    <input type="text" name="meta_title" class="form-control" value="<?= clean($editPage['meta_title'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Meta Description (SEO)</label>
                                    <input type="text" name="meta_description" class="form-control" value="<?= clean($editPage['meta_description'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="active" <?= ($editPage['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active">Aktif</label>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i><?= $editPage ? 'Güncelle' : 'Oluştur' ?>
                            </button>
                            <?php if ($editPage): ?>
                                <a href="/admin/cms-pages.php" class="btn btn-secondary">İptal</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- List -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Başlık</th>
                                    <th>Slug</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pages as $page): ?>
                                    <tr>
                                        <td><?= $page['id'] ?></td>
                                        <td><?= clean($page['title']) ?></td>
                                        <td><code><?= clean($page['slug']) ?></code></td>
                                        <td>
                                            <?php if ($page['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatDate($page['created_at']) ?></td>
                                        <td>
                                            <a href="?edit=<?= $page['id'] ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $page['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
