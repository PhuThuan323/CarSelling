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

    /**
     * Danh sach nguoi dung cho trang quan ly nhan su.
     *
     * @param string|null $roleFilter   'customer' | 'staff' | 'admin' | null
     * @param string|null $statusFilter 'active' | 'inactive' | 'blocked' | null
     * @param string|null $keyword      tim theo ten / email / so dien thoai
     */
    public function findAllForAdmin(
        ?string $roleFilter = null,
        ?string $statusFilter = null,
        ?string $keyword = null,
        int $limit = 300
    ): array {
        $limit = max(1, min($limit, 500));

        $conditions = [];

        $params = [];

        if ($roleFilter !== null && $roleFilter !== '') {
            $conditions[] = 'role = :role';
            $params['role'] = $roleFilter;
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $conditions[] = 'status = :status';
            $params['status'] = $statusFilter;
        }

        if ($keyword !== null && trim($keyword) !== '') {
            $conditions[] = '(
                name LIKE :kw
                OR email LIKE :kw
                OR phone LIKE :kw
            )';
            $params['kw'] = '%' . trim($keyword) . '%';
        }

        $where = $conditions === []
            ? ''
            : 'WHERE ' . implode(' AND ', $conditions);

        $stmt = $this->db->prepare("
            SELECT id, name, email, phone, role, status, last_login_at
            FROM users
            {$where}
            ORDER BY
                FIELD(role, 'admin', 'staff', 'customer'),
                name ASC
            LIMIT {$limit}
        ");

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dem so nguoi dung theo tung role.
     *
     * @return array<string,int>
     */
    public function countByRole(): array
    {
        $stmt = $this->db->query("
            SELECT role, COUNT(*) AS total
            FROM users
            GROUP BY role
        ");

        $counts = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['role']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * Doi role cua nguoi dung.
     */
    public function updateRole(int $id, string $role): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET role = :role
            WHERE id = :id
        ");

        $stmt->execute(['id' => $id, 'role' => $role]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Doi trang thai tai khoan.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET status = :status
            WHERE id = :id
        ");

        $stmt->execute(['id' => $id, 'status' => $status]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Dem so ho so inspection dang mo cua mot nhan vien
     * (dung de canh bao khi thu hoi quyen staff).
     */
    public function countOpenInspections(int $staffUserId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM valuation_inspection_assignments
            WHERE staff_user_id = :staff_id
              AND status IN ('assigned', 'accepted', 'in_progress')
        ");

        $stmt->execute(['staff_id' => $staffUserId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }
}