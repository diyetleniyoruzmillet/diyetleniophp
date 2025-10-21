<?php
/**
 * File Upload Helper Class
 * Handles file uploads with validation and security
 */

class FileUpload
{
    private const MAX_FILE_SIZE = 5242880; // 5MB
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_DOCUMENT_TYPES = ['application/pdf', 'application/msword'];

    /**
     * Upload an image file
     *
     * @param array $file $_FILES array element
     * @param string $directory Upload directory (relative to UPLOAD_DIR)
     * @param array $options Additional options (maxSize, allowedTypes, resize)
     * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
     */
    public static function uploadImage(array $file, string $directory = 'images', array $options = []): array
    {
        return self::upload($file, $directory, array_merge([
            'allowedTypes' => self::ALLOWED_IMAGE_TYPES,
            'resize' => true
        ], $options));
    }

    /**
     * Upload a file
     *
     * @param array $file $_FILES array element
     * @param string $directory Upload directory
     * @param array $options Upload options
     * @return array
     */
    public static function upload(array $file, string $directory, array $options = []): array
    {
        // Validate file exists
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'filename' => null, 'error' => 'Dosya yüklenmedi.'];
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'filename' => null, 'error' => self::getUploadErrorMessage($file['error'])];
        }

        // Validate file size
        $maxSize = $options['maxSize'] ?? self::MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            return ['success' => false, 'filename' => null, 'error' => "Dosya boyutu {$maxSizeMB}MB'dan küçük olmalıdır."];
        }

        // Validate file type
        $allowedTypes = $options['allowedTypes'] ?? self::ALLOWED_IMAGE_TYPES;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'filename' => null, 'error' => 'Geçersiz dosya tipi.'];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = self::generateUniqueFilename($extension);

        // Create upload directory if not exists
        $uploadPath = UPLOAD_DIR . '/' . trim($directory, '/');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fullPath = $uploadPath . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'filename' => null, 'error' => 'Dosya yüklenirken hata oluştu.'];
        }

        // Resize image if option is enabled
        if (($options['resize'] ?? false) && in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            self::resizeImage($fullPath, $options['maxWidth'] ?? 1200, $options['maxHeight'] ?? 1200);
        }

        // Return relative path for database storage
        $relativePath = trim($directory, '/') . '/' . $filename;

        return [
            'success' => true,
            'filename' => $relativePath,
            'error' => null
        ];
    }

    /**
     * Delete an uploaded file
     *
     * @param string $filename Relative filename
     * @return bool
     */
    public static function delete(string $filename): bool
    {
        if (empty($filename)) {
            return false;
        }

        $fullPath = UPLOAD_DIR . '/' . ltrim($filename, '/');

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Resize image to fit within max dimensions
     *
     * @param string $filepath
     * @param int $maxWidth
     * @param int $maxHeight
     * @return bool
     */
    private static function resizeImage(string $filepath, int $maxWidth, int $maxHeight): bool
    {
        // Get image info
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo) {
            return false;
        }

        list($width, $height, $type) = $imageInfo;

        // Check if resize is needed
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }

        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);

        // Create image resource based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filepath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($filepath);
                break;
            default:
                return false;
        }

        if (!$source) {
            return false;
        }

        // Create new image
        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save resized image
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($destination, $filepath, 90);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($destination, $filepath, 9);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($destination, $filepath);
                break;
            case IMAGETYPE_WEBP:
                $success = imagewebp($destination, $filepath, 90);
                break;
        }

        // Free memory
        imagedestroy($source);
        imagedestroy($destination);

        return $success;
    }

    /**
     * Generate unique filename
     *
     * @param string $extension
     * @return string
     */
    private static function generateUniqueFilename(string $extension): string
    {
        return bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode
     * @return string
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Dosya boyutu çok büyük.';
            case UPLOAD_ERR_PARTIAL:
                return 'Dosya kısmen yüklendi.';
            case UPLOAD_ERR_NO_FILE:
                return 'Dosya seçilmedi.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Geçici klasör bulunamadı.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Dosya yazılamadı.';
            case UPLOAD_ERR_EXTENSION:
                return 'Dosya yükleme engellenmiş bir uzantı.';
            default:
                return 'Bilinmeyen hata.';
        }
    }

    /**
     * Validate image dimensions
     *
     * @param array $file
     * @param int $minWidth
     * @param int $minHeight
     * @return bool
     */
    public static function validateImageDimensions(array $file, int $minWidth, int $minHeight): bool
    {
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return false;
        }

        list($width, $height) = $imageInfo;

        return $width >= $minWidth && $height >= $minHeight;
    }
}
