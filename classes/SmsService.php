<?php
/**
 * Diyetlenio - SMS Servisi
 * Türkiye SMS sağlayıcıları ile entegrasyon
 */

class SmsService
{
    private string $provider;
    private array $config;

    public function __construct()
    {
        $this->provider = $_ENV['SMS_PROVIDER'] ?? 'netgsm'; // netgsm, iletimerkezi, vatansms
        $this->config = [
            'username' => $_ENV['SMS_USERNAME'] ?? '',
            'password' => $_ENV['SMS_PASSWORD'] ?? '',
            'header' => $_ENV['SMS_HEADER'] ?? 'DIYETLENIO', // SMS başlığı
            'api_key' => $_ENV['SMS_API_KEY'] ?? '',
        ];
    }

    /**
     * SMS gönderir
     *
     * @param string $phone Telefon numarası (5XXXXXXXXX veya 905XXXXXXXXX formatında)
     * @param string $message Mesaj içeriği (160 karakter önerilir)
     * @return bool Başarılı ise true
     */
    public function send(string $phone, string $message): bool
    {
        try {
            // Telefon numarasını temizle ve formatla
            $phone = $this->formatPhone($phone);

            if (empty($this->config['username']) || empty($this->config['password'])) {
                error_log('SMS credentials not configured');
                return false;
            }

            // Provider'a göre API çağrısı yap
            switch ($this->provider) {
                case 'netgsm':
                    return $this->sendViaNetgsm($phone, $message);

                case 'iletimerkezi':
                    return $this->sendViaIletimerkezi($phone, $message);

                case 'vatansms':
                    return $this->sendViaVatansms($phone, $message);

                default:
                    error_log("Unsupported SMS provider: {$this->provider}");
                    return false;
            }

        } catch (Exception $e) {
            error_log('SMS send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Randevu hatırlatma SMS'i gönderir
     */
    public function sendAppointmentReminder(string $phone, array $appointmentData): bool
    {
        $date = $appointmentData['appointment_date'] ?? '';
        $time = $appointmentData['start_time'] ?? '';
        $dietitianName = $appointmentData['dietitian_name'] ?? 'Diyetisyen';
        $hoursUntil = $appointmentData['hours_until'] ?? '';

        $message = "Merhaba,\n\n";
        $message .= "{$hoursUntil} sonra {$dietitianName} ile randevunuz var.\n";
        $message .= "Tarih: {$date}\n";
        $message .= "Saat: {$time}\n\n";
        $message .= "Diyetlenio";

        return $this->send($phone, $message);
    }

    /**
     * Randevu onay SMS'i gönderir
     */
    public function sendAppointmentConfirmation(string $phone, array $appointmentData): bool
    {
        $date = $appointmentData['appointment_date'] ?? '';
        $time = $appointmentData['start_time'] ?? '';
        $dietitianName = $appointmentData['dietitian_name'] ?? 'Diyetisyen';

        $message = "Randevunuz olusturuldu!\n\n";
        $message .= "Diyetisyen: {$dietitianName}\n";
        $message .= "Tarih: {$date}\n";
        $message .= "Saat: {$time}\n\n";
        $message .= "Diyetlenio";

        return $this->send($phone, $message);
    }

    /**
     * Randevu iptal SMS'i gönderir
     */
    public function sendAppointmentCancellation(string $phone, array $appointmentData): bool
    {
        $date = $appointmentData['appointment_date'] ?? '';
        $time = $appointmentData['start_time'] ?? '';

        $message = "Randevunuz iptal edildi.\n\n";
        $message .= "Tarih: {$date}\n";
        $message .= "Saat: {$time}\n\n";
        $message .= "Yeni randevu icin: diyetlenio.com\n\n";
        $message .= "Diyetlenio";

        return $this->send($phone, $message);
    }

    /**
     * Netgsm API ile SMS gönderir
     * Dokümantasyon: https://www.netgsm.com.tr/dokuman/
     */
    private function sendViaNetgsm(string $phone, string $message): bool
    {
        $url = 'https://api.netgsm.com.tr/sms/send/get/';

        $params = [
            'usercode' => $this->config['username'],
            'password' => $this->config['password'],
            'gsmno' => $phone,
            'message' => $message,
            'msgheader' => $this->config['header'],
            'dil' => 'TR'
        ];

        $queryString = http_build_query($params);
        $response = @file_get_contents($url . '?' . $queryString);

        // Netgsm response: "00" = başarılı, diğer kodlar hata
        if ($response === false) {
            error_log('Netgsm API request failed');
            return false;
        }

        $responseCode = trim($response);

        if ($responseCode === '00' || $responseCode === '0') {
            return true;
        } else {
            error_log("Netgsm error code: {$responseCode}");
            return false;
        }
    }

    /**
     * İletimerkezi API ile SMS gönderir
     * Dokümantasyon: https://www.iletimerkezi.com/dokumantasyon
     */
    private function sendViaIletimerkezi(string $phone, string $message): bool
    {
        $url = 'https://api.iletimerkezi.com/v1/send-sms/get/';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <request>
            <authentication>
                <key>' . $this->config['api_key'] . '</key>
                <hash>' . md5($this->config['api_key'] . $this->config['password']) . '</hash>
            </authentication>
            <order>
                <sender>' . $this->config['header'] . '</sender>
                <sendDateTime></sendDateTime>
                <message>
                    <text><![CDATA[' . $message . ']]></text>
                    <receipents>
                        <number>' . $phone . '</number>
                    </receipents>
                </message>
            </order>
        </request>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && strpos($response, '<status>') !== false) {
            return true;
        }

        error_log("İletimerkezi error: {$response}");
        return false;
    }

    /**
     * Vatansms API ile SMS gönderir
     * Dokümantasyon: https://www.vatansms.net/api-dokumantasyonu
     */
    private function sendViaVatansms(string $phone, string $message): bool
    {
        $url = 'http://www.vatansms.net/api/v1/1toN';

        $data = [
            'api_id' => $this->config['api_key'],
            'api_key' => $this->config['password'],
            'sender' => $this->config['header'],
            'message_type' => 'normal',
            'message' => $message,
            'message_content_type' => 'bilgi',
            'phones' => [$phone]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 'success') {
            return true;
        }

        error_log("Vatansms error: {$response}");
        return false;
    }

    /**
     * Telefon numarasını Türkiye formatına çevirir
     *
     * @param string $phone
     * @return string 905XXXXXXXXX formatında
     */
    private function formatPhone(string $phone): string
    {
        // Sadece rakamları al
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // 0 ile başlıyorsa kaldır
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }

        // 90 ile başlamıyorsa ekle
        if (substr($phone, 0, 2) !== '90') {
            $phone = '90' . $phone;
        }

        // 905XXXXXXXXX formatında olmalı (13 karakter)
        if (strlen($phone) !== 12) {
            throw new InvalidArgumentException('Geçersiz telefon numarası formatı');
        }

        return $phone;
    }

    /**
     * Toplu SMS gönderir
     *
     * @param array $phones Telefon numaraları dizisi
     * @param string $message Mesaj
     * @return array ['success' => [...], 'failed' => [...]]
     */
    public function sendBulk(array $phones, string $message): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($phones as $phone) {
            if ($this->send($phone, $message)) {
                $results['success'][] = $phone;
            } else {
                $results['failed'][] = $phone;
            }
        }

        return $results;
    }

    /**
     * SMS kredisi kontrolü (Netgsm için)
     */
    public function checkBalance(): ?int
    {
        if ($this->provider !== 'netgsm') {
            return null;
        }

        $url = 'https://api.netgsm.com.tr/balance';
        $params = [
            'usercode' => $this->config['username'],
            'password' => $this->config['password']
        ];

        $response = @file_get_contents($url . '?' . http_build_query($params));

        if ($response !== false && is_numeric($response)) {
            return (int) $response;
        }

        return null;
    }
}
