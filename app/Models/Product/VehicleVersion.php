<?php
declare(strict_types=1);
namespace App\Models\Product;
use App\Core\Database;
use PDO;
class VehicleVersion {
    private PDO $db;

    // Single source of truth for the columns that can be written via create()/update().
    private const FIELDS = [
        'model_id',
        'name',
        'slug',
        'production_year_from',
        'production_year_to',
        'engine_name',
        'engine_code',
        'engine_displacement_cc',
        'fuel_type',
        'transmission',
        'drivetrain',
        'horsepower',
        'torque_nm',
        'seats',
        'doors',
        'battery_capacity_kwh',
        'range_km',
        'description',
        'specifications',
        'status',
    ];

    public function __construct(){ 
        $this->db=Database::connection(); 
    }
    public function all(bool $admin=false): array { 
        $sql="SELECT vv.*,vm.name 
        AS model_name,b.id 
        AS brand_id,b.name 
        AS brand_name ,vm.status AS model_status,b.status AS brand_status
        FROM vehicle_versions vv 
        JOIN vehicle_models vm ON vm.id=vv.model_id 
        JOIN brands b ON b.id=vm.brand_id 
        WHERE vv.deleted_at IS NULL AND vm.deleted_at IS NULL AND b.deleted_at IS NULL";
        if(!$admin){
            $sql.=" AND vv.status='active' AND vm.status='active' AND b.status='active'";
        }
        $sql.=" ORDER BY b.name,vm.name,vv.name";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
    }
    public function byModel(int $modelId,bool $admin=false): array { 
        $sql="SELECT * 
            FROM vehicle_versions 
            WHERE model_id=:model_id AND deleted_at IS NULL";
        if(!$admin){
            $sql.=" AND status='active'";
        }
        $sql.=" ORDER BY name";
        $s=$this->db->prepare($sql);
        $s->execute(['model_id'=>$modelId]);
        return $s->fetchAll(PDO::FETCH_ASSOC); 
    }
    public function findById(int $id,bool $admin=false): ?array { 
        $sql="SELECT vv.*,vm.name AS model_name,b.id AS brand_id,b.name AS brand_name 
            FROM vehicle_versions vv 
            JOIN vehicle_models vm ON vm.id=vv.model_id 
            JOIN brands b ON b.id=vm.brand_id 
            WHERE vv.id=:id AND vv.deleted_at IS NULL AND vm.deleted_at IS NULL AND b.deleted_at IS NULL";
        if(!$admin){
            $sql.=" AND vv.status='active' AND vm.status='active' AND b.status='active'";
        }
        $s=$this->db->prepare($sql);
        $s->execute(['id'=>$id]);
        $r=$s->fetch(PDO::FETCH_ASSOC);
        return $r?:null; 
    }
    // Encodes the specifications field (JSON) and passes every other value through untouched.
    private function bindValue(string $field, $value) {
        return $field === 'specifications'
            ? json_encode($value, JSON_UNESCAPED_UNICODE)
            : $value;
    }

    public function create(array $d): int|false {
        $columns = implode(',', self::FIELDS);
        $placeholders = implode(',', array_map(fn($f) => ":$f", self::FIELDS));

        $params = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $d)) {
                $params[$field] = $this->bindValue($field, $d[$field]);
            } elseif ($field === 'status') {
                $params[$field] = 'active';
            } else {
                $params[$field] = null;
            }
        }

        $s=$this->db->prepare("INSERT INTO vehicle_versions($columns) VALUES($placeholders)");
        $ok=$s->execute($params);
        return $ok?(int)$this->db->lastInsertId():false;
    }

    public function update(int $id,array $d): bool {
        $set=[];
        $p=['id'=>$id];

        foreach(self::FIELDS as $f){
            if(array_key_exists($f,$d)){
                $set[]="$f=:$f";
                $p[$f]=$this->bindValue($f,$d[$f]);
            }
        }

        if(!$set){
            return true;
        }

        return (bool)$this->db->prepare(
            "UPDATE vehicle_versions SET " . implode(',', $set) . " WHERE id=:id AND deleted_at IS NULL"
        )->execute($p);
    }
    public function softDelete(int $id): bool { 
        return $this->db->prepare("UPDATE vehicle_versions 
        SET status='inactive',deleted_at=NOW() 
        WHERE id=:id AND deleted_at IS NULL")->execute(['id'=>$id]); 
    }

    // A version can only be removed while no vehicle of that version exists.
    public function hasVehicles(int $id): bool {
        $s=$this->db->prepare("SELECT COUNT(*) FROM vehicles WHERE vehicle_version_id=:id");
        $s->execute(['id'=>$id]);
        return (int)$s->fetchColumn()>0;
    }
}
