<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Yorum ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        $comment = trim($_POST['comment'] ?? '');
        $guestName = trim($_POST['guest_name'] ?? '');
        $guestEmail = trim($_POST['guest_email'] ?? '');

        if (empty($comment)) {
            $errors[] = 'Yorum yazınız.';
        }

        if (!$auth->check()) {
            if (empty($guestName)) $errors[] = 'İsminizi yazın.';
            if (empty($guestEmail)) $errors[] = 'Email adresinizi yazın.';
            elseif (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli email girin.';
        }

        if (empty($errors)) {
            try {
                $userId = $auth->check() ? $auth->user()->getId() : null;
                $stmt = $db->prepare("
                    INSERT INTO article_comments (article_id, user_id, guest_name, guest_email, comment, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$id, $userId, $guestName, $guestEmail, $comment]);
                $success = true;
            } catch (Exception $e) {
                $errors[] = 'Yorum eklenirken hata oluştu.';
                error_log('Comment error: ' . $e->getMessage());
            }
        }
    }
}

$stmt = $db->prepare("
    SELECT a.*, u.first_name, u.last_name
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE a.id = ? AND a.status = 'published'
");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: /blog.php');
    exit;
}

// Yorumları çek
$commentsStmt = $db->prepare("
    SELECT c.*, u.first_name, u.last_name
    FROM article_comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.article_id = ? AND c.status = 'approved'
    ORDER BY c.created_at DESC
");
$commentsStmt->execute([$id]);
$comments = $commentsStmt->fetchAll();

$pageTitle = $article['title'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #0ea5e9 !important; }
        .article-header { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 80px 0 60px; }
        .article-category { color: rgba(255,255,255,0.9); font-weight: 600; margin-bottom: 15px; }
        .article-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 20px; }
        .article-meta { color: rgba(255,255,255,0.9); font-size: 1rem; }
        .article-content { background: white; border-radius: 20px; padding: 50px; margin: -50px auto 50px; max-width: 900px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .article-content p { color: #718096; line-height: 1.9; margin-bottom: 20px; font-size: 1.1rem; }
        .footer { background: #1e293b; color: white; padding: 40px 0; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-heartbeat me-2"></i>Diyetlenio</a>
            <div class="ms-auto">
                <a href="/blog.php" class="btn btn-outline-primary me-2">Blog</a>
                <a href="/login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        </div>
    </nav>

    <header class="article-header">
        <div class="container">
            <div class="article-category"><i class="fas fa-tag me-2"></i><?= clean($article['category'] ?? 'Genel') ?></div>
            <h1 class="article-title"><?= clean($article['title']) ?></h1>
            <div class="article-meta">
                <i class="fas fa-user me-2"></i><?= clean($article['first_name'] . ' ' . $article['last_name']) ?>
                <span class="mx-3">•</span>
                <i class="fas fa-calendar me-2"></i><?= date('d.m.Y', strtotime($article['published_at'])) ?>
            </div>
        </div>
    </header>

    <div class="container">
        <article class="article-content">
            <?php if ($article['image']): ?>
                <img src="<?= upload($article['image']) ?>" alt="<?= clean($article['title']) ?>" class="img-fluid rounded mb-4">
            <?php endif; ?>
            <div><?= nl2br(clean($article['content'])) ?></div>
        </article>

        <!-- Yorumlar Bölümü -->
        <div class="article-content mt-5">
            <h3 class="mb-4"><i class="fas fa-comments me-2"></i>Yorumlar (<?= count($comments) ?>)</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?><div><?= clean($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">Yorumunuz onay bekliyor. Teşekkürler!</div>
            <?php endif; ?>

            <!-- Yorum Ekleme Formu -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Yorum Yaz</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <?php if (!$auth->check()): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" name="guest_name" class="form-control" placeholder="Adınız" value="<?= clean($_POST['guest_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="guest_email" class="form-control" placeholder="Email" value="<?= clean($_POST['guest_email'] ?? '') ?>" required>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="4" placeholder="Yorumunuzu yazın..." required><?= clean($_POST['comment'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Gönder
                        </button>
                    </form>
                </div>
            </div>

            <!-- Yorumlar Listesi -->
            <?php if (empty($comments)): ?>
                <p class="text-muted">Henüz yorum yok. İlk yorumu siz yapın!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">
                                    <i class="fas fa-user-circle me-2 text-primary"></i>
                                    <?php if ($comment['user_id']): ?>
                                        <?= clean($comment['first_name'] . ' ' . $comment['last_name']) ?>
                                    <?php else: ?>
                                        <?= clean($comment['guest_name']) ?>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></small>
                            </div>
                            <p class="mb-0" style="color: #4a5568;"><?= nl2br(clean($comment['comment'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mb-5 mt-4">
            <a href="/blog.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Tüm Yazılar</a>
        </div>
    </div>

    <footer class="footer">
        <div class="container"><p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p></div>
    </footer>
</body>
</html>
