<?php

declare(strict_types=1);

namespace App\Models\Inspection;

use App\Core\Database;
use PDO;

/**
 * Bang phan cong inspection.
 *
 * Mot ho so co the duoc phan cong lai cho staff khac,
 * vi vay bang nay luu lich su (khong unique theo request).
 * Assignment "dang hieu luc" la ban ghi co status khac 'cancelled'.
 */
class ValuationInspectionAssignment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(
        int $requestId,
        int $staffUserId,
        int $assignedBy,
        ?string $scheduledAt,
        ?string $adminNote
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO valuation_inspection_assignments (
                valuation_request_id,
                staff_user_id,
                assigned_by,
                status,
                scheduled_at,
                admin_note
            )
            VALUES (
                :request_id,
                :staff_user_id,
                :assigned_by,
                'assigned',
                :scheduled_at,
                :admin_note
            )
        ");

        $stmt->execute([
            'request_id' => $requestId,
            'staff_user_id' => $staffUserId,
            'assigned_by' => $assignedBy,
            'scheduled_at' => $scheduledAt,
            'admin_note' => $adminNote,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_inspection_assignments
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Assignment dang hieu luc cua ho so
     * (moi nhat, chua bi huy).
     */
    public function findActiveByRequest(int $requestId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_inspection_assignments
            WHERE valuation_request_id = :request_id
              AND status <> 'cancelled'
            ORDER BY assigned_at DESC, id DESC
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
                a.*,
                u.name AS staff_name,
                u.email AS staff_email,
                u.phone AS staff_phone
            FROM valuation_inspection_assignments a
            INNER JOIN users u ON u.id = a.staff_user_id
            WHERE a.valuation_request_id = :request_id
            ORDER BY a.assigned_at DESC, a.id DESC
        ");

        $stmt->execute(['request_id' => $requestId]);

        return $stmt->fetchAll();
    }

    /**
     * Nhiem vu dang hieu luc cua mot staff.
     * $status = null => tat ca tru 'cancelled'.
     */
    public function findByStaff(int $staffUserId, ?string $status = null): array
    {
        $sql = "
            SELECT
                a.id AS assignment_id,
                a.status AS assignment_status,
                a.scheduled_at,
                a.assigned_at,
                a.accepted_at,
                a.started_at,
                a.completed_at,
                a.admin_note,
                r.id AS valuation_request_id,
                r.reference_code,
                r.status AS request_status,
                r.manufacture_year,
                r.odometer_km,
                r.exterior_color,
                r.license_plate,
                r.registration_province,
                r.owners_count,
                r.vehicle_snapshot,
                r.estimated_min_price,
                r.estimated_max_price,
                c.full_name AS contact_name,
                c.phone AS contact_phone
            FROM valuation_inspection_assignments a
            INNER JOIN valuation_requests r
                ON r.id = a.valuation_request_id
            LEFT JOIN valuation_request_contacts c
                ON c.valuation_request_id = r.id
            WHERE a.staff_user_id = :staff_user_id
        ";

        $params = ['staff_user_id' => $staffUserId];

        if ($status === null) {
            $sql .= " AND a.status <> 'cancelled'";
        } else {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY a.assigned_at DESC, a.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Dem so luong theo trang thai cho sidebar staff.
     *
     * @return array<string,int>
     */
    public function countByStaff(int $staffUserId): array
    {
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) AS total
            FROM valuation_inspection_assignments
            WHERE staff_user_id = :staff_user_id
              AND status <> 'cancelled'
            GROUP BY status
        ");

        $stmt->execute(['staff_user_id' => $staffUserId]);

        $counts = [
            'assigned' => 0,
            'accepted' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];

        foreach ($stmt->fetchAll() as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /**
     * Kiem tra staff co dang phu trach ho so nay khong.
     */
    public function findOwnedByStaff(
        int $assignmentId,
        int $staffUserId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_inspection_assignments
            WHERE id = :id
              AND staff_user_id = :staff_user_id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $assignmentId,
            'staff_user_id' => $staffUserId,
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Chuyen trang thai co kiem tra trang thai hien tai.
     */
    public function transition(
        int $assignmentId,
        string $newStatus,
        array $allowedFrom
    ): bool {
        $timestampColumn = match ($newStatus) {
            'accepted' => 'accepted_at',
            'in_progress' => 'started_at',
            'completed' => 'completed_at',
            default => null,
        };

        $placeholders = implode(
            ', ',
            array_map(
                static fn (int $i): string => ":from{$i}",
                range(1, count($allowedFrom))
            )
        );

        $sql = "
            UPDATE valuation_inspection_assignments
            SET status = :new_status
        ";

        if ($timestampColumn !== null) {
            $sql .= ", {$timestampColumn} = NOW()";
        }

        $sql .= "
            WHERE id = :id
              AND status IN ({$placeholders})
        ";

        $params = [
            'new_status' => $newStatus,
            'id' => $assignmentId,
        ];

        foreach (array_values($allowedFrom) as $index => $from) {
            $params['from' . ($index + 1)] = $from;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Huy assignment dang hieu luc khi admin thay staff.
     */
    public function cancelActiveForRequest(
        int $requestId,
        ?int $exceptId = null
    ): void {
        $sql = "
            UPDATE valuation_inspection_assignments
            SET status = 'cancelled'
            WHERE valuation_request_id = :request_id
              AND status <> 'cancelled'
        ";

        $params = ['request_id' => $requestId];

        if ($exceptId !== null) {
            $sql .= " AND id <> :except_id";
            $params['except_id'] = $exceptId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}