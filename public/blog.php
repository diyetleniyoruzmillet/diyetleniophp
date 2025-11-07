<?php
/**
 * Blog - Sağlık ve Beslenme Blog'u
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Fetch blog posts (using articles table)
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as author_name, u.profile_photo as author_photo,
           (SELECT COUNT(*) FROM article_comments WHERE article_id = a.id AND is_approved = 1) as comment_count,
           a.excerpt as content
    FROM articles a
    INNER JOIN users u ON a.author_id = u.id
    WHERE a.status = 'approved'
    ORDER BY a.published_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$posts = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $conn->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'");
$totalPosts = $countStmt->fetchColumn();
$totalPages = ceil($totalPosts / $limit);

// Featured posts
$featuredStmt = $conn->prepare("
    SELECT a.*, u.full_name as author_name, a.excerpt as content
    FROM articles a
    INNER JOIN users u ON a.author_id = u.id
    WHERE a.status = 'approved' AND a.is_featured = 1
    ORDER BY a.published_at DESC
    LIMIT 3
");
$featuredStmt->execute();
$featured = $featuredStmt->fetchAll();

$pageTitle = 'Blog - Sağlık ve Beslenme';
$metaDescription = 'Sağlıklı yaşam, beslenme ipuçları ve uzman diyetisyenlerimizin yazıları';
include __DIR__ . '/../includes/partials/header.php';
?>

<style>
    :root {
        --primary: #10b981;
        --primary-dark: #059669;
        --text-dark: #0f172a;
        --text-light: #64748b;
        --bg-light: #f8fafc;
    }

    .hero-blog {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 120px 0 80px;
        text-align: center;
        margin-top: 70px;
    }

    .hero-blog h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }

    .hero-blog p {
        font-size: 1.2rem;
        opacity: 0.95;
    }

    .blog-section {
        padding: 60px 0;
        background: var(--bg-light);
    }

    .blog-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .blog-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }

    .blog-image {
        width: 100%;
        height: 250px;
        object-fit: cover;
    }

    .blog-content {
        padding: 2rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .blog-category {
        display: inline-block;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #166534;
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .blog-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1rem;
        line-height: 1.4;
    }

    .blog-title a {
        color: var(--text-dark);
        text-decoration: none;
        transition: color 0.3s;
    }

    .blog-title a:hover {
        color: var(--primary);
    }

    .blog-excerpt {
        color: var(--text-light);
        line-height: 1.7;
        margin-bottom: 1.5rem;
        flex: 1;
    }

    .blog-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-top: 1rem;
        border-top: 2px solid #f1f5f9;
    }

    .author-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .author-info {
        flex: 1;
    }

    .author-name {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.9rem;
    }

    .post-date {
        color: var(--text-light);
        font-size: 0.85rem;
    }

    .featured-section {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        padding: 3rem;
        border-radius: 24px;
        margin-bottom: 3rem;
    }

    .featured-badge {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
    }

    .pagination {
        margin-top: 3rem;
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }

    .page-link {
        background: white;
        border: 2px solid #e2e8f0;
        color: var(--text-dark);
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .page-link:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .page-link.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .empty-state {
        text-align: center;
        padding: 5rem 2rem;
    }

    .empty-state i {
        font-size: 5rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    .empty-state h3 {
        color: var(--text-dark);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--text-light);
    }

    @media (max-width: 768px) {
        .hero-blog h1 {
            font-size: 2.5rem;
        }

        .hero-blog {
            padding: 100px 0 60px;
        }
    }
</style>

<div class="hero-blog">
    <div class="container">
        <h1><i class="fas fa-newspaper me-3"></i>Blog</h1>
        <p>Sağlıklı yaşam, beslenme ipuçları ve uzman önerileri</p>
    </div>
</div>

<div class="blog-section">
    <div class="container">
        <?php if (!empty($featured)): ?>
        <div class="featured-section">
            <span class="featured-badge">
                <i class="fas fa-star"></i>
                Öne Çıkan Yazılar
            </span>
            <div class="row">
                <?php foreach ($featured as $post): ?>
                <div class="col-md-4 mb-3">
                    <div style="background: white; border-radius: 16px; padding: 1.5rem; height: 100%;">
                        <h4 style="font-weight: 700; color: var(--text-dark); margin-bottom: 1rem;">
                            <a href="/article.php?id=<?= $post['id'] ?>" style="color: inherit; text-decoration: none;">
                                <?= clean($post['title']) ?>
                            </a>
                        </h4>
                        <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                            <?= clean(substr(strip_tags($post['content']), 0, 120)) ?>...
                        </p>
                        <small style="color: var(--text-light);">
                            <i class="fas fa-user me-1"></i><?= clean($post['author_name']) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($posts)): ?>
        <div class="row">
            <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="blog-card">
                    <?php if (!empty($post['featured_image'])): ?>
                        <img src="<?= clean($post['featured_image']) ?>"
                             alt="<?= clean($post['title']) ?>"
                             class="blog-image">
                    <?php else: ?>
                        <div class="blog-image" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-newspaper" style="font-size: 4rem; color: rgba(255,255,255,0.3);"></i>
                        </div>
                    <?php endif; ?>

                    <div class="blog-content">
                        <?php if (!empty($post['category'])): ?>
                            <span class="blog-category"><?= clean($post['category']) ?></span>
                        <?php endif; ?>

                        <h3 class="blog-title">
                            <a href="/article.php?id=<?= $post['id'] ?>">
                                <?= clean($post['title']) ?>
                            </a>
                        </h3>

                        <div class="blog-excerpt">
                            <?= clean(substr(strip_tags($post['content']), 0, 150)) ?>...
                        </div>

                        <div class="blog-meta">
                            <img src="<?= clean($post['author_photo'] ?? '/images/default-avatar.png') ?>"
                                 alt="<?= clean($post['author_name']) ?>"
                                 class="author-photo">
                            <div class="author-info">
                                <div class="author-name"><?= clean($post['author_name']) ?></div>
                                <div class="post-date">
                                    <i class="far fa-calendar me-1"></i>
                                    <?= date('d.m.Y', strtotime($post['published_at'])) ?>
                                    <i class="far fa-comment ms-2 me-1"></i>
                                    <?= $post['comment_count'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page || $i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                    <a href="?page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php elseif (abs($i - $page) == 3): ?>
                    <span class="page-link" style="border: none; cursor: default;">...</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="page-link">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-newspaper"></i>
            <h3>Henüz Blog Yazısı Yok</h3>
            <p>Yakında sağlık ve beslenme hakkında faydalı yazılarımızı burada bulabileceksiniz.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
