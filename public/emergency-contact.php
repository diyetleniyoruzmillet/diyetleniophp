<?php
/**
 * Diyetlenio - Acil Nöbetçi Diyetisyen İletişim İşleyicisi
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
        redirect('/');
    }

    // Validator ile validasyon
    $validator = new Validator($_POST);
    $validator
        ->required(['name', 'email', 'phone', 'message'])
        ->email('email')
        ->phone('phone')
        ->min('message', 10);

    if ($validator->fails()) {
        foreach ($validator->errors() as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errors[] = $error;
            }
        }
    }

    // Mesaj kaydetme
    if (empty($errors)) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $subject = $_POST['subject'] ?? 'Acil Nöbetçi Diyetisyen Talebi';
        $message = $_POST['message'];
        try {
            $conn = $db->getConnection();

            // Acil durumu belirtmek için mesajın başına özel işaret ekle
            $emergencyMessage = "🚨 ACİL TALEP 🚨\n\n" . $message;

            $stmt = $conn->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'new', NOW())
            ");
            $stmt->execute([$name, $email, $phone, $subject, $emergencyMessage]);

            // Bildirim oluştur (eğer notifications tablosu varsa)
            try {
                // Admin kullanıcılarını bul
                $adminStmt = $conn->query("
                    SELECT id FROM users
                    WHERE user_type = 'admin' AND is_active = 1
                ");
                $admins = $adminStmt->fetchAll();

                // Her admin'e bildirim gönder
                foreach ($admins as $admin) {
                    $notifStmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, created_at)
                        VALUES (?, 'emergency', 'Acil Nöbetçi Diyetisyen Talebi', ?, NOW())
                    ");
                    $notifStmt->execute([
                        $admin['id'],
                        "Yeni acil talep: {$name} - {$phone}"
                    ]);
                }
            } catch (Exception $e) {
                // Notifications tablosu yoksa devam et
                error_log('Notification error: ' . $e->getMessage());
            }

            // Email gönder (eğer Mail sistemi varsa)
            try {
                if (class_exists('Mail')) {
                    Mail::sendContactNotification([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'subject' => $subject,
                        'message' => $emergencyMessage,
                        'is_emergency' => true
                    ]);
                }
            } catch (Exception $e) {
                error_log('Email error: ' . $e->getMessage());
            }

            setFlash('success', 'Acil talebiniz başarıyla gönderildi! En kısa sürede sizinle iletişime geçeceğiz.');
            redirect('/');

        } catch (Exception $e) {
            error_log('Emergency contact error: ' . $e->getMessage());
            setFlash('error', 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin veya doğrudan telefon ile iletişime geçin.');
            redirect('/');
        }
    } else {
        setFlash('error', implode('<br>', $errors));
        redirect('/');
    }
} else {
    // GET request ise ana sayfaya yönlendir
    redirect('/');
}
