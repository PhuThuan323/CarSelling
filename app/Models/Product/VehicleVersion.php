<?php
declare(strict_types=1);
namespace App\Models\Product;
use App\Core\Database;
use PDO;
class VehicleVersion {
    private PDO $db;
    public function __construct(){ 
        $this->db=Database::connection(); 
    }
    public function all(bool $admin=false): array { 
        $sql="SELECT vv.*,vm.name 
        AS model_name,b.id 
        AS brand_id,b.name 
        AS brand_name 
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
    public function create(array $d): int|false { 
        $sql="INSERT INTO vehicle_versions(
            model_id,
            name,
            slug,
            production_year_from,
            production_year_to,
            engine_name,
            engine_code,
            engine_displacement_cc,
            fuel_type,
            transmission,
            drivetrain,
            horsepower,
            torque_nm,
            seats,
            doors,
            battery_capacity_kwh,
            range_km,
            description,
            specifications,
            status) 
        VALUES(
            :model_id,
            :name,
            :slug,
            :production_year_from,
            :production_year_to,
            :engine_name,
            :engine_code,
            :engine_displacement_cc,
            :fuel_type,
            :transmission,
            :drivetrain,
            :horsepower,
            :torque_nm,
            :seats,
            :doors,
            :battery_capacity_kwh,
            :range_km,
            :description,
            :specifications,
            :status)";
        $s=$this->db->prepare($sql);
        $ok=$s->execute([
            'model_id'=>$d['model_id'],
            'name'=>$d['name'],
            'slug'=>$d['slug'],
            'production_year_from'=>$d['production_year_from']??null,
            'production_year_to'=>$d['production_year_to']??null,
            'engine_name'=>$d['engine_name']??null,
            'engine_code'=>$d['engine_code']??null,
            'engine_displacement_cc'=>$d['engine_displacement_cc']??null,
            'fuel_type'=>$d['fuel_type']??null,
            'transmission'=>$d['transmission']??null,
            'drivetrain'=>$d['drivetrain']??null,
            'horsepower'=>$d['horsepower']??null,
            'torque_nm'=>$d['torque_nm']??null,
            'seats'=>$d['seats']??null,
            'doors'=>$d['doors']??null,
            'battery_capacity_kwh'=>$d['battery_capacity_kwh']??null,
            'range_km'=>$d['range_km']??null,
            'description'=>$d['description']??null,
            'specifications'=>isset($d['specifications'])?json_encode($d['specifications'],JSON_UNESCAPED_UNICODE):null,
            'status'=>$d['status']??'active']);
        return $ok?(int)$this->db->lastInsertId():false; 
    }
    public function update(int $id,array $d): bool { 
        $allowed=[
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
            'status'];
        $set=[];
        $p=['id'=>$id];
        foreach($allowed as $f){
            if(array_key_exists($f,$d)){
                $set[]="$f=:$f";
                $p[$f]=$f==='specifications'?json_encode($d[$f],JSON_UNESCAPED_UNICODE):$d[$f];}}
                if(!$set) return true;
                return $this->db->prepare("UPDATE vehicle_versions 
                SET ".implode(',',$set)." 
                WHERE id=:id AND deleted_at IS NULL")->execute($p);
            }
    public function softDelete(int $id): bool { 
        return $this->db->prepare("UPDATE vehicle_versions 
        SET status='inactive',deleted_at=NOW() 
        WHERE id=:id AND deleted_at IS NULL")->execute(['id'=>$id]); 
    }
}
