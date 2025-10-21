<?php
/**
 * Diyetlenio - Notification Sınıfı
 * Bildirim gönderme ve yönetme
 */

class Notification
{
    private Database $db;
    private Mailer $mailer;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->mailer = new Mailer();
    }

    /**
     * Bildirim oluşturur
     */
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): bool
    {
        try {
            $data = [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'is_read' => 0,
                'created_at' => date(DATETIME_FORMAT_DB),
            ];

            return $this->db->insert('notifications', $data);
        } catch (Exception $e) {
            error_log('Notification creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Randevu oluşturulduğunda bildirim gönder
     */
    public function notifyAppointmentCreated(int $appointmentId): bool
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT a.*, 
                       c.full_name as client_name, c.email as client_email,
                       d.full_name as dietitian_name, d.email as dietitian_email
                FROM appointments a
                INNER JOIN users c ON a.client_id = c.id
                INNER JOIN users d ON a.dietitian_id = d.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointmentId]);
            $appointment = $stmt->fetch();

            if (!$appointment) {
                return false;
            }

            // Diyetisyene bildirim
            $this->create(
                $appointment['dietitian_id'],
                'appointment',
                'Yeni Randevu',
                $appointment['client_name'] . ' adlı danışan ' . formatDate($appointment['appointment_date']) . ' tarihinde randevu oluşturdu.',
                '/dietitian/appointments.php'
            );

            // Danışana bildirim
            $this->create(
                $appointment['client_id'],
                'appointment',
                'Randevu Oluşturuldu',
                'Randevunuz başarıyla oluşturuldu. ' . formatDate($appointment['appointment_date']) . ' ' . substr($appointment['start_time'], 0, 5),
                '/client/appointments.php'
            );

            // Email gönder (opsiyonel)
            $emailData = [
                'client_name' => $appointment['client_name'],
                'dietitian_name' => $appointment['dietitian_name'],
                'date' => formatDate($appointment['appointment_date']),
                'time' => substr($appointment['start_time'], 0, 5),
            ];

            $this->mailer->sendAppointmentConfirmation($appointment['client_email'], $emailData);

            return true;
        } catch (Exception $e) {
            error_log('Appointment notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ödeme onaylandığında bildirim gönder
     */
    public function notifyPaymentApproved(int $paymentId): bool
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT p.*, u.full_name, u.email
                FROM payments p
                INNER JOIN users u ON p.client_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                return false;
            }

            $this->create(
                $payment['client_id'],
                'payment',
                'Ödeme Onaylandı',
                number_format($payment['amount'], 2) . ' ₺ tutarındaki ödemeniz onaylandı.',
                '/client/appointments.php'
            );

            // Email gönder
            $this->mailer->sendPaymentApproved($payment['email'], [
                'name' => $payment['full_name'],
                'amount' => number_format($payment['amount'], 2)
            ]);

            return true;
        } catch (Exception $e) {
            error_log('Payment notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Randevu hatırlatması gönder (cron job ile çalıştırılmalı)
     */
    public function sendAppointmentReminders(): int
    {
        try {
            $conn = $this->db->getConnection();

            // 1 saat sonraki randevuları bul
            $stmt = $conn->query("
                SELECT a.*, 
                       c.full_name as client_name, c.email as client_email,
                       d.full_name as dietitian_name
                FROM appointments a
                INNER JOIN users c ON a.client_id = c.id
                INNER JOIN users d ON a.dietitian_id = d.id
                WHERE a.status = 'scheduled'
                AND a.reminder_sent = 0
                AND CONCAT(a.appointment_date, ' ', a.start_time) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
            ");
            $appointments = $stmt->fetchAll();

            $count = 0;
            foreach ($appointments as $app) {
                // Bildirim oluştur
                $this->create(
                    $app['client_id'],
                    'appointment',
                    'Randevu Hatırlatma',
                    'Randevunuz 1 saat sonra: ' . formatDate($app['appointment_date']) . ' ' . substr($app['start_time'], 0, 5),
                    '/client/appointments.php'
                );

                // Email gönder
                $this->mailer->sendAppointmentReminder($app['client_email'], [
                    'client_name' => $app['client_name'],
                    'dietitian_name' => $app['dietitian_name'],
                    'date' => formatDate($app['appointment_date']),
                    'time' => substr($app['start_time'], 0, 5),
                ]);

                // Hatırlatma gönderildi olarak işaretle
                $updateStmt = $conn->prepare("UPDATE appointments SET reminder_sent = 1 WHERE id = ?");
                $updateStmt->execute([$app['id']]);

                $count++;
            }

            return $count;
        } catch (Exception $e) {
            error_log('Reminder error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Kullanıcının okunmamış bildirim sayısı
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $result = $this->db->select('notifications', ['COUNT(*) as count'], [
                'user_id' => $userId,
                'is_read' => 0
            ]);
            return (int) ($result[0]['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead(int $notificationId): bool
    {
        return $this->db->update('notifications', [
            'is_read' => 1,
            'read_at' => date(DATETIME_FORMAT_DB)
        ], ['id' => $notificationId]);
    }

    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->db->update('notifications', [
            'is_read' => 1,
            'read_at' => date(DATETIME_FORMAT_DB)
        ], ['user_id' => $userId, 'is_read' => 0]);
    }
}
