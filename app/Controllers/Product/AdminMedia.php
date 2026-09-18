<?php 
declare(strict_types=1);
namespace App\Controllers\Product;

use App\Core\JsonResponse;
use App\Controllers\Service\CloudinaryService;
use Throwable;

class AdminMedia{
    private const MAX_LOGO_BYTES = 5 * 1024 * 1024;
    private CloudinaryService $cloudinary;
    public function __construct(){
        $this->cloudinary = new CloudinaryService();
    }
    private function requireAdmin(): void
    {
        if (empty($_SESSION['user_id'])) {
            JsonResponse::error('Unauthenticated', 401);
        }

        $role =
            $_SESSION['user']['role']
            ?? $_SESSION['user_role']
            ?? null;

        if ($role !== 'admin') {
            JsonResponse::error('Forbidden', 403);
        }
    }
    public function uploadBrandLogo(): void
    {
        $this->requireAdmin();

        if (
            empty($_FILES['logo_file'])
            || !is_array($_FILES['logo_file'])
        ) {
            JsonResponse::error(
                'Vui lòng chọn logo để tải lên.',
                422
            );
        }

        $file = $_FILES['logo_file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            JsonResponse::error(
                'Upload file thất bại. Mã lỗi: ' . $error,
                422
            );
        }

        $size = (int) ($file['size'] ?? 0);

        if (
            $size <= 0
            || $size > self::MAX_LOGO_BYTES
        ) {
            JsonResponse::error(
                'Logo phải nhỏ hơn hoặc bằng 5 MB.',
                422
            );
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if (
            $tmpName === ''
            || !is_uploaded_file($tmpName)
        ) {
            JsonResponse::error(
                'File upload không hợp lệ.',
                422
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName) ?: '';

        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (!in_array($mime, $allowed, true)) {
            JsonResponse::error(
                'Chỉ hỗ trợ PNG, JPG và WebP.',
                422
            );
        }
        try {
            $upload =
                $this->cloudinary
                    ->uploadBrandLogo($tmpName);

            if (empty($upload['secure_url'])) {
                JsonResponse::error(
                    'Cloudinary không trả về URL ảnh.',
                    502
                );
            }

            JsonResponse::success(
                ['upload' => $upload],
                'Brand logo uploaded successfully',
                201
            );

        } catch (Throwable $e) {
            error_log(
                'Cloudinary upload error: '
                . $e->getMessage()
            );

            JsonResponse::error(
                'Không thể tải logo lên Cloudinary.',
                500
            );
        }
    }

}