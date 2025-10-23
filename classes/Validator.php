<?php
/**
 * Diyetlenio - Input Validation Sınıfı
 *
 * Form ve API input'larını doğrular
 */

class Validator
{
    private array $data;
    private array $errors = [];
    private array $validatedData = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Zorunlu alan kontrolü
     *
     * @param array|string $fields
     * @return self
     */
    public function required($fields): self
    {
        $fields = is_array($fields) ? $fields : [$fields];

        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
                $this->errors[$field][] = "$field alanı zorunludur.";
            }
        }

        return $this;
    }

    /**
     * Email validasyonu
     *
     * @param string $field
     * @return self
     */
    public function email(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "$field geçerli bir e-posta adresi olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * Minimum uzunluk kontrolü
     *
     * @param string $field
     * @param int $length
     * @return self
     */
    public function min(string $field, int $length): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (mb_strlen($this->data[$field]) < $length) {
                $this->errors[$field][] = "$field en az $length karakter olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * Maksimum uzunluk kontrolü
     *
     * @param string $field
     * @param int $length
     * @return self
     */
    public function max(string $field, int $length): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (mb_strlen($this->data[$field]) > $length) {
                $this->errors[$field][] = "$field en fazla $length karakter olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * Sayı kontrolü
     *
     * @param string $field
     * @return self
     */
    public function numeric(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field][] = "$field sayısal bir değer olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * Minimum değer kontrolü (sayılar için)
     *
     * @param string $field
     * @param float $minValue
     * @return self
     */
    public function minValue(string $field, float $minValue): self
    {
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ((float)$this->data[$field] < $minValue) {
                $this->errors[$field][] = "$field en az $minValue olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * Maksimum değer kontrolü (sayılar için)
     *
     * @param string $field
     * @param float $maxValue
     * @return self
     */
    public function maxValue(string $field, float $maxValue): self
    {
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ((float)$this->data[$field] > $maxValue) {
                $this->errors[$field][] = "$field en fazla $maxValue olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * Değer aralığı kontrolü (min ve max arasında)
     *
     * @param string $field
     * @param float $minValue
     * @param float $maxValue
     * @return self
     */
    public function between(string $field, float $minValue, float $maxValue): self
    {
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            $value = (float)$this->data[$field];
            if ($value < $minValue || $value > $maxValue) {
                $this->errors[$field][] = "$field $minValue ile $maxValue arasında olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * Regex pattern kontrolü
     *
     * @param string $field
     * @param string $pattern
     * @param string $message
     * @return self
     */
    public function pattern(string $field, string $pattern, string $message = null): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field][] = $message ?? "$field geçerli bir formatta değil.";
            }
        }

        return $this;
    }

    /**
     * Telefon numarası kontrolü (Türkiye)
     *
     * @param string $field
     * @return self
     */
    public function phone(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            // Türkiye telefon formatı: +90 veya 0 ile başlayan 10 haneli
            $pattern = '/^(\+90|0)?5\d{9}$/';
            if (!preg_match($pattern, str_replace(' ', '', $this->data[$field]))) {
                $this->errors[$field][] = "$field geçerli bir telefon numarası olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * URL validasyonu
     *
     * @param string $field
     * @return self
     */
    public function url(string $field): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->errors[$field][] = "$field geçerli bir URL olmalıdır.";
            }
        }

        return $this;
    }

    /**
     * İki alan eşleşme kontrolü (şifre onayı için)
     *
     * @param string $field
     * @param string $matchField
     * @return self
     */
    public function match(string $field, string $matchField): self
    {
        if (isset($this->data[$field], $this->data[$matchField])) {
            if ($this->data[$field] !== $this->data[$matchField]) {
                $this->errors[$field][] = "$field ile $matchField eşleşmelidir.";
            }
        }

        return $this;
    }

    /**
     * Seçenekler arasından seçim kontrolü
     *
     * @param string $field
     * @param array $options
     * @return self
     */
    public function in(string $field, array $options): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!in_array($this->data[$field], $options, true)) {
                $this->errors[$field][] = "$field geçerli bir değer değil.";
            }
        }

        return $this;
    }

    /**
     * Benzersizlik kontrolü (veritabanı)
     *
     * @param string $field
     * @param string $table
     * @param string $column
     * @param int|null $exceptId Güncelleme için mevcut kaydı hariç tut
     * @return self
     */
    public function unique(string $field, string $table, string $column, ?int $exceptId = null): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            global $conn;

            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $params = [$this->data[$field]];

            // Soft delete edilmiş kullanıcıları hariç tut (email tabanlı tablolar için)
            if ($table === 'users' && $column === 'email') {
                $sql .= " AND email NOT LIKE 'deleted_%'";
            }

            if ($exceptId !== null) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                $this->errors[$field][] = "Bu $field zaten kullanılıyor.";
            }
        }

        return $this;
    }

    /**
     * Dosya yükleme kontrolü
     *
     * @param string $field
     * @param array $allowedTypes ['jpg', 'png', 'pdf']
     * @param int $maxSize Byte cinsinden
     * @return self
     */
    public function file(string $field, array $allowedTypes = [], int $maxSize = 10485760): self
    {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$field];

            // Upload hatası kontrolü
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[$field][] = "Dosya yükleme hatası.";
                return $this;
            }

            // Boyut kontrolü
            if ($file['size'] > $maxSize) {
                $maxSizeMB = round($maxSize / 1048576, 2);
                $this->errors[$field][] = "Dosya boyutu en fazla {$maxSizeMB} MB olmalıdır.";
            }

            // Tip kontrolü
            if (!empty($allowedTypes)) {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedTypes)) {
                    $this->errors[$field][] = "Dosya tipi geçerli değil. İzin verilenler: " . implode(', ', $allowedTypes);
                }
            }

            // MIME type kontrolü (gerçek dosya tipi)
            if (!empty($allowedTypes)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                // MIME type mapping
                $mimeMapping = [
                    'image/jpeg' => ['jpg', 'jpeg'],
                    'image/png' => ['png'],
                    'image/gif' => ['gif'],
                    'image/webp' => ['webp'],
                    'application/pdf' => ['pdf'],
                    'application/msword' => ['doc'],
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
                ];

                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $validMime = false;

                foreach ($mimeMapping as $mime => $extensions) {
                    if ($mimeType === $mime && in_array($extension, $extensions)) {
                        $validMime = true;
                        break;
                    }
                }

                if (!$validMime) {
                    $this->errors[$field][] = "Dosya tipi güvenli değil.";
                }
            }
        }

        return $this;
    }

    /**
     * Tarih formatı kontrolü
     *
     * @param string $field
     * @param string $format Y-m-d gibi
     * @return self
     */
    public function date(string $field, string $format = 'Y-m-d'): self
    {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $date = \DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field][] = "$field geçerli bir tarih olmalıdır ($format).";
            }
        }

        return $this;
    }

    /**
     * Custom validation fonksiyonu
     *
     * @param string $field
     * @param callable $callback
     * @param string $message
     * @return self
     */
    public function custom(string $field, callable $callback, string $message): self
    {
        if (isset($this->data[$field])) {
            if (!$callback($this->data[$field], $this->data)) {
                $this->errors[$field][] = $message;
            }
        }

        return $this;
    }

    /**
     * Validasyon başarılı mı?
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Validasyon başarısız mı?
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Hataları döndür
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * İlk hatayı döndür
     *
     * @return string|null
     */
    public function firstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }

    /**
     * Belirli bir alanın hatasını döndür
     *
     * @param string $field
     * @return string|null
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Validate edilmiş ve temizlenmiş veriyi döndür
     *
     * @return array
     */
    public function validated(): array
    {
        if ($this->fails()) {
            throw new Exception('Validation failed. Cannot get validated data.');
        }

        // XSS koruması ile temizle
        $validated = [];
        foreach ($this->data as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = sanitizeString($value);
            } elseif (is_array($value)) {
                $validated[$key] = $value; // Array'ler için özel işlem gerekebilir
            } else {
                $validated[$key] = $value;
            }
        }

        return $validated;
    }

    /**
     * Sadece belirtilen alanları döndür
     *
     * @param array $fields
     * @return array
     */
    public function only(array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            if (isset($this->data[$field])) {
                $result[$field] = $this->data[$field];
            }
        }
        return $result;
    }

    /**
     * Belirtilen alanlar hariç tümünü döndür
     *
     * @param array $fields
     * @return array
     */
    public function except(array $fields): array
    {
        $result = $this->data;
        foreach ($fields as $field) {
            unset($result[$field]);
        }
        return $result;
    }
}
