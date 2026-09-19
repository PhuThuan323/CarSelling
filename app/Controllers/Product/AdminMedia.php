<?php

declare(strict_types=1);

namespace App\Controllers\Product;

use App\Core\JsonResponse;
use App\Service\CloudinaryService;
use Throwable;

class AdminMedia
{
    private CloudinaryService $cloudinary;

    public function __construct()
    {
        $this->cloudinary =
            new CloudinaryService();
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION['user_id'])) {
            JsonResponse::error(
                'Unauthenticated',
                401
            );
        }

        $role =
            $_SESSION['user']['role']
            ?? $_SESSION['user_role']
            ?? null;

        if ($role !== 'admin') {
            JsonResponse::error(
                'Forbidden',
                403
            );
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
                'Không tìm thấy file logo.',
                422
            );
        }

        $file = $_FILES['logo_file'];

        if (
            ($file['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_OK
        ) {
            JsonResponse::error(
                'Upload file thất bại.',
                422
            );
        }

        $tmpName =
            $file['tmp_name'] ?? '';

        if (
            $tmpName === ''
            || !is_uploaded_file($tmpName)
        ) {
            JsonResponse::error(
                'File upload không hợp lệ.',
                422
            );
        }

        if (
            ($file['size'] ?? 0)
            > 5 * 1024 * 1024
        ) {
            JsonResponse::error(
                'Logo không được vượt quá 5 MB.',
                422
            );
        }

        $finfo =
            new \finfo(
                FILEINFO_MIME_TYPE
            );

        $mime =
            $finfo->file($tmpName);

        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (
            !in_array(
                $mime,
                $allowed,
                true
            )
        ) {
            JsonResponse::error(
                'Chỉ hỗ trợ PNG, JPG và WebP.',
                422
            );
        }

        try {

            $upload =
                $this->cloudinary
                    ->uploadBrandLogo(
                        $tmpName
                    );

            JsonResponse::success(
                [
                    'upload' => $upload
                ],
                'Upload logo thành công',
                201
            );

        } catch (Throwable $e) {

            error_log(
                'Cloudinary error: '
                . $e->getMessage()
            );

            JsonResponse::error(
                'Không thể tải logo lên Cloudinary: '
                . $e->getMessage(),
                500
            );
        }
    }
}