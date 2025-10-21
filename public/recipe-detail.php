<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM recipes WHERE id = ? AND status = 'published'");
$stmt->execute([$id]);
$recipe = $stmt->fetch();

if (!$recipe) {
    header('Location: /recipes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($recipe['title']) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #0ea5e9 !important; }
        .recipe-header { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 80px 0 60px; }
        .recipe-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 20px; }
        .recipe-content { background: white; border-radius: 20px; padding: 50px; margin: -50px auto 50px; max-width: 900px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .nutrition-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0; }
        .nutrition-item { text-align: center; padding: 20px; background: #f7fafc; border-radius: 15px; }
        .nutrition-value { font-size: 1.5rem; font-weight: 700; color: #0ea5e9; }
        .footer { background: #1e293b; color: white; padding: 40px 0; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-heartbeat me-2"></i>Diyetlenio</a>
            <div class="ms-auto">
                <a href="/recipes.php" class="btn btn-outline-primary me-2">Tarifler</a>
                <a href="/login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        </div>
    </nav>

    <header class="recipe-header">
        <div class="container text-center">
            <h1 class="recipe-title"><?= clean($recipe['title']) ?></h1>
            <p class="lead"><?= clean($recipe['description'] ?? '') ?></p>
            <div class="mt-3">
                <span class="me-4"><i class="fas fa-clock me-2"></i><?= $recipe['prep_time'] ?> dakika</span>
                <span class="me-4"><i class="fas fa-users me-2"></i><?= $recipe['servings'] ?? 1 ?> kişilik</span>
                <span><i class="fas fa-fire me-2"></i><?= $recipe['calories'] ?> kcal</span>
            </div>
        </div>
    </header>

    <div class="container">
        <article class="recipe-content">
            <?php if ($recipe['image']): ?>
                <img src="<?= upload($recipe['image']) ?>" alt="<?= clean($recipe['title']) ?>" class="img-fluid rounded mb-4">
            <?php endif; ?>

            <div class="nutrition-grid">
                <div class="nutrition-item">
                    <div class="nutrition-value"><?= $recipe['calories'] ?></div>
                    <div class="text-muted">Kalori</div>
                </div>
                <div class="nutrition-item">
                    <div class="nutrition-value"><?= $recipe['protein'] ?>g</div>
                    <div class="text-muted">Protein</div>
                </div>
                <div class="nutrition-item">
                    <div class="nutrition-value"><?= $recipe['carbs'] ?>g</div>
                    <div class="text-muted">Karbonhidrat</div>
                </div>
                <div class="nutrition-item">
                    <div class="nutrition-value"><?= $recipe['fat'] ?>g</div>
                    <div class="text-muted">Yağ</div>
                </div>
            </div>

            <h3 class="mt-4">Malzemeler</h3>
            <div><?= nl2br(clean($recipe['ingredients'] ?? 'Malzemeler eklenmedi.')) ?></div>

            <h3 class="mt-4">Hazırlanışı</h3>
            <div><?= nl2br(clean($recipe['instructions'] ?? 'Tarif eklenmedi.')) ?></div>
        </article>

        <div class="text-center mb-5">
            <a href="/recipes.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Tüm Tarifler</a>
        </div>
    </div>

    <footer class="footer">
        <div class="container"><p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p></div>
    </footer>
</body>
</html>
