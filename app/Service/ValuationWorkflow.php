<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Inspection\ValuationRequest;
use App\Models\Inspection\ValuationRequestStatusHistory;
use App\Models\Notification\Notification;
use RuntimeException;

/**
 * Tap trung toan bo luat chuyen trang thai cua ho so ban xe.
 *
 * Moi thay doi trang thai deu di qua day de:
 *   - kiem tra trang thai hien tai hop le
 *   - ghi status history (audit)
 *   - tao notification cho dung nguoi
 *
 * Flow:
 *   USER dang ban xe
 *     -> ready_for_estimate
 *     -> ADMIN tiep nhan: inspection_requested
 *     -> ADMIN phan cong STAFF: inspection_assigned
 *     -> STAFF bat dau: inspection_in_progress
 *     -> STAFF gui ket qua: inspection_completed
 *     -> ADMIN chot gia + feedback: estimated
 *     -> USER dong y: accepted
 *     -> USER khong dong y: cancelled (customer_declined_offer)
 */
class ValuationWorkflow
{
    public const CANCELLATION_CUSTOMER_DECLINED = 'customer_declined_offer';

    public const CHANGED_BY_USER = 'user';

    public const CHANGED_BY_STAFF = 'staff';

    public const CHANGED_BY_ADMIN = 'admin';

    private ValuationRequest $requests;

    private ValuationRequestStatusHistory $history;

    private Notification $notifications;

    public function __construct()
    {
        $this->requests = new ValuationRequest();
        $this->history = new ValuationRequestStatusHistory();
        $this->notifications = new Notification();
    }

    /**
     * Ghi timeline. Khong nem loi de khong lam hong
     * luong nghiep vu chinh.
     */
    public function log(
        int $requestId,
        ?string $oldStatus,
        string $newStatus,
        ?string $note = null,
        ?int $changedBy = null,
        string $changedByType = self::CHANGED_BY_ADMIN
    ): void {
        try {
            $this->history->create(
                $requestId,
                $oldStatus,
                $newStatus,
                $note,
                $changedBy,
                $changedByType
            );
        } catch (\Throwable $e) {
            error_log(
                'Khong ghi duoc status history: '
                . $e->getMessage()
            );
        }
    }

    /**
     * ADMIN tiep nhan ho so -> yeu cau inspection.
     */
    public function requestInspection(
        int $requestId,
        int $adminId
    ): bool {
        $request = $this->requireRequest($requestId);

        $updated = $this->requests->transition(
            $requestId,
            'inspection_requested',
            ['ready_for_estimate']
        );

        if (!$updated) {
            throw new RuntimeException(
                'Hồ sơ không ở trạng thái chờ tiếp nhận.'
            );
        }

        $this->log(
            $requestId,
            $request['status'],
            'inspection_requested',
            'Admin đã tiếp nhận hồ sơ và yêu cầu inspection.',
            $adminId,
            self::CHANGED_BY_ADMIN
        );

        return true;
    }

    /**
     * ADMIN phan cong staff cho ho so.
     *
     * Neu da co staff cu thi huy assignment cu (giu lich su).
     */
    public function assignStaff(
        int $requestId,
        int $staffUserId,
        int $adminId,
        ?string $scheduledAt,
        ?string $adminNote,
        string $staffName,
        string $vehicleLabel
    ): bool {
        $request = $this->requireRequest($requestId);

        if (!in_array(
            (string) $request['status'],
            [
                'ready_for_estimate',
                'inspection_requested',
                'inspection_assigned',
                'inspection_in_progress',
            ],
            true
        )) {
            throw new RuntimeException(
                'Hồ sơ không thể phân công ở trạng thái hiện tại.'
            );
        }

        $updated = $this->requests->transition(
            $requestId,
            'inspection_assigned',
            [
                'ready_for_estimate',
                'inspection_requested',
                'inspection_assigned',
                'inspection_in_progress',
            ]
        );

        if (!$updated) {
            throw new RuntimeException(
                'Không thể cập nhật trạng thái hồ sơ.'
            );
        }

        $this->log(
            $requestId,
            $request['status'],
            'inspection_assigned',
            'Phân công inspection cho '
                . $staffName
                . ($scheduledAt !== null
                    ? ' · lịch ' . $scheduledAt
                    : ''),
            $adminId,
            self::CHANGED_BY_ADMIN
        );

        $scheduleText = $scheduledAt !== null
            ? 'Lịch: ' . $scheduledAt
            : 'Chưa hẹn lịch cụ thể';

        $this->notifications->create(
            $staffUserId,
            Notification::TYPE_INSPECTION_ASSIGNED,
            '🔔 Bạn có xe mới cần Inspection',
            $vehicleLabel
                . "\nMã hồ sơ: "
                . $request['reference_code']
                . "\n"
                . $scheduleText,
            'valuation_request',
            $requestId
        );

        return true;
    }

    /**
     * STAFF nhan nhiem vu: assigned -> accepted.
     */
    public function acceptAssignment(
        int $requestId,
        int $staffUserId,
        int $adminId,
        string $staffName,
        string $vehicleLabel,
        string $referenceCode,
        callable $acceptCallable
    ): bool {
        $accepted = $acceptCallable();

        if (!$accepted) {
            throw new RuntimeException(
                'Không thể nhận nhiệm vụ. Nhiệm vụ có thể đã được nhận trước đó.'
            );
        }

        $this->log(
            $requestId,
            null,
            'inspection_assigned',
            $staffName . ' đã nhận nhiệm vụ inspection.',
            $staffUserId,
            self::CHANGED_BY_STAFF
        );

        $this->notifications->create(
            $adminId,
            Notification::TYPE_INSPECTION_ASSIGNMENT_ACCEPTED,
            '📋 Nhân viên đã nhận nhiệm vụ',
            $vehicleLabel
                . "\nMã hồ sơ: "
                . $referenceCode
                . "\nNhân viên: "
                . $staffName,
            'valuation_request',
            $requestId
        );

        return true;
    }

    /**
     * STAFF bat dau inspection: -> inspection_in_progress.
     */
    public function startInspection(
        int $requestId,
        int $staffUserId,
        int $adminId,
        string $staffName,
        string $referenceCode,
        string $vehicleLabel
    ): bool {
        $request = $this->requireRequest($requestId);

        $updated = $this->requests->transition(
            $requestId,
            'inspection_in_progress',
            ['inspection_assigned']
        );

        if (!$updated) {
            throw new RuntimeException(
                'Hồ sơ chưa được phân công inspection.'
            );
        }

        $this->log(
            $requestId,
            $request['status'],
            'inspection_in_progress',
            $staffName . ' đã bắt đầu kiểm tra xe.',
            $staffUserId,
            self::CHANGED_BY_STAFF
        );

        $this->notifications->create(
            $adminId,
            Notification::TYPE_INSPECTION_STARTED,
            '🚗 Inspection đã bắt đầu',
            $vehicleLabel
                . "\nMã hồ sơ: "
                . $referenceCode
                . "\nNhân viên: "
                . $staffName,
            'valuation_request',
            $requestId
        );

        return true;
    }

    /**
     * STAFF gui ket qua: -> inspection_completed.
     */
    public function submitInspectionResult(
        int $requestId,
        int $staffUserId,
        string $staffName,
        string $referenceCode,
        string $vehicleLabel,
        ?float $priceMin,
        ?float $priceMax
    ): bool {
        $request = $this->requireRequest($requestId);

        $updated = $this->requests->transition(
            $requestId,
            'inspection_completed',
            ['inspection_in_progress', 'inspection_assigned']
        );

        if (!$updated) {
            throw new RuntimeException(
                'Hồ sơ không ở trạng thái có thể gửi kết quả.'
            );
        }

        $this->log(
            $requestId,
            $request['status'],
            'inspection_completed',
            $staffName . ' đã gửi kết quả thẩm định.',
            $staffUserId,
            self::CHANGED_BY_STAFF
        );

        $rangeText = 'Chưa đề xuất range giá';

        if ($priceMin !== null && $priceMax !== null) {
            $rangeText = 'Range đề xuất: '
                . number_format($priceMin, 0, ',', '.')
                . ' - '
                . number_format($priceMax, 0, ',', '.')
                . ' đ';
        }

        $this->notifications->notifyAllAdmins(
            Notification::TYPE_INSPECTION_RESULT_READY,
            '🔔 Có kết quả Inspection mới',
            $vehicleLabel
                . "\nMã hồ sơ: "
                . $referenceCode
                . "\nNhân viên: "
                . $staffName
                . "\n"
                . $rangeText,
            'valuation_request',
            $requestId
        );

        return true;
    }

    /**
     * ADMIN chot gia cuoi cung + gui feedback cho khach.
     */
    public function publishEstimate(
        int $requestId,
        int $adminId,
        float $priceMin,
        float $priceMax,
        string $feedback
    ): bool {
        $request = $this->requireRequest($requestId);

        $updated = $this->requests->publishEstimate(
            $requestId,
            $priceMin,
            $priceMax,
            ['inspection_completed', 'estimated']
        );

        if (!$updated) {
            throw new RuntimeException(
                'Hồ sơ chưa có kết quả inspection để chốt giá.'
            );
        }

        $this->log(
            $requestId,
            $request['status'],
            'estimated',
            'Admin đã chốt giá '
                . number_format($priceMin, 0, ',', '.')
                . ' - '
                . number_format($priceMax, 0, ',', '.')
                . ' đ và gửi kết quả cho khách.',
            $adminId,
            self::CHANGED_BY_ADMIN
        );

        return true;
    }

    /**
     * USER dong y ban xe: estimated -> accepted.
     */
    public function acceptOffer(
        int $requestId,
        int $userId,
        string $vehicleLabel,
        string $referenceCode
    ): bool {
        $request = $this->requireRequest($requestId);

        $updated = $this->requests->transition(
            $requestId,
            'accepted',
            ['estimated']
        );

        if (!$updated) {
            throw new RuntimeException(
                'Hồ sơ chưa có kết quả định giá để xác nhận.'
            );
        }

        $this->log(
            $requestId,
            $request['status'],
            'accepted',
            'Khách hàng đồng ý bán xe.',
            $userId,
            self::CHANGED_BY_USER
        );

        $this->notifications->notifyAllAdmins(
            Notification::TYPE_OFFER_ACCEPTED,
            '🎉 Khách đã đồng ý bán xe',
            $vehicleLabel
                . "\nMã hồ sơ: "
                . $referenceCode,
            'valuation_request',
            $requestId
        );

        return true;
    }

    /**
     * USER khong dong y: estimated -> cancelled.
     *
     * Dung 'cancelled' + cancellation_reason thay vi 'rejected'
     * (rejected danh cho admin tu choi ho so).
     */
    public function declineOffer(
        int $requestId,
        int $userId,
        string $vehicleLabel,
        string $referenceCode
    ): bool {
        $request = $this->requireRequest($requestId);

        $updated = $this->requests->transition(
            $requestId,
            'cancelled',
            ['estimated'],
            self::CANCELLATION_CUSTOMER_DECLINED
        );

        if (!$updated) {
            throw new RuntimeException(
                'Hồ sơ không thể hủy ở trạng thái hiện tại.'
            );
        }

        $this->log(
            $requestId,
            $request['status'],
            'cancelled',
            'Khách hàng không đồng ý mức định giá và đã hủy bán xe.',
            $userId,
            self::CHANGED_BY_USER
        );

        $this->notifications->notifyAllAdmins(
            Notification::TYPE_OFFER_DECLINED,
            '❌ Khách không đồng ý mức định giá',
            $vehicleLabel
                . "\nMã hồ sơ: "
                . $referenceCode,
            'valuation_request',
            $requestId
        );

        return true;
    }

    private function requireRequest(int $requestId): array
    {
        $request = $this->requests->findById($requestId);

        if (!$request) {
            throw new RuntimeException('Không tìm thấy hồ sơ.');
        }

        return $request;
    }
}