<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Arama parametresi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Arama varsa filtrele
$conn = $db->getConnection();

if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT *,
               MATCH(title, description, ingredients) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
        FROM recipes
        WHERE status = 'published'
        AND (title LIKE ? OR description LIKE ? OR ingredients LIKE ? OR MATCH(title, description, ingredients) AGAINST(? IN NATURAL LANGUAGE MODE))
        ORDER BY relevance DESC, created_at DESC
        LIMIT ? OFFSET ?
    ");
    $searchParam = '%' . $search . '%';
    $stmt->execute([$search, $searchParam, $searchParam, $searchParam, $search, $perPage, $offset]);

    $totalStmt = $conn->prepare("SELECT COUNT(*) FROM recipes WHERE status = 'published' AND (title LIKE ? OR description LIKE ? OR ingredients LIKE ?)");
    $totalStmt->execute([$searchParam, $searchParam, $searchParam]);
    $totalRecipes = $totalStmt->fetchColumn();
} else {
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE status = 'published' ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);

    $totalRecipes = $conn->query("SELECT COUNT(*) FROM recipes WHERE status = 'published'")->fetchColumn();
}

$recipes = $stmt->fetchAll();
$totalPages = ceil($totalRecipes / $perPage);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifler - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #0ea5e9 !important; }
        .hero { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 100px 0 80px; text-align: center; }
        .hero h1 { font-size: 3rem; font-weight: 800; margin-bottom: 20px; }
        .recipe-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: all 0.3s; height: 100%; }
        .recipe-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
        .recipe-image { width: 100%; height: 250px; background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); display: flex; align-items: center; justify-content: center; }
        .recipe-image i { font-size: 4rem; color: white; opacity: 0.7; }
        .recipe-content { padding: 25px; }
        .recipe-title { font-size: 1.3rem; font-weight: 600; color: #2d3748; margin-bottom: 15px; }
        .recipe-meta { display: flex; gap: 20px; color: #718096; font-size: 0.9rem; margin-bottom: 15px; }
        .recipe-nutrition { display: flex; justify-content: space-between; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 0.85rem; color: #718096; }
        .footer { background: #1e293b; color: white; padding: 40px 0; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-heartbeat me-2"></i>Diyetlenio</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dietitians.php">Diyetisyenler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/recipes.php">Tarifler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact.php">İletişim</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="/login.php">Giriş Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <h1>Sağlıklı Tarifler</h1>
            <p>Lezzetli ve besleyici tariflerle sağlıklı beslenin</p>
            <div class="mt-4" style="max-width: 600px; margin-left: auto; margin-right: auto;">
                <form method="GET" action="/recipes.php" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Tariflerde ara..." value="<?= clean($search) ?>" style="border-radius: 12px; padding: 12px 20px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.9);">
                    <button type="submit" class="btn btn-light" style="border-radius: 12px; padding: 12px 30px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <?php if (!empty($search)): ?>
                <div class="mb-4">
                    <h5 class="text-muted">"<?= clean($search) ?>" için <?= $totalRecipes ?> tarif bulundu
                        <a href="/recipes.php" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="fas fa-times me-1"></i>Aramayı Temizle
                        </a>
                    </h5>
                </div>
            <?php endif; ?>
            <?php if (empty($recipes)): ?>
                <div class="text-center"><p class="text-muted">Henüz tarif bulunmuyor.</p></div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="col-md-3">
                            <div class="recipe-card">
                                <div class="recipe-image">
                                    <?php if ($recipe['image']): ?>
                                        <img src="<?= upload($recipe['image']) ?>" alt="<?= clean($recipe['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <i class="fas fa-utensils"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="recipe-content">
                                    <div class="recipe-title"><?= clean($recipe['title']) ?></div>
                                    <div class="recipe-meta">
                                        <span><i class="fas fa-clock me-1"></i><?= $recipe['prep_time'] ?> dk</span>
                                        <span><i class="fas fa-fire me-1"></i><?= $recipe['calories'] ?> kcal</span>
                                    </div>
                                    <p class="text-muted small"><?= clean(truncate($recipe['description'] ?? '', 100)) ?></p>
                                    <div class="recipe-nutrition">
                                        <span><strong>P:</strong> <?= $recipe['protein'] ?>g</span>
                                        <span><strong>K:</strong> <?= $recipe['carbs'] ?>g</span>
                                        <span><strong>Y:</strong> <?= $recipe['fat'] ?>g</span>
                                    </div>
                                    <a href="/recipe-detail.php?id=<?= $recipe['id'] ?>" class="btn btn-primary w-100 mt-3">Tarifi Gör</a>
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
        <div class="container"><p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p></div>
    </footer>
</body>
</html>
