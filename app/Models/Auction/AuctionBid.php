<?php

declare(strict_types=1);

namespace App\Models\Auction;

use App\Core\Database;
use PDO;

/**
 * Lich su dat gia cua mot phien dau gia.
 */
class AuctionBid
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Danh sach luot dat gia cua phien, moi nhat truoc.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByAuction(int $auctionId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 300));

        $stmt = $this->db->prepare("
            SELECT b.id,
                   b.auction_id,
                   b.user_id,
                   b.amount,
                   b.previous_price,
                   b.is_winning,
                   b.created_at,
                   u.name AS user_name
            FROM auction_bids b
            INNER JOIN users u
                ON u.id = b.user_id
            WHERE b.auction_id = :auction_id
            ORDER BY b.created_at DESC, b.id DESC
            LIMIT {$limit}
        ");

        $stmt->execute(['auction_id' => $auctionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Bid dang giu gia cao nhat cua phien.
     */
    public function findWinning(int $auctionId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT b.*,
                   u.name AS user_name
            FROM auction_bids b
            INNER JOIN users u
                ON u.id = b.user_id
            WHERE b.auction_id = :auction_id
              AND b.is_winning = 1
            ORDER BY b.id DESC
            LIMIT 1
        ");

        $stmt->execute(['auction_id' => $auctionId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * So nguoi tham gia (distinct user co bid).
     */
    public function countParticipants(int $auctionId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT user_id) AS total
            FROM auction_bids
            WHERE auction_id = :auction_id
        ");

        $stmt->execute(['auction_id' => $auctionId]);

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    /**
     * Bid cao nhat cua mot user trong phien.
     */
    public function findBestBid(int $auctionId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM auction_bids
            WHERE auction_id = :auction_id
              AND user_id = :user_id
            ORDER BY amount DESC, id DESC
            LIMIT 1
        ");

        $stmt->execute([
            'auction_id' => $auctionId,
            'user_id' => $userId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Ghi mot luot dat gia.
     */
    public function create(
        int $auctionId,
        int $userId,
        float $amount,
        float $previousPrice,
        bool $isWinning
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO auction_bids (
                auction_id,
                user_id,
                amount,
                previous_price,
                is_winning
            )
            VALUES (
                :auction_id,
                :user_id,
                :amount,
                :previous_price,
                :is_winning
            )
        ");

        $stmt->execute([
            'auction_id' => $auctionId,
            'user_id' => $userId,
            'amount' => $amount,
            'previous_price' => $previousPrice,
            'is_winning' => $isWinning ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Bo co is_winning cua tat ca bid trong phien
     * (truoc khi dat co cho bid moi).
     */
    public function clearWinning(int $auctionId): void
    {
        $stmt = $this->db->prepare("
            UPDATE auction_bids
            SET is_winning = 0
            WHERE auction_id = :auction_id
              AND is_winning = 1
        ");

        $stmt->execute(['auction_id' => $auctionId]);
    }

    public function markWinning(int $bidId): void
    {
        $stmt = $this->db->prepare("
            UPDATE auction_bids
            SET is_winning = 1
            WHERE id = :id
        ");

        $stmt->execute(['id' => $bidId]);
    }
}
