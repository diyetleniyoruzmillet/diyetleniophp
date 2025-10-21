<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

        <div class="text-center mb-5">
            <a href="/blog.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Tüm Yazılar</a>
        </div>
    </div>

    <footer class="footer">
        <div class="container"><p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p></div>
    </footer>
</body>
</html>
