<?php
declare(strict_types=1);
namespace App\Controllers\Product;
use App\Core\JsonResponse;
use App\Models\Product\Brand;
use PDOException;
class AdminBrand {
    private Brand $brand; 
    public function __construct(){ 
        $this->brand =new Brand(); 
    }

    private function requireAdmin(): void {
        if(empty($_SESSION['user_id'])) JsonResponse::error('Unauthenticated',401);
        $role=$_SESSION['user']['role']??$_SESSION['user_role']??null;
        if($role!=='admin') JsonResponse::error('Forbidden',403);
    }

    private function requestData(): array {
        $ct=$_SERVER['CONTENT_TYPE']??'';
        if(str_contains($ct,'application/json')){ 
            $d = json_decode(file_get_contents('php://input'),true); 
            return is_array($d)?$d:[]; 
        }
        if(($_SERVER['REQUEST_METHOD']??'')==='POST') return $_POST;
        $raw=file_get_contents('php://input');$d=[];
        parse_str($raw,$d);
        return $d;
    }
    
    private function slugify(string $v): string {
        $v=trim(mb_strtolower($v,'UTF-8'));
        if(function_exists('iconv')){ 
            $x=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$v); 
            if($x!==false)$v=$x; 
        }
        $v=preg_replace('/[^a-z0-9]+/','-',$v)??'';
        return trim($v,'-');
    }

    public function index():void{
        $this->requireAdmin();
        JsonResponse::success([
            'brands'=>$this->brands->all(true)],'Brands retrieved successfully',200);
        }
    public function show(string $id):void{
        $this->requireAdmin();
        $x=$this->brands->findById((int)$id,true);
        if(!$x) JsonResponse::error('Brand not found',404);
        JsonResponse::success(['brand'=>$x],'Brand retrieved successfully',200);
    }

    public function store():void{
        $this->requireAdmin();
        $d=$this->requestData();
        $name=trim((string)($d['name']??''));
        if($name==='') JsonResponse::error('Brand name is required',422);
        $d['name']=$name;
        $d['slug']=trim((string)($d['slug']??''))?:$this->slugify($name);
        $d['status']=$d['status']??'active';
        try{
            $id=$this->brand->create($d);
        } catch(PDOException $e){
            if((string)$e->getCode()==='23000') 
                JsonResponse::error('Brand name or slug already exists',409);
            throw $e;
        }
        if(!$id) JsonResponse::error('Unable to create brand',500);
        JsonResponse::success(['brand'=>$this->brands->findById((int)$id,true)],'Brand created successfully',201);
    }

    public function update(string $id):void{
        $this->requireAdmin();
        $id=(int)$id;
        if(!$this->brands->findById($id,true))
            JsonResponse::error('Brand not found',404);
        $d=$this->requestData();
        if(isset($d['name'])){
            $d['name']=trim((string)$d['name']);
            if($d['name']==='') 
                JsonResponse::error('Brand name cannot be empty',422);
            if(empty($d['slug'])) 
                $d['slug']=$this->slugify($d['name']);
        }
        $this->brand->update($id,$d);
        JsonResponse::success(['brand'=>$this->brands->findById($id,true)],'Brand updated successfully',200);
    }

    public function destroy(string $id):void{
        $this->requireAdmin();
        $id=(int)$id;
        if(!$this->brands->findById($id,true)) 
            JsonResponse::error('Brand not found',404);
        if($this->brands->hasModels($id))
            JsonResponse::error('Cannot delete brand because it still contains vehicle models',409);
        $this->brands->softDelete($id);
        JsonResponse::success([],'Brand deleted successfully',200);
    }
}
