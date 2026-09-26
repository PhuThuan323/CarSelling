<?php

declare(strict_types=1);

namespace App\Controllers\Inspection;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Models\Inspection\ValuationCustomerFeedback;
use App\Models\Inspection\ValuationInspectionAssignment;
use App\Models\Inspection\ValuationInspectionResult;
use App\Models\Inspection\ValuationRequest;
use App\Models\Notification\Notification;
use App\Models\UserManagement\User;
use App\Service\ValuationWorkflow;
use RuntimeException;
use Throwable;

/**
 * API khu vuc ADMIN cho workflow inspection.
 *
 *   POST /api/v1/admin/inspections/{id}/request
 *   POST /api/v1/admin/inspections/{id}/assign
 *   POST /api/v1/admin/inspections/{id}/approve
 *   GET  /api/v1/admin/inspections
 *   GET  /api/v1/admin/inspections/{id}
 */
class AdminInspectionController
{
    private ValuationRequest $requests;

    private ValuationInspectionAssignment $assignments;

    private ValuationInspectionResult $results;

    private ValuationCustomerFeedback $feedbacks;

    private User $users;

    private ValuationWorkflow $workflow;

    public function __construct()
    {
        $this->requests = new ValuationRequest();
        $this->assignments = new ValuationInspectionAssignment();
        $this->results = new ValuationInspectionResult();
        $this->feedbacks = new ValuationCustomerFeedback();
        $this->users = new User();
        $this->workflow = new ValuationWorkflow();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        $group = trim((string) ($_GET['group'] ?? ''));

        $groups = $this->groups();

        if ($group !== '' && isset($groups[$group])) {
            $statuses = $groups[$group]['statuses'];
        } else {
            $statuses = [
                'inspection_requested',
                'inspection_assigned',
                'inspection_in_progress',
                'inspection_completed',
            ];
        }

        $rows = $this->requests->findByStatuses($statuses, 300);

        JsonResponse::success([
            'group' => $group !== '' ? $group : 'all',
            'counts' => $this->counts(),
            'items' => $rows,
        ]);
    }

    /**
     * Chi tiet phuc vu man hinh phan cong / duyet.
     */
    public function show(string $id): void
    {
        Auth::requireAdmin();

        $requestId = (int) $id;

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        $assignment = $this->assignments->findActiveByRequest($requestId);
        $result = $this->results->findByRequest($requestId);

        JsonResponse::success([
            'request' => $request,
            'status_label' => ValuationRequest::statusLabel(
                (string) $request['status']
            ),
            'assignment' => $assignment,
            'result' => $result,
            'staff_options' => $this->users->findActiveStaff(),
            'feedback' => $this->feedbacks->findLatestByRequest($requestId),
            'history' => $this->requests->findHistory($requestId),
        ]);
    }

    /**
     * Admin tiep nhan ho so -> inspection_requested.
     */
    public function requestInspection(string $id): void
    {
        $admin = Auth::requireAdmin();

        $requestId = (int) $id;

        try {
            $this->workflow->requestInspection(
                $requestId,
                (int) $admin['id']
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('requestInspection loi: ' . $e->getMessage());
            JsonResponse::error('Không thể tiếp nhận hồ sơ.', 500);
        }

        JsonResponse::success(
            ['valuation_request_id' => $requestId],
            'Đã tiếp nhận hồ sơ và yêu cầu inspection.'
        );
    }

    /**
     * Admin phan cong (hoac thay) staff.
     *
     * Body:
     *   staff_user_id  (required)
     *   scheduled_at   (optional, Y-m-d H:i)
     *   admin_note     (optional)
     */
    public function assignStaff(string $id): void
    {
        $admin = Auth::requireAdmin();

        $requestId = (int) $id;

        $data = $this->requestData();

        $staffUserId = (int) ($data['staff_user_id'] ?? 0);

        if ($staffUserId <= 0) {
            JsonResponse::error('Vui lòng chọn nhân viên inspection.', 422);
        }

        $staff = $this->users->findActiveStaffById($staffUserId);

        if (!$staff) {
            JsonResponse::error(
                'Nhân viên không tồn tại hoặc đã ngừng hoạt động.',
                422
            );
        }

        $scheduledAt = $this->nullableText($data['scheduled_at'] ?? null);

        if ($scheduledAt !== null) {
            $scheduledAt = $this->normalizeDateTime($scheduledAt);

            if ($scheduledAt === null) {
                JsonResponse::error(
                    'Lịch inspection không hợp lệ.',
                    422
                );
            }
        }

        $adminNote = $this->nullableText($data['admin_note'] ?? null);

        if ($adminNote !== null && mb_strlen($adminNote) > 500) {
            JsonResponse::error(
                'Ghi chú không vượt quá 500 ký tự.',
                422
            );
        }

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        $vehicleLabel = $this->vehicleLabel($request);

        try {
            $existing = $this->assignments->findActiveByRequest($requestId);

            // Thay staff: huy assignment cu nhung giu lich su.
            if ($existing !== null) {
                $this->assignments->cancelActiveForRequest($requestId);
            }

            $this->assignments->create(
                $requestId,
                $staffUserId,
                (int) $admin['id'],
                $scheduledAt,
                $adminNote
            );

            $this->workflow->assignStaff(
                $requestId,
                $staffUserId,
                (int) $admin['id'],
                $scheduledAt,
                $adminNote,
                (string) $staff['name'],
                $vehicleLabel
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('assignStaff loi: ' . $e->getMessage());
            JsonResponse::error('Không thể phân công nhân viên.', 500);
        }

        JsonResponse::success(
            [
                'valuation_request_id' => $requestId,
                'staff_user_id' => $staffUserId,
                'staff_name' => $staff['name'],
                'scheduled_at' => $scheduledAt,
            ],
            'Đã phân công nhân viên inspection.'
        );
    }

    /**
     * Admin doc ket qua + chot gia cuoi cung + feedback cho khach.
     *
     * Body:
     *   price_min (required)
     *   price_max (required)
     *   message   (required) - feedback gui khach
     */
    public function approve(string $id): void
    {
        $admin = Auth::requireAdmin();

        $requestId = (int) $id;

        $data = $this->requestData();

        $priceMin = $this->parsePrice($data['price_min'] ?? null);
        $priceMax = $this->parsePrice($data['price_max'] ?? null);

        if ($priceMin === null || $priceMax === null) {
            JsonResponse::error(
                'Vui lòng nhập giá đề xuất thấp nhất và cao nhất.',
                422
            );
        }

        if ($priceMin <= 0 || $priceMax <= 0) {
            JsonResponse::error('Giá đề xuất phải lớn hơn 0.', 422);
        }

        if ($priceMax < $priceMin) {
            JsonResponse::error(
                'Giá cao nhất phải lớn hơn hoặc bằng giá thấp nhất.',
                422
            );
        }

        $message = trim((string) ($data['message'] ?? ''));

        if ($message === '') {
            JsonResponse::error(
                'Vui lòng nhập feedback gửi khách hàng.',
                422
            );
        }

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        try {
            $this->workflow->publishEstimate(
                $requestId,
                (int) $admin['id'],
                $priceMin,
                $priceMax,
                $message
            );

            $this->feedbacks->create(
                $requestId,
                (int) $admin['id'],
                $priceMin,
                $priceMax,
                $message
            );

            // Thong bao cho khach so huu ho so.
            $ownerId = (int) ($request['user_id'] ?? 0);

            if ($ownerId > 0) {
                (new Notification())->create(
                    $ownerId,
                    Notification::TYPE_INSPECTION_RESULT_READY,
                    'Kết quả định giá xe của bạn đã có',
                    $this->vehicleLabel($request)
                        . "\nGiá đề xuất: "
                        . number_format($priceMin, 0, ',', '.')
                        . ' - '
                        . number_format($priceMax, 0, ',', '.')
                        . ' đ',
                    'valuation_request',
                    $requestId
                );
            }
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('approve inspection loi: ' . $e->getMessage());
            JsonResponse::error('Không thể gửi kết quả cho khách.', 500);
        }

        JsonResponse::success(
            [
                'valuation_request_id' => $requestId,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
            ],
            'Đã chốt giá và gửi kết quả cho khách hàng.'
        );
    }

    /**
     * So luong theo tung nhom trang thai cho sidebar + KPI.
     *
     * @return array<string,int>
     */
    private function counts(): array
    {
        $counts = [];

        foreach ($this->groups() as $key => $group) {
            $counts[$key] = count(
                $this->requests->findByStatuses($group['statuses'], 500)
            );
        }

        $counts['pending_assignment'] = count(
            $this->requests->findByStatuses(['inspection_requested'], 500)
        );

        $counts['inspection_total'] = $counts['all'] ?? 0;

        return $counts;
    }

    /**
     * Dinh nghia cac nhom trang thai dung cho sidebar.
     *
     * @return array<string,array{label:string,statuses:string[]}>
     */
    private function groups(): array
    {
        return [
            'all' => [
                'label' => 'Tất cả xe chờ Inspection',
                'statuses' => [
                    'inspection_requested',
                    'inspection_assigned',
                    'inspection_in_progress',
                    'inspection_completed',
                ],
            ],
            'unassigned' => [
                'label' => 'Chưa phân công',
                'statuses' => ['inspection_requested'],
            ],
            'assigned' => [
                'label' => 'Đã phân công',
                'statuses' => ['inspection_assigned'],
            ],
            'in_progress' => [
                'label' => 'Đang Inspection',
                'statuses' => ['inspection_in_progress'],
            ],
            'review' => [
                'label' => 'Chờ duyệt kết quả',
                'statuses' => ['inspection_completed'],
            ],
        ];
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

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function parsePrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Chap nhan "425.000.000" hoac "425,000,000" hoac "425000000"
        $normalized = preg_replace(
            '/[^\d]/',
            '',
            (string) $value
        );

        if ($normalized === null || $normalized === '') {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Chuan hoa "26/09/2026 09:00" hoac "2026-09-26T09:00"
     * ve dinh dang DATETIME.
     */
    private function normalizeDateTime(string $value): ?string
    {
        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
            'd/m/Y H:i',
            'd/m/Y H:i:s',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);

            if ($date instanceof \DateTime) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function vehicleLabel(array $request): string
    {
        $snapshot = $request['vehicle_snapshot'] ?? [];

        if (is_string($snapshot)) {
            $decoded = json_decode($snapshot, true);

            $snapshot = is_array($decoded) ? $decoded : [];
        }

        $brand = trim((string) ($snapshot['brand_name'] ?? ''));
        $model = trim((string) ($snapshot['model_name'] ?? ''));

        $name = trim($brand . ' ' . $model);

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