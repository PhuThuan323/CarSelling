<?php
namespace App\Core;

use App\Models\UserManagement\User;

class Auth
{
    // Always read the current role/status from the database, never from request data.
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) return null;
        $user = (new User())->findById((int) $_SESSION['user_id']);
        if (!$user || $user['status'] !== 'active') {
            unset($_SESSION['user_id'], $_SESSION['user'], $_SESSION['user_role']);
            return null;
        }
        $_SESSION['user'] = array_intersect_key($user, array_flip(['id', 'name', 'email', 'role']));
        return $_SESSION['user'];
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function requireAdmin(bool $api = true): array
    {
        $user = self::user();
        if (!$user) {
            if ($api) JsonResponse::error('Vui lòng đăng nhập.', 401);
            header('Location: /auth/login');
            exit;
        }
        if (($user['role'] ?? '') !== 'admin') {
            if ($api) JsonResponse::error('Bạn không có quyền quản trị.', 403);
            http_response_code(403);
            echo '403 - Bạn không có quyền truy cập trang quản trị. <a href="/">Về trang chủ</a>';
            exit;
        }
        header('Cache-Control: no-store');
        if ($api && !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
            if (!is_string($token) || !hash_equals(self::csrfToken(), $token)) {
                JsonResponse::error('Phiên bảo mật không hợp lệ. Vui lòng tải lại trang.', 419);
            }
        }
        return $user;
    }
}
