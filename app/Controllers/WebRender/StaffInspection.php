<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Inspection\ValuationInspectionAssignment;
use App\Models\Inspection\ValuationInspectionResult;
use App\Models\Inspection\ValuationRequest;
use App\Models\Notification\Notification;

/**
 * Trang khu vuc STAFF (nhan vien inspection).
 *
 *   GET /staff/inspections              - nhiem vu duoc giao
 *   GET /staff/inspections/{id}         - chi tiet + form tham dinh
 */
class StaffInspection
{
    private View $view;

    private ValuationInspectionAssignment $assignments;

    private ValuationInspectionResult $results;

    private ValuationRequest $requests;

    private Notification $notifications;

    public function __construct()
    {
        $this->view = new View();
        $this->assignments = new ValuationInspectionAssignment();
        $this->results = new ValuationInspectionResult();
        $this->requests = new ValuationRequest();
        $this->notifications = new Notification();
    }

    public function index(): void
    {
        $staff = Auth::requireStaff(false);

        $staffId = (int) $staff['id'];

        $counts = $this->assignments->countByStaff($staffId);

        $items = $this->assignments->findByStaff($staffId);

        foreach ($items as &$item) {
            $item['vehicle_label'] = $this->vehicleLabel($item);
            $item['request_status_label'] = ValuationRequest::statusLabel(
                (string) $item['request_status']
            );
            $item['assignment_status_label'] = $this->assignmentStatusLabel(
                (string) $item['assignment_status']
            );
        }

        unset($item);

        $this->assignCommon($staff);

        $this->view->assign('page_title', 'Nhiệm vụ Inspection - CarSelling');
        $this->view->assign('active_menu', 'staff_tasks');
        $this->view->assign('counts', $counts);
        $this->view->assign('items', $items);
        $this->view->assign(
            'unread_notifications',
            $this->notifications->unreadCount($staffId)
        );

        $this->view->display('staff/inspections');
    }

    public function detail(string $id): void
    {
        $staff = Auth::requireStaff(false);

        $staffId = (int) $staff['id'];

        $assignmentId = (int) $id;

        // Staff chi xem duoc nhiem vu cua chinh minh.
        $assignment = $this->assignments->findOwnedByStaff(
            $assignmentId,
            $staffId
        );

        if (!$assignment) {
            http_response_code(404);
            $this->view->display('errors/404');

            return;
        }

        $requestId = (int) $assignment['valuation_request_id'];

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            http_response_code(404);
            $this->view->display('errors/404');

            return;
        }

        $result = $this->results->findByAssignment($assignmentId);

        $this->assignCommon($staff);

        $this->view->assign(
            'page_title',
            'Inspection ' . ($request['reference_code'] ?? '') . ' - CarSelling'
        );
        $this->view->assign('active_menu', 'staff_tasks');
        $this->view->assign('assignment', $assignment);
        $this->view->assign(
            'assignment_status_label',
            $this->assignmentStatusLabel((string) $assignment['status'])
        );
        $this->view->assign('request', $request);
        $this->view->assign(
            'request_status_label',
            ValuationRequest::statusLabel((string) $request['status'])
        );
        $this->view->assign('result', $result);
        $this->view->assign('vehicle_label', $this->vehicleLabel($request));
        $this->view->assign(
            'rating_fields',
            $this->ratingFieldsForForm($result)
        );
        $this->view->assign(
            'unread_notifications',
            $this->notifications->unreadCount($staffId)
        );

        $this->view->display('staff/inspection_detail');
    }

    private function assignCommon(array $staff): void
    {
        $this->view->assign('staff_user', $staff);
        $this->view->assign('csrf_token', Auth::csrfToken());
    }

    /**
     * Hang muc danh gia cho form, kem gia tri da luu.
     */
    private function ratingFieldsForForm(?array $result): array
    {
        $fields = [];

        foreach (ValuationInspectionResult::RATING_FIELDS as $field => $meta) {
            $options = [];

            foreach (
                ValuationInspectionResult::allowedRatings(
                    (string) $meta['type']
                ) as $value
            ) {
                $options[] = [
                    'value' => $value,
                    'label' => $this->ratingLabel(
                        $value,
                        (string) $meta['type']
                    ),
                ];
            }

            $fields[] = [
                'key' => $field,
                'label' => $meta['label'],
                'group' => $meta['group'],
                'options' => $options,
                'value' => $result[$field] ?? '',
            ];
        }

        return $fields;
    }

    private function ratingLabel(string $rating, string $type): string
    {
        if ($type === 'good_issue') {
            return $rating === 'good' ? 'Tốt' : 'Có vấn đề';
        }

        return [
            'good' => 'Tốt',
            'average' => 'Trung bình',
            'poor' => 'Kém',
        ][$rating] ?? $rating;
    }

    private function assignmentStatusLabel(string $status): string
    {
        return [
            'assigned' => 'Đã phân công',
            'accepted' => 'Đã nhận nhiệm vụ',
            'in_progress' => 'Đang thực hiện',
            'completed' => 'Đã hoàn tất',
            'cancelled' => 'Đã hủy',
        ][$status] ?? $status;
    }

    private function vehicleLabel(array $request): string
    {
        $snapshot = $request['vehicle_snapshot'] ?? [];

        if (is_string($snapshot)) {
            $decoded = json_decode($snapshot, true);

            $snapshot = is_array($decoded) ? $decoded : [];
        }

        $name = trim(
            (string) ($snapshot['brand_name'] ?? '')
            . ' '
            . (string) ($snapshot['model_name'] ?? '')
        );

        if ($name === '') {
            $name = 'Xe #' . ($request['reference_code'] ?? '');
        }

        $year = $request['manufacture_year'] ?? null;

        if ($year !== null && $year !== '') {
            $name .= ' ' . $year;
        }

        return $name;
    }
}