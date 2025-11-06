<?php
/**
 * Recipe Detail Page - Tarif Detay Sayfası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

// Get recipe ID
$recipeId = (int) ($_GET['id'] ?? 0);

if (!$recipeId) {
    header('Location: /recipes.php');
    exit;
}

// Fetch recipe details
$stmt = $conn->prepare("
    SELECT r.*, u.full_name as author_name, u.profile_photo as author_photo
    FROM recipes r
    INNER JOIN users u ON r.author_id = u.id
    WHERE r.id = ? AND r.status = 'approved'
");
$stmt->execute([$recipeId]);
$recipe = $stmt->fetch();

if (!$recipe) {
    header('Location: /recipes.php');
    exit;
}

// Increment view count
$conn->prepare("UPDATE recipes SET views_count = views_count + 1 WHERE id = ?")->execute([$recipeId]);

// Fetch ingredients
$ingredientsStmt = $conn->prepare("
    SELECT * FROM recipe_ingredients
    WHERE recipe_id = ?
    ORDER BY sort_order, id
");
$ingredientsStmt->execute([$recipeId]);
$ingredients = $ingredientsStmt->fetchAll();

// Fetch steps
$stepsStmt = $conn->prepare("
    SELECT * FROM recipe_steps
    WHERE recipe_id = ?
    ORDER BY step_number, sort_order
");
$stepsStmt->execute([$recipeId]);
$steps = $stepsStmt->fetchAll();

// Fetch diet tags
$tagsStmt = $conn->prepare("
    SELECT dt.* FROM recipe_diet_tags dt
    INNER JOIN recipe_diet_relations rdr ON dt.id = rdr.diet_tag_id
    WHERE rdr.recipe_id = ?
");
$tagsStmt->execute([$recipeId]);
$dietTags = $tagsStmt->fetchAll();

// Fetch categories
$categoriesStmt = $conn->prepare("
    SELECT c.* FROM recipe_categories c
    INNER JOIN recipe_category_relations rcr ON c.id = rcr.category_id
    WHERE rcr.recipe_id = ?
");
$categoriesStmt->execute([$recipeId]);
$categories = $categoriesStmt->fetchAll();

// Fetch recent comments
$commentsStmt = $conn->prepare("
    SELECT c.*, u.full_name, u.profile_photo
    FROM recipe_comments c
    INNER JOIN users u ON c.user_id = u.id
    WHERE c.recipe_id = ? AND c.is_approved = 1
    ORDER BY c.created_at DESC
    LIMIT 10
");
$commentsStmt->execute([$recipeId]);
$comments = $commentsStmt->fetchAll();

// Check if user has favorited
$isFavorited = false;
if ($auth->check()) {
    $favStmt = $conn->prepare("SELECT id FROM recipe_favorites WHERE recipe_id = ? AND user_id = ?");
    $favStmt->execute([$recipeId, $auth->user()['id']]);
    $isFavorited = (bool) $favStmt->fetch();
}

$difficultyLabels = [
    'easy' => 'Kolay',
    'medium' => 'Orta',
    'hard' => 'Zor'
];

$pageTitle = $recipe['title'];
$metaDescription = substr($recipe['description'], 0, 150);
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

    .recipe-hero {
        position: relative;
        height: 500px;
        background-size: cover;
        background-position: center;
        margin-top: 70px;
    }

    .recipe-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
    }

    .recipe-hero-content {
        position: relative;
        z-index: 10;
        height: 100%;
        display: flex;
        align-items: flex-end;
        padding-bottom: 3rem;
        color: white;
    }

    .recipe-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .recipe-meta {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        padding: 0.75rem 1.25rem;
        border-radius: 50px;
        font-weight: 600;
    }

    .recipe-content {
        padding: 60px 0;
        background: var(--bg-light);
    }

    .content-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 2rem;
    }

    .content-card h2 {
        color: var(--text-dark);
        font-weight: 800;
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .content-card h2 i {
        color: var(--primary);
    }

    .ingredients-list {
        list-style: none;
        padding: 0;
    }

    .ingredients-list li {
        padding: 1rem 1.5rem;
        background: #f8fafc;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border-left: 4px solid var(--primary);
    }

    .ingredients-list li i {
        color: var(--primary);
    }

    .step-item {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .step-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .step-number {
        flex-shrink: 0;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .step-content {
        flex: 1;
    }

    .step-content p {
        color: var(--text-light);
        line-height: 1.8;
        margin: 0;
    }

    .nutrition-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1.5rem;
    }

    .nutrition-item {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        border: 2px solid #bbf7d0;
    }

    .nutrition-item i {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .nutrition-value {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-dark);
        display: block;
    }

    .nutrition-label {
        color: var(--text-light);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .diet-tag {
        display: inline-block;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0.25rem;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
    }

    .rating-display {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.5rem;
    }

    .rating-stars {
        color: #fbbf24;
    }

    .comment-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 2px solid #e2e8f0;
    }

    .comment-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .commenter-photo {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .btn-favorite {
        background: white;
        border: 2px solid var(--primary);
        color: var(--primary);
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.3s;
    }

    .btn-favorite:hover, .btn-favorite.active {
        background: var(--primary);
        color: white;
    }

    .sidebar-widget {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 2rem;
        position: sticky;
        top: 90px;
    }

    @media (max-width: 768px) {
        .recipe-title {
            font-size: 2rem;
        }

        .recipe-hero {
            height: 400px;
        }

        .sidebar-widget {
            position: static;
        }
    }
</style>

<div class="recipe-hero" style="background-image: url('<?= clean($recipe['featured_image'] ?? '/images/default-recipe.jpg') ?>');">
    <div class="recipe-hero-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="recipe-title"><?= clean($recipe['title']) ?></h1>

                    <?php if (!empty($categories)): ?>
                    <div style="margin-bottom: 1rem;">
                        <?php foreach ($categories as $cat): ?>
                            <span class="diet-tag" style="background: rgba(255,255,255,0.3); color: white;">
                                <?= !empty($cat['icon']) ? '<i class="' . clean($cat['icon']) . '"></i> ' : '' ?>
                                <?= clean($cat['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="recipe-meta">
                        <div class="meta-item">
                            <i class="far fa-clock"></i>
                            <?= $recipe['total_time'] ?? ($recipe['prep_time'] + $recipe['cook_time']) ?> dk
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-utensils"></i>
                            <?= $recipe['servings'] ?> kişilik
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-signal"></i>
                            <?= $difficultyLabels[$recipe['difficulty']] ?? 'Orta' ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-fire"></i>
                            <?= $recipe['calories_per_serving'] ?> kcal
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="recipe-content">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Description -->
                <?php if (!empty($recipe['description'])): ?>
                <div class="content-card">
                    <h2><i class="fas fa-info-circle"></i> Açıklama</h2>
                    <p style="color: var(--text-light); line-height: 1.8; font-size: 1.1rem;">
                        <?= nl2br(clean($recipe['description'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Ingredients -->
                <?php if (!empty($ingredients)): ?>
                <div class="content-card">
                    <h2><i class="fas fa-shopping-basket"></i> Malzemeler</h2>
                    <ul class="ingredients-list">
                        <?php foreach ($ingredients as $ing): ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <strong><?= clean($ing['ingredient_name']) ?></strong>
                            <?php if (!empty($ing['quantity']) || !empty($ing['unit'])): ?>
                                - <?= clean($ing['quantity'] . ' ' . $ing['unit']) ?>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Steps -->
                <?php if (!empty($steps)): ?>
                <div class="content-card">
                    <h2><i class="fas fa-list-ol"></i> Yapılışı</h2>
                    <?php foreach ($steps as $step): ?>
                    <div class="step-item">
                        <div class="step-number"><?= $step['step_number'] ?></div>
                        <div class="step-content">
                            <p><?= nl2br(clean($step['instruction'])) ?></p>
                            <?php if (!empty($step['image'])): ?>
                                <img src="<?= clean($step['image']) ?>"
                                     alt="Adım <?= $step['step_number'] ?>"
                                     style="max-width: 100%; border-radius: 12px; margin-top: 1rem;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Tips -->
                <?php if (!empty($recipe['tips'])): ?>
                <div class="content-card">
                    <h2><i class="fas fa-lightbulb"></i> İpuçları</h2>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        <?= nl2br(clean($recipe['tips'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Storage Info -->
                <?php if (!empty($recipe['storage_info'])): ?>
                <div class="content-card">
                    <h2><i class="fas fa-box"></i> Saklama Bilgisi</h2>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        <?= nl2br(clean($recipe['storage_info'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Comments -->
                <div class="content-card">
                    <h2><i class="fas fa-comments"></i> Yorumlar (<?= count($comments) ?>)</h2>
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-card">
                            <div class="comment-header">
                                <img src="<?= clean($comment['profile_photo'] ?? '/images/default-avatar.png') ?>"
                                     alt="<?= clean($comment['full_name']) ?>"
                                     class="commenter-photo">
                                <div>
                                    <h5 style="margin: 0; color: var(--text-dark);">
                                        <?= clean($comment['full_name']) ?>
                                    </h5>
                                    <small class="text-muted">
                                        <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <p style="color: var(--text-light); margin: 0;">
                                <?= nl2br(clean($comment['comment'])) ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-light); text-align: center; padding: 2rem;">
                            Henüz yorum yapılmamış. İlk yorumu siz yapın!
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sidebar-widget">
                    <!-- Author Info -->
                    <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px solid #f1f5f9;">
                        <img src="<?= clean($recipe['author_photo'] ?? '/images/default-avatar.png') ?>"
                             alt="<?= clean($recipe['author_name']) ?>"
                             style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                        <h4 style="color: var(--text-dark); margin-bottom: 0.5rem;">
                            <?= clean($recipe['author_name']) ?>
                        </h4>
                        <small style="color: var(--text-light);">Tarif Yazarı</small>
                    </div>

                    <!-- Rating -->
                    <div style="margin-bottom: 2rem;">
                        <div class="rating-display" style="justify-content: center; margin-bottom: 1rem;">
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= round($recipe['rating_avg']) ? 'fas' : 'far' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span style="color: var(--text-dark); font-weight: 700;">
                                <?= number_format($recipe['rating_avg'], 1) ?>
                            </span>
                        </div>
                        <p style="text-align: center; color: var(--text-light); font-size: 0.9rem; margin: 0;">
                            <?= $recipe['rating_count'] ?> değerlendirme
                        </p>
                    </div>

                    <!-- Favorite Button -->
                    <button class="btn-favorite <?= $isFavorited ? 'active' : '' ?>" style="width: 100%; margin-bottom: 1rem;">
                        <i class="<?= $isFavorited ? 'fas' : 'far' ?> fa-heart me-2"></i>
                        <?= $isFavorited ? 'Favorilerde' : 'Favorilere Ekle' ?>
                    </button>

                    <!-- Stats -->
                    <div style="text-align: center; padding-top: 2rem; border-top: 2px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-around;">
                            <div>
                                <i class="fas fa-eye" style="color: var(--primary); font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <div style="font-weight: 700; color: var(--text-dark);">
                                    <?= number_format($recipe['views_count']) ?>
                                </div>
                                <small style="color: var(--text-light);">Görüntüleme</small>
                            </div>
                            <div>
                                <i class="fas fa-heart" style="color: #ef4444; font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <div style="font-weight: 700; color: var(--text-dark);">
                                    <?= number_format($recipe['likes_count']) ?>
                                </div>
                                <small style="color: var(--text-light);">Beğeni</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nutrition Facts -->
                <div class="sidebar-widget">
                    <h3 style="color: var(--text-dark); font-weight: 700; margin-bottom: 1.5rem; text-align: center;">
                        <i class="fas fa-chart-pie me-2" style="color: var(--primary);"></i>
                        Besin Değerleri
                    </h3>
                    <div class="nutrition-grid">
                        <div class="nutrition-item">
                            <i class="fas fa-fire"></i>
                            <span class="nutrition-value"><?= $recipe['calories_per_serving'] ?></span>
                            <div class="nutrition-label">Kalori</div>
                        </div>
                        <div class="nutrition-item">
                            <i class="fas fa-drumstick-bite"></i>
                            <span class="nutrition-value"><?= number_format($recipe['protein'], 1) ?>g</span>
                            <div class="nutrition-label">Protein</div>
                        </div>
                        <div class="nutrition-item">
                            <i class="fas fa-bread-slice"></i>
                            <span class="nutrition-value"><?= number_format($recipe['carbs'], 1) ?>g</span>
                            <div class="nutrition-label">Karbonhidrat</div>
                        </div>
                        <div class="nutrition-item">
                            <i class="fas fa-bacon"></i>
                            <span class="nutrition-value"><?= number_format($recipe['fat'], 1) ?>g</span>
                            <div class="nutrition-label">Yağ</div>
                        </div>
                        <?php if (!empty($recipe['fiber'])): ?>
                        <div class="nutrition-item">
                            <i class="fas fa-leaf"></i>
                            <span class="nutrition-value"><?= number_format($recipe['fiber'], 1) ?>g</span>
                            <div class="nutrition-label">Lif</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Diet Tags -->
                <?php if (!empty($dietTags)): ?>
                <div class="sidebar-widget">
                    <h3 style="color: var(--text-dark); font-weight: 700; margin-bottom: 1.5rem; text-align: center;">
                        <i class="fas fa-tags me-2" style="color: var(--primary);"></i>
                        Diyet Etiketleri
                    </h3>
                    <div style="text-align: center;">
                        <?php foreach ($dietTags as $tag): ?>
                            <span class="diet-tag" style="<?= !empty($tag['color']) ? 'background-color: ' . clean($tag['color']) . '20; color: ' . clean($tag['color']) : '' ?>">
                                <?= clean($tag['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
