#!/usr/bin/env php
<?php
/**
 * Diyetlenio - Randevu Hatırlatmaları Gönder
 * Cron Job: Her saat başı çalıştırılmalı
 * Örnek: 0 * * * * /usr/bin/php /path/to/scripts/send-reminders.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

echo "=== Randevu Hatırlatmaları Gönderiliyor ===\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $notification = new Notification();
    $count = $notification->sendAppointmentReminders();

    echo "Toplam {$count} hatırlatma gönderildi.\n";

    // Log kaydı
    $logData = [
        'user_id' => null,
        'action' => 'cron_reminders',
        'description' => "{$count} randevu hatırlatması gönderildi",
        'ip_address' => 'CRON',
        'created_at' => date(DATETIME_FORMAT_DB)
    ];

    $db = Database::getInstance();
    $db->insert('activity_logs', $logData);

    echo "İşlem başarılı.\n";
    exit(0);

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    error_log("Reminder cron error: " . $e->getMessage());
    exit(1);
}
