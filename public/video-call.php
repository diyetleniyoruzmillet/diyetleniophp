<?php
/**
 * Diyetlenio - Video Görüşme (Jitsi Meet)
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

// Randevu bilgilerini çek
$stmt = $conn->prepare("
    SELECT a.*,
           c.full_name as client_name, c.email as client_email,
           d.full_name as dietitian_name, d.email as dietitian_email
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

// Video session kaydını bul veya oluştur
$stmt = $conn->prepare("SELECT * FROM video_sessions WHERE appointment_id = ?");
$stmt->execute([$appointmentId]);
$videoSession = $stmt->fetch();

if (!$videoSession) {
    // Yeni session oluştur
    $roomId = 'diyetlenio-' . $appointmentId . '-' . uniqid();
    $stmt = $conn->prepare("
        INSERT INTO video_sessions (appointment_id, room_id, session_type, created_at)
        VALUES (?, ?, 'regular', NOW())
    ");
    $stmt->execute([$appointmentId, $roomId]);
} else {
    $roomId = $videoSession['room_id'];
}

// Kullanıcı bilgileri
$displayName = $auth->user()->getFullName();
$userEmail = $auth->user()->getEmail();
$isModerator = ($auth->user()->getUserType() === 'dietitian' || $auth->user()->getUserType() === 'admin');

$pageTitle = 'Video Görüşme';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <script src="https://meet.jit.si/external_api.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        #meet { width: 100%; height: 100vh; }
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            flex-direction: column;
        }
        .loading h1 { font-size: 2rem; margin-bottom: 20px; }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="loading" class="loading">
        <h1>Video Görüşme Başlatılıyor...</h1>
        <div class="spinner"></div>
    </div>
    <div id="meet"></div>

    <script>
        const domain = 'meet.jit.si';
        const options = {
            roomName: '<?= $roomId ?>',
            width: '100%',
            height: '100%',
            parentNode: document.querySelector('#meet'),
            configOverwrite: {
                startWithAudioMuted: false,
                startWithVideoMuted: false,
                prejoinPageEnabled: true,
                enableWelcomePage: false,
                enableClosePage: false,
            },
            interfaceConfigOverwrite: {
                SHOW_JITSI_WATERMARK: false,
                SHOW_WATERMARK_FOR_GUESTS: false,
                DEFAULT_BACKGROUND: '#11998e',
                DISABLE_JOIN_LEAVE_NOTIFICATIONS: false,
                TOOLBAR_BUTTONS: [
                    'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                    'fodeviceselection', 'hangup', 'chat', 'raisehand',
                    'videoquality', 'tileview', 'settings', 'shortcuts',
                    'stats', 'feedback'
                ],
            },
            userInfo: {
                displayName: '<?= clean($displayName) ?>',
                email: '<?= clean($userEmail) ?>'
            }
        };

        const api = new JitsiMeetExternalAPI(domain, options);

        // Loading ekranını kaldır
        api.addEventListener('videoConferenceJoined', () => {
            document.getElementById('loading').style.display = 'none';
        });

        // Görüşme bittiğinde
        api.addEventListener('videoConferenceLeft', () => {
            // Session bilgilerini güncelle
            fetch('/api/video-session-end.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    appointment_id: <?= $appointmentId ?>,
                    ended_at: new Date().toISOString()
                })
            }).then(() => {
                window.location.href = '/<?= $auth->user()->getUserType() ?>/appointments.php';
            });
        });

        // Sayfa kapatılınca görüşmeyi bitir
        window.addEventListener('beforeunload', () => {
            api.executeCommand('hangup');
        });

        <?php if ($isModerator): ?>
        // Diyetisyen için moderatör yetkileri
        api.addEventListener('videoConferenceJoined', () => {
            // Kayıt başlat (opsiyonel)
            // api.executeCommand('startRecording', { mode: 'file' });
        });
        <?php endif; ?>
    </script>
</body>
</html>
