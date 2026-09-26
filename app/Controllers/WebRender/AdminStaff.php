<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\UserManagement\User;

/**
 * Trang quan tri: quan ly nhan su + phan quyen.
 *
 *   GET /admin/staff          - tat ca nguoi dung
 *   GET /admin/staff?role=staff
 */
class AdminStaff
{
    private const ROLE_FILTERS = [
        'all' => [
            'label' => 'Tất cả',
            'role' => null,
        ],
        'staff' => [
            'label' => 'Nhân viên Inspection',
            'role' => 'staff',
        ],
        'customer' => [
            'label' => 'Khách hàng',
            'role' => 'customer',
        ],
        'admin' => [
            'label' => 'Quản trị viên',
            'role' => 'admin',
        ],
    ];

    private View $view;

    private User $users;

    public function __construct()
    {
        $this->view = new View();
        $this->users = new User();
    }

    public function index(): void
    {
        $user = Auth::requireAdmin(false);

        $filter = trim((string) ($_GET['role'] ?? ''));

        if ($filter === '' || !isset(self::ROLE_FILTERS[$filter])) {
            $filter = 'all';
        }

        $role = self::ROLE_FILTERS[$filter]['role'];

        $members = $this->users->findAllForAdmin($role);

        // Danh dau nhung nguoi dang giu ho so inspection dang mo,
        // de admin biet truoc khi thu hoi quyen.
        foreach ($members as &$member) {
            $member['open_inspections'] = (string) $member['role'] === 'staff'
                ? $this->users->countOpenInspections((int) $member['id'])
                : 0;

            $member['is_self'] = (int) $member['id'] === (int) $user['id'];
        }

        unset($member);

        $counts = $this->users->countByRole();

        $this->view->assign('admin_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign('inspection_counts', $this->inspectionCounts());
        $this->view->assign('page_title', 'Quản lý nhân sự - CarSelling');
        $this->view->assign('screen_title', 'Quản lý nhân sự & phân quyền');
        $this->view->assign(
            'screen_subtitle',
            'Cấp hoặc thu hồi quyền nhân viên Inspection cho tài khoản khách hàng.'
        );
        $this->view->assign('active_menu', 'staff');
        $this->view->assign('active_filter', $filter);
        $this->view->assign(
            'role_filters',
            $this->filterTabs($filter, $counts)
        );
        $this->view->assign('members', $members);

        $this->view->display('admin/staff');
    }

    /**
     * Tab loc theo role, kem so luong.
     */
    private function filterTabs(string $activeFilter, array $counts): array
    {
        $tabs = [];

        foreach (self::ROLE_FILTERS as $key => $meta) {
            $total = $meta['role'] === null
                ? array_sum($counts)
                : ($counts[$meta['role']] ?? 0);

            $tabs[] = [
                'key' => $key,
                'label' => $meta['label'],
                'total' => $total,
                'is_active' => $key === $activeFilter,
            ];
        }

        return $tabs;
    }

    /**
     * So luong cho badge tren sidebar.
     */
    private function inspectionCounts(): array
    {
        $requests = new \App\Models\Inspection\ValuationRequest();

        $count = fn (array $statuses): int
            => count($requests->findByStatuses($statuses, 500));

        $unassigned = $count(['inspection_requested']);
        $assigned = $count(['inspection_assigned']);
        $inProgress = $count(['inspection_in_progress']);
        $review = $count(['inspection_completed']);

        return [
            'unassigned' => $unassigned,
            'assigned' => $assigned,
            'in_progress' => $inProgress,
            'review' => $review,
            'all' => $unassigned + $assigned + $inProgress + $review,
        ];
    }
}