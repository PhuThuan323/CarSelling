<?php

declare(strict_types=1);

namespace App\Models\Notification;

use App\Core\Database;
use PDO;

/**
 * Thong bao trong he thong (staff <-> admin).
 */
class Notification
{
    public const TYPE_INSPECTION_ASSIGNED = 'inspection_assigned';

    public const TYPE_INSPECTION_RESULT_READY = 'inspection_result_ready';

    public const TYPE_INSPECTION_STARTED = 'inspection_started';

    public const TYPE_INSPECTION_ASSIGNMENT_ACCEPTED
        = 'inspection_assignment_accepted';

    public const TYPE_OFFER_ACCEPTED = 'offer_accepted';

    public const TYPE_OFFER_DECLINED = 'offer_declined';

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(
        int $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (
                user_id,
                type,
                title,
                message,
                reference_type,
                reference_id
            )
            VALUES (
                :user_id,
                :type,
                :title,
                :message,
                :reference_type,
                :reference_id
            )
        ");

        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Gui thong bao cho tat ca tai khoan admin dang active.
     */
    public function notifyAllAdmins(
        string $type,
        string $title,
        ?string $message = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): int {
        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE role = 'admin'
              AND status = 'active'
        ");

        $stmt->execute();

        $sent = 0;

        foreach ($stmt->fetchAll() as $row) {
            $this->create(
                (int) $row['id'],
                $type,
                $title,
                $message,
                $referenceType,
                $referenceId
            );

            $sent++;
        }

        return $sent;
    }

    public function findByUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));

        $stmt = $this->db->prepare("
            SELECT *
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ");

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id = :user_id
              AND is_read = 0
        ");

        $stmt->execute(['user_id' => $userId]);

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1,
                read_at = NOW()
            WHERE id = :id
              AND user_id = :user_id
              AND is_read = 0
        ");

        $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function markAllAsRead(int $userId): int
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1,
                read_at = NOW()
            WHERE user_id = :user_id
              AND is_read = 0
        ");

        $stmt->execute(['user_id' => $userId]);

        return $stmt->rowCount();
    }
}