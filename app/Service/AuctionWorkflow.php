<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Models\Auction\Auction;
use App\Models\Auction\AuctionBid;
use App\Models\Auction\AuctionPayment;
use App\Models\Notification\Notification;
use App\Models\UserManagement\User;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Toan bo nghiep vu dau gia xe.
 *
 * Nguyen tac:
 *   - Moi thay doi trang thai di qua day.
 *   - Dat gia co transaction + SELECT ... FOR UPDATE de chong race condition.
 *   - Buoc gia toi thieu = 0.05% gia goc (luu san trong auctions.min_increment).
 *   - Het gio: dong phien theo co che "lazy close" (goi tu controller,
 *     khong can cron). Nguoi thang duoc thong bao va cho thanh toan.
 */
class AuctionWorkflow
{
    public const CHANGED_BY_ADMIN = 'admin';

    public const CHANGED_BY_USER = 'user';

    public const CHANGED_BY_SYSTEM = 'system';

    private PDO $db;

    private Auction $auctions;

    private AuctionBid $bids;

    private AuctionPayment $payments;

    private Notification $notifications;

    private FirebaseNotifier $firebase;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->auctions = new Auction();
        $this->bids = new AuctionBid();
        $this->payments = new AuctionPayment();
        $this->notifications = new Notification();
        $this->firebase = new FirebaseNotifier();
    }

    /**
     * Buoc gia toi thieu tinh tu gia goc (0.05%).
     * Lam tron len 1 dong de tranh so le.
     */
    public static function calculateMinIncrement(float $startPrice): float
    {
        return ceil($startPrice * Auction::MIN_INCREMENT_RATE);
    }

    /**
     * ADMIN tao phien dau gia tu mot ho so ban xe da 'accepted'.
     *
     * @param int   $valuationRequestId
     * @param int   $adminId
     * @param float $startPrice gia goc admin dat
     * @param int   $durationDays 4..10
     * @param string|null $startAt thoi diem bat dau (mac dinh = now)
     */
    public function createAuction(
        array $valuationRequest,
        int $adminId,
        float $startPrice,
        int $durationDays,
        ?string $startAt = null
    ): int {
        if ((string) ($valuationRequest['status'] ?? '') !== 'accepted') {
            throw new RuntimeException(
                'Chỉ có thể mở đấu giá cho hồ sơ đã được khách đồng ý bán.'
            );
        }

        $requestId = (int) $valuationRequest['id'];

        if ($this->auctions->existsActiveForRequest($requestId)) {
            throw new RuntimeException(
                'Hồ sơ này đã có phiên đấu giá đang tồn tại.'
            );
        }

        if ($startPrice <= 0) {
            throw new RuntimeException('Giá gốc phải lớn hơn 0.');
        }

        if (
            $durationDays < Auction::MIN_DURATION_DAYS
            || $durationDays > Auction::MAX_DURATION_DAYS
        ) {
            throw new RuntimeException(
                'Thời gian đấu giá phải từ '
                . Auction::MIN_DURATION_DAYS
                . ' đến '
                . Auction::MAX_DURATION_DAYS
                . ' ngày.'
            );
        }

        $title = $this->vehicleLabel($valuationRequest);

        $minIncrement = self::calculateMinIncrement($startPrice);

        // Thoi gian tinh bang SQL (DATE_ADD(NOW(), ...)) de dong nhat
        // mui gio voi cac phep so sanh NOW() khac trong DB.
        $auctionId = $this->auctions->create(
            $requestId,
            $title,
            $startPrice,
            $minIncrement,
            $durationDays,
            $startAt,
            $adminId
        );

        return $auctionId;
    }

    /**
     * USER dat gia.
     *
     * Chong race condition:
     *   - Transaction + SELECT ... FOR UPDATE tren row auctions.
     *   - Kiem tra lai end_at/status ben trong lock.
     *   - UPDATE ... WHERE current_price = <gia da doc> (optimistic guard).
     *
     * @return array{auction_id:int,amount:float,current_price:float}
     */
    public function placeBid(int $auctionId, int $userId, float $amount): array
    {
        $this->db->beginTransaction();

        try {
            // Lock row phien dau gia.
            // So sanh thoi gian bang SQL (NOW()) de tranh lech timezone PHP/MySQL.
            $stmt = $this->db->prepare("
                SELECT *,
                       (end_at <= NOW()) AS is_expired
                FROM auctions
                WHERE id = :id
                FOR UPDATE
            ");

            $stmt->execute(['id' => $auctionId]);

            $auction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$auction) {
                throw new RuntimeException('Không tìm thấy phiên đấu giá.');
            }

            // Trong lock: neu da het gio thi bao loi.
            if ((int) ($auction['is_expired'] ?? 0) === 1) {
                throw new RuntimeException(
                    'Phiên đấu giá đã kết thúc.'
                );
            }

            if ((string) $auction['status'] !== 'active') {
                throw new RuntimeException(
                    'Phiên đấu giá chưa bắt đầu hoặc đã kết thúc.'
                );
            }

            if ((int) $auction['winner_user_id'] === $userId) {
                throw new RuntimeException(
                    'Bạn đang giữ giá cao nhất, không cần đặt thêm.'
                );
            }

            $currentPrice = (float) $auction['current_price'];

            $minIncrement = (float) $auction['min_increment'];

            $minRequired = $currentPrice + $minIncrement;

            // Lam tron len 1 dong de so sanh on dinh.
            $amount = ceil($amount);

            if ($amount < $minRequired) {
                throw new RuntimeException(
                    'Giá đặt tối thiểu là '
                    . number_format($minRequired, 0, ',', '.')
                    . ' đ (cao hơn giá hiện tại ít nhất '
                    . number_format($minIncrement, 0, ',', '.')
                    . ' đ).'
                );
            }

            // Optimistic guard: chi update khi current_price chua doi.
            $update = $this->db->prepare("
                UPDATE auctions
                SET current_price = :amount,
                    winner_user_id = :user_id,
                    bid_count = bid_count + 1,
                    participant_count = (
                        SELECT COUNT(DISTINCT user_id)
                        FROM auction_bids
                        WHERE auction_id = :sub_auction_id
                          AND user_id <> :sub_user_id
                    ) + 1
                WHERE id = :id
                  AND current_price = :expected_price
                  AND status = 'active'
            ");

            $update->execute([
                'amount' => $amount,
                'user_id' => $userId,
                'sub_auction_id' => $auctionId,
                'sub_user_id' => $userId,
                'id' => $auctionId,
                'expected_price' => $currentPrice,
            ]);

            if ($update->rowCount() === 0) {
                throw new RuntimeException(
                    'Giá vừa bị người khác thay đổi. Vui lòng tải lại và đặt lại.'
                );
            }

            // Bo co winning cu, ghi bid moi + danh dau winning.
            $this->bids->clearWinning($auctionId);

            $bidId = $this->bids->create(
                $auctionId,
                $userId,
                $amount,
                $currentPrice,
                true
            );

            $this->bids->markWinning($bidId);

            // Cap nhat lai participant_count chinh xac.
            $participants = $this->bids->countParticipants($auctionId);

            $this->db->prepare("
                UPDATE auctions
                SET participant_count = :count
                WHERE id = :id
            ")->execute([
                'count' => $participants,
                'id' => $auctionId,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }

        $this->notifyOutbid($auction, $userId, $amount);

        return [
            'auction_id' => $auctionId,
            'amount' => $amount,
            'current_price' => $amount,
        ];
    }

    /**
     * Dong cac phien da het gio (lazy close).
     * Goi tu controller truoc khi hien thi danh sach / chi tiet.
     *
     * @return int so phien vua dong
     */
    public function closeExpiredAuctions(int $limit = 50): int
    {
        $expired = $this->auctions->findExpired($limit);

        $closed = 0;

        foreach ($expired as $row) {
            try {
                if ($this->closeAuction((int) $row['id'])) {
                    $closed++;
                }
            } catch (Throwable $e) {
                error_log(
                    'closeAuction loi #' . $row['id'] . ': ' . $e->getMessage()
                );
            }
        }

        return $closed;
    }

    /**
     * Dong mot phien: xac dinh winner, tao payment pending, thong bao.
     */
    public function closeAuction(int $auctionId): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT *,
                       (end_at <= NOW()) AS is_expired
                FROM auctions
                WHERE id = :id
                FOR UPDATE
            ");

            $stmt->execute(['id' => $auctionId]);

            $auction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$auction) {
                $this->db->rollBack();

                return false;
            }

            if (
                !in_array(
                    (string) $auction['status'],
                    ['scheduled', 'active'],
                    true
                )
            ) {
                $this->db->rollBack();

                return false;
            }

            // So sanh thoi gian bang SQL de tranh lech timezone PHP/MySQL.
            if ((int) ($auction['is_expired'] ?? 0) !== 1) {
                $this->db->rollBack();

                return false;
            }

            $winnerId = $auction['winner_user_id'] !== null
                ? (int) $auction['winner_user_id']
                : null;

            $finalPrice = (float) $auction['current_price'];

            // Khong co ai dat gia -> huy phien.
            if ($winnerId === null) {
                $this->db->prepare("
                    UPDATE auctions
                    SET status = 'cancelled',
                        closed_at = NOW()
                    WHERE id = :id
                ")->execute(['id' => $auctionId]);

                $this->db->commit();

                return true;
            }

            $this->db->prepare("
                UPDATE auctions
                SET status = 'awaiting_payment',
                    final_price = :final_price,
                    closed_at = NOW()
                WHERE id = :id
            ")->execute([
                'final_price' => $finalPrice,
                'id' => $auctionId,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }

        // Tao ban ghi thanh toan cho winner.
        $this->payments->createPending($auctionId, $winnerId, $finalPrice);

        // Thong bao cho winner.
        $this->notifyWinner($auction, $winnerId, $finalPrice);

        return true;
    }

    /**
     * ADMIN doi trang thai thanh toan (paid / cancelled).
     * paid -> phien 'paid'; cancelled -> phien 'cancelled'.
     */
    public function confirmPayment(
        int $auctionId,
        string $status,
        ?string $note,
        int $adminId
    ): bool {
        if (!in_array($status, ['paid', 'cancelled'], true)) {
            throw new RuntimeException('Trạng thái thanh toán không hợp lệ.');
        }

        $auction = $this->auctions->findById($auctionId);

        if (!$auction) {
            throw new RuntimeException('Không tìm thấy phiên đấu giá.');
        }

        if ((string) $auction['status'] !== 'awaiting_payment') {
            throw new RuntimeException(
                'Chỉ có thể cập nhật thanh toán cho phiên đang chờ thanh toán.'
            );
        }

        $this->payments->updateStatus($auctionId, $status, $note, $adminId);

        $this->auctions->updateStatus(
            $auctionId,
            $status === 'paid' ? 'paid' : 'cancelled'
        );

        $this->notifyPayment($auction, $status, $note);

        return true;
    }

    /**
     * Thong bao cho nguoi tung giu gia cao nhat bi vuot.
     */
    private function notifyOutbid(
        array $auction,
        int $newBidderId,
        float $amount
    ): void {
        $previousWinner = $auction['winner_user_id'] !== null
            ? (int) $auction['winner_user_id']
            : null;

        if ($previousWinner === null || $previousWinner === $newBidderId) {
            return;
        }

        try {
            $title = 'Bạn vừa bị vượt giá';
            $message = $auction['title']
                . "\nGiá mới: "
                . number_format($amount, 0, ',', '.')
                . ' đ';

            $this->notifications->create(
                $previousWinner,
                'auction_outbid',
                $title,
                $message,
                'auction',
                (int) $auction['id']
            );

            $this->firebase->sendToUser(
                $previousWinner,
                $title,
                $message,
                ['type' => 'auction_outbid', 'auction_id' => (string) $auction['id']]
            );
        } catch (Throwable $e) {
            error_log('notifyOutbid loi: ' . $e->getMessage());
        }
    }

    /**
     * Thong bao winner khi het gio (kem nhac thanh toan).
     */
    private function notifyWinner(
        array $auction,
        int $winnerId,
        float $finalPrice
    ): void {
        try {
            $title = 'Chúc mừng! Bạn đã thắng phiên đấu giá';
            $message = $auction['title']
                . "\nGiá thắng: "
                . number_format($finalPrice, 0, ',', '.')
                . " đ\nVui lòng thanh toán để hoàn tất.";

            $this->notifications->create(
                $winnerId,
                'auction_won',
                $title,
                $message,
                'auction',
                (int) $auction['id']
            );

            $this->firebase->sendToUser(
                $winnerId,
                $title,
                $message,
                ['type' => 'auction_won', 'auction_id' => (string) $auction['id']]
            );
        } catch (Throwable $e) {
            error_log('notifyWinner loi: ' . $e->getMessage());
        }
    }

    private function notifyPayment(
        array $auction,
        string $status,
        ?string $note
    ): void {
        $winnerId = (int) ($auction['winner_user_id'] ?? 0);

        if ($winnerId <= 0) {
            return;
        }

        try {
            $paid = $status === 'paid';

            $title = $paid
                ? 'Thanh toán đã được xác nhận'
                : 'Thanh toán đã bị hủy';

            $message = $auction['title']
                . ($note !== null ? "\nGhi chú: " . $note : '');

            $this->notifications->create(
                $winnerId,
                $paid ? 'auction_payment_paid' : 'auction_payment_cancelled',
                $title,
                $message,
                'auction',
                (int) $auction['id']
            );

            $this->firebase->sendToUser(
                $winnerId,
                $title,
                $message,
                ['type' => 'auction_payment', 'auction_id' => (string) $auction['id']]
            );
        } catch (Throwable $e) {
            error_log('notifyPayment loi: ' . $e->getMessage());
        }
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
