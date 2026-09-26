<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Inspection\ValuationRequest;

class AdminDashboard
{
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
        $this->view->assign('page_title', 'Bảng điều khiển quản trị - CarSelling');
        $this->view->assign('admin_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign('active_menu', 'dashboard');
        $this->view->assign('inspection_counts', $this->inspectionCounts());
        $this->view->assign('kpis', $this->kpis());
        $this->view->display('admin/dashboard');
    }

    /**
     * KPI cho dashboard admin.
     *
     * @return array<string,int>
     */
    private function kpis(): array
    {
        $count = fn (array $statuses): int
            => count($this->requests->findByStatuses($statuses, 500));

        return [
            'ready_for_estimate' => $count(['ready_for_estimate']),
            'inspection_pending' => $count(['inspection_requested']),
            'inspection_assigned' => $count(['inspection_assigned']),
            'inspection_in_progress' => $count(['inspection_in_progress']),
            'inspection_completed' => $count(['inspection_completed']),
            'estimated' => $count(['estimated']),
            'accepted' => $count(['accepted']),
            'cancelled' => $count(['cancelled']),
        ];
    }

    /**
     * So luong cho menu "Xe chờ Inspection".
     *
     * @return array<string,int>
     */
    private function inspectionCounts(): array
    {
        return [
            'all' => count(
                $this->requests->findByStatuses(
                    [
                        'inspection_requested',
                        'inspection_assigned',
                        'inspection_in_progress',
                        'inspection_completed',
                    ],
                    500
                )
            ),
            'unassigned' => count(
                $this->requests->findByStatuses(['inspection_requested'], 500)
            ),
            'assigned' => count(
                $this->requests->findByStatuses(['inspection_assigned'], 500)
            ),
            'in_progress' => count(
                $this->requests->findByStatuses(['inspection_in_progress'], 500)
            ),
            'review' => count(
                $this->requests->findByStatuses(['inspection_completed'], 500)
            ),
        ];
    }
}
