<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    redirect('/login.php');
}

$user = $auth->user();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($firstName)) $errors[] = 'Ad gereklidir.';
        if (empty($lastName)) $errors[] = 'Soyad gereklidir.';
        if (empty($email)) $errors[] = 'Email gereklidir.';

        if (empty($errors)) {
            try {
                $conn = $db->getConnection();
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                $fullName = trim($firstName . ' ' . $lastName);
                $stmt->execute([$fullName, $email, $phone, $user->getId()]);
                $success = true;
                setFlash('success', 'Profil güncellendi!');
                redirect('/admin/profile.php');
            } catch (Exception $e) {
                $errors[] = 'Güncelleme hatası: ' . $e->getMessage();
            }
        }
    }
}

$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user->getId()]);
$userData = $stmt->fetch();

// Split full_name into first and last name
$nameParts = explode(' ', $userData['full_name'] ?? '', 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';

$pageTitle = 'Profilim';
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
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <h2 class="mb-4">Profilim</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= clean($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($msg = getFlash('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?= clean($msg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profil Bilgileri</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Ad</label>
                                                <input type="text" name="first_name" class="form-control"
                                                       value="<?= clean($firstName) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Soyad</label>
                                                <input type="text" name="last_name" class="form-control"
                                                       value="<?= clean($lastName) ?>" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control"
                                                   value="<?= clean($userData['email']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Telefon</label>
                                            <input type="tel" name="phone" class="form-control"
                                                   value="<?= clean($userData['phone'] ?? '') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Kullanıcı Tipi</label>
                                            <input type="text" class="form-control"
                                                   value="<?= clean($userData['user_type'] ?? '') ?>" disabled>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Güncelle
                                        </button>
                                        <a href="/admin/dashboard.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>İptal
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
