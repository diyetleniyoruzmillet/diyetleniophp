<?php
/**
 * Diyetlenio - Danışan Değerlendirme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();
$appointmentId = $_GET['appointment'] ?? null;

if (!$appointmentId) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/client/appointments.php');
}

// Randevu bilgilerini çek
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as dietitian_name, dp.title as dietitian_title
    FROM appointments a
    INNER JOIN users u ON a.dietitian_id = u.id
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE a.id = ? AND a.client_id = ? AND a.status = 'completed'
");
$stmt->execute([$appointmentId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Geçerli bir tamamlanmış randevu bulunamadı.');
    redirect('/client/appointments.php');
}

// Daha önce değerlendirme yapılmış mı kontrol et
$stmt = $conn->prepare("SELECT * FROM reviews WHERE appointment_id = ?");
$stmt->execute([$appointmentId]);
$existingReview = $stmt->fetch();

$success = false;

// Değerlendirme gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $rating = (int)$_POST['rating'];
        $review = trim($_POST['review']);

        $errors = [];

        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Lütfen 1-5 arası puan verin.';
        }

        if (empty($review) || strlen($review) < 10) {
            $errors[] = 'Değerlendirme en az 10 karakter olmalıdır.';
        }

        if (empty($errors)) {
            if ($existingReview) {
                // Güncelle
                $stmt = $conn->prepare("
                    UPDATE reviews
                    SET rating = ?, review = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$rating, $review, $existingReview['id']]);
            } else {
                // Yeni ekle
                $stmt = $conn->prepare("
                    INSERT INTO reviews (
                        client_id, dietitian_id, appointment_id,
                        rating, review, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $appointment['dietitian_id'],
                    $appointmentId,
                    $rating,
                    $review
                ]);
            }

            $success = true;
        }
    }
}

$pageTitle = 'Değerlendirme Yap';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        .content-wrapper { padding: 30px; }
        .rating-stars {
            font-size: 2.5rem;
            cursor: pointer;
        }
        .rating-stars .star {
            color: #ddd;
            transition: color 0.2s;
        }
        .rating-stars .star.active,
        .rating-stars .star:hover {
            color: #ffc107;
        }
        .success-animation {
            text-align: center;
            padding: 50px 0;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: bounceIn 0.6s ease-out;
        }
        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .success-icon i {
            font-size: 50px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/client/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/client/dietitians.php">
                            <i class="fas fa-user-md me-2"></i>Diyetisyenler
                        </a>
                        <a class="nav-link" href="/client/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link" href="/client/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planlarım
                        </a>
                        <a class="nav-link" href="/client/weight-tracking.php">
                            <i class="fas fa-weight me-2"></i>Kilo Takibi
                        </a>
                        <a class="nav-link" href="/client/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link" href="/client/profile.php">
                            <i class="fas fa-user me-2"></i>Profilim
                        </a>
                        <hr class="text-white-50 my-3">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                        <a class="nav-link" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <?php if ($success): ?>
                                <!-- Success Message -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="success-animation">
                                            <div class="success-icon">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <h3>Teşekkürler!</h3>
                                            <p class="text-muted">
                                                Değerlendirmeniz başarıyla kaydedildi.
                                            </p>
                                            <div class="mt-4">
                                                <a href="/client/appointments.php" class="btn btn-success me-2">
                                                    <i class="fas fa-calendar-check me-2"></i>Randevularıma Dön
                                                </a>
                                                <a href="/client/dashboard.php" class="btn btn-outline-success">
                                                    <i class="fas fa-home me-2"></i>Dashboard
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Review Form -->
                                <div class="card">
                                    <div class="card-body">
                                        <h3 class="mb-4">
                                            <i class="fas fa-star text-warning me-2"></i>Değerlendirme Yap
                                        </h3>

                                        <!-- Appointment Info -->
                                        <div class="alert alert-info mb-4">
                                            <h5 class="mb-2"><?= clean($appointment['dietitian_name']) ?></h5>
                                            <p class="mb-1">
                                                <strong><?= clean($appointment['dietitian_title']) ?></strong>
                                            </p>
                                            <p class="mb-0">
                                                <i class="fas fa-calendar me-2"></i>
                                                Randevu Tarihi: <?= date('d.m.Y H:i', strtotime($appointment['appointment_date'])) ?>
                                            </p>
                                        </div>

                                        <?php if ($existingReview): ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Bu randevu için daha önce değerlendirme yapmıştınız.
                                                Aşağıdaki formu kullanarak değerlendirmenizi güncelleyebilirsiniz.
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($errors)): ?>
                                            <div class="alert alert-danger">
                                                <strong>Hatalar:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <?php foreach ($errors as $error): ?>
                                                        <li><?= clean($error) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                            <!-- Rating -->
                                            <div class="mb-4 text-center">
                                                <label class="form-label d-block mb-3">
                                                    <h5>Puanınız</h5>
                                                </label>
                                                <div class="rating-stars" id="ratingStars">
                                                    <i class="fas fa-star star" data-rating="1"></i>
                                                    <i class="fas fa-star star" data-rating="2"></i>
                                                    <i class="fas fa-star star" data-rating="3"></i>
                                                    <i class="fas fa-star star" data-rating="4"></i>
                                                    <i class="fas fa-star star" data-rating="5"></i>
                                                </div>
                                                <input type="hidden" name="rating" id="rating" value="<?= $existingReview['rating'] ?? '' ?>" required>
                                                <div class="text-muted mt-2" id="ratingText"></div>
                                            </div>

                                            <!-- Review Text -->
                                            <div class="mb-4">
                                                <label class="form-label">
                                                    <h5>Değerlendirmeniz</h5>
                                                </label>
                                                <textarea
                                                    name="review"
                                                    class="form-control"
                                                    rows="6"
                                                    placeholder="Deneyiminizi paylaşın... (En az 10 karakter)"
                                                    required
                                                ><?= $existingReview['review'] ?? '' ?></textarea>
                                                <small class="text-muted">
                                                    Diyetisyenin profesyonelliği, iletişimi ve sunduğu hizmetin kalitesi hakkında görüşlerinizi yazabilirsiniz.
                                                </small>
                                            </div>

                                            <!-- Submit -->
                                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                                <a href="/client/appointments.php" class="btn btn-secondary">
                                                    <i class="fas fa-arrow-left me-2"></i>Geri Dön
                                                </a>
                                                <button type="submit" name="submit_review" class="btn btn-success btn-lg">
                                                    <i class="fas fa-paper-plane me-2"></i>
                                                    <?= $existingReview ? 'Değerlendirmeyi Güncelle' : 'Gönder' ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');
        const ratingText = document.getElementById('ratingText');

        const ratingLabels = {
            1: 'Çok Kötü',
            2: 'Kötü',
            3: 'Orta',
            4: 'İyi',
            5: 'Mükemmel'
        };

        // Set initial rating if exists
        const initialRating = ratingInput.value;
        if (initialRating) {
            updateStars(parseInt(initialRating));
        }

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                updateStars(rating);
            });

            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                highlightStars(rating);
            });
        });

        document.getElementById('ratingStars').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            if (currentRating) {
                updateStars(currentRating);
            } else {
                stars.forEach(s => s.classList.remove('active'));
                ratingText.textContent = '';
            }
        });

        function highlightStars(rating) {
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            ratingText.textContent = ratingLabels[rating];
        }

        function updateStars(rating) {
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            ratingText.textContent = ratingLabels[rating];
        }
    </script>
</body>
</html>
