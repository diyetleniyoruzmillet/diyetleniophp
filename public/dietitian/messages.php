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
            setFlash('success', 'Mesaj gönderildi.');
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
