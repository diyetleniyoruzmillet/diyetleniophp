<?php
/**
 * Video Call Room
 * WebRTC based video conferencing for appointments
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Auth kontrolü
if (!$auth->check()) {
    redirect('/login.php');
}

$user = $auth->user();
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$roomId = isset($_GET['room_id']) ? clean($_GET['room_id']) : '';

if (!$appointmentId || !$roomId) {
    setFlash('error', 'Geçersiz randevu bilgisi.');
    redirect('/' . $user->getUserType() . '/appointments.php');
}

// Randevu bilgisini çek
$conn = $db->getConnection();
$stmt = $conn->prepare("
    SELECT a.*,
           u1.first_name as client_name, u1.last_name as client_lastname,
           u2.first_name as dietitian_name, u2.last_name as dietitian_lastname
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
    ? $appointment['client_name'] . ' ' . $appointment['client_lastname']
    : 'Dyt. ' . $appointment['dietitian_name'] . ' ' . $appointment['dietitian_lastname'];

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #1e293b;
            overflow: hidden;
        }
        .video-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            background: #0f172a;
        }
        #remoteVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #1e293b;
        }
        #localVideo {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 300px;
            height: 225px;
            border-radius: 15px;
            border: 3px solid #0ea5e9;
            object-fit: cover;
            background: #334155;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            background: rgba(30, 41, 59, 0.95);
            padding: 20px 30px;
            border-radius: 50px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .control-btn:hover {
            transform: scale(1.1);
        }
        .btn-mute {
            background: #475569;
        }
        .btn-mute.active {
            background: #ef4444;
        }
        .btn-video {
            background: #475569;
        }
        .btn-video.active {
            background: #ef4444;
        }
        .btn-end {
            background: #ef4444;
            width: 70px;
            height: 70px;
        }
        .btn-end:hover {
            background: #dc2626;
        }
        .participant-info {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(30, 41, 59, 0.9);
            padding: 15px 25px;
            border-radius: 15px;
            color: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .participant-info h5 {
            margin: 0;
            font-weight: 600;
        }
        .connection-status {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(30, 41, 59, 0.9);
            padding: 10px 20px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .waiting-screen {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            z-index: 100;
        }
        .waiting-screen.hidden {
            display: none;
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
    </style>
</head>
<body>
    <div class="video-container">
        <!-- Waiting Screen -->
        <div class="waiting-screen" id="waitingScreen">
            <div class="spinner"></div>
            <h2>Bağlantı kuruluyor...</h2>
            <p>Lütfen bekleyin</p>
        </div>

        <!-- Participant Info -->
        <div class="participant-info">
            <h5><i class="fas fa-user-circle me-2"></i><?= clean($participantName) ?></h5>
            <small><?= date('d.m.Y H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])) ?></small>
        </div>

        <!-- Connection Status -->
        <div class="connection-status">
            <div class="status-dot"></div>
            <span id="statusText">Bağlı</span>
        </div>

        <!-- Video Elements -->
        <video id="remoteVideo" autoplay playsinline></video>
        <video id="localVideo" autoplay muted playsinline></video>

        <!-- Controls -->
        <div class="controls">
            <button class="control-btn btn-mute" id="muteBtn" title="Mikrofonu Kapat">
                <i class="fas fa-microphone"></i>
            </button>
            <button class="control-btn btn-video" id="videoBtn" title="Kamerayı Kapat">
                <i class="fas fa-video"></i>
            </button>
            <button class="control-btn btn-end" id="endBtn" title="Görüşmeyi Bitir">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>
    </div>


    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script src="/assets/js/webrtc-client.js"></script>
    <script>
        // Configuration
        <?php
        $config = include __DIR__ . '/../config/config.php';
        $signalingServerUrl = $config['webrtc']['signaling_server_url'];
        ?>
        const signalingServerUrl = '<?= $signalingServerUrl ?>';
        const roomId = '<?= $roomId ?>';
        const userId = '<?= $user->getId() ?>';
        const userName = '<?= clean($user->getFullName()) ?>';

        // Initialize WebRTC Client
        const webrtcClient = new WebRTCClient({
            roomId: roomId,
            userId: userId,
            userName: userName,
            signalingServerUrl: signalingServerUrl
        });

        // Setup callbacks
        webrtcClient.onLocalStream = (stream) => {
            document.getElementById('localVideo').srcObject = stream;
            console.log('Local stream started');
        };

        webrtcClient.onRemoteStream = (stream, socketId) => {
            document.getElementById('remoteVideo').srcObject = stream;
            document.getElementById('waitingScreen').classList.add('hidden');
            updateConnectionStatus('connected');
            console.log('Remote stream received from:', socketId);
        };

        webrtcClient.onConnectionStateChange = (state, socketId) => {
            console.log('Connection state changed:', state);
            updateConnectionStatus(state);
        };

        webrtcClient.onError = (error) => {
            console.error('WebRTC Error:', error);
            alert(error.message || 'Video call hatası oluştu. Lütfen sayfayı yenileyip tekrar deneyin.');
        };

        webrtcClient.onUserJoined = ({ socketId, userId, userName }) => {
            console.log('User joined:', userName);
        };

        webrtcClient.onUserLeft = ({ socketId, userId, userName }) => {
            console.log('User left:', userName);
            document.getElementById('waitingScreen').classList.remove('hidden');
            updateConnectionStatus('disconnected');
        };

        // Initialize WebRTC
        webrtcClient.init().catch(error => {
            console.error('Failed to initialize WebRTC:', error);
            alert('Video görüşme başlatılamadı. Lütfen:\n1. Kamera ve mikrofon izinlerini kontrol edin\n2. HTTPS bağlantısı kullandığınızdan emin olun\n3. Tarayıcınızı güncelleyin');
            setTimeout(() => {
                window.location.href = '/<?= $user->getUserType() ?>/appointments.php';
            }, 3000);
        });

        // Mute/Unmute button
        document.getElementById('muteBtn').addEventListener('click', () => {
            const isMuted = webrtcClient.toggleMute();
            const btn = document.getElementById('muteBtn');
            btn.classList.toggle('active', isMuted);
            btn.querySelector('i').className = isMuted ? 'fas fa-microphone-slash' : 'fas fa-microphone';
            console.log('Microphone', isMuted ? 'muted' : 'unmuted');
        });

        // Video on/off button
        document.getElementById('videoBtn').addEventListener('click', () => {
            const isOff = webrtcClient.toggleVideo();
            const btn = document.getElementById('videoBtn');
            btn.classList.toggle('active', isOff);
            btn.querySelector('i').className = isOff ? 'fas fa-video-slash' : 'fas fa-video';
            console.log('Video', isOff ? 'off' : 'on');
        });

        // End call button
        document.getElementById('endBtn').addEventListener('click', () => {
            if (confirm('Görüşmeyi sonlandırmak istediğinizden emin misiniz?')) {
                webrtcClient.leave();
                window.location.href = '/<?= $user->getUserType() ?>/appointments.php';
            }
        });

        // Update connection status display
        function updateConnectionStatus(state) {
            const statusText = document.getElementById('statusText');
            const statusDot = document.querySelector('.status-dot');

            switch(state) {
                case 'connected':
                    statusText.textContent = 'Bağlı';
                    statusDot.style.background = '#10b981';
                    break;
                case 'connecting':
                    statusText.textContent = 'Bağlanıyor...';
                    statusDot.style.background = '#f59e0b';
                    break;
                case 'disconnected':
                case 'failed':
                    statusText.textContent = 'Bağlantı Kesildi';
                    statusDot.style.background = '#ef4444';
                    break;
                default:
                    statusText.textContent = 'Hazırlanıyor...';
                    statusDot.style.background = '#6b7280';
            }
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            webrtcClient.leave();
        });

        // Hide waiting screen after 10 seconds if no connection
        setTimeout(() => {
            const waitingScreen = document.getElementById('waitingScreen');
            if (!waitingScreen.classList.contains('hidden')) {
                console.warn('No remote stream after 10 seconds');
                // Keep showing "waiting" - other participant may join late
            }
        }, 10000);
    </script>
</body>
</html>
