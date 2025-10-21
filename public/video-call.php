<?php
/**
 * Diyetlenio - Video Görüşme
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if (!$auth->check()) {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$appointmentId = $_GET['appointment'] ?? null;

if (!$appointmentId) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();
$userType = $auth->user()->getUserType();

// Randevu bilgilerini çek
$stmt = $conn->prepare("
    SELECT a.*,
           c.full_name as client_name,
           d.full_name as dietitian_name
    FROM appointments a
    INNER JOIN users c ON a.client_id = c.id
    INNER JOIN users d ON a.dietitian_id = d.id
    WHERE a.id = ?
    AND (a.client_id = ? OR a.dietitian_id = ?)
");
$stmt->execute([$appointmentId, $userId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Geçerli bir randevu bulunamadı.');
    redirect('/');
}

if (!$appointment['is_online']) {
    setFlash('error', 'Bu randevu online değil.');
    redirect('/');
}

$pageTitle = 'Video Görüşme';
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
        body {
            background: #1a1a1a;
            color: white;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .video-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .main-video {
            width: 100%;
            height: 100%;
            background: #2a2a2a;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .small-video {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 250px;
            height: 150px;
            background: #3a3a3a;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #fff;
        }
        .controls {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
        }
        .control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s;
        }
        .control-btn:hover {
            transform: scale(1.1);
        }
        .btn-mic {
            background: #4CAF50;
            color: white;
        }
        .btn-camera {
            background: #2196F3;
            color: white;
        }
        .btn-end {
            background: #f44336;
            color: white;
        }
        .info-bar {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            padding: 15px 20px;
            border-radius: 10px;
        }
        .placeholder-video {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #888;
        }
        .placeholder-video i {
            font-size: 100px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="video-container">
        <!-- Info Bar -->
        <div class="info-bar">
            <h5 class="mb-1">
                <?= $userType === 'client' ? clean($appointment['dietitian_name']) : clean($appointment['client_name']) ?>
            </h5>
            <small class="text-muted">
                <?= date('d.m.Y H:i', strtotime($appointment['appointment_date'])) ?>
            </small>
        </div>

        <!-- Main Video -->
        <div class="main-video">
            <div class="placeholder-video">
                <i class="fas fa-video-slash"></i>
                <h4>Video Görüşme</h4>
                <p>Gerçek video entegrasyonu için WebRTC veya üçüncü parti servis gereklidir.</p>
                <p class="text-muted">(Jitsi Meet, Zoom API, vb.)</p>
            </div>
        </div>

        <!-- Small Video (Self) -->
        <div class="small-video">
            <div class="placeholder-video h-100">
                <i class="fas fa-user-circle fa-3x"></i>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <button class="control-btn btn-mic" title="Mikrofon">
                <i class="fas fa-microphone"></i>
            </button>
            <button class="control-btn btn-camera" title="Kamera">
                <i class="fas fa-video"></i>
            </button>
            <button class="control-btn btn-end" title="Görüşmeyi Bitir" onclick="endCall()">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function endCall() {
            if (confirm('Görüşmeyi bitirmek istediğinize emin misiniz?')) {
                <?php if ($userType === 'client'): ?>
                    window.location.href = '/client/appointments.php';
                <?php else: ?>
                    window.location.href = '/dietitian/appointments.php';
                <?php endif; ?>
            }
        }

        // Toggle microphone
        document.querySelector('.btn-mic').addEventListener('click', function() {
            this.classList.toggle('bg-danger');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-microphone');
            icon.classList.toggle('fa-microphone-slash');
        });

        // Toggle camera
        document.querySelector('.btn-camera').addEventListener('click', function() {
            this.classList.toggle('bg-danger');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-video');
            icon.classList.toggle('fa-video-slash');
        });
    </script>
</body>
</html>
