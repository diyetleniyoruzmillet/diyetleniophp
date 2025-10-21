<?php
/**
 * Notifications API
 * Handle notification operations
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Auth kontrolü
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $auth->user();
$notificationService = new NotificationService();

// Get method from request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            // Get notifications
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

            $notifications = $notificationService->getUserNotifications($user->getId(), $limit, $unreadOnly);
            $unreadCount = $notificationService->getUnreadCount($user->getId());

            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
            break;

        case 'mark_read':
            // Mark notification as read
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

            if (!$notificationId) {
                throw new Exception('Notification ID required');
            }

            $success = $notificationService->markAsRead($notificationId, $user->getId());

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Marked as read' : 'Failed to mark as read'
            ]);
            break;

        case 'mark_all_read':
            // Mark all as read
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $success = $notificationService->markAllAsRead($user->getId());

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'All marked as read' : 'Failed to mark all as read'
            ]);
            break;

        case 'delete':
            // Delete notification
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

            if (!$notificationId) {
                throw new Exception('Notification ID required');
            }

            $success = $notificationService->delete($notificationId, $user->getId());

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Notification deleted' : 'Failed to delete notification'
            ]);
            break;

        case 'count':
            // Get unread count only
            $count = $notificationService->getUnreadCount($user->getId());

            echo json_encode([
                'success' => true,
                'unread_count' => $count
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
