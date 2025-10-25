<?php
/**
 * Video Call Room - Jitsi Meet
 * Ücretsiz, sınırsız süre video görüşme
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Auth kontrolü
if (!$auth->check()) {
    redirect('/login.php');
}

$user = $auth->user();
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if (!$appointmentId) {
    setFlash('error', 'Geçersiz randevu bilgisi.');
    redirect('/' . $user->getUserType() . '/appointments.php');
}

// Randevu bilgisini çek
$conn = $db->getConnection();
$stmt = $conn->prepare("
    SELECT a.*,
           u1.full_name as client_name,
           u2.full_name as dietitian_name
    FROM appointments a
    LEFT JOIN users u1 ON a.client_id = u1.id
    LEFT JOIN users u2 ON a.dietitian_id = u2.id
    WHERE a.id = ? AND (a.client_id = ? OR a.dietitian_id = ?)
");
$stmt->execute([$appointmentId, $user->getId(), $user->getId()]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/' . $user->getUserType() . '/appointments.php');
}

$isDietitian = $user->getUserType() === 'dietitian';
$participantName = $isDietitian
    ? $appointment['client_name']
    : 'Dyt. ' . $appointment['dietitian_name'];

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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            overflow: hidden;
        }

        #jitsi-container {
            width: 100vw;
            height: 100vh;
        }

        .loading-screen {
            position: fixed;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            z-index: 9999;
        }

        .loading-screen.hidden {
            display: none;
        }

        .loading-logo {
            font-size: 4rem;
            margin-bottom: 30px;
            animation: pulse 2s ease-in-out infinite;
        }

        .spinner {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(255,255,255,0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 30px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .loading-text {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .loading-subtext {
            font-size: 1rem;
            opacity: 0.9;
        }

        .info-banner {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.98);
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 20px;
            max-width: 90%;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .info-banner.hidden {
            display: none;
        }

        .info-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-content h5 {
            margin: 0 0 5px 0;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
        }

        .info-content p {
            margin: 0;
            color: #64748b;
            font-size: 0.95rem;
        }

        .info-duration {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #475569;
        }

        .info-duration i {
            color: #10b981;
        }

        .close-banner {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .close-banner:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        @media (max-width: 768px) {
            .info-banner {
                flex-direction: column;
                text-align: center;
                padding: 15px 20px;
            }

            .info-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .info-content h5 {
                font-size: 1rem;
            }

            .info-content p {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-logo">
            <i class="fas fa-heartbeat"></i>
        </div>
        <div class="spinner"></div>
        <h2 class="loading-text">Video görüşme hazırlanıyor...</h2>
        <p class="loading-subtext">Kamera ve mikrofon erişimi isteyebiliriz</p>
    </div>

    <!-- Info Banner -->
    <div class="info-banner" id="infoBanner">
        <div class="info-icon">
            <i class="fas fa-video"></i>
        </div>
        <div class="info-content">
            <h5>Randevu #<?= $appointmentId ?> - <?= clean($participantName) ?></h5>
            <p><?= date('d.m.Y H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time'])) ?></p>
            <div class="info-duration">
                <i class="fas fa-clock"></i>
                <strong>Süre:</strong> <?= $appointment['duration'] ?? 45 ?> dakika |
                <strong>Ücretsiz</strong> Jitsi Meet ile
            </div>
        </div>
        <button class="close-banner" onclick="closeBanner()" title="Kapat">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Jitsi Meet Container -->
    <div id="jitsi-container"></div>

    <!-- Jitsi Meet External API -->
    <script src="https://meet.jit.si/external_api.js"></script>
    <script>
        const appointmentId = <?= $appointmentId ?>;
        const backUrl = '/<?= $user->getUserType() ?>/appointments.php';
        let jitsiApi = null;

        // Banner'ı kapat
        function closeBanner() {
            document.getElementById('infoBanner').classList.add('hidden');
        }

        // Jitsi room bilgilerini al ve başlat
        async function initializeJitsi() {
            try {
                const response = await fetch(`/api/jitsi-room.php?appointment_id=${appointmentId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Room oluşturulamadı');
                }

                const { room_name, display_name, domain, subject } = data;

                console.log('Jitsi room info:', { room_name, display_name, domain });

                // Jitsi Meet API options
                const options = {
                    roomName: room_name,
                    width: '100%',
                    height: '100%',
                    parentNode: document.getElementById('jitsi-container'),
                    configOverwrite: {
                        // Video/Audio ayarları
                        startWithAudioMuted: false,
                        startWithVideoMuted: false,
                        enableWelcomePage: false,
                        prejoinPageEnabled: false, // Bekleme sayfasını atla

                        // UI/UX
                        disableDeepLinking: true,
                        defaultLanguage: 'tr',
                        hideConferenceSubject: false,
                        subject: subject,

                        // Özellikler
                        enableNoisyMicDetection: true,
                        enableNoAudioDetection: true,
                        enableClosePage: false,

                        // Kalite
                        resolution: 720,
                        constraints: {
                            video: {
                                height: { ideal: 720, max: 1080, min: 360 }
                            }
                        },

                        // Diğer
                        disableInviteFunctions: false,
                        doNotStoreRoom: false,

                        // Recording (self-hosted'da çalışır, public serviste yok)
                        // fileRecordingsEnabled: false,
                        // liveStreamingEnabled: false
                    },
                    interfaceConfigOverwrite: {
                        // Toolbar butonları
                        TOOLBAR_BUTTONS: [
                            'microphone',
                            'camera',
                            'closedcaptions',
                            'desktop', // Ekran paylaşımı
                            'fullscreen',
                            'fodeviceselection', // Cihaz seçimi
                            'hangup',
                            'chat',
                            'raisehand',
                            'videoquality',
                            'filmstrip',
                            'settings',
                            'tileview',
                            'videobackgroundblur', // Arka plan bulanıklığı
                            'stats',
                            'help'
                        ],

                        // Branding
                        SHOW_JITSI_WATERMARK: false,
                        SHOW_WATERMARK_FOR_GUESTS: false,
                        SHOW_BRAND_WATERMARK: false,
                        BRAND_WATERMARK_LINK: '',

                        // Görünüm
                        DEFAULT_BACKGROUND: '#0f172a',
                        DISABLE_VIDEO_BACKGROUND: false,
                        VERTICAL_FILMSTRIP: true,

                        // Davet özellikleri
                        HIDE_INVITE_MORE_HEADER: false,
                        MOBILE_APP_PROMO: false,

                        // Hoşgeldin sayfası
                        DISPLAY_WELCOME_PAGE_CONTENT: false,
                        DISPLAY_WELCOME_PAGE_TOOLBAR_ADDITIONAL_CONTENT: false,

                        // Video layout
                        TILE_VIEW_MAX_COLUMNS: 2
                    },
                    userInfo: {
                        displayName: display_name,
                        email: '' // İsterseniz ekleyin
                    }
                };

                // Jitsi API başlat
                jitsiApi = new JitsiMeetExternalAPI(domain, options);

                console.log('Jitsi API initialized');

                // Event listeners
                jitsiApi.addEventListener('videoConferenceJoined', (event) => {
                    console.log('✅ Conference joined:', event);
                    document.getElementById('loadingScreen').classList.add('hidden');

                    // Banner'ı 10 saniye sonra otomatik gizle
                    setTimeout(() => {
                        closeBanner();
                    }, 10000);
                });

                jitsiApi.addEventListener('participantJoined', (event) => {
                    console.log('👤 Participant joined:', event);
                    // Bildirim gösterebilirsiniz
                });

                jitsiApi.addEventListener('participantLeft', (event) => {
                    console.log('👋 Participant left:', event);
                });

                jitsiApi.addEventListener('videoConferenceLeft', (event) => {
                    console.log('❌ Conference left:', event);
                    // Randevu sayfasına dön
                    window.location.href = backUrl;
                });

                jitsiApi.addEventListener('readyToClose', () => {
                    console.log('🚪 Ready to close');
                    jitsiApi.dispose();
                    window.location.href = backUrl;
                });

                jitsiApi.addEventListener('errorOccurred', (event) => {
                    console.error('⚠️ Jitsi error:', event);
                    alert('Video görüşme hatası oluştu: ' + (event.error || 'Bilinmeyen hata') + '\n\nLütfen sayfayı yenileyip tekrar deneyin.');
                });

                // Kalite değişimi
                jitsiApi.addEventListener('videoQualityChanged', (event) => {
                    console.log('📹 Video quality changed:', event);
                });

                // Audio/Video mute olayları
                jitsiApi.addEventListener('audioMuteStatusChanged', (event) => {
                    console.log('🎤 Audio mute:', event.muted);
                });

                jitsiApi.addEventListener('videoMuteStatusChanged', (event) => {
                    console.log('📹 Video mute:', event.muted);
                });

            } catch (error) {
                console.error('❌ Failed to initialize Jitsi:', error);

                document.getElementById('loadingScreen').innerHTML = `
                    <div class="loading-logo">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                    </div>
                    <h2 class="loading-text">Bağlantı Hatası</h2>
                    <p class="loading-subtext">${error.message}</p>
                    <button onclick="window.location.href='${backUrl}'"
                            style="margin-top: 30px; padding: 15px 40px; background: white; color: #10b981;
                                   border: none; border-radius: 12px; font-weight: 700; cursor: pointer;
                                   font-size: 1rem; transition: all 0.3s;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.2)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <i class="fas fa-arrow-left" style="margin-right: 10px;"></i>Geri Dön
                    </button>
                `;
            }
        }

        // Sayfayı kapatırken Jitsi'yi temizle
        window.addEventListener('beforeunload', () => {
            if (jitsiApi) {
                jitsiApi.dispose();
            }
        });

        // Initialize
        initializeJitsi();
    </script>
</body>
</html>
