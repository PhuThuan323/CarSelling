<?php

namespace App\Models\UserManagement;

use App\Core\Database;
use PDO;

class PasswordReset
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::connection();
    }

    public function deleteByUserId(
        int $userId
    ): bool {

        $stmt = $this->db->prepare(
            "
            DELETE FROM password_reset_codes
            WHERE user_id = :user_id
            "
        );

        return $stmt->execute([
            ':user_id' => $userId
        ]);
    }


    public function create(
        int $userId,
        string $codeHash,
        string $expiresAt
    ): bool {

        $stmt = $this->db->prepare(
            "
            INSERT INTO password_reset_codes
            (
                user_id,
                code_hash,
                expires_at
            )
            VALUES
            (
                :user_id,
                :code_hash,
                :expires_at
            )
            "
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':code_hash' => $codeHash,
            ':expires_at' => $expiresAt
        ]);
    }

    public function findLatestByUserId(
        int $userId
    ): ?array {

        $stmt = $this->db->prepare(
            "
            SELECT *
            FROM password_reset_codes
            WHERE user_id = :user_id
            ORDER BY id DESC
            LIMIT 1
            "
        );

        $stmt->execute([
            ':user_id' => $userId
        ]);

        $result =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function incrementAttempts(
        int $id
    ): bool {

        $stmt = $this->db->prepare(
            "
            UPDATE password_reset_codes
            SET attempts = attempts + 1
            WHERE id = :id
            "
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }


    public function markVerified(
        int $id
    ): bool {

        $stmt = $this->db->prepare(
            "
            UPDATE password_reset_codes
            SET verified_at = NOW()
            WHERE id = :id
            "
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}