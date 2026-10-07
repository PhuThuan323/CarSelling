<?php

declare(strict_types=1);

namespace App\Models\Auction;

use App\Core\Database;
use PDO;

/**
 * Phien dau gia xe.
 *
 * Trang thai:
 *   scheduled        -> da len lich, chua bat dau
 *   active           -> dang dien ra
 *   awaiting_payment -> het gio, co winner, cho thanh toan
 *   paid             -> admin xac nhan da thanh toan
 *   cancelled        -> huy phien / khong co ai dau gia
 */
class Auction
{
    /** Thoi luong phien: toi thieu 4 ngay, toi da 10 ngay. */
    public const MIN_DURATION_DAYS = 4;

    public const MAX_DURATION_DAYS = 10;

    /** Buoc gia toi thieu = 0.05% gia goc. */
    public const MIN_INCREMENT_RATE = 0.0005;

    public const STATUS_LABELS = [
        'scheduled' => 'Sắp diễn ra',
        'active' => 'Đang đấu giá',
        'awaiting_payment' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'cancelled' => 'Đã hủy',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public static function statusLabel(?string $status): string
    {
        if ($status === null) {
            return '';
        }

        return self::STATUS_LABELS[$status] ?? $status;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*,
                   u.name AS winner_name,
                   u.email AS winner_email,
                   r.reference_code,
                   r.vehicle_snapshot,
                   r.manufacture_year,
                   r.odometer_km,
                   r.exterior_color
            FROM auctions a
            LEFT JOIN users u
                ON u.id = a.winner_user_id
            LEFT JOIN valuation_requests r
                ON r.id = a.valuation_request_id
            WHERE a.id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->decodeRows([$row])[0];
    }

    /**
     * Phien dang trong thoi gian dau gia (da bat dau, chua het gio).
     * Dung cho trang "Mua Xe".
     *
     * @return array<int,array<string,mixed>>
     */
    public function findActive(int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));

        $stmt = $this->db->prepare("
            SELECT a.*,
                   u.name AS winner_name,
                   r.reference_code,
                   r.vehicle_snapshot,
                   r.manufacture_year,
                   r.odometer_km,
                   r.exterior_color
            FROM auctions a
            LEFT JOIN users u
                ON u.id = a.winner_user_id
            LEFT JOIN valuation_requests r
                ON r.id = a.valuation_request_id
            WHERE a.status = 'active'
              AND a.start_at <= NOW()
              AND a.end_at > NOW()
            ORDER BY a.end_at ASC, a.id DESC
            LIMIT {$limit}
        ");

        $stmt->execute();

        return $this->decodeRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Tat ca phien cho admin (moi trang thai).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAllForAdmin(?string $status = null, int $limit = 300): array
    {
        $limit = max(1, min($limit, 500));

        $where = '';
        $params = [];

        if ($status !== null && $status !== '') {
            $where = 'WHERE a.status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare("
            SELECT a.*,
                   u.name AS winner_name,
                   u.email AS winner_email,
                   r.reference_code,
                   r.vehicle_snapshot,
                   r.manufacture_year,
                   p.status AS payment_status
            FROM auctions a
            LEFT JOIN users u
                ON u.id = a.winner_user_id
            LEFT JOIN valuation_requests r
                ON r.id = a.valuation_request_id
            LEFT JOIN auction_payments p
                ON p.auction_id = a.id
            {$where}
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT {$limit}
        ");

        $stmt->execute($params);

        return $this->decodeRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Cac phien user da thang.
     *
     * @param string[] $statuses
     * @return array<int,array<string,mixed>>
     */
    public function findByWinner(int $userId, array $statuses = [], int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));

        $where = 'WHERE a.winner_user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($statuses !== []) {
            $placeholders = implode(
                ', ',
                array_map(
                    static fn (int $i): string => ":s{$i}",
                    range(1, count($statuses))
                )
            );

            $where .= " AND a.status IN ({$placeholders})";

            foreach (array_values($statuses) as $index => $value) {
                $params['s' . ($index + 1)] = $value;
            }
        }

        $stmt = $this->db->prepare("
            SELECT a.*,
                   r.reference_code,
                   r.vehicle_snapshot,
                   r.manufacture_year,
                   r.odometer_km,
                   r.exterior_color,
                   p.status AS payment_status,
                   p.note AS payment_note,
                   p.confirmed_at AS payment_confirmed_at
            FROM auctions a
            LEFT JOIN valuation_requests r
                ON r.id = a.valuation_request_id
            LEFT JOIN auction_payments p
                ON p.auction_id = a.id
            {$where}
            ORDER BY a.closed_at DESC, a.end_at DESC, a.id DESC
            LIMIT {$limit}
        ");

        $stmt->execute($params);

        return $this->decodeRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Cac phien user da tung tham gia dat gia (ke ca chua thang).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByBidder(int $userId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));

        $stmt = $this->db->prepare("
            SELECT a.*,
                   r.reference_code,
                   r.vehicle_snapshot,
                   r.manufacture_year,
                   my.amount AS my_best_bid,
                   p.status AS payment_status
            FROM auctions a
            INNER JOIN (
                SELECT auction_id, MAX(amount) AS amount
                FROM auction_bids
                WHERE user_id = :user_id
                GROUP BY auction_id
            ) my ON my.auction_id = a.id
            LEFT JOIN valuation_requests r
                ON r.id = a.valuation_request_id
            LEFT JOIN auction_payments p
                ON p.auction_id = a.id
            ORDER BY a.end_at DESC, a.id DESC
            LIMIT {$limit}
        ");

        $stmt->execute(['user_id' => $userId]);

        return $this->decodeRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Kiem tra ho so da co phien chua huy chua (1 ho so chi 1 phien).
     */
    public function existsActiveForRequest(int $valuationRequestId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM auctions
            WHERE valuation_request_id = :request_id
              AND status <> 'cancelled'
        ");

        $stmt->execute(['request_id' => $valuationRequestId]);

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;
    }

    /**
     * Danh sach phien da het gio nhung chua duoc dong.
     * Dung cho co che lazy-close.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findExpired(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));

        $stmt = $this->db->prepare("
            SELECT id
            FROM auctions
            WHERE status IN ('scheduled', 'active')
              AND end_at <= NOW()
            ORDER BY end_at ASC
            LIMIT {$limit}
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cap nhat cac phien den gio bat dau: scheduled -> active.
     */
    public function activateDue(): int
    {
        $stmt = $this->db->prepare("
            UPDATE auctions
            SET status = 'active'
            WHERE status = 'scheduled'
              AND start_at <= NOW()
              AND end_at > NOW()
        ");

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function create(
        int $valuationRequestId,
        string $title,
        float $startPrice,
        float $minIncrement,
        int $durationDays,
        ?string $startAt,
        int $createdBy
    ): int {
        // start_at / end_at tinh bang SQL (NOW() / DATE_ADD) de dong nhat
        // mui gio voi cac phep so sanh thoi gian khac trong DB.
        // durationDays da duoc validate 4..10 o tang service, cast int an toan.
        $days = (int) $durationDays;

        $startExpr = $startAt !== null
            ? ':start_at'
            : 'NOW()';

        $endExpr = $startAt !== null
            ? "DATE_ADD(:start_at, INTERVAL {$days} DAY)"
            : "DATE_ADD(NOW(), INTERVAL {$days} DAY)";

        $stmt = $this->db->prepare("
            INSERT INTO auctions (
                valuation_request_id,
                title,
                start_price,
                current_price,
                min_increment,
                start_at,
                end_at,
                status,
                created_by
            )
            VALUES (
                :valuation_request_id,
                :title,
                :start_price,
                :current_price,
                :min_increment,
                {$startExpr},
                {$endExpr},
                'scheduled',
                :created_by
            )
        ");

        $params = [
            'valuation_request_id' => $valuationRequestId,
            'title' => $title,
            'start_price' => $startPrice,
            'current_price' => $startPrice,
            'min_increment' => $minIncrement,
            'created_by' => $createdBy,
        ];

        if ($startAt !== null) {
            $params['start_at'] = $startAt;
        }

        $stmt->execute($params);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cap nhat trang thai don gian (admin doi).
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE auctions
            SET status = :status
            WHERE id = :id
        ");

        $stmt->execute(['id' => $id, 'status' => $status]);

        return $stmt->rowCount() > 0;
    }

    private function decodeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (!empty($row['vehicle_snapshot'])) {
                $decoded = json_decode((string) $row['vehicle_snapshot'], true);
                $row['vehicle_snapshot'] = is_array($decoded) ? $decoded : [];
            }
        }

        unset($row);

        return $rows;
    }
}
