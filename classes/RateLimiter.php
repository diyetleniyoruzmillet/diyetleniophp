<?php
/**
 * Diyetlenio - Rate Limiting Sınıfı
 *
 * Brute force, spam ve DDoS saldırılarına karşı koruma
 */

class RateLimiter
{
    private Database $db;
    private string $storageMethod = 'database'; // 'database' veya 'session'

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Belirli bir işlem için rate limit kontrolü yapar
     *
     * @param string $action İşlem adı (login, register, api_call, contact_form, vb.)
     * @param string|null $identifier Kullanıcı ID veya IP adresi
     * @param int $maxAttempts Maksimum deneme sayısı
     * @param int $decayMinutes Süre (dakika)
     * @return bool True = izin var, False = limit aşıldı
     */
    public function attempt(string $action, ?string $identifier = null, int $maxAttempts = 5, int $decayMinutes = 1): bool
    {
        $identifier = $identifier ?? $this->getIdentifier();
        $key = $this->getKey($action, $identifier);

        // Mevcut deneme sayısını al
        $attempts = $this->getAttempts($key);

        // Limit aşıldı mı?
        if ($attempts >= $maxAttempts) {
            // Son deneme zamanını kontrol et
            $lastAttempt = $this->getLastAttemptTime($key);
            $decaySeconds = $decayMinutes * 60;

            if ($lastAttempt && (time() - $lastAttempt) < $decaySeconds) {
                // Hala limit içinde
                return false;
            } else {
                // Süre doldu, sıfırla
                $this->clear($key);
            }
        }

        // Denemeyi kaydet
        $this->hit($key, $decayMinutes);

        return true;
    }

    /**
     * Rate limit kontrolü (deneme kaydetmeden)
     *
     * @param string $action
     * @param string|null $identifier
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @return bool
     */
    public function tooManyAttempts(string $action, ?string $identifier = null, int $maxAttempts = 5, int $decayMinutes = 1): bool
    {
        $identifier = $identifier ?? $this->getIdentifier();
        $key = $this->getKey($action, $identifier);

        $attempts = $this->getAttempts($key);

        if ($attempts >= $maxAttempts) {
            $lastAttempt = $this->getLastAttemptTime($key);
            $decaySeconds = $decayMinutes * 60;

            if ($lastAttempt && (time() - $lastAttempt) < $decaySeconds) {
                return true;
            } else {
                $this->clear($key);
                return false;
            }
        }

        return false;
    }

    /**
     * Deneme sayısını artır
     *
     * @param string $key
     * @param int $decayMinutes
     * @return int Yeni deneme sayısı
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        if ($this->storageMethod === 'database') {
            return $this->hitDatabase($key, $decayMinutes);
        } else {
            return $this->hitSession($key, $decayMinutes);
        }
    }

    /**
     * Deneme sayısını al
     *
     * @param string $key
     * @return int
     */
    public function getAttempts(string $key): int
    {
        if ($this->storageMethod === 'database') {
            return $this->getAttemptsDatabase($key);
        } else {
            return $this->getAttemptsSession($key);
        }
    }

    /**
     * Son deneme zamanını al
     *
     * @param string $key
     * @return int|null Unix timestamp
     */
    public function getLastAttemptTime(string $key): ?int
    {
        if ($this->storageMethod === 'database') {
            return $this->getLastAttemptTimeDatabase($key);
        } else {
            return $this->getLastAttemptTimeSession($key);
        }
    }

    /**
     * Kalan süreyi (saniye) hesapla
     *
     * @param string $action
     * @param string|null $identifier
     * @param int $decayMinutes
     * @return int
     */
    public function availableIn(string $action, ?string $identifier = null, int $decayMinutes = 1): int
    {
        $identifier = $identifier ?? $this->getIdentifier();
        $key = $this->getKey($action, $identifier);

        $lastAttempt = $this->getLastAttemptTime($key);
        if (!$lastAttempt) {
            return 0;
        }

        $decaySeconds = $decayMinutes * 60;
        $elapsed = time() - $lastAttempt;
        $remaining = $decaySeconds - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Rate limit'i temizle
     *
     * @param string $key
     * @return void
     */
    public function clear(string $key): void
    {
        if ($this->storageMethod === 'database') {
            $this->clearDatabase($key);
        } else {
            $this->clearSession($key);
        }
    }

    /**
     * Tüm rate limit'leri temizle (cleanup için)
     *
     * @return void
     */
    public function clearExpired(): void
    {
        if ($this->storageMethod === 'database') {
            $this->db->query("DELETE FROM rate_limits WHERE expires_at < NOW()");
        }
    }

    // =============================================================
    // Database Storage Methods
    // =============================================================

    private function hitDatabase(string $key, int $decayMinutes): int
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($decayMinutes * 60));

        // Mevcut kayıt var mı?
        $existing = $this->db->query(
            "SELECT id, attempts FROM rate_limits WHERE rate_key = ? AND expires_at > NOW()",
            [$key]
        )->fetch();

        if ($existing) {
            // Güncelle
            $newAttempts = $existing['attempts'] + 1;
            $this->db->query(
                "UPDATE rate_limits SET attempts = ?, expires_at = ?, last_attempt_at = NOW() WHERE id = ?",
                [$newAttempts, $expiresAt, $existing['id']]
            );
            return $newAttempts;
        } else {
            // Yeni kayıt
            $this->db->query(
                "INSERT INTO rate_limits (rate_key, attempts, expires_at, last_attempt_at) VALUES (?, 1, ?, NOW())",
                [$key, $expiresAt]
            );
            return 1;
        }
    }

    private function getAttemptsDatabase(string $key): int
    {
        $result = $this->db->query(
            "SELECT attempts FROM rate_limits WHERE rate_key = ? AND expires_at > NOW()",
            [$key]
        )->fetch();

        return $result ? (int)$result['attempts'] : 0;
    }

    private function getLastAttemptTimeDatabase(string $key): ?int
    {
        $result = $this->db->query(
            "SELECT UNIX_TIMESTAMP(last_attempt_at) as timestamp FROM rate_limits WHERE rate_key = ? AND expires_at > NOW()",
            [$key]
        )->fetch();

        return $result ? (int)$result['timestamp'] : null;
    }

    private function clearDatabase(string $key): void
    {
        $this->db->query("DELETE FROM rate_limits WHERE rate_key = ?", [$key]);
    }

    // =============================================================
    // Session Storage Methods (Fallback)
    // =============================================================

    private function hitSession(string $key, int $decayMinutes): int
    {
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [
                'attempts' => 0,
                'expires_at' => time() + ($decayMinutes * 60),
                'last_attempt' => time()
            ];
        }

        // Expired kontrolü
        if ($_SESSION['rate_limits'][$key]['expires_at'] < time()) {
            $_SESSION['rate_limits'][$key] = [
                'attempts' => 1,
                'expires_at' => time() + ($decayMinutes * 60),
                'last_attempt' => time()
            ];
            return 1;
        }

        $_SESSION['rate_limits'][$key]['attempts']++;
        $_SESSION['rate_limits'][$key]['last_attempt'] = time();
        $_SESSION['rate_limits'][$key]['expires_at'] = time() + ($decayMinutes * 60);

        return $_SESSION['rate_limits'][$key]['attempts'];
    }

    private function getAttemptsSession(string $key): int
    {
        if (!isset($_SESSION['rate_limits'][$key])) {
            return 0;
        }

        if ($_SESSION['rate_limits'][$key]['expires_at'] < time()) {
            unset($_SESSION['rate_limits'][$key]);
            return 0;
        }

        return $_SESSION['rate_limits'][$key]['attempts'];
    }

    private function getLastAttemptTimeSession(string $key): ?int
    {
        if (!isset($_SESSION['rate_limits'][$key])) {
            return null;
        }

        if ($_SESSION['rate_limits'][$key]['expires_at'] < time()) {
            unset($_SESSION['rate_limits'][$key]);
            return null;
        }

        return $_SESSION['rate_limits'][$key]['last_attempt'];
    }

    private function clearSession(string $key): void
    {
        unset($_SESSION['rate_limits'][$key]);
    }

    // =============================================================
    // Helper Methods
    // =============================================================

    /**
     * Benzersiz key oluştur
     *
     * @param string $action
     * @param string $identifier
     * @return string
     */
    private function getKey(string $action, string $identifier): string
    {
        return hash('sha256', $action . '|' . $identifier);
    }

    /**
     * Identifier al (IP adresi veya user ID)
     *
     * @return string
     */
    private function getIdentifier(): string
    {
        // Önce kullanıcı ID'sini kontrol et
        if (isset($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }

        // IP adresi
        return 'ip_' . $this->getClientIp();
    }

    /**
     * Gerçek client IP'sini al (proxy arkasında bile)
     *
     * @return string
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR'            // Direkt bağlantı
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                // X-Forwarded-For birden fazla IP içerebilir
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Rate limit tablosunu oluştur (ilk kurulum için)
     *
     * @return void
     */
    public static function createTable(): void
    {
        $db = Database::getInstance();
        $sql = "
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                rate_key VARCHAR(64) NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                expires_at DATETIME NOT NULL,
                last_attempt_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_rate_key (rate_key),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        try {
            $db->query($sql);
        } catch (Exception $e) {
            error_log("Failed to create rate_limits table: " . $e->getMessage());
        }
    }
}
