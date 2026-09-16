<?php
declare(strict_types=1);
namespace App\Models\Product;
use App\Core\Database;
use PDO;
class VehicleModel {
    private PDO $db;
    public function __construct(){ 
        $this->db=Database::connection(); }
    public function all(bool $admin=false): array {
        $sql="
        SELECT vm.*,b.name AS brand_name 
        FROM vehicle_models vm 
        JOIN brands b ON b.id=vm.brand_id 
        WHERE vm.deleted_at IS NULL 
        AND b.deleted_at IS NULL";
        if(!$admin){
            $sql.=" AND vm.status='active' AND b.status='active'";
        }
        $sql.=" ORDER BY brand_name ASC, vm.name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function byBrand(int $brandId,bool $admin=false): array {
        $sql="
        SELECT vm.*,b.name AS brand_name
        FROM vehicle_models vm
        JOIN brands b ON b.id=vm.brand_id
        WHERE vm.brand_id=:brand_id AND vm.deleted_at IS NULL AND b.deleted_at IS NULL";
        if(!$admin){
            $sql.=" AND vm.status='active' AND b.status='active'";
        }
        $sql.=" ORDER BY vm.name";
        $s=$this->db->prepare($sql);
        $s->execute(['brand_id'=>$brandId]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findById(int $id,bool $admin=false): ?array {
        $sql="
        SELECT vm.*,b.name AS brand_name 
        FROM vehicle_models vm 
        JOIN brands b ON b.id=vm.brand_id 
        WHERE vm.id=:id AND vm.deleted_at IS NULL AND b.deleted_at IS NULL";
        if(!$admin){
            $sql.=" AND vm.status='active' AND b.status='active'";
        }
        $s=$this->db->prepare($sql);
        $s->execute(['id'=>$id]);
        $r=$s->fetch(PDO::FETCH_ASSOC);
        return $r?:null;
    }
    public function create(array $d): int|false {
        $s=$this->db->prepare("INSERT INTO vehicle_models(brand_id,name,slug,body_type,description,status) VALUES(:brand_id,:name,:slug,:body_type,:description,:status)");$ok=$s->execute(['brand_id'=>$d['brand_id'],'name'=>$d['name'],'slug'=>$d['slug'],'body_type'=>$d['body_type']??null,'description'=>$d['description']??null,'status'=>$d['status']??'active']);return $ok?(int)$this->db->lastInsertId():false;
    }
    public function update(int $id,array $d): bool { 
        $allowed=['brand_id','name','slug','body_type','description','status'];
        $set=[];
        $p=['id'=>$id];
        foreach($allowed as $f){
            if(array_key_exists($f,$d)){
                $set[]="$f=:$f";
                $p[$f]=$d[$f];}
            }
            if(!$set) return true;
            return $this->db->prepare("
            UPDATE vehicle_models 
            SET ".implode(',',$set)."
            WHERE id=:id AND deleted_at IS NULL")->execute($p);
    }
    public function hasVersions(int $id): bool { 
        $s=$this->db->prepare("
            SELECT COUNT(*) 
            FROM vehicle_versions 
            WHERE model_id=:id AND deleted_at IS NULL"
        );
        $s->execute(['id'=>$id]);
        return (int)$s->fetchColumn()>0; 
    }
    public function softDelete(int $id): bool { 
        return $this->db->prepare("
        UPDATE vehicle_models 
        SET status='inactive',deleted_at=NOW() 
        WHERE id=:id AND deleted_at IS NULL")->execute(['id'=>$id]); 
    }
}
