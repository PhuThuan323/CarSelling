<?php
declare(strict_types=1);
namespace App\Models\Inspection;
use App\Core\Database;
use PDO;
class ValuationRequest {
    private PDO $db;
    public function __construct(){
        $this->db = Database::connection();
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
}