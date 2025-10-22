<?php
/**
 * Diyetlenio - Danışan Mesajlaşma
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Mesaj gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $receiverId = (int)$_POST['receiver_id'];
        $message = trim($_POST['message']);

        if (!empty($message)) {
            $stmt = $conn->prepare("
                INSERT INTO messages (sender_id, receiver_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $receiverId, $message]);
            setFlash('success', 'Mesaj gönderildi.');
        }
    }
}

// Seçili kişiyi belirle
$selectedUserId = $_GET['dietitian_id'] ?? null;

// Diyetisyenleri listele (mesajlaşılan)
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name, dp.title,
           (SELECT COUNT(*) FROM messages
            WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count,
           (SELECT created_at FROM messages
            WHERE (sender_id = u.id AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = u.id)
            ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id IN (
        SELECT DISTINCT
            CASE
                WHEN sender_id = ? THEN receiver_id
                WHEN receiver_id = ? THEN sender_id
            END as user_id
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
    )
    ORDER BY last_message_time DESC
");
$stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
$conversations = $stmt->fetchAll();

// İlk kişiyi otomatik seç
if (!$selectedUserId && count($conversations) > 0) {
    $selectedUserId = $conversations[0]['id'];
}

// Seçili kişinin bilgileri
$selectedUser = null;
if ($selectedUserId) {
    $stmt = $conn->prepare("
        SELECT u.*, dp.title, dp.specialization
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$selectedUserId]);
    $selectedUser = $stmt->fetch();

    // Mesajları okundu olarak işaretle
    $stmt = $conn->prepare("
        UPDATE messages SET is_read = 1
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$selectedUserId, $userId]);
}

// Mesajları çek
$messages = [];
if ($selectedUserId) {
    $stmt = $conn->prepare("
        SELECT m.*, u.full_name as sender_name
        FROM messages m
        INNER JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$userId, $selectedUserId, $selectedUserId, $userId]);
    $messages = $stmt->fetchAll();
}

$pageTitle = 'Mesajlar';
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
        .messages-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .conversations-list {
            border-right: 1px solid #e9ecef;
            height: 700px;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.2s;
        }
        .conversation-item:hover {
            background: #f8f9fa;
        }
        .conversation-item.active {
            background: #e7f5ed;
            border-left: 3px solid #28a745;
        }
        .messages-area {
            height: 580px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .message-bubble {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 12px 18px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        .message-sent {
            margin-left: auto;
            background: #28a745;
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message-received {
            margin-right: auto;
            background: white;
            border-bottom-left-radius: 4px;
        }
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 5px;
        }
        .message-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #e9ecef;
        }
        .chat-header {
            padding: 20px;
            background: white;
            border-bottom: 1px solid #e9ecef;
        }
        .unread-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 11px;
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
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
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
                        <a class="nav-link active" href="/client/messages.php">
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
                    <h2 class="mb-4">Mesajlar</h2>

                    <?php if (count($conversations) === 0): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Henüz mesajlaşma yok</h4>
                                <p class="text-muted">Diyetisyeninizle randevu aldıktan sonra mesajlaşmaya başlayabilirsiniz.</p>
                                <a href="/client/dietitians.php" class="btn btn-success mt-3">
                                    <i class="fas fa-search me-2"></i>Diyetisyen Bul
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="messages-container">
                            <div class="row g-0">
                                <!-- Conversations List -->
                                <div class="col-md-4 conversations-list">
                                    <div class="p-3 border-bottom">
                                        <h5 class="mb-0">Konuşmalar</h5>
                                    </div>
                                    <?php foreach ($conversations as $conv): ?>
                                        <a href="?dietitian_id=<?= $conv['id'] ?>" class="text-decoration-none">
                                            <div class="conversation-item position-relative <?= $selectedUserId == $conv['id'] ? 'active' : '' ?>">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0"><?= clean($conv['full_name']) ?></h6>
                                                        <small class="text-muted"><?= clean($conv['title']) ?></small>
                                                    </div>
                                                </div>
                                                <?php if ($conv['last_message_time']): ?>
                                                    <small class="text-muted d-block mt-1">
                                                        <?= date('d.m.Y H:i', strtotime($conv['last_message_time'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if ($conv['unread_count'] > 0): ?>
                                                    <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Messages Area -->
                                <div class="col-md-8">
                                    <?php if ($selectedUser): ?>
                                        <!-- Chat Header -->
                                        <div class="chat-header">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h5 class="mb-0"><?= clean($selectedUser['full_name']) ?></h5>
                                                    <small class="text-muted">
                                                        <?= clean($selectedUser['title']) ?> - <?= clean($selectedUser['specialization']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Messages -->
                                        <div class="messages-area" id="messagesArea">
                                            <?php if (count($messages) === 0): ?>
                                                <div class="text-center text-muted py-5">
                                                    <i class="fas fa-comment fa-3x mb-3"></i>
                                                    <p>Henüz mesaj yok. İlk mesajı gönderin!</p>
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($messages as $msg): ?>
                                                    <div class="d-flex <?= $msg['sender_id'] == $userId ? 'justify-content-end' : 'justify-content-start' ?>">
                                                        <div class="message-bubble <?= $msg['sender_id'] == $userId ? 'message-sent' : 'message-received' ?>">
                                                            <div><?= nl2br(clean($msg['message'])) ?></div>
                                                            <div class="message-time">
                                                                <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Message Input -->
                                        <div class="message-input-area">
                                            <form method="POST" id="messageForm">
                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                <input type="hidden" name="receiver_id" value="<?= $selectedUserId ?>">
                                                <div class="input-group">
                                                    <textarea
                                                        name="message"
                                                        class="form-control"
                                                        placeholder="Mesajınızı yazın..."
                                                        rows="2"
                                                        required
                                                    ></textarea>
                                                    <button type="submit" name="send_message" class="btn btn-success">
                                                        <i class="fas fa-paper-plane me-2"></i>Gönder
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to bottom of messages
        const messagesArea = document.getElementById('messagesArea');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Auto-refresh messages every 10 seconds
        setInterval(() => {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
