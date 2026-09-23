<?php

declare(strict_types=1);

namespace App\Models\Inspection;

use App\Core\Database;
use PDO;

class ValuationRequestImage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Tìm ảnh đang active theo request + slot
     */
    public function findActiveBySlot(
        int $requestId,
        string $slotKey
    ): ?array {
        $stmt = $this->db->prepare("
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

    /**
     * Tìm ảnh theo request + slot,
     * bao gồm cả ảnh đã soft delete
     */
    public function findAnyBySlot(
        int $requestId,
        string $slotKey
    ): ?array {
        $stmt = $this->db->prepare("
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

    /**
     * Tạo ảnh mới
     */
    public function create(
        array $data
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO valuation_request_images (
                valuation_request_id,
                category,
                slot_key,
                image_url,
                storage_provider,
                public_id,
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
                :storage_provider,
                :public_id,
                :mime_type,
                :file_size_bytes,
                :width,
                :height,
                :is_required,
                :sort_order,
                :uploaded_by
            )
        ");

        $stmt->execute([
            'valuation_request_id' =>
                $data['valuation_request_id'],

            'category' =>
                $data['category'],

            'slot_key' =>
                $data['slot_key'],

            'image_url' =>
                $data['image_url'],

            'storage_provider' =>
                $data['storage_provider'] ?? 'cloudinary',

            'public_id' =>
                $data['public_id'],

            'mime_type' =>
                $data['mime_type'],

            'file_size_bytes' =>
                $data['file_size_bytes'],

            'width' =>
                $data['width'],

            'height' =>
                $data['height'],

            'is_required' =>
                $data['is_required'],

            'sort_order' =>
                $data['sort_order'],

            'uploaded_by' =>
                $data['uploaded_by'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cập nhật / khôi phục ảnh
     */
    public function replace(
        int $id,
        array $data
    ): void {
        $stmt = $this->db->prepare("
            UPDATE valuation_request_images
            SET
                category = :category,
                image_url = :image_url,
                storage_provider = :storage_provider,
                public_id = :public_id,
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

        $stmt->execute([
            'category' =>
                $data['category'],

            'image_url' =>
                $data['image_url'],

            'storage_provider' =>
                $data['storage_provider'] ?? 'cloudinary',

            'public_id' =>
                $data['public_id'],

            'mime_type' =>
                $data['mime_type'],

            'file_size_bytes' =>
                $data['file_size_bytes'],

            'width' =>
                $data['width'],

            'height' =>
                $data['height'],

            'is_required' =>
                $data['is_required'],

            'sort_order' =>
                $data['sort_order'],

            'uploaded_by' =>
                $data['uploaded_by'],

            'id' =>
                $id,
        ]);
    }

    /**
     * Soft delete ảnh
     */
    public function softDelete(
        int $id
    ): void {
        $stmt = $this->db->prepare("
            UPDATE valuation_request_images
            SET deleted_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $id,
        ]);
    }

    /**
     * Lấy toàn bộ ảnh đang active của valuation request
     */
    public function activeImages(
        int $requestId
    ): array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM valuation_request_images
            WHERE valuation_request_id = :request_id
              AND deleted_at IS NULL
            ORDER BY
                category,
                sort_order,
                id
        ");

        $stmt->execute([
            'request_id' => $requestId,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách slot đang có ảnh active
     */
    public function activeSlotKeys(
        int $requestId
    ): array {
        $stmt = $this->db->prepare("
            SELECT slot_key
            FROM valuation_request_images
            WHERE valuation_request_id = :request_id
              AND deleted_at IS NULL
              AND slot_key IS NOT NULL
        ");

        $stmt->execute([
            'request_id' => $requestId,
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