<?php
/**
 * Notification Service
 * Handles creation and management of user notifications
 */

class NotificationService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new notification
     *
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string|null $link
     * @return bool
     */
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): bool
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([$userId, $type, $title, $message, $link]);

        } catch (Exception $e) {
            error_log('Notification create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user notifications
     *
     * @param int $userId
     * @param int $limit
     * @param bool $unreadOnly
     * @return array
     */
    public function getUserNotifications(int $userId, int $limit = 10, bool $unreadOnly = false): array
    {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ?";
            if ($unreadOnly) {
                $sql .= " AND is_read = FALSE";
            }
            $sql .= " ORDER BY created_at DESC LIMIT ?";

            $conn = $this->db->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $limit]);

            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log('Get notifications error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread count
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM notifications
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);

            return (int) $stmt->fetchColumn();

        } catch (Exception $e) {
            error_log('Get unread count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE notifications
                SET is_read = TRUE, read_at = NOW()
                WHERE id = ? AND user_id = ?
            ");

            return $stmt->execute([$notificationId, $userId]);

        } catch (Exception $e) {
            error_log('Mark as read error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all as read
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE notifications
                SET is_read = TRUE, read_at = NOW()
                WHERE user_id = ? AND is_read = FALSE
            ");

            return $stmt->execute([$userId]);

        } catch (Exception $e) {
            error_log('Mark all as read error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete notification
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function delete(int $notificationId, int $userId): bool
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                DELETE FROM notifications
                WHERE id = ? AND user_id = ?
            ");

            return $stmt->execute([$notificationId, $userId]);

        } catch (Exception $e) {
            error_log('Delete notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Quick notification creators
     */

    public function notifyAppointmentCreated(int $dietitianId, int $clientId, array $appointmentData): void
    {
        $this->create(
            $dietitianId,
            'appointment',
            'Yeni Randevu',
            "Yeni bir randevu talebi aldınız: {$appointmentData['date']} {$appointmentData['time']}",
            '/dietitian/appointments.php'
        );
    }

    public function notifyAppointmentConfirmed(int $clientId, array $appointmentData): void
    {
        $this->create(
            $clientId,
            'appointment',
            'Randevu Onaylandı',
            "Randevunuz onaylandı: {$appointmentData['date']} {$appointmentData['time']}",
            '/client/appointments.php'
        );
    }

    public function notifyNewMessage(int $userId, string $senderName): void
    {
        $this->create(
            $userId,
            'message',
            'Yeni Mesaj',
            "{$senderName} size bir mesaj gönderdi",
            '/messages.php'
        );
    }

    public function notifyPaymentReceived(int $userId, float $amount): void
    {
        $this->create(
            $userId,
            'payment',
            'Ödeme Alındı',
            number_format($amount, 2) . " ₺ ödeme alındı",
            '/payments.php'
        );
    }
}
