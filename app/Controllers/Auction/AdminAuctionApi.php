<?php

declare(strict_types=1);

namespace App\Controllers\Auction;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Models\Auction\Auction;
use App\Models\Auction\AuctionPayment;
use App\Models\Inspection\ValuationRequest;
use App\Service\AuctionWorkflow;
use RuntimeException;
use Throwable;

/**
 * API quan ly dau gia (admin).
 *
 *   GET  /api/v1/admin/auctions                    - danh sach phien
 *   POST /api/v1/admin/auctions                    - tao phien tu ho so 'accepted'
 *   GET  /api/v1/admin/auctions/{id}               - chi tiet
 *   POST /api/v1/admin/auctions/{id}/payment       - doi trang thai thanh toan
 *   POST /api/v1/admin/auctions/{id}/cancel        - huy phien
 */
class AdminAuctionApi
{
    private Auction $auctions;

    private AuctionPayment $payments;

    private ValuationRequest $requests;

    private AuctionWorkflow $workflow;

    public function __construct()
    {
        $this->auctions = new Auction();
        $this->payments = new AuctionPayment();
        $this->requests = new ValuationRequest();
        $this->workflow = new AuctionWorkflow();
    }

    /**
     * Danh sach phien cho admin.
     */
    public function index(): void
    {
        Auth::requireAdmin();

        // Dong phien het gio de trang thai luon dung.
        $this->workflow->closeExpiredAuctions();

        $status = trim((string) ($_GET['status'] ?? ''));

        if (
            $status !== ''
            && !in_array($status, array_keys(Auction::STATUS_LABELS), true)
        ) {
            $status = '';
        }

        JsonResponse::success([
            'auctions' => $this->auctions->findAllForAdmin(
                $status !== '' ? $status : null,
                300
            ),
            'counts' => $this->counts(),
        ], 'Admin auctions retrieved successfully');
    }

    public function show(string $id): void
    {
        Auth::requireAdmin();

        $auctionId = (int) $id;

        $this->workflow->closeExpiredAuctions();

        $auction = $this->auctions->findById($auctionId);

        if (!$auction) {
            JsonResponse::error('Không tìm thấy phiên đấu giá.', 404);
        }

        JsonResponse::success([
            'auction' => $auction,
            'payment' => $this->payments->findByAuction($auctionId),
        ], 'Auction retrieved successfully');
    }

    /**
     * Tao phien dau gia.
     *
     * Body:
     *   valuation_request_id (required)
     *   start_price          (required)
     *   duration_days        (required, 4..10)
     *   start_at             (optional, Y-m-d H:i)
     */
    public function store(): void
    {
        $admin = Auth::requireAdmin();

        $data = $this->requestData();

        $requestId = (int) ($data['valuation_request_id'] ?? 0);

        if ($requestId <= 0) {
            JsonResponse::error('Vui lòng chọn hồ sơ bán xe.', 422);
        }

        $request = $this->requests->findDetailed($requestId);

        if (!$request) {
            JsonResponse::error('Không tìm thấy hồ sơ bán xe.', 404);
        }

        $startPrice = $this->parsePrice($data['start_price'] ?? null);

        if ($startPrice === null) {
            JsonResponse::error('Giá gốc không hợp lệ.', 422);
        }

        $durationDays = (int) ($data['duration_days'] ?? 0);

        if ($durationDays <= 0) {
            JsonResponse::error('Vui lòng chọn thời gian đấu giá.', 422);
        }

        $startAt = null;

        if (!empty($data['start_at'])) {
            $startAt = $this->normalizeDateTime((string) $data['start_at']);

            if ($startAt === null) {
                JsonResponse::error('Thời gian bắt đầu không hợp lệ.', 422);
            }
        }

        try {
            $auctionId = $this->workflow->createAuction(
                $request,
                (int) $admin['id'],
                $startPrice,
                $durationDays,
                $startAt
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('createAuction loi: ' . $e->getMessage());
            JsonResponse::error('Không thể tạo phiên đấu giá.', 500);
        }

        JsonResponse::success(
            ['auction_id' => $auctionId],
            'Đã tạo phiên đấu giá.'
        );
    }

    /**
     * Admin doi trang thai thanh toan cua winner.
     *
     * Body:
     *   status (required): paid | cancelled
     *   note   (optional)
     */
    public function updatePayment(string $id): void
    {
        $admin = Auth::requireAdmin();

        $auctionId = (int) $id;

        $data = $this->requestData();

        $status = trim((string) ($data['status'] ?? ''));

        $note = $this->nullableText($data['note'] ?? null);

        if ($note !== null && mb_strlen($note) > 500) {
            JsonResponse::error('Ghi chú không vượt quá 500 ký tự.', 422);
        }

        try {
            $this->workflow->confirmPayment(
                $auctionId,
                $status,
                $note,
                (int) $admin['id']
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('confirmPayment loi: ' . $e->getMessage());
            JsonResponse::error('Không thể cập nhật thanh toán.', 500);
        }

        JsonResponse::success(
            [
                'auction_id' => $auctionId,
                'payment_status' => $status,
            ],
            $status === 'paid'
                ? 'Đã xác nhận thanh toán.'
                : 'Đã hủy thanh toán.'
        );
    }

    /**
     * Admin huy phien (truoc khi co winner).
     */
    public function cancel(string $id): void
    {
        Auth::requireAdmin();

        $auctionId = (int) $id;

        $auction = $this->auctions->findById($auctionId);

        if (!$auction) {
            JsonResponse::error('Không tìm thấy phiên đấu giá.', 404);
        }

        if (
            !in_array(
                (string) $auction['status'],
                ['scheduled', 'active'],
                true
            )
        ) {
            JsonResponse::error(
                'Chỉ có thể hủy phiên chưa kết thúc.',
                422
            );
        }

        $this->auctions->updateStatus($auctionId, 'cancelled');

        JsonResponse::success(
            ['auction_id' => $auctionId],
            'Đã hủy phiên đấu giá.'
        );
    }

    /**
     * @return array<string,int>
     */
    private function counts(): array
    {
        $counts = [];

        foreach (array_keys(Auction::STATUS_LABELS) as $status) {
            $counts[$status] = count(
                $this->auctions->findAllForAdmin($status, 500)
            );
        }

        return $counts;
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

    private function parsePrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d]/', '', (string) $value);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        return (float) $normalized;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

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
}
