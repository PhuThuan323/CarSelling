<?php

declare(strict_types=1);

namespace App\Controllers\Inspection;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Models\Inspection\ValuationInspectionAssignment;
use App\Models\Inspection\ValuationInspectionResult;
use App\Models\Inspection\ValuationRequest;
use App\Service\ValuationWorkflow;
use RuntimeException;
use Throwable;

/**
 * API khu vuc STAFF (nhan vien inspection).
 *
 *   GET  /api/v1/staff/inspections
 *   GET  /api/v1/staff/inspections/{assignmentId}
 *   POST /api/v1/staff/inspections/{assignmentId}/accept
 *   POST /api/v1/staff/inspections/{assignmentId}/start
 *   POST /api/v1/staff/inspections/{assignmentId}/submit
 *
 * Staff CHI duoc de xuat range gia.
 * Gia cuoi cung + feedback khach do admin quyet dinh.
 */
class StaffInspectionController
{
    private ValuationInspectionAssignment $assignments;

    private ValuationInspectionResult $results;

    private ValuationRequest $requests;

    private ValuationWorkflow $workflow;

    public function __construct()
    {
        $this->assignments = new ValuationInspectionAssignment();
        $this->results = new ValuationInspectionResult();
        $this->requests = new ValuationRequest();
        $this->workflow = new ValuationWorkflow();
    }

    /**
     * Danh sach nhiem vu cua staff dang dang nhap.
     */
    public function index(): void
    {
        $staff = Auth::requireStaff();

        $staffId = (int) $staff['id'];

        $status = trim((string) ($_GET['status'] ?? ''));

        $allowed = ['assigned', 'accepted', 'in_progress', 'completed'];

        if ($status !== '' && !in_array($status, $allowed, true)) {
            JsonResponse::error('Trạng thái lọc không hợp lệ.', 422);
        }

        $items = $this->assignments->findByStaff(
            $staffId,
            $status !== '' ? $status : null
        );

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

        JsonResponse::success([
            'counts' => $this->assignments->countByStaff($staffId),
            'items' => $items,
        ]);
    }

    /**
     * Chi tiet nhiem vu + ket qua staff da nhap (neu co).
     */
    public function show(string $id): void
    {
        $staff = Auth::requireStaff();

        $assignmentId = (int) $id;

        $assignment = $this->assignments->findOwnedByStaff(
            $assignmentId,
            (int) $staff['id']
        );

        if (!$assignment) {
            JsonResponse::error('Không tìm thấy nhiệm vụ.', 404);
        }

        $requestId = (int) $assignment['valuation_request_id'];

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        JsonResponse::success([
            'assignment' => $assignment,
            'assignment_status_label' => $this->assignmentStatusLabel(
                (string) $assignment['status']
            ),
            'request' => $request,
            'request_status_label' => ValuationRequest::statusLabel(
                (string) $request['status']
            ),
            'result' => $this->results->findByAssignment($assignmentId),
            'rating_fields' => $this->ratingFieldOptions(),
        ]);
    }

    /**
     * Nhan nhiem vu: assignment assigned -> accepted.
     */
    public function accept(string $id): void
    {
        $staff = Auth::requireStaff();

        $staffId = (int) $staff['id'];

        $assignmentId = (int) $id;

        $assignment = $this->assignments->findOwnedByStaff(
            $assignmentId,
            $staffId
        );

        if (!$assignment) {
            JsonResponse::error('Không tìm thấy nhiệm vụ.', 404);
        }

        $requestId = (int) $assignment['valuation_request_id'];

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        try {
            $this->workflow->acceptAssignment(
                $requestId,
                $staffId,
                (int) $assignment['assigned_by'],
                (string) $staff['name'],
                $this->vehicleLabel($request),
                (string) $request['reference_code'],
                fn (): bool => $this->assignments->transition(
                    $assignmentId,
                    'accepted',
                    ['assigned']
                )
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('staff accept loi: ' . $e->getMessage());
            JsonResponse::error('Không thể nhận nhiệm vụ.', 500);
        }

        JsonResponse::success(
            ['assignment_id' => $assignmentId, 'status' => 'accepted'],
            'Đã nhận nhiệm vụ inspection.'
        );
    }

    /**
     * Bat dau inspection: -> in_progress.
     */
    public function start(string $id): void
    {
        $staff = Auth::requireStaff();

        $staffId = (int) $staff['id'];

        $assignmentId = (int) $id;

        $assignment = $this->assignments->findOwnedByStaff(
            $assignmentId,
            $staffId
        );

        if (!$assignment) {
            JsonResponse::error('Không tìm thấy nhiệm vụ.', 404);
        }

        if ((string) $assignment['status'] !== 'accepted') {
            JsonResponse::error(
                'Bạn cần nhận nhiệm vụ trước khi bắt đầu.',
                422
            );
        }

        $requestId = (int) $assignment['valuation_request_id'];

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        try {
            if (!$this->assignments->transition(
                $assignmentId,
                'in_progress',
                ['accepted']
            )) {
                throw new RuntimeException(
                    'Không thể bắt đầu nhiệm vụ này.'
                );
            }

            $this->workflow->startInspection(
                $requestId,
                $staffId,
                (int) $assignment['assigned_by'],
                (string) $staff['name'],
                (string) $request['reference_code'],
                $this->vehicleLabel($request)
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('staff start loi: ' . $e->getMessage());
            JsonResponse::error('Không thể bắt đầu inspection.', 500);
        }

        JsonResponse::success(
            ['assignment_id' => $assignmentId, 'status' => 'in_progress'],
            'Đã bắt đầu inspection.'
        );
    }

    /**
     * Gui ket qua tham dinh cho admin.
     *
     * Body:
     *   odometer_actual
     *   *_rating (exterior|interior|engine|transmission|chassis|legal)
     *   summary, staff_note
     *   suggested_price_min, suggested_price_max
     */
    public function submit(string $id): void
    {
        $staff = Auth::requireStaff();

        $staffId = (int) $staff['id'];

        $assignmentId = (int) $id;

        $assignment = $this->assignments->findOwnedByStaff(
            $assignmentId,
            $staffId
        );

        if (!$assignment) {
            JsonResponse::error('Không tìm thấy nhiệm vụ.', 404);
        }

        if (!in_array(
            (string) $assignment['status'],
            ['accepted', 'in_progress'],
            true
        )) {
            JsonResponse::error(
                'Nhiệm vụ đã hoàn tất hoặc chưa được nhận.',
                422
            );
        }

        $requestId = (int) $assignment['valuation_request_id'];

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        $data = $this->requestData();

        $payload = $this->validateResultPayload($data);

        if (isset($payload['error'])) {
            JsonResponse::error($payload['error'], 422);
        }

        try {
            $this->results->upsert(
                $requestId,
                $assignmentId,
                $staffId,
                $payload
            );

            if (!$this->assignments->transition(
                $assignmentId,
                'completed',
                ['accepted', 'in_progress']
            )) {
                throw new RuntimeException(
                    'Không thể hoàn tất nhiệm vụ này.'
                );
            }

            $this->workflow->submitInspectionResult(
                $requestId,
                $staffId,
                (string) $staff['name'],
                (string) $request['reference_code'],
                $this->vehicleLabel($request),
                $payload['suggested_price_min'] ?? null,
                $payload['suggested_price_max'] ?? null
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('staff submit loi: ' . $e->getMessage());
            JsonResponse::error('Không thể gửi kết quả thẩm định.', 500);
        }

        JsonResponse::success(
            ['assignment_id' => $assignmentId, 'status' => 'completed'],
            'Đã gửi kết quả cho Admin.'
        );
    }

    /**
     * Validate toan bo form ket qua.
     *
     * @return array<string,mixed>
     */
    private function validateResultPayload(array $data): array
    {
        $odometer = null;

        if (isset($data['odometer_actual']) && $data['odometer_actual'] !== '') {
            $odometer = (int) $data['odometer_actual'];

            if ($odometer < 0 || $odometer > 5000000) {
                return ['error' => 'ODO thực tế không hợp lệ.'];
            }
        }

        $payload = ['odometer_actual' => $odometer];

        foreach (ValuationInspectionResult::RATING_FIELDS as $field => $meta) {
            $value = $data[$field] ?? null;

            if ($value === null || $value === '') {
                $payload[$field] = null;

                continue;
            }

            $allowed = ValuationInspectionResult::allowedRatings(
                (string) $meta['type']
            );

            if (!in_array((string) $value, $allowed, true)) {
                return [
                    'error' => 'Giá trị đánh giá "'
                        . $meta['label']
                        . '" không hợp lệ.',
                ];
            }

            $payload[$field] = (string) $value;
        }

        $summary = trim((string) ($data['summary'] ?? ''));

        if ($summary === '') {
            return ['error' => 'Vui lòng nhập nhận xét tổng quan.'];
        }

        $payload['summary'] = $summary;

        $payload['staff_note'] = $this->nullableText(
            $data['staff_note'] ?? null
        );

        $priceMin = $this->parsePrice($data['suggested_price_min'] ?? null);
        $priceMax = $this->parsePrice($data['suggested_price_max'] ?? null);

        if ($priceMin === null || $priceMax === null) {
            return [
                'error' => 'Vui lòng nhập range giá đề xuất (thấp nhất và cao nhất).',
            ];
        }

        if ($priceMin <= 0 || $priceMax <= 0) {
            return ['error' => 'Range giá đề xuất phải lớn hơn 0.'];
        }

        if ($priceMax < $priceMin) {
            return [
                'error' => 'Giá cao nhất phải lớn hơn hoặc bằng giá thấp nhất.',
            ];
        }

        $payload['suggested_price_min'] = $priceMin;
        $payload['suggested_price_max'] = $priceMax;

        return $payload;
    }

    /**
     * Danh sach hang muc danh gia de render form.
     *
     * @return array<string,array<string,mixed>>
     */
    private function ratingFieldOptions(): array
    {
        $options = [];

        foreach (ValuationInspectionResult::RATING_FIELDS as $field => $meta) {
            $options[$field] = [
                'label' => $meta['label'],
                'group' => $meta['group'],
                'type' => $meta['type'],
                'options' => ValuationInspectionResult::allowedRatings(
                    (string) $meta['type']
                ),
            ];
        }

        return $options;
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

        // Go bo dau phan cach: 425.000.000 -> 425000000
        $normalized = preg_replace('/[^\d]/', '', (string) $value);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        return (float) $normalized;
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