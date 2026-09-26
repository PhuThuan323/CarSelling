<?php

declare(strict_types=1);

namespace App\Models\Inspection;

use App\Core\Database;
use PDO;

/**
 * Ket qua tham dinh do staff nhap.
 *
 * Staff chi DE XUAT range gia (suggested_price_min/max).
 * Gia cuoi cung do admin chot va luu o valuation_requests.
 */
class ValuationInspectionResult
{
    private PDO $db;

    /**
     * Cac hang muc danh gia dang luu truc tiep tren bang.
     * rating_type: 'good_average_poor' hoac 'good_issue'
     */
    public const RATING_FIELDS = [
        'exterior_rating' => [
            'label' => 'Ngoại thất',
            'group' => 'Ngoại thất',
            'type' => 'good_average_poor',
        ],
        'interior_rating' => [
            'label' => 'Nội thất',
            'group' => 'Nội thất',
            'type' => 'good_average_poor',
        ],
        'engine_rating' => [
            'label' => 'Động cơ',
            'group' => 'Máy móc',
            'type' => 'good_average_poor',
        ],
        'transmission_rating' => [
            'label' => 'Hộp số',
            'group' => 'Máy móc',
            'type' => 'good_average_poor',
        ],
        'chassis_rating' => [
            'label' => 'Gầm',
            'group' => 'Máy móc',
            'type' => 'good_average_poor',
        ],
        'legal_rating' => [
            'label' => 'Pháp lý',
            'group' => 'Pháp lý',
            'type' => 'good_issue',
        ],
    ];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public static function allowedRatings(string $type): array
    {
        return $type === 'good_issue'
            ? ['good', 'issue']
            : ['good', 'average', 'poor'];
    }

    public function upsert(
        int $requestId,
        int $assignmentId,
        int $staffUserId,
        array $data
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO valuation_inspection_results (
                valuation_request_id,
                assignment_id,
                staff_user_id,
                odometer_actual,
                exterior_rating,
                interior_rating,
                engine_rating,
                transmission_rating,
                chassis_rating,
                legal_rating,
                summary,
                staff_note,
                suggested_price_min,
                suggested_price_max,
                submitted_at
            )
            VALUES (
                :request_id,
                :assignment_id,
                :staff_user_id,
                :odometer_actual,
                :exterior_rating,
                :interior_rating,
                :engine_rating,
                :transmission_rating,
                :chassis_rating,
                :legal_rating,
                :summary,
                :staff_note,
                :suggested_price_min,
                :suggested_price_max,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                odometer_actual = VALUES(odometer_actual),
                exterior_rating = VALUES(exterior_rating),
                interior_rating = VALUES(interior_rating),
                engine_rating = VALUES(engine_rating),
                transmission_rating = VALUES(transmission_rating),
                chassis_rating = VALUES(chassis_rating),
                legal_rating = VALUES(legal_rating),
                summary = VALUES(summary),
                staff_note = VALUES(staff_note),
                suggested_price_min = VALUES(suggested_price_min),
                suggested_price_max = VALUES(suggested_price_max),
                submitted_at = NOW(),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            'request_id' => $requestId,
            'assignment_id' => $assignmentId,
            'staff_user_id' => $staffUserId,
            'odometer_actual' => $data['odometer_actual'] ?? null,
            'exterior_rating' => $data['exterior_rating'] ?? null,
            'interior_rating' => $data['interior_rating'] ?? null,
            'engine_rating' => $data['engine_rating'] ?? null,
            'transmission_rating' => $data['transmission_rating'] ?? null,
            'chassis_rating' => $data['chassis_rating'] ?? null,
            'legal_rating' => $data['legal_rating'] ?? null,
            'summary' => $data['summary'] ?? null,
            'staff_note' => $data['staff_note'] ?? null,
            'suggested_price_min' => $data['suggested_price_min'] ?? null,
            'suggested_price_max' => $data['suggested_price_max'] ?? null,
        ]);

        if ((int) $this->db->lastInsertId() > 0) {
            return (int) $this->db->lastInsertId();
        }

        $existing = $this->findByAssignment($assignmentId);

        return $existing ? (int) $existing['id'] : 0;
    }

    /**
     * Ket qua moi nhat cua ho so (moi nhat theo submitted_at).
     */
    public function findByRequest(int $requestId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                r.*,
                u.name AS staff_name
            FROM valuation_inspection_results r
            LEFT JOIN users u ON u.id = r.staff_user_id
            WHERE r.valuation_request_id = :request_id
            ORDER BY r.submitted_at DESC, r.id DESC
            LIMIT 1
        ");

        $stmt->execute(['request_id' => $requestId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByAssignment(int $assignmentId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_inspection_results
            WHERE assignment_id = :assignment_id
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute(['assignment_id' => $assignmentId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }
}