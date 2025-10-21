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
$stmt = $db->prepare("
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

    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script>
        const roomId = '<?= $roomId ?>';
        const userId = '<?= $user->getId() ?>';
        const userName = '<?= clean($user->getFullName()) ?>';

        let localStream = null;
        let remoteStream = null;
        let peerConnection = null;
        let isMuted = false;
        let isVideoOff = false;

        // ICE servers for WebRTC
        const iceServers = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        // Initialize
        async function init() {
            try {
                // Get local media stream
                localStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 1280, height: 720 },
                    audio: true
                });

                document.getElementById('localVideo').srcObject = localStream;

                // Create peer connection
                peerConnection = new RTCPeerConnection(iceServers);

                // Add local tracks to peer connection
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });

                // Handle remote stream
                peerConnection.ontrack = (event) => {
                    if (!remoteStream) {
                        remoteStream = new MediaStream();
                        document.getElementById('remoteVideo').srcObject = remoteStream;
                    }
                    remoteStream.addTrack(event.track);
                    document.getElementById('waitingScreen').classList.add('hidden');
                };

                // ICE candidate handling
                peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        console.log('New ICE candidate:', event.candidate);
                        // Send to signaling server
                        // socket.emit('ice-candidate', roomId, userId, event.candidate);
                        // Note: Uncomment above when signaling server is running
                        // For now, ICE candidates are handled directly by STUN/TURN servers
                    }
                };

                // Connection state
                peerConnection.onconnectionstatechange = () => {
                    updateConnectionStatus(peerConnection.connectionState);
                };

                setTimeout(() => {
                    document.getElementById('waitingScreen').classList.add('hidden');
                }, 2000);

            } catch (error) {
                console.error('Error accessing media devices:', error);
                alert('Kamera veya mikrofon erişimi reddedildi. Lütfen izinleri kontrol edin.');
            }
        }

        // Mute/Unmute
        document.getElementById('muteBtn').addEventListener('click', () => {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    isMuted = !isMuted;
                    audioTrack.enabled = !isMuted;
                    const btn = document.getElementById('muteBtn');
                    btn.classList.toggle('active', isMuted);
                    btn.querySelector('i').className = isMuted ? 'fas fa-microphone-slash' : 'fas fa-microphone';
                }
            }
        });

        // Video On/Off
        document.getElementById('videoBtn').addEventListener('click', () => {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    isVideoOff = !isVideoOff;
                    videoTrack.enabled = !isVideoOff;
                    const btn = document.getElementById('videoBtn');
                    btn.classList.toggle('active', isVideoOff);
                    btn.querySelector('i').className = isVideoOff ? 'fas fa-video-slash' : 'fas fa-video';
                }
            }
        });

        // End Call
        document.getElementById('endBtn').addEventListener('click', () => {
            if (confirm('Görüşmeyi sonlandırmak istediğinizden emin misiniz?')) {
                endCall();
                window.location.href = '/<?= $user->getUserType() ?>/appointments.php';
            }
        });

        function endCall() {
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            if (peerConnection) {
                peerConnection.close();
            }
        }

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
            }
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', endCall);

        // Start
        init();
    </script>
</body>
</html>
