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
include __DIR__ . '/../../includes/client_header.php';
?>

<style>
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
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
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
</style>

<?php if ($success): ?>
    <div class="success-animation">
        <div class="success-icon">
            <i class="fas fa-check fa-3x text-white"></i>
        </div>
        <h3 class="text-success">Teşekkürler!</h3>
        <p class="text-muted mb-4">Değerlendirmeniz başarıyla kaydedildi.</p>
        <a href="/client/appointments.php" class="btn btn-success">
            <i class="fas fa-arrow-left me-2"></i>Randevularıma Dön
        </a>
    </div>
<?php else: ?>
    <h2 class="mb-4">Değerlendirme Yap</h2>

    <div class="card">
        <div class="card-body">
            <div class="mb-4">
                <h5>Randevu Bilgileri</h5>
                <p class="mb-1"><strong>Diyetisyen:</strong> <?= clean($appointment['dietitian_name']) ?></p>
                <p class="mb-1"><strong>Ünvan:</strong> <?= clean($appointment['dietitian_title']) ?></p>
                <p class="mb-0"><strong>Tarih:</strong> <?= date('d.m.Y H:i', strtotime($appointment['appointment_date'])) ?></p>
            </div>

            <?php if (isset($errors) && count($errors) > 0): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= clean($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <div class="mb-4">
                    <label class="form-label">Puan *</label>
                    <div class="rating-stars" id="ratingStars">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="<?= $existingReview['rating'] ?? 5 ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Değerlendirmeniz *</label>
                    <textarea name="review" class="form-control" rows="5" required
                              placeholder="Deneyiminizi paylaşın..."><?= $existingReview['review'] ?? '' ?></textarea>
                    <small class="text-muted">En az 10 karakter</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="submit_review" class="btn btn-success">
                        <i class="fas fa-save me-2"></i><?= $existingReview ? 'Güncelle' : 'Gönder' ?>
                    </button>
                    <a href="/client/appointments.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const currentRating = parseInt(ratingInput.value) || 5;

        // Set initial rating
        updateStars(currentRating);

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                ratingInput.value = rating;
                updateStars(rating);
            });

            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                updateStars(rating);
            });
        });

        document.getElementById('ratingStars').addEventListener('mouseout', function() {
            updateStars(parseInt(ratingInput.value));
        });

        function updateStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
