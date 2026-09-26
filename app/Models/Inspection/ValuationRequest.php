<?php
declare(strict_types=1);
namespace App\Models\Inspection;
use App\Core\Database;
use PDO;
class ValuationRequest {

    /**
     * Workflow trang thai ho so ban xe.
     *
     * USER  : draft -> photos_pending -> contact_pending -> ready_for_estimate
     * ADMIN : ready_for_estimate -> inspection_requested
     *         -> inspection_assigned / inspection_in_progress / inspection_completed
     *         -> estimated
     * USER  : estimated -> accepted | cancelled
     * ADMIN : ready_for_estimate -> rejected
     */
    public const STATUS_LABELS = [
        'draft' => 'Bản nháp',
        'photos_pending' => 'Đang bổ sung ảnh',
        'contact_pending' => 'Chờ thông tin liên hệ',
        'ready_for_estimate' => 'Chờ tiếp nhận',
        'inspection_requested' => 'Đã yêu cầu inspection',
        'inspection_assigned' => 'Đã phân công staff',
        'inspection_in_progress' => 'Đang inspection',
        'inspection_completed' => 'Chờ admin duyệt kết quả',
        'estimating' => 'Đang định giá',
        'estimated' => 'Đã có kết quả định giá',
        'accepted' => 'Khách đã đồng ý bán',
        'rejected' => 'Hồ sơ bị từ chối',
        'cancelled' => 'Đã hủy',
    ];

    /** Trang thai dang nam trong pha inspection. */
    public const INSPECTION_STATUSES = [
        'inspection_requested',
        'inspection_assigned',
        'inspection_in_progress',
        'inspection_completed',
    ];

    private PDO $db;

    public function __construct(){
        $this->db = Database::connection();
    }

    public static function statusLabel(?string $status): string
    {
        if ($status === null) {
            return '';
        }

        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function isInspectionStatus(?string $status): bool
    {
        return in_array($status, self::INSPECTION_STATUSES, true);
    }
    public function findVersion( int $versionId): ?array {
        $sql = "
            SELECT
                vv.*,

                vm.id AS model_id,
                vm.name AS model_name,
                vm.slug AS model_slug,

                b.id AS brand_id,
                b.name AS brand_name,
                b.slug AS brand_slug

            FROM vehicle_versions vv

            INNER JOIN vehicle_models vm
                ON vm.id = vv.model_id
                AND vm.deleted_at IS NULL

            INNER JOIN brands b
                ON b.id = vm.brand_id
                AND b.deleted_at IS NULL

            WHERE vv.id = :id
            AND vv.deleted_at IS NULL
            AND vv.status = 'active'
            AND vm.status = 'active'
            AND b.status = 'active'

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            'id' => $versionId
        ]);

        $row =
            $stmt->fetch();

        return $row ?: null;
    }
    public function markContactPending(int $requestId): bool {

    $sql = "
        UPDATE valuation_requests

        SET status = 'contact_pending'

        WHERE id = :id
    ";

    $stmt =
        $this->db->prepare($sql);

    return $stmt->execute([
        ':id' => $requestId
    ]);
    }
    public function createDraft(int $userId, array $data, array $snapshot): int {
        $sql = "
            INSERT INTO valuation_requests (
                reference_code,
                user_id,
                vehicle_version_id,
                manufacture_year,
                odometer_km,
                exterior_color,
                interior_color,
                license_plate,
                registration_province,
                owners_count,
                seller_note,
                vehicle_snapshot,
                status
            )
            VALUES(
                :reference_code,
                :user_id,
                :vehicle_version_id,
                :manufacture_year,
                :odometer_km,
                :exterior_color,
                :interior_color,
                :license_plate,
                :registration_province,
                :owners_count,
                :seller_note,
                :vehicle_snapshot,
                'draft'
            )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'reference_code' => $data['reference_code'],
            'user_id' => $userId,
            'vehicle_version_id' => $data['vehicle_version_id'],
            'manufacture_year' => $data['manufacture_year'],
            'odometer_km' => $data['odometer_km'],
            'exterior_color' => $data['exterior_color'],
            'interior_color' => $data['interior_color'],
            'license_plate' => $data['license_plate'],
            'registration_province' => $data['registration_province'],
            'owners_count' => $data['owners_count'],
            'seller_note' => $data['seller_note'],
            'vehicle_snapshot' =>
                json_encode(
                    $snapshot,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                ),
        ]);
        return(int) $this->db->lastInsertId();
    }

    public function findOwned(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT *
                FROM valuation_requests

                WHERE id = :id
                AND user_id = :user_id

                LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'user_id' =>$userId,
        ]);
        $row = $stmt->fetch();
        if(!$row) return null;
        if(!empty($row['vehicle_snapshot'])){
            $row['vehicle_snapshot'] =
                    json_decode(
                        $row['vehicle_snapshot'],
                        true
                    );
        }
        return $row;
    }
    public function markPhotosPending(
            int $id
        ): void {
            $stmt =
                $this->db->prepare("
                    UPDATE valuation_requests

                    SET status =
                        CASE
                            WHEN status = 'draft'
                                THEN 'photos_pending'
                            ELSE status
                        END

                    WHERE id = :id
                ");

            $stmt->execute([
                'id' => $id
            ]);
        }

        public function submit(
            int $id
        ): void {
            $stmt =
                $this->db->prepare("
                    UPDATE valuation_requests

                    SET
                        status = 'ready_for_estimate',
                        submitted_at = NOW()

                    WHERE id = :id
                    AND status IN (
                        'draft',
                        'photos_pending'
                    )
                ");

            $stmt->execute([
                'id' => $id
            ]);
        }

        public function setConsents(
            int $id,
            bool $privacy,
            bool $terms
        ): void {
            $stmt =
                $this->db->prepare("
                    UPDATE valuation_requests

                    SET
                        consent_privacy_at =
                            CASE
                                WHEN :privacy = 1
                                    THEN COALESCE(
                                        consent_privacy_at,
                                        NOW()
                                    )
                                ELSE consent_privacy_at
                            END,

                        consent_terms_at =
                            CASE
                                WHEN :terms = 1
                                    THEN COALESCE(
                                        consent_terms_at,
                                        NOW()
                                    )
                                ELSE consent_terms_at
                            END

                    WHERE id = :id
                ");

            $stmt->execute([
                'privacy' =>
                    $privacy ? 1 : 0,

                'terms' =>
                    $terms ? 1 : 0,

                'id' =>
                    $id,
            ]);
        }
    public function submitAfterContact(int $id): bool {
        $stmt = $this->db->prepare("
            UPDATE valuation_requests

            SET
                status = 'ready_for_estimate',
                submitted_at = NOW(),
                updated_at = CURRENT_TIMESTAMP

            WHERE id = :id
            AND status = 'contact_pending'
        ");

        $stmt->execute([
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }
    public function findAllByUser(int $userId):array{
        $stmt=$this->db->prepare("
            SELECT  id, reference_code, vehicle_version_id, manufacture_year, odometer_km, vehicle_snapshot, status, estimated_price_min, estimated_price_max, submitted_at, created_at, updated_at
            FROM valuation_requests
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id'=>$userId]);
        $rows = $stmt->fetchAll();
        foreach($rows as &$row){
            if(!empty($row['vehicle_snapshot'])){
                $row['vehicle_snapshot'] = json_decode($row['vehicle_snapshot'], true);
            }
        };
        unset($row);
        return $rows;
    }

    /**
     * Chuyen trang thai co kiem tra trang thai hien tai.
     * Tra ve true neu update thanh cong.
     */
    public function transition(
        int $id,
        string $newStatus,
        array $allowedFrom,
        ?string $cancellationReason = null
    ): bool {
        if ($allowedFrom === []) {
            return false;
        }

        $placeholders = implode(
            ', ',
            array_map(
                static fn (int $i): string => ":from{$i}",
                range(1, count($allowedFrom))
            )
        );

        $sql = "
            UPDATE valuation_requests
            SET status = :new_status
        ";

        if ($cancellationReason !== null) {
            $sql .= ", cancellation_reason = :cancellation_reason";
        }

        $sql .= "
            WHERE id = :id
              AND status IN ({$placeholders})
        ";

        $params = [
            'new_status' => $newStatus,
            'id' => $id,
        ];

        if ($cancellationReason !== null) {
            $params['cancellation_reason'] = $cancellationReason;
        }

        foreach (array_values($allowedFrom) as $index => $from) {
            $params['from' . ($index + 1)] = $from;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Admin chot gia cuoi cung va gui cho khach.
     * Chi ap dung khi ho so da qua inspection.
     */
    public function publishEstimate(
        int $id,
        float $priceMin,
        float $priceMax,
        array $allowedFrom
    ): bool {
        if ($allowedFrom === []) {
            return false;
        }

        $placeholders = implode(
            ', ',
            array_map(
                static fn (int $i): string => ":from{$i}",
                range(1, count($allowedFrom))
            )
        );

        $sql = "
            UPDATE valuation_requests
            SET status = 'estimated',
                estimated_min_price = :price_min,
                estimated_max_price = :price_max
            WHERE id = :id
              AND status IN ({$placeholders})
        ";

        $params = [
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'id' => $id,
        ];

        foreach (array_values($allowedFrom) as $index => $from) {
            $params['from' . ($index + 1)] = $from;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_requests
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->decodeSnapshot($row);
    }

    /**
     * Ho so kem thong tin khach + xe, dung cho admin / staff.
     */
    public function findDetailed(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT
                r.*,
                c.full_name AS contact_name,
                c.phone AS contact_phone,
                c.email AS contact_email,
                c.contact_note AS contact_note,
                u.name AS owner_name,
                u.email AS owner_email
            FROM valuation_requests r
            LEFT JOIN valuation_request_contacts c
                ON c.valuation_request_id = r.id
            LEFT JOIN users u
                ON u.id = r.user_id
            WHERE r.id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->decodeSnapshot($row);
    }

    /**
     * Danh sach ho so cho admin, loc theo nhom trang thai.
     *
     * @param string[] $statuses
     */
    public function findByStatuses(
        array $statuses,
        int $limit = 200
    ): array {
        if ($statuses === []) {
            return [];
        }

        $limit = max(1, min($limit, 500));

        $placeholders = implode(
            ', ',
            array_map(
                static fn (int $i): string => ":s{$i}",
                range(1, count($statuses))
            )
        );

        $stmt = $this->db->prepare("
            SELECT
                r.id,
                r.reference_code,
                r.status,
                r.manufacture_year,
                r.odometer_km,
                r.license_plate,
                r.exterior_color,
                r.vehicle_snapshot,
                r.estimated_min_price,
                r.estimated_max_price,
                r.submitted_at,
                r.created_at,
                c.full_name AS contact_name,
                c.phone AS contact_phone,
                a.id AS assignment_id,
                a.status AS assignment_status,
                a.scheduled_at,
                a.staff_user_id,
                su.name AS staff_name,
                res.id AS result_id,
                res.suggested_price_min,
                res.suggested_price_max,
                res.submitted_at AS result_submitted_at
            FROM valuation_requests r
            LEFT JOIN valuation_request_contacts c
                ON c.valuation_request_id = r.id
            LEFT JOIN valuation_inspection_assignments a
                ON a.id = (
                    SELECT a2.id
                    FROM valuation_inspection_assignments a2
                    WHERE a2.valuation_request_id = r.id
                      AND a2.status <> 'cancelled'
                    ORDER BY a2.assigned_at DESC, a2.id DESC
                    LIMIT 1
                )
            LEFT JOIN users su
                ON su.id = a.staff_user_id
            LEFT JOIN valuation_inspection_results res
                ON res.id = (
                    SELECT r2.id
                    FROM valuation_inspection_results r2
                    WHERE r2.valuation_request_id = r.id
                    ORDER BY r2.submitted_at DESC, r2.id DESC
                    LIMIT 1
                )
            WHERE r.status IN ({$placeholders})
            ORDER BY r.submitted_at DESC, r.id DESC
            LIMIT {$limit}
        ");

        $params = [];

        foreach (array_values($statuses) as $index => $status) {
            $params['s' . ($index + 1)] = $status;
        }

        $stmt->execute($params);

        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row = $this->decodeSnapshot($row);
        }

        unset($row);

        return $rows;
    }

    public function findHistory(int $requestId): array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_request_status_histories
            WHERE valuation_request_id = :request_id
            ORDER BY created_at ASC, id ASC
        ");

        $stmt->execute(['request_id' => $requestId]);

        return $stmt->fetchAll();
    }

    private function decodeSnapshot(array $row): array
    {
        if (!empty($row['vehicle_snapshot'])) {
            $decoded = json_decode((string) $row['vehicle_snapshot'], true);

            $row['vehicle_snapshot'] = is_array($decoded)
                ? $decoded
                : [];
        }

        return $row;
    }
}