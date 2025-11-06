<?php
/**
 * Test Randevuları Oluştur
 * Bu dosyayı tarayıcıdan bir kez çalıştırın
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Sadece development ortamında çalışsın
if (getenv('APP_ENV') === 'production') {
    die('Bu script production ortamında çalıştırılamaz!');
}

try {
    $conn = $db->getConnection();

    // Test Diyetisyen
    $stmt = $conn->prepare("
        INSERT INTO users (full_name, email, password_hash, user_type, is_active, email_verified, created_at)
        VALUES (?, ?, ?, 'dietitian', 1, 1, NOW())
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
    ");
    $stmt->execute([
        'Dr. Ayşe Yılmaz',
        'diyetisyen@test.com',
        password_hash('password', PASSWORD_DEFAULT)
    ]);
    $dietitianId = $conn->lastInsertId() ?: $conn->query("SELECT id FROM users WHERE email='diyetisyen@test.com'")->fetchColumn();

    // Diyetisyen profili
    $stmt = $conn->prepare("
        INSERT INTO dietitian_profiles (user_id, title, specialization, consultation_fee, is_approved, rating_avg, total_clients)
        VALUES (?, 'Diyetisyen', 'Kilo Yönetimi', 300.00, 1, 4.8, 25)
        ON DUPLICATE KEY UPDATE user_id=user_id
    ");
    $stmt->execute([$dietitianId]);

    // Test Danışan
    $stmt = $conn->prepare("
        INSERT INTO users (full_name, email, password_hash, user_type, is_active, email_verified, created_at)
        VALUES (?, ?, ?, 'client', 1, 1, NOW())
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
    ");
    $stmt->execute([
        'Mehmet Demir',
        'danisan@test.com',
        password_hash('password', PASSWORD_DEFAULT)
    ]);
    $clientId = $conn->lastInsertId() ?: $conn->query("SELECT id FROM users WHERE email='danisan@test.com'")->fetchColumn();

    // Eski randevuları temizle
    $conn->prepare("DELETE FROM appointments WHERE dietitian_id = ? OR client_id = ?")->execute([$dietitianId, $clientId]);

    // Test Randevuları
    $appointments = [
        [
            'date' => date('Y-m-d'),
            'start' => date('H:i:00', strtotime('-5 minutes')),
            'end' => date('H:i:00', strtotime('+40 minutes')),
            'label' => 'ŞU ANDA AKTİF - Hemen katılabilirsiniz!'
        ],
        [
            'date' => date('Y-m-d'),
            'start' => date('H:i:00', strtotime('+15 minutes')),
            'end' => date('H:i:00', strtotime('+60 minutes')),
            'label' => '15 dakika sonra - Katılıma hazır'
        ],
        [
            'date' => date('Y-m-d', strtotime('+1 day')),
            'start' => '10:00:00',
            'end' => '10:45:00',
            'label' => 'Yarın sabah 10:00'
        ],
        [
            'date' => date('Y-m-d', strtotime('+1 day')),
            'start' => '14:00:00',
            'end' => '14:45:00',
            'label' => 'Yarın öğleden sonra 14:00'
        ],
        [
            'date' => date('Y-m-d', strtotime('-2 days')),
            'start' => '10:00:00',
            'end' => '10:45:00',
            'label' => 'Geçmiş randevu (tamamlandı)',
            'status' => 'completed'
        ]
    ];

    $stmt = $conn->prepare("
        INSERT INTO appointments (
            dietitian_id, client_id, appointment_date, start_time, end_time,
            duration, status, is_paid, payment_amount, created_at
        ) VALUES (?, ?, ?, ?, ?, 45, ?, 1, 300.00, NOW())
    ");

    $createdCount = 0;
    foreach ($appointments as $apt) {
        $stmt->execute([
            $dietitianId,
            $clientId,
            $apt['date'],
            $apt['start'],
            $apt['end'],
            $apt['status'] ?? 'scheduled'
        ]);
        $createdCount++;
    }

    // Sonuçları göster
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Test Randevuları Oluşturuldu</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 2rem;
                font-family: 'Inter', sans-serif;
            }
            .container {
                max-width: 900px;
            }
            .card {
                border-radius: 20px;
                border: none;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .card-header {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                border-radius: 20px 20px 0 0 !important;
                padding: 2rem;
            }
            .badge-custom {
                padding: 0.5rem 1rem;
                border-radius: 50px;
                font-size: 0.9rem;
            }
            .btn-custom {
                border-radius: 50px;
                padding: 0.75rem 2rem;
                font-weight: 600;
                transition: all 0.3s;
            }
            .btn-custom:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            }
            .info-box {
                background: #f8fafc;
                border-left: 4px solid #10b981;
                padding: 1.5rem;
                border-radius: 12px;
                margin: 1rem 0;
            }
            .success-icon {
                font-size: 4rem;
                color: #10b981;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="card-header text-center">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h2 class="mb-0">✅ Test Verisi Oluşturuldu!</h2>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong><?= $createdCount ?> test randevusu</strong> başarıyla oluşturuldu!
                    </div>

                    <h4 class="mt-4 mb-3"><i class="fas fa-user me-2"></i>Test Hesapları</h4>

                    <div class="info-box">
                        <h5><i class="fas fa-user-md text-primary me-2"></i>Diyetisyen Hesabı</h5>
                        <p class="mb-1"><strong>Email:</strong> <code>diyetisyen@test.com</code></p>
                        <p class="mb-1"><strong>Şifre:</strong> <code>password</code></p>
                        <p class="mb-0"><strong>Ad:</strong> Dr. Ayşe Yılmaz</p>
                    </div>

                    <div class="info-box">
                        <h5><i class="fas fa-user text-success me-2"></i>Danışan Hesabı</h5>
                        <p class="mb-1"><strong>Email:</strong> <code>danisan@test.com</code></p>
                        <p class="mb-1"><strong>Şifre:</strong> <code>password</code></p>
                        <p class="mb-0"><strong>Ad:</strong> Mehmet Demir</p>
                    </div>

                    <h4 class="mt-4 mb-3"><i class="fas fa-calendar-alt me-2"></i>Oluşturulan Randevular</h4>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td>
                                        <?php if (($apt['status'] ?? 'scheduled') === 'completed'): ?>
                                            <span class="badge bg-secondary">Tamamlandı</span>
                                        <?php elseif (strtotime($apt['date'] . ' ' . $apt['start']) <= time()): ?>
                                            <span class="badge bg-success">🔴 AKTİF</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Yaklaşan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d.m.Y', strtotime($apt['date'])) ?></td>
                                    <td><?= date('H:i', strtotime($apt['start'])) ?></td>
                                    <td><?= $apt['label'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="mt-4 mb-3"><i class="fas fa-rocket me-2"></i>Şimdi Ne Yapmalı?</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="/login.php" class="btn btn-success btn-custom w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Giriş Yap
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="/client/dashboard.php" class="btn btn-primary btn-custom w-100">
                                <i class="fas fa-user me-2"></i>
                                Danışan Paneli
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="/dietitian/dashboard.php" class="btn btn-info btn-custom w-100">
                                <i class="fas fa-user-md me-2"></i>
                                Diyetisyen Paneli
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="/" class="btn btn-secondary btn-custom w-100">
                                <i class="fas fa-home me-2"></i>
                                Ana Sayfa
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>İpucu:</strong> Aktif randevuyu test etmek için <code>danisan@test.com</code> ile giriş yapıp
                        <strong>"Görüşmeye Katıl"</strong> butonuna tıklayın!
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Not:</strong> Bu dosyayı production ortamına deploy etmeyin!
                        Sadece local test için kullanılır.
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Hata: ' . $e->getMessage() . '</div>';
    error_log('Test appointment creation error: ' . $e->getMessage());
}
