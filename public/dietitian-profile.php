<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Diyetisyen bilgilerini çek
$conn = $db->getConnection();
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.profile_photo, u.created_at,
           dp.user_id, dp.title, dp.specialization, dp.about_me, dp.education,
           dp.experience_years, dp.consultation_fee, dp.rating_avg, dp.rating_count,
           dp.total_clients, dp.is_approved
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id = ? AND u.user_type = 'dietitian' AND u.is_active = 1 AND dp.is_approved = 1
");
$stmt->execute([$id]);
$dietitian = $stmt->fetch();

if (!$dietitian) {
    header('Location: /dietitians.php');
    exit;
}

// Yorumları/Reviews çek (en son 6 tanesi)
$reviewStmt = $conn->prepare("
    SELECT r.*, u.full_name as client_name, u.profile_photo as client_photo
    FROM reviews r
    LEFT JOIN users u ON r.client_id = u.id
    WHERE r.dietitian_id = ?
    ORDER BY r.created_at DESC
    LIMIT 6
");
$reviewStmt->execute([$id]);
$reviews = $reviewStmt->fetchAll();

$pageTitle = $dietitian['full_name'];
$showNavbar = true;
include __DIR__ . '/../includes/partials/header.php';
?>
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --secondary: #10b981;
            --accent: #f97316;
            --dark: #0f172a;
            --light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            background: var(--light);
        }

        /* Modern Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            padding: 80px 0 120px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .profile-photo-large {
            width: 200px;
            height: 200px;
            border-radius: 30px;
            border: 6px solid white;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            object-fit: cover;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        .profile-photo-large i {
            font-size: 5rem;
            color: var(--primary);
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .hero .specialty {
            font-size: 1.3rem;
            color: rgba(255,255,255,0.95);
            margin-bottom: 20px;
        }

        .rating-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 12px 25px;
            border-radius: 50px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .rating-badge i {
            color: #fbbf24;
        }

        .btn-book-now {
            background: white;
            color: var(--primary);
            padding: 18px 45px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-book-now:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            color: var(--primary);
        }

        /* Stats Cards */
        .stats-section {
            margin-top: -80px;
            position: relative;
            z-index: 2;
            margin-bottom: 60px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.icon-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.icon-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.icon-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.icon-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #64748b;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            transition: all 0.3s;
        }

        .info-card:hover {
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--dark);
            display: flex;
            align-items: center;
        }

        .info-card h3 i {
            margin-right: 15px;
            color: var(--primary);
        }

        .info-card p {
            color: #64748b;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Expertise Badges */
        .expertise-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin: 5px;
            font-size: 0.95rem;
            border: 2px solid var(--primary);
        }

        /* Reviews Section */
        .review-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .review-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .review-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .review-rating {
            color: #fbbf24;
        }

        .review-text {
            color: #64748b;
            line-height: 1.7;
            font-style: italic;
        }

        /* Sticky Booking Sidebar */
        .booking-sidebar {
            position: sticky;
            top: 100px;
        }

        .booking-card {
            background: white;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 2px solid var(--primary);
        }

        .price-tag {
            text-align: center;
            margin-bottom: 25px;
        }

        .price-amount {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .price-label {
            color: #64748b;
            font-size: 1rem;
            margin-top: 5px;
        }

        .info-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item i {
            width: 30px;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .info-item span {
            color: #64748b;
            font-size: 0.95rem;
        }

        .btn-book-large {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            padding: 18px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
            margin-top: 20px;
        }

        .btn-book-large:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.4);
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0;
            text-align: center;
            margin-top: 80px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .stat-number { font-size: 2rem; }
            .booking-sidebar { position: static; margin-top: 30px; }
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content text-center">
                <div class="profile-photo-large">
                    <?php if ($dietitian['profile_photo']): ?>
                        <?php $p=$dietitian['profile_photo']; $photoUrl='/assets/uploads/' . ltrim($p,'/'); ?>
                        <img src="<?= clean($photoUrl) ?>" alt="<?= clean($dietitian['full_name']) ?>" style="width:100%;height:100%;border-radius:30px;object-fit:cover;">
                    <?php else: ?>
                        <i class="fas fa-user-md"></i>
                    <?php endif; ?>
                </div>
                <h1><?= clean($dietitian['full_name']) ?></h1>
                <p class="specialty"><?= clean($dietitian['title'] ?? 'Diyetisyen') ?></p>
                <div class="rating-badge">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?= $i <= round($dietitian['rating_avg']) ? '' : ' far' ?>"></i>
                    <?php endfor; ?>
                    <span class="ms-2"><?= number_format($dietitian['rating_avg'], 1) ?> (<?= $dietitian['rating_count'] ?> değerlendirme)</span>
                </div>
                <br>
                <?php if ($auth->check()): ?>
                    <a href="/book-appointment.php?dietitian_id=<?= $id ?>" class="btn-book-now">
                        <i class="fas fa-calendar-check me-2"></i>Randevu Al
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="btn-book-now">
                        <i class="fas fa-sign-in-alt me-2"></i>Randevu İçin Giriş Yapın
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-1">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?= $dietitian['total_clients'] ?>+</div>
                        <div class="stat-label">Mutlu Danışan</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-2">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-number"><?= number_format($dietitian['rating_avg'], 1) ?></div>
                        <div class="stat-label">Ortalama Puan</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-3">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-number"><?= $dietitian['experience_years'] ?></div>
                        <div class="stat-label">Yıl Deneyim</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-4">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number">&lt;24</div>
                        <div class="stat-label">Saat Yanıt Süresi</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8">
                <!-- Hakkında -->
                <div class="info-card">
                    <h3><i class="fas fa-user-circle"></i>Hakkımda</h3>
                    <p><?= nl2br(clean($dietitian['about_me'] ?? 'Hakkımda bilgisi henüz eklenmemiş.')) ?></p>
                </div>

                <!-- Uzmanlık Alanları -->
                <?php if ($dietitian['specialization']): ?>
                <div class="info-card">
                    <h3><i class="fas fa-certificate"></i>Uzmanlık Alanlarım</h3>
                    <?php
                    $specializations = explode(',', $dietitian['specialization']);
                    foreach ($specializations as $spec):
                    ?>
                        <span class="expertise-badge"><?= clean(trim($spec)) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Eğitim -->
                <?php if ($dietitian['education']): ?>
                <div class="info-card">
                    <h3><i class="fas fa-graduation-cap"></i>Eğitim</h3>
                    <p><?= nl2br(clean($dietitian['education'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Yorumlar -->
                <?php if (count($reviews) > 0): ?>
                <div class="info-card">
                    <h3><i class="fas fa-comments"></i>Danışan Yorumları</h3>
                    <div class="row">
                        <?php foreach ($reviews as $review): ?>
                        <div class="col-md-6">
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-photo">
                                        <?php if ($review['client_photo']): ?>
                                            <img src="<?= clean($review['client_photo']) ?>" alt="<?= clean($review['client_name']) ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review-info">
                                        <h4><?= clean($review['client_name'] ?? 'Anonim') ?></h4>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= $review['rating'] ? '' : ' far' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <p class="review-text">"<?= clean($review['review'] ?? 'Harika bir deneyim!') ?>"</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sticky Booking Sidebar -->
            <div class="col-lg-4">
                <div class="booking-sidebar">
                    <div class="booking-card">
                        <div class="price-tag">
                            <div class="price-amount"><?= number_format($dietitian['consultation_fee'], 0) ?> ₺</div>
                            <div class="price-label">Randevu Başına</div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-video"></i>
                            <span>Online Video Görüşme</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span>45-60 Dakika</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Esnek Randevu Saatleri</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Kişisel Diyet Programı</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-comments"></i>
                            <span>WhatsApp Destek</span>
                        </div>

                        <?php if ($auth->check()): ?>
                            <a href="/book-appointment.php?dietitian_id=<?= $id ?>" class="btn btn-book-large">
                                <i class="fas fa-calendar-check me-2"></i>Randevu Al
                            </a>
                        <?php else: ?>
                            <a href="/login.php" class="btn btn-book-large">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yapın
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
        </div>
    </footer>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
