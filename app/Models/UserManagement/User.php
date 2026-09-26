<?php
namespace App\Models\UserManagement;
use App\Core\Database;
use PDO;

class User {
    private PDO $db;
    public function __construct() {
        $this->db = Database::connection();
    }
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
    public function create(array $data): bool {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)");
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':role' => $data['role'] ?? 'customer',
            ':status' => $data['status'] ?? 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id");
        return $stmt->execute([':id' => $id, ':name' => $data['name'], ':email' => $data['email'], ':password' => $data['password']]);
    }
    public function updatePassword(int $id, string $password): bool {
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute([':id' => $id, ':password' => $password]);
    }
    public function findByGoogleId(string $googleId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = :google_id");
        $stmt->execute([':google_id' => $googleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Danh sach nhan vien inspection (role=staff) dang active.
     */
    public function findActiveStaff(): array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, status
            FROM users
            WHERE role = 'staff'
              AND status = 'active'
            ORDER BY name ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActiveStaffById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, status, role
            FROM users
            WHERE id = :id
              AND role = 'staff'
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}