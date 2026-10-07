<?php

declare(strict_types=1);

namespace App\Models\Auction;

use App\Core\Database;
use PDO;

/**
 * Ban ghi thanh toan cua nguoi thang phien dau gia.
 *
 * Hien tai chi mo phong: admin doi trang thai pending -> paid / cancelled.
 * Luong thanh toan that se bo sung sau.
 */
class AuctionPayment
{
    public const STATUS_LABELS = [
        'pending' => 'Chờ thanh toán',
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

    public function findByAuction(int $auctionId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM auction_payments
            WHERE auction_id = :auction_id
            LIMIT 1
        ");

        $stmt->execute(['auction_id' => $auctionId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Tao ban ghi thanh toan cho winner (pending).
     * Neu da ton tai thi tra ve ban ghi cu.
     */
    public function createPending(
        int $auctionId,
        int $userId,
        float $amount
    ): int {
        $existing = $this->findByAuction($auctionId);

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO auction_payments (
                auction_id,
                user_id,
                amount,
                status
            )
            VALUES (
                :auction_id,
                :user_id,
                :amount,
                'pending'
            )
        ");

        $stmt->execute([
            'auction_id' => $auctionId,
            'user_id' => $userId,
            'amount' => $amount,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Admin doi trang thai thanh toan.
     */
    public function updateStatus(
        int $auctionId,
        string $status,
        ?string $note,
        int $adminId
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE auction_payments
            SET status = :status,
                note = :note,
                confirmed_by = :confirmed_by,
                confirmed_at = NOW()
            WHERE auction_id = :auction_id
        ");

        $stmt->execute([
            'status' => $status,
            'note' => $note,
            'confirmed_by' => $adminId,
            'auction_id' => $auctionId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
