<?php
/**
 * Diyetlenio - User Sınıfı
 *
 * Kullanıcı işlemlerini yönetir (CRUD, profil, şifre vb.)
 */

class User
{
    /**
     * @var Database Veritabanı instance
     */
    private Database $db;

    /**
     * @var int|null Kullanıcı ID
     */
    private ?int $id = null;

    /**
     * @var array Kullanıcı verileri
     */
    private array $data = [];

    /**
     * Constructor
     *
     * @param int|null $userId
     * @throws Exception
     */
    public function __construct(?int $userId = null)
    {
        $this->db = Database::getInstance();

        if ($userId) {
            $this->id = $userId;
            $this->load();
        }
    }

    /**
     * Kullanıcıyı veritabanından yükler
     *
     * @return bool
     */
    private function load(): bool
    {
        $user = $this->db->select('users', ['*'], ['id' => $this->id]);

        if (!empty($user)) {
            $this->data = $user[0];
            return true;
        }

        return false;
    }

    /**
     * E-posta ile kullanıcıyı bulur
     *
     * @param string $email
     * @return User|null
     * @throws Exception
     */
    public static function findByEmail(string $email): ?User
    {
        $db = Database::getInstance();
        $result = $db->select('users', ['*'], ['email' => $email]);

        if (!empty($result)) {
            $user = new self();
            $user->id = (int) $result[0]['id'];
            $user->data = $result[0];
            return $user;
        }

        return null;
    }

    /**
     * ID ile kullanıcıyı bulur
     *
     * @param int $id
     * @return User|null
     * @throws Exception
     */
    public static function findById(int $id): ?User
    {
        try {
            $user = new self($id);
            return $user->exists() ? $user : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Yeni kullanıcı oluşturur
     *
     * @param array $data
     * @return User|null
     * @throws Exception
     */
    public static function create(array $data): ?User
    {
        // Zorunlu alanları kontrol et
        $required = ['email', 'password', 'full_name', 'user_type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Zorunlu alan eksik: {$field}");
            }
        }

        // E-posta benzersizliğini kontrol et
        if (self::findByEmail($data['email'])) {
            throw new Exception('Bu e-posta adresi zaten kullanılıyor.');
        }

        $db = Database::getInstance();

        // Şifreyi hashle
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // E-posta doğrulama token'ı oluştur
        if (!isset($data['email_verification_token'])) {
            $data['email_verification_token'] = bin2hex(random_bytes(32));
        }

        // Varsayılan değerler
        $defaults = [
            'is_active' => 0,
            'is_email_verified' => 0,
            'created_at' => date(DATETIME_FORMAT_DB),
        ];

        $data = array_merge($defaults, $data);

        // Kullanıcıyı ekle
        if ($db->insert('users', $data)) {
            $userId = (int) $db->lastInsertId();
            return new self($userId);
        }

        return null;
    }

    /**
     * Kullanıcıyı günceller
     *
     * @param array $data
     * @return bool
     */
    public function update(array $data): bool
    {
        if (!$this->exists()) {
            return false;
        }

        // Güvenli alanlar (güncellenebilir)
        $allowedFields = [
            'full_name', 'phone', 'profile_photo', 'is_active',
            'is_email_verified', 'last_login'
        ];

        $updateData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateData[$key] = $value;
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $updateData['updated_at'] = date(DATETIME_FORMAT_DB);

        if ($this->db->update('users', $updateData, ['id' => $this->id])) {
            $this->load(); // Verileri yeniden yükle
            return true;
        }

        return false;
    }

    /**
     * Kullanıcıyı siler
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        return $this->db->delete('users', ['id' => $this->id]);
    }

    /**
     * Şifre günceller
     *
     * @param string $oldPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(string $oldPassword, string $newPassword): bool
    {
        if (!$this->exists()) {
            return false;
        }

        // Eski şifreyi kontrol et
        if (!password_verify($oldPassword, $this->data['password'])) {
            throw new Exception('Mevcut şifre hatalı.');
        }

        // Yeni şifreyi hashle
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db->query("UPDATE users SET password = :password, updated_at = :updated_at WHERE id = :id");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':updated_at', date(DATETIME_FORMAT_DB));
        $this->db->bind(':id', $this->id);

        return $this->db->execute();
    }

    /**
     * Şifre sıfırlama token'ı oluşturur
     *
     * @return string|null
     */
    public function generatePasswordResetToken(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date(DATETIME_FORMAT_DB, strtotime('+1 hour'));

        $this->db->query("UPDATE users SET password_reset_token = :token, password_reset_expires = :expires WHERE id = :id");
        $this->db->bind(':token', $token);
        $this->db->bind(':expires', $expires);
        $this->db->bind(':id', $this->id);

        if ($this->db->execute()) {
            return $token;
        }

        return null;
    }

    /**
     * Şifre sıfırlama token'ı ile şifre günceller
     *
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public static function resetPasswordWithToken(string $token, string $newPassword): bool
    {
        $db = Database::getInstance();

        // Token'ı ve geçerlilik süresini kontrol et
        $db->query("SELECT id FROM users WHERE password_reset_token = :token AND password_reset_expires > NOW()");
        $db->bind(':token', $token);
        $result = $db->fetch();

        if (!$result) {
            throw new Exception('Geçersiz veya süresi dolmuş token.');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // Şifreyi güncelle ve token'ı temizle
        $db->query("UPDATE users SET password = :password, password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id");
        $db->bind(':password', $hashedPassword);
        $db->bind(':id', $result['id']);

        return $db->execute();
    }

    /**
     * E-posta adresini doğrular
     *
     * @param string $token
     * @return bool
     */
    public static function verifyEmail(string $token): bool
    {
        $db = Database::getInstance();

        $db->query("UPDATE users SET is_email_verified = 1, email_verification_token = NULL WHERE email_verification_token = :token");
        $db->bind(':token', $token);

        return $db->execute() && $db->rowCount() > 0;
    }

    /**
     * Son giriş zamanını günceller
     *
     * @return bool
     */
    public function updateLastLogin(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = :id");
        $this->db->bind(':id', $this->id);

        return $this->db->execute();
    }

    /**
     * Kullanıcı var mı kontrol eder
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->id !== null && !empty($this->data);
    }

    /**
     * Kullanıcı aktif mi kontrol eder
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->exists() && (int) $this->data['is_active'] === 1;
    }

    /**
     * E-posta doğrulanmış mı kontrol eder
     *
     * @return bool
     */
    public function isEmailVerified(): bool
    {
        return $this->exists() && (int) $this->data['is_email_verified'] === 1;
    }

    /**
     * Kullanıcı tipini kontrol eder
     *
     * @param string $type
     * @return bool
     */
    public function hasType(string $type): bool
    {
        return $this->exists() && $this->data['user_type'] === $type;
    }

    /**
     * Admin mi kontrol eder
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasType(USER_TYPE_ADMIN);
    }

    /**
     * Diyetisyen mi kontrol eder
     *
     * @return bool
     */
    public function isDietitian(): bool
    {
        return $this->hasType(USER_TYPE_DIETITIAN);
    }

    /**
     * Danışan mı kontrol eder
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->hasType(USER_TYPE_CLIENT);
    }

    /**
     * Kullanıcı ID'sini döndürür
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Kullanıcı verisini döndürür
     *
     * @param string|null $key
     * @return mixed
     */
    public function get(?string $key = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    /**
     * Profil fotoğrafı URL'sini döndürür
     *
     * @return string
     */
    public function getProfilePhotoUrl(): string
    {
        if (!empty($this->data['profile_photo'])) {
            return UPLOAD_URL . '/profiles/' . $this->data['profile_photo'];
        }

        // Varsayılan avatar
        return ASSETS_URL . '/img/default-avatar.png';
    }

    /**
     * Tam ad döndürür
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->data['full_name'] ?? '';
    }

    /**
     * E-posta döndürür
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->data['email'] ?? '';
    }

    /**
     * Kullanıcı tipini döndürür
     *
     * @return string
     */
    public function getUserType(): string
    {
        return $this->data['user_type'] ?? '';
    }

    /**
     * Array olarak kullanıcı verisini döndürür
     *
     * @param bool $includePassword
     * @return array
     */
    public function toArray(bool $includePassword = false): array
    {
        $data = $this->data;

        if (!$includePassword) {
            unset($data['password']);
            unset($data['email_verification_token']);
            unset($data['password_reset_token']);
        }

        return $data;
    }
}
