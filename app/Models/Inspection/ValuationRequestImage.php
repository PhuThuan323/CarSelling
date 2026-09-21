<?php

declare(strict_types=1);

namespace App\Models\Inspectation;

use App\Core\Database;
use PDO;

class ValuationRequestImage
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::connection();
    }

    public function findActiveBySlot(
        int $requestId,
        string $slotKey
    ): ?array {
        $stmt =
            $this->db->prepare("
                SELECT *
                FROM valuation_request_images

                WHERE valuation_request_id = :request_id
                  AND slot_key = :slot_key
                  AND deleted_at IS NULL

                LIMIT 1
            ");

        $stmt->execute([
            'request_id' => $requestId,
            'slot_key' => $slotKey,
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findAnyBySlot(
        int $requestId,
        string $slotKey
    ): ?array {
        $stmt =
            $this->db->prepare("
                SELECT *
                FROM valuation_request_images

                WHERE valuation_request_id = :request_id
                  AND slot_key = :slot_key

                LIMIT 1
            ");

        $stmt->execute([
            'request_id' => $requestId,
            'slot_key' => $slotKey,
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        array $data
    ): int {
        $stmt =
            $this->db->prepare("
                INSERT INTO valuation_request_images (
                    valuation_request_id,
                    category,
                    slot_key,
                    image_url,
                    public_id,
                    asset_id,
                    mime_type,
                    file_size_bytes,
                    width,
                    height,
                    is_required,
                    sort_order,
                    uploaded_by
                )
                VALUES (
                    :valuation_request_id,
                    :category,
                    :slot_key,
                    :image_url,
                    :public_id,
                    :asset_id,
                    :mime_type,
                    :file_size_bytes,
                    :width,
                    :height,
                    :is_required,
                    :sort_order,
                    :uploaded_by
                )
            ");

        $stmt->execute($data);

        return (int)
            $this->db->lastInsertId();
    }

    public function replace(
        int $id,
        array $data
    ): void {
        $stmt =
            $this->db->prepare("
                UPDATE valuation_request_images

                SET
                    category = :category,
                    image_url = :image_url,
                    public_id = :public_id,
                    asset_id = :asset_id,
                    mime_type = :mime_type,
                    file_size_bytes = :file_size_bytes,
                    width = :width,
                    height = :height,
                    is_required = :is_required,
                    sort_order = :sort_order,
                    uploaded_by = :uploaded_by,
                    deleted_at = NULL

                WHERE id = :id
            ");

        $data['id'] = $id;

        $stmt->execute($data);
    }

    public function softDelete(
        int $id
    ): void {
        $stmt =
            $this->db->prepare("
                UPDATE valuation_request_images

                SET deleted_at = NOW()

                WHERE id = :id
            ");

        $stmt->execute([
            'id' => $id
        ]);
    }

    public function activeImages(
        int $requestId
    ): array {
        $stmt =
            $this->db->prepare("
                SELECT *
                FROM valuation_request_images

                WHERE valuation_request_id =
                    :request_id

                  AND deleted_at IS NULL

                ORDER BY
                    category,
                    sort_order,
                    id
            ");

        $stmt->execute([
            'request_id' => $requestId
        ]);

        return $stmt->fetchAll();
    }

    public function activeSlotKeys(
        int $requestId
    ): array {
        $stmt =
            $this->db->prepare("
                SELECT slot_key

                FROM valuation_request_images

                WHERE valuation_request_id =
                    :request_id

                  AND deleted_at IS NULL

                  AND slot_key IS NOT NULL
            ");

        $stmt->execute([
            'request_id' => $requestId
        ]);

        return array_values(
            array_filter(
                array_column(
                    $stmt->fetchAll(),
                    'slot_key'
                )
            )
        );
    }
}