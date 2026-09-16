<?php
declare(strict_types=1);
namespace App\Models\Product;
use App\Core\Database;
use PDO;

class Brand{
    private static ?string $logoColumn = null;
    private PDO $db;
    public function __construct(){
        $this->db = Database::connection();
    }
    public function all(bool $isAdmin=false) : array{
        $logo = $this->logoColumn();
        $sql = "SELECT id, name, slug, country, $logo AS logo, $logo AS logo_url, description, status, created_at, updated_at
                FROM brands
                WHERE deleted_at IS NULL";
        if(!$isAdmin) $sql.=" AND status ='active'";
        $sql.=" ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id, bool $isAdmin=false): ?array{
        $logo = $this->logoColumn();
        $sql = "SELECT id, name, slug, country, $logo AS logo, $logo AS logo_url, description, status, created_at, updated_at
                FROM brands
                WHERE id=:id AND deleted_at IS NULL";
        if(!$isAdmin){
            $sql.=" AND status='active'";
        }
        $s=$this->db->prepare($sql);
        $s->execute(['id'=>$id]);
        $r=$s->fetch(PDO::FETCH_ASSOC);
        return $r?:null;
    }

    // The repository uses logo_url in the schema and logo in schema.sql, accept both.
    public function logoColumn(): string{
        if(self::$logoColumn===null){
            $cols=$this->db->query("SHOW COLUMNS FROM brands")->fetchAll(PDO::FETCH_COLUMN);
            self::$logoColumn=in_array('logo',$cols,true)?'logo':'logo_url';
        }
        return self::$logoColumn;
    }

    public function create(array $d): int|false{
        $logo=$this->logoColumn();
        $sql = $this->db->prepare("INSERT INTO brands(name, slug, country, $logo, description, status)
        VALUES(:name, :slug, :country, :logo, :description, :status)");
        $ok = $sql->execute([
            'name' => $d['name'],
            'slug' => $d['slug'],
            'country' => $d['country'] ?? null,
            'logo' => $d['logo'] ?? $d['logo_url'] ?? null,
            'description' => $d['description'] ?? null,
            'status' => $d['status'] ?? 'active',
        ]);
        return $ok?(int)$this->db->lastInsertId():false;
    }

    public function update(int $id, array $d): bool{
        $logo=$this->logoColumn();
        $allowed=['name', 'slug', 'country', 'description', 'status'];
        $set = [];
        $p = ['id'=>$id];
        foreach ($allowed as $f) {
            if(array_key_exists($f,$d)){
                $set[]="$f=:$f";
                $p[$f] = $d[$f];
            }
        }
        if(array_key_exists('logo',$d)||array_key_exists('logo_url',$d)){
            $set[]="$logo=:$logo";
            $p[$logo]=$d['logo']??$d['logo_url'];
        }
        if(!$set) return true;
        return $this->db->prepare("UPDATE brands
            SET ".implode(',',$set)."
            WHERE id=:id AND deleted_at IS NULL")->execute($p);
    }
    public function hasModels(int $id): bool { $s=$this->db->prepare("SELECT COUNT(*) FROM vehicle_models WHERE brand_id=:id AND deleted_at IS NULL");$s->execute(['id'=>$id]);return (int)$s->fetchColumn()>0; }
    public function softDelete(int $id): bool { return $this->db->prepare("UPDATE brands SET status='inactive',deleted_at=NOW() WHERE id=:id AND deleted_at IS NULL")->execute(['id'=>$id]); }
}