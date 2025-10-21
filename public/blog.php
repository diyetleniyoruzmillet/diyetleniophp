<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Blog yazılarını çek
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT a.*, u.first_name, u.last_name, 
           (SELECT COUNT(*) FROM article_comments WHERE article_id = a.id) as comment_count
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE a.status = 'published'
    ORDER BY a.published_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$articles = $stmt->fetchAll();

$totalStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
$totalArticles = $totalStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);

$pageTitle = 'Blog';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #0ea5e9 !important; }
        .hero { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 100px 0 80px; text-align: center; }
        .hero h1 { font-size: 3rem; font-weight: 800; margin-bottom: 20px; }
        .section { padding: 80px 0; }
        .article-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: all 0.3s; height: 100%; }
        .article-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
        .article-image { width: 100%; height: 250px; background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); display: flex; align-items: center; justify-content: center; }
        .article-image i { font-size: 4rem; color: white; opacity: 0.7; }
        .article-content { padding: 25px; }
        .article-category { color: #0ea5e9; font-weight: 600; font-size: 0.9rem; margin-bottom: 10px; }
        .article-title { font-size: 1.3rem; font-weight: 600; color: #2d3748; margin-bottom: 15px; }
        .article-excerpt { color: #718096; margin-bottom: 20px; }
        .article-meta { display: flex; justify-content: space-between; align-items: center; color: #a0aec0; font-size: 0.9rem; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .footer { background: #1e293b; color: white; padding: 40px 0; text-align: center; }
        .footer a { color: #0ea5e9; text-decoration: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-heartbeat me-2"></i>Diyetlenio</a>
            <div class="ms-auto">
                <a href="/" class="btn btn-outline-primary me-2">Ana Sayfa</a>
                <a href="/login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <h1>Blog</h1>
            <p>Sağlıklı yaşam, beslenme ve diyet hakkında her şey</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if (empty($articles)): ?>
                <div class="text-center">
                    <p class="text-muted">Henüz yazı bulunmuyor.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($articles as $article): ?>
                        <div class="col-md-4">
                            <div class="article-card">
                                <div class="article-image">
                                    <?php if ($article['image']): ?>
                                        <img src="<?= upload($article['image']) ?>" alt="<?= clean($article['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <i class="fas fa-newspaper"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="article-content">
                                    <div class="article-category"><?= clean($article['category'] ?? 'Genel') ?></div>
                                    <h3 class="article-title"><?= clean($article['title']) ?></h3>
                                    <p class="article-excerpt"><?= clean(truncate($article['content'], 150)) ?></p>
                                    <div class="article-meta">
                                        <span><i class="fas fa-user me-2"></i><?= clean($article['first_name'] . ' ' . $article['last_name']) ?></span>
                                        <span><i class="fas fa-comment me-2"></i><?= $article['comment_count'] ?></span>
                                    </div>
                                    <a href="/blog-detail.php?id=<?= $article['id'] ?>" class="btn btn-primary w-100 mt-3">Devamını Oku</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>
