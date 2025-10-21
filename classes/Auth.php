<?php
/**
 * Diyetlenio - Auth Sınıfı
 *
 * Kimlik doğrulama ve yetkilendirme işlemlerini yönetir.
 */

class Auth
{
    /**
     * @var Database Veritabanı instance
     */
    private Database $db;

    /**
     * @var User|null Giriş yapmış kullanıcı
     */
    private ?User $user = null;

    /**
     * @var string Session key
     */
    private const SESSION_USER_KEY = 'user_id';

    /**
     * @var string Remember me cookie name
     */
    private const REMEMBER_COOKIE = 'remember_token';

    /**
     * Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->checkSession();
    }

    /**
     * Session'dan kullanıcıyı kontrol eder
     *
     * @return void
     */
    private function checkSession(): void
    {
        // Session'da kullanıcı var mı?
        if (isset($_SESSION[self::SESSION_USER_KEY])) {
            try {
                $this->user = User::findById($_SESSION[self::SESSION_USER_KEY]);

                // Kullanıcı bulunamadı veya aktif değil
                if (!$this->user || !$this->user->isActive()) {
                    $this->logout();
                }
            } catch (Exception $e) {
                $this->logout();
            }
        }
        // Remember me cookie var mı?
        elseif (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            $this->checkRememberToken($_COOKIE[self::REMEMBER_COOKIE]);
        }
    }

    /**
     * Remember me token'ını kontrol eder
     *
     * @param string $token
     * @return void
     */
    private function checkRememberToken(string $token): void
    {
        // Token'ı veritabanında ara (activity_logs tablosunda saklanabilir)
        // Şimdilik basit bir implementasyon
        // Gerçek uygulamada token'lar ayrı bir tabloda saklanmalı
    }

    /**
     * Kullanıcı girişi yapar
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return bool
     * @throws Exception
     */
    public function login(string $email, string $password, bool $remember = false): bool
    {
        // E-posta ile kullanıcıyı bul
        $user = User::findByEmail($email);

        if (!$user) {
            throw new Exception('E-posta veya şifre hatalı.');
        }

        // Şifre kontrolü
        if (!password_verify($password, $user->get('password'))) {
            // Hatalı giriş denemesini logla
            $this->logActivity($user->getId(), ACTIVITY_LOGIN, 'Başarısız giriş denemesi', false);
            throw new Exception('E-posta veya şifre hatalı.');
        }

        // Kullanıcı aktif mi?
        if (!$user->isActive()) {
            throw new Exception('Hesabınız aktif değil. Lütfen yönetici ile iletişime geçin.');
        }

        // E-posta doğrulaması gerekli mi? (İsteğe bağlı)
        // if (!$user->isEmailVerified()) {
        //     throw new Exception('Lütfen e-posta adresinizi doğrulayın.');
        // }

        // Session'a kullanıcıyı kaydet
        $_SESSION[self::SESSION_USER_KEY] = $user->getId();
        $_SESSION['user_type'] = $user->getUserType();
        $_SESSION['user_name'] = $user->getFullName();
        $_SESSION['user_email'] = $user->getEmail();

        // Session ID'yi yenile (session fixation saldırılarına karşı)
        session_regenerate_id(true);

        // Remember me
        if ($remember) {
            $this->setRememberToken($user);
        }

        // Son giriş zamanını güncelle
        $user->updateLastLogin();

        // Aktiviteyi logla
        $this->logActivity($user->getId(), ACTIVITY_LOGIN, 'Başarılı giriş', true);

        $this->user = $user;

        return true;
    }

    /**
     * Remember me token'ı oluşturur
     *
     * @param User $user
     * @return void
     */
    private function setRememberToken(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 gün

        // Cookie'yi ayarla
        setcookie(
            self::REMEMBER_COOKIE,
            $token,
            $expiry,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true // httponly
        );

        // Token'ı veritabanına kaydet (activity_logs veya ayrı bir tablo)
        // Bu implementasyon örnek amaçlıdır
    }

    /**
     * Yeni kullanıcı kaydı yapar
     *
     * @param array $data
     * @return User
     * @throws Exception
     */
    public function register(array $data): User
    {
        // E-posta validasyonu
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçersiz e-posta adresi.');
        }

        // Şifre güvenlik kontrolü
        if (strlen($data['password']) < 8) {
            throw new Exception('Şifre en az 8 karakter olmalıdır.');
        }

        // Kullanıcıyı oluştur
        $user = User::create($data);

        if (!$user) {
            throw new Exception('Kullanıcı oluşturulamadı.');
        }

        // Aktiviteyi logla
        $this->logActivity($user->getId(), ACTIVITY_REGISTER, 'Yeni kullanıcı kaydı', true);

        // E-posta doğrulama maili gönder (isteğe bağlı)
        // $this->sendVerificationEmail($user);

        return $user;
    }

    /**
     * Kullanıcı çıkışı yapar
     *
     * @return void
     */
    public function logout(): void
    {
        if ($this->user) {
            // Aktiviteyi logla
            $this->logActivity($this->user->getId(), ACTIVITY_LOGOUT, 'Kullanıcı çıkışı', true);
        }

        // Session'ı temizle
        unset($_SESSION[self::SESSION_USER_KEY]);
        unset($_SESSION['user_type']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);

        // Remember me cookie'sini sil
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/');
        }

        // Session'ı yok et (opsiyonel - tüm session verisi silinir)
        // session_destroy();

        $this->user = null;
    }

    /**
     * Kullanıcı giriş yapmış mı kontrol eder
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->user !== null && $this->user->isActive();
    }

    /**
     * Misafir mi kontrol eder
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Giriş yapmış kullanıcıyı döndürür
     *
     * @return User|null
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /**
     * Kullanıcı ID'sini döndürür
     *
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->user ? $this->user->getId() : null;
    }

    /**
     * Kullanıcının yetkisini kontrol eder
     *
     * @param string $type
     * @return bool
     */
    public function hasType(string $type): bool
    {
        return $this->check() && $this->user->hasType($type);
    }

    /**
     * Admin yetkisi kontrol eder
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasType(USER_TYPE_ADMIN);
    }

    /**
     * Diyetisyen yetkisi kontrol eder
     *
     * @return bool
     */
    public function isDietitian(): bool
    {
        return $this->hasType(USER_TYPE_DIETITIAN);
    }

    /**
     * Danışan yetkisi kontrol eder
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->hasType(USER_TYPE_CLIENT);
    }

    /**
     * Giriş yapma zorunluluğu
     *
     * @param string|null $redirectUrl
     * @return void
     */
    public function requireAuth(?string $redirectUrl = null): void
    {
        if ($this->guest()) {
            $redirect = $redirectUrl ?? '/login.php';
            header("Location: {$redirect}");
            exit;
        }
    }

    /**
     * Misafir olma zorunluluğu (giriş yapılmış kullanıcıları yönlendir)
     *
     * @param string|null $redirectUrl
     * @return void
     */
    public function requireGuest(?string $redirectUrl = null): void
    {
        if ($this->check()) {
            $redirect = $redirectUrl ?? '/dashboard.php';
            header("Location: {$redirect}");
            exit;
        }
    }

    /**
     * Yetki kontrolü (belirtilen tiplere sahip değilse yönlendir)
     *
     * @param array $allowedTypes
     * @param string|null $redirectUrl
     * @return void
     */
    public function requireType(array $allowedTypes, ?string $redirectUrl = null): void
    {
        $this->requireAuth();

        $hasPermission = false;
        foreach ($allowedTypes as $type) {
            if ($this->hasType($type)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            $redirect = $redirectUrl ?? '/unauthorized.php';
            header("Location: {$redirect}");
            exit;
        }
    }

    /**
     * Admin yetkisi zorunluluğu
     *
     * @param string|null $redirectUrl
     * @return void
     */
    public function requireAdmin(?string $redirectUrl = null): void
    {
        $this->requireType([USER_TYPE_ADMIN], $redirectUrl);
    }

    /**
     * Diyetisyen yetkisi zorunluluğu
     *
     * @param string|null $redirectUrl
     * @return void
     */
    public function requireDietitian(?string $redirectUrl = null): void
    {
        $this->requireType([USER_TYPE_ADMIN, USER_TYPE_DIETITIAN], $redirectUrl);
    }

    /**
     * CSRF token oluşturur
     *
     * @return string
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * CSRF token'ı doğrular
     *
     * @param string $token
     * @return bool
     */
    public function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Aktivite loglar
     *
     * @param int|null $userId
     * @param string $action
     * @param string|null $description
     * @param bool $success
     * @return void
     */
    private function logActivity(?int $userId, string $action, ?string $description = null, bool $success = true): void
    {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date(DATETIME_FORMAT_DB),
            ];

            $this->db->insert('activity_logs', $data);
        } catch (Exception $e) {
            // Log hatalarını sessizce yakala
            error_log('Activity log error: ' . $e->getMessage());
        }
    }

    /**
     * IP adresine göre giriş denemelerini kontrol eder (brute force koruması)
     *
     * @param string $email
     * @param int $maxAttempts
     * @param int $lockoutMinutes
     * @return bool
     */
    public function checkLoginAttempts(string $email, int $maxAttempts = 5, int $lockoutMinutes = 15): bool
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $timeLimit = date(DATETIME_FORMAT_DB, strtotime("-{$lockoutMinutes} minutes"));

        $this->db->query("
            SELECT COUNT(*) as attempt_count
            FROM activity_logs
            WHERE action = :action
            AND ip_address = :ip
            AND created_at > :time_limit
            AND description LIKE '%Başarısız%'
        ");

        $this->db->bind(':action', ACTIVITY_LOGIN);
        $this->db->bind(':ip', $ipAddress);
        $this->db->bind(':time_limit', $timeLimit);

        $result = $this->db->fetch();

        if ($result && $result['attempt_count'] >= $maxAttempts) {
            throw new Exception("Çok fazla başarısız giriş denemesi. {$lockoutMinutes} dakika sonra tekrar deneyin.");
        }

        return true;
    }
}
