<?php
/**
 * Dietitian Recipes Management
 * Diyetisyen tarafından tarif oluşturma ve yönetme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Auth kontrolü - Diyetisyen olmalı
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();
$dietitianId = $auth->id();
$pageTitle = "Tariflerim";

// Form gönderimi - Yeni tarif veya güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $recipeId = (int) ($_POST['recipe_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prepTime = (int) ($_POST['prep_time'] ?? 0);
        $cookTime = (int) ($_POST['cook_time'] ?? 0);
        $servings = (int) ($_POST['servings'] ?? 1);
        $calories = (int) ($_POST['calories'] ?? 0);
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $submitAction = $_POST['submit_action'] ?? 'save_draft'; // save_draft or submit_for_approval

        $errors = [];

        if (empty($title)) $errors[] = 'Tarif başlığı gereklidir.';
        if (empty($description)) $errors[] = 'Tarif açıklaması gereklidir.';

        if (empty($errors)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $status = ($submitAction === 'submit_for_approval') ? 'pending' : 'draft';

            try {
                if ($action === 'update' && $recipeId > 0) {
                    // Tariflerin sahibi olduğunu kontrol et
                    $stmt = $conn->prepare("SELECT id FROM recipes WHERE id = ? AND author_id = ?");
                    $stmt->execute([$recipeId, $dietitianId]);

                    if ($stmt->fetch()) {
                        $stmt = $conn->prepare("
                            UPDATE recipes SET
                                title = ?,
                                slug = ?,
                                description = ?,
                                prep_time = ?,
                                cook_time = ?,
                                servings = ?,
                                calories_per_serving = ?,
                                difficulty = ?,
                                status = ?,
                                updated_at = NOW()
                            WHERE id = ? AND author_id = ?
                        ");

                        $stmt->execute([
                            $title, $slug, $description, $prepTime, $cookTime,
                            $servings, $calories, $difficulty, $status,
                            $recipeId, $dietitianId
                        ]);

                        $successMessage = ($submitAction === 'submit_for_approval')
                            ? 'Tarif onay için gönderildi!'
                            : 'Tarif güncellendi!';
                    }
                } else {
                    // Yeni tarif oluştur
                    $stmt = $conn->prepare("
                        INSERT INTO recipes (
                            author_id, title, slug, description, prep_time, cook_time,
                            servings, calories_per_serving, difficulty, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");

                    $stmt->execute([
                        $dietitianId, $title, $slug, $description, $prepTime, $cookTime,
                        $servings, $calories, $difficulty, $status
                    ]);

                    $successMessage = ($submitAction === 'submit_for_approval')
                        ? 'Tarif onay için gönderildi!'
                        : 'Tarif taslak olarak kaydedildi!';
                }
            } catch (Exception $e) {
                error_log('Recipe save error: ' . $e->getMessage());
                $errorMessage = 'Tarif kaydedilirken bir hata oluştu.';
            }
        }
    } elseif ($action === 'delete') {
        $recipeId = (int) ($_POST['recipe_id'] ?? 0);

        try {
            // Sadece draft ve rejected tarifleri silebilir
            $stmt = $conn->prepare("
                DELETE FROM recipes
                WHERE id = ? AND author_id = ? AND status IN ('draft', 'rejected')
            ");
            $stmt->execute([$recipeId, $dietitianId]);

            $successMessage = 'Tarif silindi!';
        } catch (Exception $e) {
            error_log('Recipe delete error: ' . $e->getMessage());
            $errorMessage = 'Tarif silinirken bir hata oluştu.';
        }
    }
}

// Tarifleri getir
$statusFilter = $_GET['status'] ?? 'all';
$whereClause = "author_id = ?";
$params = [$dietitianId];

if ($statusFilter !== 'all') {
    $whereClause .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmt = $conn->prepare("
    SELECT r.*,
           (SELECT COUNT(*) FROM recipe_ratings WHERE recipe_id = r.id) as rating_count_calc
    FROM recipes r
    WHERE $whereClause
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// İstatistikler
$stats = $conn->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM recipes
    WHERE author_id = ?
");
$stats->execute([$dietitianId]);
$stats = $stats->fetch();

require_once __DIR__ . '/../../includes/partials/header.php';
?>

<div class="container my-5">
    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="bi bi-book"></i> Tariflerim
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recipeModal">
            <i class="bi bi-plus-lg"></i> Yeni Tarif Ekle
        </button>
    </div>

    <!-- Başarı/Hata Mesajları -->
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                    <small>Toplam Tarif</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['draft_count'] ?></h3>
                    <small>Taslak</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['pending_count'] ?></h3>
                    <small>Onay Bekliyor</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['approved_count'] ?></h3>
                    <small>Onaylı</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?status=all" class="btn btn-outline-primary <?= $statusFilter === 'all' ? 'active' : '' ?>">
                    Tümü (<?= $stats['total'] ?>)
                </a>
                <a href="?status=draft" class="btn btn-outline-secondary <?= $statusFilter === 'draft' ? 'active' : '' ?>">
                    Taslak (<?= $stats['draft_count'] ?>)
                </a>
                <a href="?status=pending" class="btn btn-outline-warning <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                    Onay Bekliyor (<?= $stats['pending_count'] ?>)
                </a>
                <a href="?status=approved" class="btn btn-outline-success <?= $statusFilter === 'approved' ? 'active' : '' ?>">
                    Onaylı (<?= $stats['approved_count'] ?>)
                </a>
                <a href="?status=rejected" class="btn btn-outline-danger <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
                    Reddedildi (<?= $stats['rejected_count'] ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Tarifler Listesi -->
    <div class="row g-4">
        <?php if (empty($recipes)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> Henüz tarif eklemediniz.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($recipes as $recipe): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <?php if ($recipe['featured_image']): ?>
                            <img src="<?= htmlspecialchars($recipe['featured_image']) ?>"
                                 class="card-img-top"
                                 alt="<?= htmlspecialchars($recipe['title']) ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($recipe['title']) ?></h5>
                                <?php
                                $statusBadge = [
                                    'draft' => '<span class="badge bg-secondary">Taslak</span>',
                                    'pending' => '<span class="badge bg-warning">Bekliyor</span>',
                                    'approved' => '<span class="badge bg-success">Onaylı</span>',
                                    'rejected' => '<span class="badge bg-danger">Reddedildi</span>'
                                ];
                                echo $statusBadge[$recipe['status']] ?? '';
                                ?>
                            </div>

                            <p class="card-text small text-muted">
                                <?= htmlspecialchars(mb_substr($recipe['description'], 0, 100)) ?>...
                            </p>

                            <div class="d-flex gap-3 text-muted small mb-3">
                                <span><i class="bi bi-clock"></i> <?= $recipe['prep_time'] + $recipe['cook_time'] ?> dk</span>
                                <span><i class="bi bi-people"></i> <?= $recipe['servings'] ?> kişi</span>
                                <span><i class="bi bi-heart"></i> <?= $recipe['like_count'] ?></span>
                            </div>

                            <?php if ($recipe['status'] === 'rejected' && $recipe['rejection_reason']): ?>
                                <div class="alert alert-danger small py-2">
                                    <strong>Red Nedeni:</strong><br>
                                    <?= htmlspecialchars($recipe['rejection_reason']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <?php if (in_array($recipe['status'], ['draft', 'rejected'])): ?>
                                    <button type="button" class="btn btn-sm btn-primary flex-fill"
                                            onclick="editRecipe(<?= htmlspecialchars(json_encode($recipe)) ?>)">
                                        <i class="bi bi-pencil"></i> Düzenle
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tarifi silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-info text-white flex-fill"
                                            onclick="viewRecipe(<?= htmlspecialchars(json_encode($recipe)) ?>)">
                                        <i class="bi bi-eye"></i> Görüntüle
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-footer bg-light small text-muted">
                            Oluşturulma: <?= date('d.m.Y H:i', strtotime($recipe['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Tarif Modal -->
<div class="modal fade" id="recipeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="recipeModalTitle">Yeni Tarif Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="modalAction" value="create">
                    <input type="hidden" name="recipe_id" id="recipeId">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tarif Başlığı *</label>
                            <input type="text" name="title" id="recipeTitle" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Zorluk</label>
                            <select name="difficulty" id="recipeDifficulty" class="form-select">
                                <option value="easy">Kolay</option>
                                <option value="medium" selected>Orta</option>
                                <option value="hard">Zor</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Hazırlık Süresi (dk)</label>
                            <input type="number" name="prep_time" id="recipePrepTime" class="form-control" min="0" value="15">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Pişirme Süresi (dk)</label>
                            <input type="number" name="cook_time" id="recipeCookTime" class="form-control" min="0" value="30">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Porsiyon</label>
                            <input type="number" name="servings" id="recipeServings" class="form-control" min="1" value="4">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Kalori (opsiyonel)</label>
                            <input type="number" name="calories" id="recipeCalories" class="form-control" min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Açıklama *</label>
                            <textarea name="description" id="recipeDescription" class="form-control" rows="5" required
                                      placeholder="Tarifin kısa açıklaması..."></textarea>
                            <small class="text-muted">Malzemeler ve yapılış talimatları admin tarafından eklenecektir.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="submit_action" value="save_draft" class="btn btn-outline-primary">
                        <i class="bi bi-save"></i> Taslak Olarak Kaydet
                    </button>
                    <button type="submit" name="submit_action" value="submit_for_approval" class="btn btn-primary">
                        <i class="bi bi-send"></i> Onay İçin Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Görüntüleme Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
function editRecipe(recipe) {
    document.getElementById('recipeModalTitle').textContent = 'Tarifi Düzenle';
    document.getElementById('modalAction').value = 'update';
    document.getElementById('recipeId').value = recipe.id;
    document.getElementById('recipeTitle').value = recipe.title;
    document.getElementById('recipeDifficulty').value = recipe.difficulty;
    document.getElementById('recipePrepTime').value = recipe.prep_time;
    document.getElementById('recipeCookTime').value = recipe.cook_time;
    document.getElementById('recipeServings').value = recipe.servings;
    document.getElementById('recipeCalories').value = recipe.calories_per_serving || '';
    document.getElementById('recipeDescription').value = recipe.description;

    new bootstrap.Modal(document.getElementById('recipeModal')).show();
}

function viewRecipe(recipe) {
    document.getElementById('viewModalTitle').textContent = recipe.title;

    let statusBadge = {
        'draft': '<span class="badge bg-secondary">Taslak</span>',
        'pending': '<span class="badge bg-warning">Onay Bekliyor</span>',
        'approved': '<span class="badge bg-success">Onaylı</span>',
        'rejected': '<span class="badge bg-danger">Reddedildi</span>'
    }[recipe.status] || '';

    document.getElementById('viewModalBody').innerHTML = `
        <div class="mb-3">${statusBadge}</div>

        ${recipe.featured_image ? `
            <div class="mb-3">
                <img src="${recipe.featured_image}" class="img-fluid rounded" alt="${recipe.title}">
            </div>
        ` : ''}

        <div class="mb-3">
            <div class="d-flex gap-4 text-muted">
                <span><i class="bi bi-clock"></i> Hazırlık: ${recipe.prep_time} dk</span>
                <span><i class="bi bi-fire"></i> Pişirme: ${recipe.cook_time} dk</span>
                <span><i class="bi bi-people"></i> ${recipe.servings} kişilik</span>
                <span><i class="bi bi-speedometer"></i> ${recipe.difficulty === 'easy' ? 'Kolay' : recipe.difficulty === 'hard' ? 'Zor' : 'Orta'}</span>
                ${recipe.calories_per_serving ? `<span><i class="bi bi-lightning"></i> ${recipe.calories_per_serving} kcal</span>` : ''}
            </div>
        </div>

        <div class="mb-3">
            <h6>Açıklama:</h6>
            <p>${recipe.description}</p>
        </div>

        ${recipe.rejection_reason ? `
            <div class="alert alert-danger">
                <strong>Red Nedeni:</strong><br>${recipe.rejection_reason}
            </div>
        ` : ''}

        <div class="text-muted small">
            <div>Oluşturulma: ${new Date(recipe.created_at).toLocaleDateString('tr-TR')}</div>
            ${recipe.updated_at ? `<div>Son Güncelleme: ${new Date(recipe.updated_at).toLocaleDateString('tr-TR')}</div>` : ''}
        </div>
    `;

    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

// Modal temizleme
document.getElementById('recipeModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('recipeModalTitle').textContent = 'Yeni Tarif Ekle';
    document.getElementById('modalAction').value = 'create';
    document.getElementById('recipeId').value = '';
    this.querySelector('form').reset();
});
</script>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
