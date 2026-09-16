<?php
declare(strict_types=1);
namespace App\Controllers\Product;
use App\Core\JsonResponse;
use App\Models\Product\VehicleModel;
use App\Models\Product\VehicleVersion;
class AdminVehicleVersionApi {
    private VehicleVersion $versions; 
    private VehicleModel $models; 
    public function __construct(){ 
        $this->versions=new VehicleVersion();
        $this->models=new VehicleModel(); 
    }

    private function requireAdmin(): void {
        if(empty($_SESSION['user_id'])) JsonResponse::error('Unauthenticated',401);
        $role=$_SESSION['user']['role']??$_SESSION['user_role']??null;
        if($role!=='admin') JsonResponse::error('Forbidden',403);
    }
    private function requestData(): array {
        $ct=$_SERVER['CONTENT_TYPE']??'';
        if(str_contains($ct,'application/json')){ $d=json_decode(file_get_contents('php://input'),true); return is_array($d)?$d:[]; }
        if(($_SERVER['REQUEST_METHOD']??'')==='POST') return $_POST;
        $raw=file_get_contents('php://input');$d=[];parse_str($raw,$d);return $d;
    }
    private function slugify(string $v): string {
        $v=trim(mb_strtolower($v,'UTF-8'));
        if(function_exists('iconv')){ $x=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$v); if($x!==false)$v=$x; }
        $v=preg_replace('/[^a-z0-9]+/','-',$v)??'';return trim($v,'-');
    }

    public function index():void{
        $this->requireAdmin();
        JsonResponse::success(['versions'=>$this->versions->all(true)],'Vehicle versions retrieved successfully',200);
    }
    public function show(string $id):void{
        $this->requireAdmin();
        $x=$this->versions->findById((int)$id,true);
        if(!$x) JsonResponse::error('Vehicle version not found',404);
        JsonResponse::success(['version'=>$x],'Vehicle version retrieved successfully',200);
    }
    public function byModel(string $modelId):void{
        $this->requireAdmin();
        if(!$this->models->findById((int)$modelId,true)) 
            JsonResponse::error('Model not found',404);
        JsonResponse::success(['versions'=>$this->versions->byModel((int)$modelId,true)],'Vehicle versions retrieved successfully',200);
    }
    public function store():void{
        $this->requireAdmin();
        $d=$this->requestData();
        $modelId=(int)($d['model_id']??0);
        $name=trim((string)($d['name']??''));
        if(!$this->models->findById($modelId,true))
            JsonResponse::error('Valid model_id is required',422);
        if($name==='')
            JsonResponse::error('Vehicle version name is required',422);
        $d['model_id']=$modelId;
        $d['name']=$name;
        $d['slug']=trim((string)($d['slug']??''))?:$this->slugify($name);
        $id=$this->versions->create($d);
        if(!$id) JsonResponse::error('Unable to create vehicle version',500);
        JsonResponse::success(['version'=>$this->versions->findById((int)$id,true)],'Vehicle version created successfully',201);
    }

    public function update(string $id):void{
        $this->requireAdmin();
        $id=(int)$id;
        if(!$this->versions->findById($id,true))
            JsonResponse::error('Vehicle version not found',404);
        $d=$this->requestData();
        if(isset($d['model_id']) && !$this->models->findById((int)$d['model_id'],true))
            JsonResponse::error('Model not found',422);
        if(isset($d['name']) && empty($d['slug'])) 
            $d['slug']=$this->slugify((string)$d['name']);
        $this->versions->update($id,$d);
        JsonResponse::success(['version'=>$this->versions->findById($id,true)],'Vehicle version updated successfully',200);
    }
    public function destroy(string $id):void{
        $this->requireAdmin();
        $id=(int)$id;
        if(!$this->versions->findById($id,true)) 
            JsonResponse::error('Vehicle version not found',404);
        this->versions->softDelete($id);
        JsonResponse::success([],'Vehicle version deleted successfully',200);
    }
}
