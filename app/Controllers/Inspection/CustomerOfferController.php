<?php

declare(strict_types=1);

namespace App\Controllers\Inspection;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Models\Inspection\ValuationRequest;
use App\Service\ValuationWorkflow;
use RuntimeException;
use Throwable;

/**
 * API khach hang phan hoi ket qua dinh gia.
 *
 *   POST /api/v1/valuations/accept-offer
 *   POST /api/v1/valuations/decline-offer
 *
 * Body: { "valuation_request_id": 4 }
 *
 * Bat buoc: request.user_id = user dang dang nhap
 *           va status = 'estimated'.
 */
class CustomerOfferController
{
    private ValuationRequest $requests;

    private ValuationWorkflow $workflow;

    public function __construct()
    {
        $this->requests = new ValuationRequest();
        $this->workflow = new ValuationWorkflow();
    }

    public function accept(): void
    {
        $this->handle('accept');
    }

    public function decline(): void
    {
        $this->handle('decline');
    }

    private function handle(string $action): void
    {
        $user = Auth::requireLogin();

        $userId = (int) $user['id'];

        $data = $this->requestData();

        $requestId = (int) ($data['valuation_request_id'] ?? 0);

        if ($requestId <= 0) {
            JsonResponse::error('ID hồ sơ không hợp lệ.', 422);
        }

        // Chi chu ho so moi duoc phan hoi.
        $request = $this->requests->findOwned($requestId, $userId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ.', 404);
        }

        if ((string) $request['status'] !== 'estimated') {
            JsonResponse::error(
                'Hồ sơ chưa có kết quả định giá để phản hồi.',
                422
            );
        }

        $vehicleLabel = $this->vehicleLabel($request);

        $referenceCode = (string) $request['reference_code'];

        try {
            if ($action === 'accept') {
                $this->workflow->acceptOffer(
                    $requestId,
                    $userId,
                    $vehicleLabel,
                    $referenceCode
                );
            } else {
                $this->workflow->declineOffer(
                    $requestId,
                    $userId,
                    $vehicleLabel,
                    $referenceCode
                );
            }
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('customer offer loi: ' . $e->getMessage());
            JsonResponse::error('Không thể cập nhật hồ sơ.', 500);
        }

        JsonResponse::success(
            [
                'valuation_request_id' => $requestId,
                'status' => $action === 'accept'
                    ? 'accepted'
                    : 'cancelled',
            ],
            $action === 'accept'
                ? 'Cảm ơn bạn! FastCar sẽ liên hệ để hoàn tất thủ tục.'
                : 'Đã hủy hồ sơ bán xe.'
        );
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