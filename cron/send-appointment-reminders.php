#!/usr/bin/env php
<?php
/**
 * Cron Job: Randevu Hatırlatmaları Gönder
 * Bu script her 5 dakikada bir çalıştırılmalıdır
 *
 * Crontab örneği:
 * */5 * * * * /usr/bin/php /path/to/diyetlenio/cron/send-appointment-reminders.php >> /var/log/diyetlenio-reminders.log 2>&1
 */

// CLI modunda çalıştığını kontrol et
if (php_sapi_name() !== 'cli') {
    die('Bu script sadece CLI modunda çalıştırılabilir.');
}

require_once __DIR__ . '/../includes/bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Randevu hatırlatmaları kontrolü başlıyor...\n";

$conn = $db->getConnection();
$mailer = new Mailer();
$smsService = new SmsService();
$now = date('Y-m-d H:i:s');

try {
    // Gönderilmesi gereken hatırlatmaları getir
    $stmt = $conn->prepare("
        SELECT
            ar.id as reminder_id,
            ar.appointment_id,
            ar.reminder_type,
            ar.scheduled_for,
            a.appointment_date,
            a.start_time,
            a.end_time,
            a.status as appointment_status,
            c.id as client_id,
            c.full_name as client_name,
            c.email as client_email,
            d.id as dietitian_id,
            d.full_name as dietitian_name,
            d.email as dietitian_email
        FROM appointment_reminders ar
        INNER JOIN appointments a ON a.id = ar.appointment_id
        INNER JOIN users c ON c.id = a.client_id AND c.is_active = 1
        INNER JOIN users d ON d.id = a.dietitian_id AND d.is_active = 1
        WHERE ar.status = 'pending'
        AND ar.scheduled_for <= ?
        AND a.status = 'scheduled'
        ORDER BY ar.scheduled_for ASC
        LIMIT 100
    ");

    $stmt->execute([$now]);
    $reminders = $stmt->fetchAll();

    echo "Toplam " . count($reminders) . " hatırlatma bulundu.\n";

    $successCount = 0;
    $failCount = 0;

    foreach ($reminders as $reminder) {
        try {
            $appointmentDateTime = strtotime($reminder['appointment_date'] . ' ' . $reminder['start_time']);
            $hoursUntil = round(($appointmentDateTime - time()) / 3600, 1);

            // Saat formatı
            $hoursText = '';
            if ($hoursUntil >= 24) {
                $hoursText = '24 saat';
            } elseif ($hoursUntil >= 1) {
                $hoursText = round($hoursUntil) . ' saat';
            } else {
                $minutesUntil = round(($appointmentDateTime - time()) / 60);
                $hoursText = $minutesUntil . ' dakika';
            }

            // E-posta verilerini hazırla
            $emailData = [
                'client_name' => $reminder['client_name'],
                'dietitian_name' => $reminder['dietitian_name'],
                'appointment_date' => date('d.m.Y', strtotime($reminder['appointment_date'])),
                'start_time' => date('H:i', strtotime($reminder['start_time'])),
                'hours_until' => $hoursText,
                'video_url' => BASE_URL . '/client/appointments.php'
            ];

            // Hatırlatma e-postası gönder
            if ($reminder['reminder_type'] === 'email') {
                $sent = $mailer->sendAppointmentReminder($reminder['client_email'], $emailData);

                if ($sent) {
                    // Başarılı olarak işaretle
                    $updateStmt = $conn->prepare("
                        UPDATE appointment_reminders
                        SET status = 'sent', sent_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$reminder['reminder_id']]);

                    echo "✅ Hatırlatma gönderildi: {$reminder['client_email']} (Randevu #{$reminder['appointment_id']}, {$hoursText} kaldı)\n";
                    $successCount++;
                } else {
                    throw new Exception('E-posta gönderilemedi');
                }
            } elseif ($reminder['reminder_type'] === 'sms') {
                // SMS hatırlatması gönder
                // Telefon numarasını al (client tablosundan veya user tablosundan)
                $phoneStmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
                $phoneStmt->execute([$reminder['client_id']]);
                $phoneData = $phoneStmt->fetch();

                if (!empty($phoneData['phone'])) {
                    $sent = $smsService->sendAppointmentReminder($phoneData['phone'], $emailData);

                    if ($sent) {
                        // Başarılı olarak işaretle
                        $updateStmt = $conn->prepare("
                            UPDATE appointment_reminders
                            SET status = 'sent', sent_at = NOW()
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$reminder['reminder_id']]);

                        echo "📱 SMS hatırlatma gönderildi: {$phoneData['phone']} (Randevu #{$reminder['appointment_id']}, {$hoursText} kaldı)\n";
                        $successCount++;
                    } else {
                        throw new Exception('SMS gönderilemedi');
                    }
                } else {
                    // Telefon numarası yok
                    $updateStmt = $conn->prepare("
                        UPDATE appointment_reminders
                        SET status = 'failed', error_message = 'Telefon numarası bulunamadı'
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$reminder['reminder_id']]);
                    echo "⚠️  Telefon numarası yok (Hatırlatma #{$reminder['reminder_id']})\n";
                    $failCount++;
                }
            }

        } catch (Exception $e) {
            // Başarısız olarak işaretle
            $updateStmt = $conn->prepare("
                UPDATE appointment_reminders
                SET status = 'failed', error_message = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$e->getMessage(), $reminder['reminder_id']]);

            echo "❌ Hatırlatma gönderilemedi (#{$reminder['reminder_id']}): {$e->getMessage()}\n";
            $failCount++;
        }
    }

    echo "\n=== ÖZET ===\n";
    echo "Başarılı: {$successCount}\n";
    echo "Başarısız: {$failCount}\n";
    echo "Toplam: " . count($reminders) . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] İşlem tamamlandı.\n\n";

} catch (Exception $e) {
    echo "❌ HATA: {$e->getMessage()}\n";
    error_log('Reminder cron error: ' . $e->getMessage());
    exit(1);
}

exit(0);
