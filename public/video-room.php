<?php
/**
 * Diyetlenio - Video Görüşme Odası
 * Jitsi Meet Entegrasyonu
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Auth kontrolü
if (!$auth->check()) {
    header('Location: /login.php');
    exit;
}

$appointmentId = (int) ($_GET['appointment_id'] ?? 0);

if (!$appointmentId) {
    $_SESSION['error'] = 'Geçersiz randevu ID';
    header('Location: /');
    exit;
}

// Randevu bilgilerini çek
try {
    $conn = $db->getConnection();
    $stmt = $conn->prepare("
        SELECT a.*,
               u1.full_name as client_name,
               u1.email as client_email,
               u2.full_name as dietitian_name,
               u2.email as dietitian_email,
               dp.title as dietitian_title
        FROM appointments a
        LEFT JOIN users u1 ON a.client_id = u1.id
        LEFT JOIN users u2 ON a.dietitian_id = u2.id
        LEFT JOIN dietitian_profiles dp ON a.dietitian_id = dp.user_id
        WHERE a.id = ? AND (a.client_id = ? OR a.dietitian_id = ?)
    ");
    $stmt->execute([$appointmentId, $auth->id(), $auth->id()]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $_SESSION['error'] = 'Randevu bulunamadı veya erişim yetkiniz yok';
        header('Location: /');
        exit;
    }

    // Randevu saati kontrolü
    $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
    $now = time();
    $thirtyMinsBefore = $appointmentDateTime - (30 * 60);
    $twoHoursAfter = $appointmentDateTime + (2 * 60 * 60);

    if ($now < $thirtyMinsBefore) {
        $minutesUntil = ceil(($thirtyMinsBefore - $now) / 60);
        $_SESSION['error'] = "Randevu henüz başlamadı. {$minutesUntil} dakika sonra katılabilirsiniz.";
        header('Location: /');
        exit;
    }

    if ($now > $twoHoursAfter) {
        $_SESSION['error'] = 'Bu randevu sona ermiş.';
        header('Location: /');
        exit;
    }

} catch (Exception $e) {
    error_log('Video room error: ' . $e->getMessage());
    $_SESSION['error'] = 'Bir hata oluştu';
    header('Location: /');
    exit;
}

$userType = $auth->user()->getUserType();
$displayName = $auth->user()->getFullName();

if ($userType === 'dietitian') {
    $displayName = ($appointment['dietitian_title'] ?? 'Dyt.') . ' ' . $displayName;
}

// Benzersiz room ID oluştur
$roomName = 'Diyetlenio_' . $appointmentId . '_' . substr(md5($appointmentId . '_' . $appointment['appointment_date']), 0, 8);

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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            overflow: hidden;
        }

        .video-container {
            width: 100vw;
            height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .video-header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            border-bottom: 2px solid #10b981;
        }

        .appointment-info {
            color: white;
        }

        .appointment-info h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .appointment-info p {
            font-size: 0.9rem;
            color: #94a3b8;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge i {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .btn-exit {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-exit:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
            color: white;
        }

        #jitsi-container {
            flex: 1;
            width: 100%;
            height: calc(100vh - 80px);
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }

        .loading-spinner {
            width: 80px;
            height: 80px;
            border: 4px solid rgba(16, 185, 129, 0.2);
            border-top-color: #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 2rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .loading-subtext {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .video-header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="video-container">
        <!-- Header -->
        <div class="video-header">
            <div class="appointment-info">
                <h4>
                    <i class="fas fa-video me-2"></i>
                    Randevu #<?= $appointmentId ?>
                </h4>
                <p>
                    <?= $userType === 'client' ? clean($appointment['dietitian_name']) : clean($appointment['client_name']) ?>
                    • <?= date('d.m.Y H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time'])) ?>
                    • <?= $appointment['duration'] ?? 45 ?> dakika
                </p>
            </div>
            <div class="header-actions">
                <div class="status-badge">
                    <i class="fas fa-circle"></i>
                    <span>Canlı</span>
                </div>
                <a href="<?= $userType === 'client' ? '/client/dashboard.php' : '/dietitian/dashboard.php' ?>" class="btn-exit">
                    <i class="fas fa-times"></i>
                    <span>Görüşmeyi Sonlandır</span>
                </a>
            </div>
        </div>

        <!-- Loading -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">Video görüşme başlatılıyor...</div>
            <div class="loading-subtext">Lütfen bekleyin</div>
        </div>

        <!-- Jitsi Meet Container -->
        <div id="jitsi-container"></div>
    </div>

    <!-- Jitsi Meet External API -->
    <script src="https://meet.jit.si/external_api.js"></script>
    <script>
        // Jitsi Meet configuration
        const domain = 'meet.jit.si';
        const options = {
            roomName: '<?= $roomName ?>',
            width: '100%',
            height: '100%',
            parentNode: document.querySelector('#jitsi-container'),
            userInfo: {
                displayName: '<?= clean($displayName) ?>'
            },
            configOverwrite: {
                startWithAudioMuted: false,
                startWithVideoMuted: false,
                enableWelcomePage: false,
                prejoinPageEnabled: false,
                disableDeepLinking: true,
                defaultLanguage: 'tr'
            },
            interfaceConfigOverwrite: {
                SHOW_JITSI_WATERMARK: false,
                SHOW_WATERMARK_FOR_GUESTS: false,
                SHOW_BRAND_WATERMARK: false,
                BRAND_WATERMARK_LINK: '',
                DEFAULT_BACKGROUND: '#0f172a',
                DISABLE_JOIN_LEAVE_NOTIFICATIONS: false,
                HIDE_INVITE_MORE_HEADER: true,
                MOBILE_APP_PROMO: false,
                TOOLBAR_BUTTONS: [
                    'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                    'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                    'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                    'videoquality', 'filmstrip', 'feedback', 'stats', 'shortcuts',
                    'tileview', 'select-background', 'download', 'help', 'mute-everyone'
                ]
            }
        };

        // Initialize Jitsi Meet
        const api = new JitsiMeetExternalAPI(domain, options);

        // Hide loading overlay when ready
        api.addEventListener('videoConferenceJoined', () => {
            document.getElementById('loadingOverlay').style.display = 'none';
            console.log('Video conference joined successfully');
        });

        // Handle video conference left
        api.addEventListener('videoConferenceLeft', () => {
            window.location.href = '<?= $userType === 'client' ? '/client/dashboard.php' : '/dietitian/dashboard.php' ?>';
        });

        // Handle errors
        api.addEventListener('errorOccurred', (error) => {
            console.error('Jitsi error:', error);
            alert('Video görüşme sırasında bir hata oluştu. Lütfen sayfayı yenileyin.');
        });

        // Log when participant joins
        api.addEventListener('participantJoined', (participant) => {
            console.log('Participant joined:', participant);
        });

        // Log when participant leaves
        api.addEventListener('participantLeft', (participant) => {
            console.log('Participant left:', participant);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (api) {
                api.dispose();
            }
        });
    </script>
</body>
</html>
