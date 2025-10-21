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
                $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$firstName, $lastName, $email, $phone, $user->getId()]);
                $success = true;
                setFlash('success', 'Profil güncellendi!');
            } catch (Exception $e) {
                $errors[] = 'Güncelleme hatası: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user->getId()]);
$userData = $stmt->fetch();

include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Profilim</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= clean($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">Profil başarıyla güncellendi!</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profil Bilgileri</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Ad</label>
                                <input type="text" name="first_name" class="form-control" value="<?= clean($userData['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Soyad</label>
                                <input type="text" name="last_name" class="form-control" value="<?= clean($userData['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= clean($userData['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="phone" class="form-control" value="<?= clean($userData['phone'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
