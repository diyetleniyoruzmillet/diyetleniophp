<?php
/**
 * Diyetlenio - Diyetisyen Mesajlaşma
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
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
            redirect('/dietitian/messages.php?client_id=' . $receiverId);
        }
    }
}

// Seçili kişiyi belirle
$selectedUserId = $_GET['client_id'] ?? null;

// Danışanları listele (mesajlaşılan)
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name,
           (SELECT COUNT(*) FROM messages
            WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count,
           (SELECT created_at FROM messages
            WHERE (sender_id = u.id AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = u.id)
            ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT
            CASE
                WHEN sender_id = ? THEN receiver_id
                WHEN receiver_id = ? THEN sender_id
            END as user_id
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
    )
    AND u.user_type = 'client'
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
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'client'");
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
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .messages-container {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        height: 700px;
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
        position: relative;
    }
    .conversation-item:hover {
        background: #f8f9fa;
    }
    .conversation-item.active {
        background: #fff0f6;
        border-left: 3px solid #f093fb;
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
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }
    .message-received {
        margin-right: auto;
        background: white;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
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

<h2 class="mb-4">Mesajlar</h2>

<div class="row">
    <div class="col-md-12">
        <div class="messages-container row g-0">
            <!-- Conversations List -->
            <div class="col-md-4 conversations-list">
                <?php if (count($conversations) === 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Henüz mesajlaşma yok</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="?client_id=<?= $conv['id'] ?>" class="conversation-item <?= $selectedUserId == $conv['id'] ? 'active' : '' ?> text-decoration-none text-dark d-block">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= clean($conv['full_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= $conv['last_message_time'] ? timeAgo($conv['last_message_time']) : '' ?>
                                    </small>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Chat Area -->
            <div class="col-md-8">
                <?php if ($selectedUser): ?>
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-circle me-2"></i>
                            <?= clean($selectedUser['full_name']) ?>
                        </h5>
                        <small><?= clean($selectedUser['email']) ?></small>
                    </div>

                    <!-- Messages Area -->
                    <div class="messages-area" id="messagesArea">
                        <?php if (count($messages) === 0): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Henüz mesaj yok. İlk mesajı gönderin!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="message-bubble <?= $msg['sender_id'] == $userId ? 'message-sent' : 'message-received' ?>">
                                    <div><?= nl2br(clean($msg['message'])) ?></div>
                                    <div class="message-time">
                                        <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="message-input-area">
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="receiver_id" value="<?= $selectedUserId ?>">
                            <input type="text" name="message" class="form-control" placeholder="Mesajınızı yazın..." required autofocus>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Bir konuşma seçin</h5>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of messages
        const messagesArea = document.getElementById('messagesArea');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Auto-refresh every 10 seconds
        setTimeout(() => {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
