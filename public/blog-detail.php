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
                $conn = $db->getConnection();
                $userId = $auth->check() ? $auth->user()->getId() : null;
                $stmt = $conn->prepare("
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

$conn = $db->getConnection();
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as author_name, u.profile_photo as author_photo
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE a.id = ? AND a.is_published = 1
");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: /blog.php');
    exit;
}

// Yorumları çek
$commentsStmt = $conn->prepare("
    SELECT c.*, u.full_name, u.profile_photo
    FROM article_comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.article_id = ? AND c.status = 'approved'
    ORDER BY c.created_at DESC
");
$commentsStmt->execute([$id]);
$comments = $commentsStmt->fetchAll();

// İlgili makaleler
$relatedStmt = $conn->prepare("
    SELECT a.id, a.title, a.image, a.published_at, u.full_name as author_name
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE a.is_published = 1 AND a.id != ? AND a.category = ?
    ORDER BY a.published_at DESC
    LIMIT 3
");
$relatedStmt->execute([$id, $article['category']]);
$relatedArticles = $relatedStmt->fetchAll();

$pageTitle = $article['title'];
$metaDescription = mb_substr(strip_tags($article['excerpt'] ?? ''), 0, 160);
include __DIR__ . '/../includes/partials/header.php';
?>
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --secondary: #10b981;
            --dark: #0f172a;
            --light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--light);
            color: var(--dark);
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hero Header */
        .article-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 100px 0 80px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .article-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            opacity: 0.3;
        }

        .article-hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .category-badge {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .article-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 25px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .article-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            opacity: 0.95;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-photo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        /* Reading Progress Bar */
        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            z-index: 1001;
            transition: width 0.1s;
        }

        /* Article Content */
        .article-content {
            background: white;
            border-radius: 30px;
            padding: 60px;
            margin: -60px auto 60px;
            max-width: 900px;
            box-shadow: 0 10px 60px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }

        .featured-image {
            width: 100%;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .article-body {
            font-size: 1.15rem;
            line-height: 1.9;
            color: #374151;
        }

        .article-body p {
            margin-bottom: 25px;
        }

        /* Share Buttons */
        .share-section {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-radius: 20px;
            padding: 30px;
            margin: 50px 0;
            text-align: center;
        }

        .share-section h4 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .share-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .share-btn {
            padding: 12px 25px;
            border-radius: 50px;
            border: none;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .share-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
        }

        .share-btn.facebook { background: #1877f2; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.whatsapp { background: #25d366; }
        .share-btn.linkedin { background: #0077b5; }

        /* Related Articles */
        .related-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.3s;
            height: 100%;
        }

        .related-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        }

        .related-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .related-body {
            padding: 25px;
        }

        .related-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark);
        }

        /* Comments */
        .comment-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .article-title { font-size: 2rem; }
            .article-content { padding: 30px 25px; }
        }
    </style>
</head>
<body>
    <div class="reading-progress" id="readingProgress"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat me-2"></i>Diyetlenio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="/">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="/blog.php">Blog</a></li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="/<?= $auth->user()->getUserType() ?>/dashboard.php">Panel</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-primary ms-2" href="/login.php">Giriş Yap</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="article-hero">
        <div class="container">
            <div class="article-hero-content">
                <div class="category-badge">
                    <i class="fas fa-tag me-2"></i><?= clean($article['category'] ?? 'Genel') ?>
                </div>
                <h1 class="article-title"><?= clean($article['title']) ?></h1>
                <div class="article-meta">
                    <div class="author-info">
                        <?php if ($article['author_photo']): ?>
                            <img src="<?= clean($article['author_photo']) ?>" alt="<?= clean($article['author_name']) ?>" class="author-photo">
                        <?php else: ?>
                            <div class="author-photo" style="background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <span><?= clean($article['author_name'] ?? 'Diyetlenio') ?></span>
                    </div>
                    <div><i class="fas fa-calendar me-2"></i><?= date('d.m.Y', strtotime($article['published_at'])) ?></div>
                    <div><i class="fas fa-clock me-2"></i><?= ceil(str_word_count($article['content']) / 200) ?> dk okuma</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="container">
        <article class="article-content">
            <?php if ($article['image']): ?>
                <img src="<?= clean($article['image']) ?>" alt="<?= clean($article['title']) ?>" class="featured-image">
            <?php endif; ?>

            <div class="article-body">
                <?= nl2br(clean($article['content'])) ?>
            </div>

            <!-- Share Section -->
            <div class="share-section">
                <h4><i class="fas fa-share-alt me-2"></i>Bu yazıyı paylaşın</h4>
                <?php $shareUrl = BASE_URL . '/blog-detail.php?id=' . $id; ?>
                <div class="share-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" class="share-btn facebook">
                        <i class="fab fa-facebook-f"></i>Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['title']) ?>" target="_blank" class="share-btn twitter">
                        <i class="fab fa-twitter"></i>Twitter
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode($article['title'] . ' ' . $shareUrl) ?>" target="_blank" class="share-btn whatsapp">
                        <i class="fab fa-whatsapp"></i>WhatsApp
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shareUrl) ?>" target="_blank" class="share-btn linkedin">
                        <i class="fab fa-linkedin-in"></i>LinkedIn
                    </a>
                </div>
            </div>

            <!-- Comments -->
            <div class="mt-5">
                <h3 class="mb-4"><i class="fas fa-comments me-2"></i>Yorumlar (<?= count($comments) ?>)</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?><div><?= clean($error) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">Yorumunuz onay bekliyor. Teşekkürler!</div>
                <?php endif; ?>

                <!-- Yorum Formu -->
                <div class="comment-card mb-4">
                    <h5 class="mb-3">Yorum Yaz</h5>
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
                            <textarea name="comment" class="form-control" rows="4" placeholder="Yorumunuz..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Gönder
                        </button>
                    </form>
                </div>

                <!-- Yorumlar Listesi -->
                <?php foreach ($comments as $comment): ?>
                <div class="comment-card">
                    <div class="d-flex align-items-start mb-3">
                        <div class="me-3">
                            <?php if (!empty($comment['profile_photo'])): ?>
                                <?php $p=$comment['profile_photo']; $photoUrl = (strpos($p,'http')===0) ? $p : ('/assets/uploads/' . ltrim($p,'/')); ?>
                                <img src="<?= clean($photoUrl) ?>" alt="<?= clean($comment['full_name'] ?? $comment['guest_name'] ?? 'Kullanıcı') ?>" style="width:50px;height:50px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <div style="width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:white;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h6 class="mb-1"><?= clean($comment['full_name'] ?? $comment['guest_name'] ?? 'Anonim') ?></h6>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></small>
                        </div>
                    </div>
                    <p class="mb-0"><?= nl2br(clean($comment['comment'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </article>

        <!-- Related Articles -->
        <?php if (count($relatedArticles) > 0): ?>
        <div class="my-5">
            <h3 class="text-center mb-4"><i class="fas fa-newspaper me-2"></i>İlgili Yazılar</h3>
            <div class="row g-4">
                <?php foreach ($relatedArticles as $related): ?>
                <div class="col-md-4">
                    <a href="/blog-detail.php?id=<?= $related['id'] ?>" class="text-decoration-none">
                        <div class="related-card">
                            <?php if ($related['image']): ?>
                                <img src="<?= clean($related['image']) ?>" alt="<?= clean($related['title']) ?>" class="related-image">
                            <?php else: ?>
                                <div class="related-image d-flex align-items-center justify-content-center text-white">
                                    <i class="fas fa-newspaper fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            <div class="related-body">
                                <h5 class="related-title"><?= clean($related['title']) ?></h5>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i><?= clean($related['author_name']) ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-calendar me-1"></i><?= date('d.m.Y', strtotime($related['published_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center mb-5">
            <a href="/blog.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Tüm Yazılar
            </a>
        </div>
    </div>

    <footer class="footer bg-dark text-white text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Reading progress bar
        window.addEventListener('scroll', function() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            document.getElementById('readingProgress').style.width = scrolled + '%';
        });
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
