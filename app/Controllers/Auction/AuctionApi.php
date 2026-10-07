<?php

declare(strict_types=1);

namespace App\Controllers\Auction;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Models\Auction\Auction;
use App\Models\Auction\AuctionBid;
use App\Models\Auction\AuctionPayment;
use App\Service\AuctionWorkflow;
use RuntimeException;
use Throwable;

/**
 * API dau gia phia khach hang.
 *
 *   GET  /api/v1/auctions                - danh sach phien dang dien ra
 *   GET  /api/v1/auctions/{id}           - chi tiet + lich su dat gia
 *   POST /api/v1/auctions/{id}/bid       - dat gia (them tien)
 *   GET  /api/v1/my-auctions             - cac phien user da thang
 *   POST /api/v1/auctions/{id}/pay       - (tam thoi) danh dau da san sang thanh toan
 */
class AuctionApi
{
    private Auction $auctions;

    private AuctionBid $bids;

    private AuctionPayment $payments;

    private AuctionWorkflow $workflow;

    public function __construct()
    {
        $this->auctions = new Auction();
        $this->bids = new AuctionBid();
        $this->payments = new AuctionPayment();
        $this->workflow = new AuctionWorkflow();
    }

    /**
     * Danh sach phien dang dien ra.
     */
    public function index(): void
    {
        // Dong cac phien het gio truoc khi tra du lieu (lazy close).
        $this->workflow->closeExpiredAuctions();

        $this->auctions->activateDue();

        JsonResponse::success([
            'auctions' => $this->auctions->findActive(100),
        ], 'Auctions retrieved successfully');
    }

    /**
     * Chi tiet phien dau gia + lich su dat gia.
     */
    public function show(string $id): void
    {
        $auctionId = (int) $id;

        $this->workflow->closeExpiredAuctions();

        $this->auctions->activateDue();

        $auction = $this->auctions->findById($auctionId);

        if (!$auction) {
            JsonResponse::error('Không tìm thấy phiên đấu giá.', 404);
        }

        $currentUser = Auth::user();

        $myBestBid = null;

        if ($currentUser !== null) {
            $myBestBid = $this->bids->findBestBid(
                $auctionId,
                (int) $currentUser['id']
            );
        }

        JsonResponse::success([
            'auction' => $auction,
            'bids' => $this->bids->findByAuction($auctionId, 100),
            'my_best_bid' => $myBestBid,
            'current_user_id' => $currentUser !== null
                ? (int) $currentUser['id']
                : null,
        ], 'Auction retrieved successfully');
    }

    /**
     * User dat gia.
     *
     * Body: { "amount": 425000000 }
     */
    public function bid(string $id): void
    {
        $user = Auth::requireLogin();

        $auctionId = (int) $id;

        $data = $this->requestData();

        $amount = $this->parseAmount($data['amount'] ?? null);

        if ($amount === null || $amount <= 0) {
            JsonResponse::error('Vui lòng nhập giá đặt hợp lệ.', 422);
        }

        try {
            $result = $this->workflow->placeBid(
                $auctionId,
                (int) $user['id'],
                $amount
            );
        } catch (RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('placeBid loi: ' . $e->getMessage());
            JsonResponse::error('Không thể đặt giá. Vui lòng thử lại.', 500);
        }

        JsonResponse::success(
            $result,
            'Đặt giá thành công!'
        );
    }

    /**
     * Cac phien user da thang (muc "Xe da dau gia thanh cong").
     */
    public function myAuctions(): void
    {
        $user = Auth::requireLogin(false);

        $userId = (int) $user['id'];

        $this->workflow->closeExpiredAuctions();

        $won = $this->auctions->findByWinner(
            $userId,
            ['awaiting_payment', 'paid', 'cancelled']
        );

        $participated = $this->auctions->findByBidder($userId, 100);

        $pendingPayment = 0;

        foreach ($won as $row) {
            if ((string) $row['status'] === 'awaiting_payment') {
                $pendingPayment++;
            }
        }

        JsonResponse::success([
            'won' => $won,
            'participated' => $participated,
            'pending_payment' => $pendingPayment,
        ], 'My auctions retrieved successfully');
    }

    /**
     * (Tam thoi) Winner bao da san sang thanh toan.
     * Hien tai chi ghi nhan; admin xac nhan trang thai thanh toan.
     */
    public function markReadyToPay(string $id): void
    {
        $user = Auth::requireLogin();

        $auctionId = (int) $id;

        $auction = $this->auctions->findById($auctionId);

        if (!$auction) {
            JsonResponse::error('Không tìm thấy phiên đấu giá.', 404);
        }

        if ((int) $auction['winner_user_id'] !== (int) $user['id']) {
            JsonResponse::error(
                'Bạn không phải người thắng phiên này.',
                403
            );
        }

        if ((string) $auction['status'] !== 'awaiting_payment') {
            JsonResponse::error(
                'Phiên không ở trạng thái chờ thanh toán.',
                422
            );
        }

        JsonResponse::success(
            ['auction_id' => $auctionId],
            'FastCar sẽ liên hệ hướng dẫn thanh toán cho bạn.'
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

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Chap nhan "425.000.000" / "425,000,000" / "425000000".
        $normalized = preg_replace('/[^\d]/', '', (string) $value);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        return (float) $normalized;
    }
}
