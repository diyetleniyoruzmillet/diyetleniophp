<?php
/**
 * Diyetlenio - Admin Tarif Ekleme/Düzenleme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$errors = [];
$recipe = null;
$isEdit = false;

// Düzenleme modu kontrolü
if (isset($_GET['id'])) {
    $recipeId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ?");
    $stmt->execute([$recipeId]);
    $recipe = $stmt->fetch();

    if ($recipe) {
        $isEdit = true;
    } else {
        setFlash('error', 'Tarif bulunamadı.');
        redirect('/admin/recipes.php');
    }
}

// Form gönderildi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        $validator = new Validator($_POST);
        $validator
            ->required(['title', 'description', 'ingredients', 'instructions', 'prep_time', 'cook_time', 'servings'])
            ->min('title', 3)
            ->max('title', 200)
            ->min('description', 20)
            ->min('ingredients', 10)
            ->min('instructions', 20)
            ->numeric('prep_time')
            ->numeric('cook_time')
            ->numeric('servings');

        if ($validator->fails()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        if (empty($errors)) {
            $title = sanitizeString($_POST['title'], 200);
            $description = sanitizeString($_POST['description'], 1000);
            $ingredients = $_POST['ingredients']; // Text/JSON
            $instructions = $_POST['instructions']; // Text/HTML
            $prepTime = (int)$_POST['prep_time'];
            $cookTime = (int)$_POST['cook_time'];
            $servings = (int)$_POST['servings'];
            $category = sanitizeString($_POST['category'] ?? 'genel', 50);
            $calories = isset($_POST['calories']) && $_POST['calories'] !== '' ? (int)$_POST['calories'] : null;
            $imageUrl = sanitizeString($_POST['image_url'] ?? '', 500);
            $isPublished = isset($_POST['is_published']) ? 1 : 0;
            $authorId = $auth->user()->getId();

            // Slug oluştur
            $slug = sanitizeString(
                strtolower(
                    preg_replace('/[^a-z0-9]+/i', '-',
                    str_replace(['ı', 'ş', 'ğ', 'ü', 'ö', 'ç', 'İ', 'Ş', 'Ğ', 'Ü', 'Ö', 'Ç'],
                                ['i', 's', 'g', 'u', 'o', 'c', 'i', 's', 'g', 'u', 'o', 'c'],
                                $title))
                ),
                200
            );
            $slug = trim($slug, '-');

            try {
                if ($isEdit) {
                    // Güncelleme
                    $stmt = $conn->prepare("
                        UPDATE recipes
                        SET title = ?,
                            slug = ?,
                            description = ?,
                            ingredients = ?,
                            instructions = ?,
                            prep_time = ?,
                            cook_time = ?,
                            servings = ?,
                            category = ?,
                            calories = ?,
                            image_url = ?,
                            is_published = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $ingredients, $instructions,
                        $prepTime, $cookTime, $servings, $category, $calories,
                        $imageUrl, $isPublished, $recipeId
                    ]);

                    setFlash('success', 'Tarif başarıyla güncellendi.');
                } else {
                    // Yeni ekleme
                    $stmt = $conn->prepare("
                        INSERT INTO recipes (title, slug, description, ingredients, instructions, prep_time, cook_time, servings, category, calories, image_url, is_published, author_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $ingredients, $instructions,
                        $prepTime, $cookTime, $servings, $category, $calories,
                        $imageUrl, $isPublished, $authorId
                    ]);

                    setFlash('success', 'Tarif başarıyla eklendi.');
                }

                redirect('/admin/recipes.php');

            } catch (Exception $e) {
                error_log('Recipe save error: ' . $e->getMessage());
                $errors[] = 'Tarif kaydedilirken bir hata oluştu: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Tarif Düzenle' : 'Yeni Tarif';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../includes/admin-styles.php'; ?>
    <style>
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?= $pageTitle ?></h2>
                        <a href="/admin/recipes.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Geri Dön
                        </a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= clean($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                        <div class="form-section">
                            <h5 class="mb-4">Temel Bilgiler</h5>

                            <div class="mb-3">
                                <label for="title" class="form-label">Tarif Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="<?= clean($recipe['title'] ?? $_POST['title'] ?? '') ?>"
                                       maxlength="200" required>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Kategori</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="genel">Genel</option>
                                    <?php
                                    $categories = [
                                        'kahvalti' => 'Kahvaltı',
                                        'ana-yemek' => 'Ana Yemek',
                                        'corba' => 'Çorba',
                                        'salata' => 'Salata',
                                        'tatli' => 'Tatlı',
                                        'atistirmalik' => 'Atıştırmalık'
                                    ];
                                    $selectedCategory = $recipe['category'] ?? $_POST['category'] ?? '';
                                    foreach ($categories as $value => $label):
                                    ?>
                                        <option value="<?= $value ?>" <?= $selectedCategory === $value ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Açıklama <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          required><?= clean($recipe['description'] ?? $_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="image_url" class="form-label">Resim URL</label>
                                <input type="url" class="form-control" id="image_url" name="image_url"
                                       value="<?= clean($recipe['image_url'] ?? $_POST['image_url'] ?? '') ?>"
                                       placeholder="https://example.com/image.jpg">
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-4">Malzemeler ve Talimatlar</h5>

                            <div class="mb-3">
                                <label for="ingredients" class="form-label">Malzemeler <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="ingredients" name="ingredients" rows="8"
                                          placeholder="Her satıra bir malzeme yazın&#10;Örnek:&#10;- 2 su bardağı un&#10;- 1 yemek kaşığı zeytinyağı"
                                          required><?= clean($recipe['ingredients'] ?? $_POST['ingredients'] ?? '') ?></textarea>
                                <small class="text-muted">Her malzemeyi yeni satıra yazın</small>
                            </div>

                            <div class="mb-3">
                                <label for="instructions" class="form-label">Yapılış <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="instructions" name="instructions" rows="10"
                                          required><?= clean($recipe['instructions'] ?? $_POST['instructions'] ?? '') ?></textarea>
                                <small class="text-muted">Adım adım yapılışı yazın</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-4">Detaylar</h5>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="prep_time" class="form-label">Hazırlama (dk) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="prep_time" name="prep_time"
                                           value="<?= clean($recipe['prep_time'] ?? $_POST['prep_time'] ?? '') ?>"
                                           min="0" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="cook_time" class="form-label">Pişirme (dk) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="cook_time" name="cook_time"
                                           value="<?= clean($recipe['cook_time'] ?? $_POST['cook_time'] ?? '') ?>"
                                           min="0" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="servings" class="form-label">Porsiyon <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="servings" name="servings"
                                           value="<?= clean($recipe['servings'] ?? $_POST['servings'] ?? '') ?>"
                                           min="1" required>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="calories" class="form-label">Kalori (porsiyon)</label>
                                    <input type="number" class="form-control" id="calories" name="calories"
                                           value="<?= clean($recipe['calories'] ?? $_POST['calories'] ?? '') ?>"
                                           min="0" placeholder="Opsiyonel">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5 class="mb-4">Yayın Ayarları</h5>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published"
                                       <?= ($recipe['is_published'] ?? $_POST['is_published'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_published">
                                    Tarifi Yayınla
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                            </button>
                            <a href="/admin/recipes.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
