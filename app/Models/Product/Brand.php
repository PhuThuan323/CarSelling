<?php
namespace App\Model\Product;
use App\Core\Database;
use PDO;

class Brand{
    private PDO $db;
    public function __construct(){
        $this->db = Database::connection();
    }
    public function all(bool $isAdmin=false) : array{
        $sql = "SELECT id, name, slug, country, logo, description, status, created_at, updated_at
                FROM brands 
                WHERE deleted_at is null";
        if(!$isadmin) $sql.=" AND status ='active'";
        $sql.=" ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findbyId(int $id, bool $isAdmin=false): ?array{
        $sql = "SELECT id, name, slug, country, logo, description, status, created_at, updated_at
                FROM brands 
                WHERE id=:id AND deleted_at is null";
        if(!isAdmin){
            $sql.="
            AND status='active'";
        }
        $s=$this->db->prepare($sql);
        $s->execute(['id'=>$id]);
        $r=$s->fetch(PDO::FETCH_ASSOC);
        return $rz?:null;
    }
    public function create(array $d): int|false{
        $sql = $this->db->prepare("INSERT INTO brands(name, slug, country,logo,description,status)
        VALUES(:name, :slug, :country, :logo, :description, :status)");
        $ok = $sql->execute([
            'name' => $d['name'],
            'slug' => $d['slug'],
            'country' => $d['country'], 
            'logo' => $d['country'],
            'description' => $d['country'],
            'status' => $d['status'],
        ]);
        return $ok?(int)$this->db->lastInsertId():false;
    }
    public function update(int $id, array $d):bool{
        $allowed=['name', 'slug', 'country', 'logo', 'description', 'status '];
        $set = [];
        $p = ['id'=>$id];
        foreach ($allowed as $f) {
            if(array_key_exists($f,$d)){
                $set[]="$f=:$f";
                $p[$f] = $d[$f];
            };
            if(!$set) return true;
            return $this->db->prepare("UPDATE brands
                SET ".implode(',',$set)" 
                WHERE id=:id AND deleted_at is NULL")->execute($p);
        }
    }
    public function hasModels(int $id): bool { $s=$this->db->prepare("SELECT COUNT(*) FROM vehicle_models WHERE brand_id=:id AND deleted_at IS NULL");$s->execute(['id'=>$id]);return (int)$s->fetchColumn()>0; }
    public function softDelete(int $id): bool { return $this->db->prepare("UPDATE brands SET status='inactive',deleted_at=NOW() WHERE id=:id AND deleted_at IS NULL")->execute(['id'=>$id]); }
}