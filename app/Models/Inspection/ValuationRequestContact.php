<?php

declare(strict_types=1);

namespace App\Models\Inspection;

use App\Core\Database;
use PDO;

class ValuationRequestContact
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function upsert(
        int $requestId,
        string $fullName,
        string $phone,
        ?string $email
    ): void {
        $sql = "
            INSERT INTO valuation_request_contacts (
                valuation_request_id,
                full_name,
                phone,
                email
            )
            VALUES (
                :request_id,
                :full_name,
                :phone,
                :email
            )
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                phone = VALUES(phone),
                email = VALUES(email),
                updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'request_id' => $requestId,
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    public function findByRequestId(
        int $requestId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT
                id,
                valuation_request_id,
                full_name,
                phone,
                email,
                created_at,
                updated_at

            FROM valuation_request_contacts

            WHERE valuation_request_id = :request_id

            LIMIT 1
        ");

        $stmt->execute([
            'request_id' => $requestId,
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }
}