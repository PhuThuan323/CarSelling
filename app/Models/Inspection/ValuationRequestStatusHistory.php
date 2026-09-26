<?php

declare(strict_types=1);

namespace App\Models\Inspection;

use App\Core\Database;
use PDO;

class ValuationRequestStatusHistory
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::connection();
    }


    public function create(
        int $requestId,
        ?string $oldStatus,
        string $newStatus,
        ?string $note,
        ?int $changedBy,
        string $changedByType
    ): void {

        $stmt =
            $this->db->prepare("
                INSERT INTO
                    valuation_request_status_histories
                (
                    valuation_request_id,
                    old_status,
                    new_status,
                    note,
                    changed_by,
                    changed_by_type
                )

                VALUES
                (
                    :request_id,
                    :old_status,
                    :new_status,
                    :note,
                    :changed_by,
                    :changed_by_type
                )
            ");


        $stmt->execute([
            'request_id'
                => $requestId,

            'old_status'
                => $oldStatus,

            'new_status'
                => $newStatus,

            'note'
                => $note,

            'changed_by'
                => $changedBy,

            'changed_by_type'
                => $changedByType,
        ]);
    }


    public function findByRequestId(
        int $requestId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT *

                FROM valuation_request_status_histories

                WHERE valuation_request_id =
                    :request_id

                ORDER BY created_at ASC
            ");


        $stmt->execute([
            'request_id'
                => $requestId
        ]);


        return
            $stmt->fetchAll();
    }
}