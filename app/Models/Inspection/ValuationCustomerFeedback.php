<?php

declare(strict_types=1);

namespace App\Models\Inspection;

use App\Core\Database;
use PDO;

/**
 * Feedback admin gui cho khach (gia chot + nhan xet).
 *
 * Luu thanh bang rieng de admin co the gui lai
 * feedback lan 2 ma khong mat lich su.
 */
class ValuationCustomerFeedback
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(
        int $requestId,
        int $createdBy,
        ?float $priceMin,
        ?float $priceMax,
        string $message
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO valuation_customer_feedbacks (
                valuation_request_id,
                created_by,
                price_min,
                price_max,
                message,
                sent_at
            )
            VALUES (
                :request_id,
                :created_by,
                :price_min,
                :price_max,
                :message,
                NOW()
            )
        ");

        $stmt->execute([
            'request_id' => $requestId,
            'created_by' => $createdBy,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'message' => $message,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Feedback moi nhat gui cho khach.
     */
    public function findLatestByRequest(int $requestId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_customer_feedbacks
            WHERE valuation_request_id = :request_id
            ORDER BY sent_at DESC, id DESC
            LIMIT 1
        ");

        $stmt->execute(['request_id' => $requestId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByRequest(int $requestId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                f.*,
                u.name AS created_by_name
            FROM valuation_customer_feedbacks f
            LEFT JOIN users u ON u.id = f.created_by
            WHERE f.valuation_request_id = :request_id
            ORDER BY f.sent_at DESC, f.id DESC
        ");

        $stmt->execute(['request_id' => $requestId]);

        return $stmt->fetchAll();
    }
}