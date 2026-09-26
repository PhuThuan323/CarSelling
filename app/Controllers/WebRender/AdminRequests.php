<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Inspection\ValuationRequest;

/**
 * Trang admin: danh sach TAT CA ho so ban xe (moi trang thai).
 *
 *   GET /admin/requests            - toan bo ho so
 *   GET /admin/requests?status=x   - loc theo 1 trang thai
 */
class AdminRequests
{
    /**
     * Cac tab loc nhanh theo nhom trang thai.
     * 'statuses' => null nghia la hien tat ca.
     */
    private const FILTERS = [
        'all' => [
            'label' => 'Tất cả hồ sơ',
            'statuses' => null,
        ],
        'pending' => [
            'label' => 'Chờ tiếp nhận',
            'statuses' => ['ready_for_estimate'],
        ],
        'inspection' => [
            'label' => 'Đang Inspection',
            'statuses' => [
                'inspection_requested',
                'inspection_assigned',
                'inspection_in_progress',
                'inspection_completed',
            ],
        ],
        'estimated' => [
            'label' => 'Đã gửi giá',
            'statuses' => ['estimated'],
        ],
        'accepted' => [
            'label' => 'Khách đồng ý',
            'statuses' => ['accepted'],
        ],
        'cancelled' => [
            'label' => 'Đã hủy / từ chối',
            'statuses' => ['cancelled', 'rejected'],
        ],
    ];

    private View $view;

    private ValuationRequest $requests;

    public function __construct()
    {
        $this->view = new View();
        $this->requests = new ValuationRequest();
    }

    public function index(): void
    {
        $user = Auth::requireAdmin(false);

        $filter = trim((string) ($_GET['filter'] ?? ''));

        if ($filter === '' || !isset(self::FILTERS[$filter])) {
            $filter = 'all';
        }

        $statuses = self::FILTERS[$filter]['statuses'];

        if ($statuses === null) {
            $items = $this->requests->findAllRequests();
        } else {
            $items = $this->requests->findByStatuses($statuses);
        }

        foreach ($items as &$item) {
            $item['status_label'] = ValuationRequest::statusLabel(
                (string) $item['status']
            );
        }

        unset($item);

        $counts = $this->requests->countByStatus();

        $this->view->assign('admin_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign('inspection_counts', $this->inspectionCounts());
        $this->view->assign('staff_counts', (new \App\Models\UserManagement\User())->countByRole());
        $this->view->assign('page_title', 'Hồ sơ bán xe - CarSelling');
        $this->view->assign('screen_title', self::FILTERS[$filter]['label']);
        $this->view->assign(
            'screen_subtitle',
            'Toàn bộ hồ sơ khách đăng bán xe và trạng thái xử lý hiện tại.'
        );
        $this->view->assign('active_menu', 'requests');
        $this->view->assign('active_filter', $filter);
        $this->view->assign('filters', $this->filterTabs($filter, $counts));
        $this->view->assign('items', $items);

        $this->view->display('admin/requests');
    }

    /**
     * So luong cho badge tren sidebar (menu Xe chờ Inspection).
     */
    private function inspectionCounts(): array
    {
        $count = fn (array $statuses): int
            => count($this->requests->findByStatuses($statuses, 500));

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

    /**
     * Tab kem so luong de admin nhin nhanh.
     */
    private function filterTabs(string $activeFilter, array $counts): array
    {
        $tabs = [];

        foreach (self::FILTERS as $key => $meta) {
            $total = 0;

            if ($meta['statuses'] === null) {
                $total = array_sum($counts);
            } else {
                foreach ($meta['statuses'] as $status) {
                    $total += $counts[$status] ?? 0;
                }
            }

            $tabs[] = [
                'key' => $key,
                'label' => $meta['label'],
                'total' => $total,
                'is_active' => $key === $activeFilter,
            ];
        }

        return $tabs;
    }
}