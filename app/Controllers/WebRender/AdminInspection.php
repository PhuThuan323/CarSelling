<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Core\View;
use App\Models\Inspection\ValuationCustomerFeedback;
use App\Models\Inspection\ValuationInspectionAssignment;
use App\Models\Inspection\ValuationInspectionResult;
use App\Models\Inspection\ValuationRequest;
use App\Models\UserManagement\User;

/**
 * Trang quan tri cho workflow inspection.
 *
 *   GET /admin/inspections                  - danh sach xe cho inspection
 *   GET /admin/inspections/review           - cho duyet ket qua
 *   GET /admin/inspections/{id}             - chi tiet + phan cong
 */
class AdminInspection
{
    private View $view;

    private ValuationRequest $requests;

    private ValuationInspectionAssignment $assignments;

    private ValuationInspectionResult $results;

    private ValuationCustomerFeedback $feedbacks;

    private User $users;

    public function __construct()
    {
        $this->view = new View();
        $this->requests = new ValuationRequest();
        $this->assignments = new ValuationInspectionAssignment();
        $this->results = new ValuationInspectionResult();
        $this->feedbacks = new ValuationCustomerFeedback();
        $this->users = new User();
    }

    public function index(): void
    {
        $user = Auth::requireAdmin(false);

        $items = $this->requests->findByStatuses(
            [
                'inspection_requested',
                'inspection_assigned',
                'inspection_in_progress',
                'inspection_completed',
            ]
        );

        $this->assignCommon($user);

        $this->view->assign('page_title', 'Xe chờ Inspection - CarSelling');
        $this->view->assign('screen_title', 'Xe chờ Inspection');
        $this->view->assign(
            'screen_subtitle',
            'Tiếp nhận hồ sơ, phân công nhân viên và theo dõi tiến độ thẩm định.'
        );
        $this->view->assign('active_menu', 'inspections');
        $this->view->assign('items', $items);

        $this->view->display('admin/inspections');
    }

    public function review(): void
    {
        $user = Auth::requireAdmin(false);

        $items = $this->requests->findByStatuses(['inspection_completed']);

        $this->assignCommon($user);

        $this->view->assign('page_title', 'Chờ duyệt kết quả - CarSelling');
        $this->view->assign('screen_title', 'Chờ duyệt kết quả Inspection');
        $this->view->assign(
            'screen_subtitle',
            'Xem kết quả thẩm định, chốt range giá cuối cùng và gửi feedback cho khách.'
        );
        $this->view->assign('active_menu', 'inspection_review');
        $this->view->assign('items', $items);

        $this->view->display('admin/inspection_review');
    }

    public function detail(string $id): void
    {
        $user = Auth::requireAdmin(false);

        $requestId = (int) $id;

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            http_response_code(404);
            $this->view->display('errors/404');

            return;
        }

        $this->assignCommon($user);

        $this->view->assign(
            'page_title',
            'Inspection '
                . ($request['reference_code'] ?? ('#' . $requestId))
                . ' - CarSelling'
        );
        $this->view->assign('active_menu', 'inspections');
        $this->view->assign('request', $request);
        $this->view->assign(
            'status_label',
            ValuationRequest::statusLabel((string) $request['status'])
        );
        $this->view->assign(
            'assignment',
            $this->assignments->findActiveByRequest($requestId)
        );
        $this->view->assign(
            'assignment_history',
            $this->assignments->findByRequest($requestId)
        );
        $this->view->assign(
            'result',
            $this->results->findByRequest($requestId)
        );
        $this->view->assign(
            'rating_fields',
            $this->ratingFieldsForDisplay(
                $this->results->findByRequest($requestId)
            )
        );
        $this->view->assign('staff_options', $this->users->findActiveStaff());
        $this->view->assign(
            'latest_feedback',
            $this->feedbacks->findLatestByRequest($requestId)
        );
        $this->view->assign(
            'history',
            $this->requests->findHistory($requestId)
        );

        $this->view->display('admin/inspection_detail');
    }

    private function assignCommon(array $user): void
    {
        $this->view->assign('admin_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
    }

    /**
     * Chuan hoa ket qua tham dinh thanh label de hien thi.
     */
    private function ratingFieldsForDisplay(?array $result): array
    {
        $rows = [];

        foreach (ValuationInspectionResult::RATING_FIELDS as $field => $meta) {
            $value = $result[$field] ?? null;

            $rows[] = [
                'label' => $meta['label'],
                'group' => $meta['group'],
                'value' => $value,
                'label_value' => $this->ratingLabel(
                    $value !== null ? (string) $value : null,
                    (string) $meta['type']
                ),
            ];
        }

        return $rows;
    }

    private function ratingLabel(?string $rating, string $type): string
    {
        if ($rating === null) {
            return 'Chưa đánh giá';
        }

        if ($type === 'good_issue') {
            return $rating === 'good' ? 'Tốt' : 'Có vấn đề';
        }

        return [
            'good' => 'Tốt',
            'average' => 'Trung bình',
            'poor' => 'Kém',
        ][$rating] ?? $rating;
    }
}