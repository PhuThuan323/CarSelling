<?php

declare(strict_types=1);

namespace App\Controllers\UserManagement;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Models\UserManagement\User;

/**
 * API quan ly nhan su + phan quyen (chi admin).
 *
 *   GET  /api/v1/admin/users
 *   POST /api/v1/admin/users/{id}/role
 *   POST /api/v1/admin/users/{id}/status
 *
 * Quy tac an toan:
 *   - Khong tu ha quyen chinh minh (tranh tu khoa quyen admin).
 *   - Khong ha quyen admin cuoi cung cua he thong.
 *   - Thu hoi quyen staff se canh bao neu con ho so dang mo.
 */
class AdminStaffController
{
    private const ASSIGNABLE_ROLES = ['customer', 'staff', 'admin'];

    private const ASSIGNABLE_STATUSES = ['active', 'inactive', 'blocked'];

    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    /**
     * GET /api/v1/admin/users
     */
    public function index(): void
    {
        Auth::requireAdmin();

        $role = trim((string) ($_GET['role'] ?? ''));

        $status = trim((string) ($_GET['status'] ?? ''));

        $keyword = trim((string) ($_GET['q'] ?? ''));

        // Chi loc khi gia tri hop le, tranh loc sai.
        if ($role !== '' && !in_array($role, self::ASSIGNABLE_ROLES, true)) {
            $role = '';
        }

        if ($status !== '' && !in_array($status, self::ASSIGNABLE_STATUSES, true)) {
            $status = '';
        }

        JsonResponse::success([
            'users' => $this->users->findAllForAdmin(
                $role !== '' ? $role : null,
                $status !== '' ? $status : null,
                $keyword !== '' ? $keyword : null
            ),
            'counts' => [
                'customer' => $this->users->countByRole()['customer'] ?? 0,
                'staff' => $this->users->countByRole()['staff'] ?? 0,
                'admin' => $this->users->countByRole()['admin'] ?? 0,
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/users/{id}/role
     * Body: { "role": "staff" }
     */
    public function updateRole(string $id): void
    {
        $admin = Auth::requireAdmin();

        $userId = (int) $id;

        $data = $this->requestData();

        $role = trim((string) ($data['role'] ?? ''));

        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            JsonResponse::error('Quyền không hợp lệ.', 422);
        }

        $target = $this->users->findById($userId);

        if (!$target) {
            JsonResponse::error('Không tìm thấy người dùng.', 404);
        }

        if ((string) $target['role'] === $role) {
            JsonResponse::error(
                'Người dùng này đã có quyền ' . $role . '.',
                422
            );
        }

        // Khong cho tu ha quyen chinh minh.
        if ($userId === (int) $admin['id']) {
            JsonResponse::error(
                'Bạn không thể tự thay đổi quyền của chính mình.',
                422
            );
        }

        $this->guardLastAdmin($target, $role);

        // Thu hoi quyen staff khi con ho so dang mo -> canh bao
        // de admin biet ma phan cong lai.
        $warning = null;

        if (
            (string) $target['role'] === 'staff'
            && $role !== 'staff'
        ) {
            $open = $this->users->countOpenInspections($userId);

            if ($open > 0) {
                $warning = 'Nhân viên này còn '
                    . $open
                    . ' hồ sơ inspection đang xử lý. Hãy phân công lại cho người khác.';
            }
        }

        $this->users->updateRole($userId, $role);

        JsonResponse::success(
            [
                'user_id' => $userId,
                'role' => $role,
                'warning' => $warning,
            ],
            $this->roleMessage($role, $target),
            200
        );
    }

    /**
     * POST /api/v1/admin/users/{id}/status
     * Body: { "status": "blocked" }
     */
    public function updateStatus(string $id): void
    {
        $admin = Auth::requireAdmin();

        $userId = (int) $id;

        $data = $this->requestData();

        $status = trim((string) ($data['status'] ?? ''));

        if (!in_array($status, self::ASSIGNABLE_STATUSES, true)) {
            JsonResponse::error('Trạng thái không hợp lệ.', 422);
        }

        $target = $this->users->findById($userId);

        if (!$target) {
            JsonResponse::error('Không tìm thấy người dùng.', 404);
        }

        if ($userId === (int) $admin['id']) {
            JsonResponse::error(
                'Bạn không thể tự khóa tài khoản của chính mình.',
                422
            );
        }

        // Khoa/vo hieu hoa admin cuoi cung la khong an toan.
        if (
            (string) $target['role'] === 'admin'
            && $status !== 'active'
        ) {
            $this->guardLastAdmin($target, 'customer');
        }

        $this->users->updateStatus($userId, $status);

        JsonResponse::success(
            [
                'user_id' => $userId,
                'status' => $status,
            ],
            $status === 'active'
                ? 'Đã mở lại tài khoản.'
                : 'Đã cập nhật trạng thái tài khoản.'
        );
    }

    /**
     * Chan viec ha quyen / khoa admin cuoi cung.
     */
    private function guardLastAdmin(array $target, string $newRole): void
    {
        if ((string) $target['role'] !== 'admin') {
            return;
        }

        if ($newRole === 'admin') {
            return;
        }

        $admins = $this->users->countByRole()['admin'] ?? 0;

        if ($admins <= 1) {
            JsonResponse::error(
                'Đây là quản trị viên cuối cùng. Không thể hạ quyền hoặc khóa.',
                422
            );
        }
    }

    private function roleMessage(string $role, array $target): string
    {
        $name = (string) $target['name'];

        return match ($role) {
            'staff' => $name . ' đã được cấp quyền nhân viên Inspection.',
            'admin' => $name . ' đã được cấp quyền quản trị viên.',
            default => $name . ' đã được chuyển về quyền khách hàng.',
        };
    }

    private function requestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');

            $decoded = json_decode($raw ?: '', true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }
}