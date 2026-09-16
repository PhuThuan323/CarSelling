<?php
declare(strict_types=1);
namespace App\Controllers\Product;
use App\Core\JsonResponse;
use App\Models\Product\Brand;
use App\Models\Product\VehicleModel;
class AdminModelApi {
    use CatalogAdminSupport;
    private VehicleModel $models;
    private Brand $brand;
    public function __construct(){
        $this->models=new VehicleModel();
        $this->brand=new Brand();
    }

    public function index():void{
        $this->requireAdminApi();
        JsonResponse::success(['models'=>$this->models->all(true)],'Models retrieved successfully',200);
    }

    public function show(string $id):void{
        $this->requireAdminApi();
        $x=$this->models->findById((int)$id,true);
        if(!$x)JsonResponse::error('Model not found',404);
        JsonResponse::success(['model'=>$x],'Model retrieved successfully',200);
    }

    public function byBrand(string $brandId):void{
        $this->requireAdminApi();
        if(!$this->brand->findById((int)$brandId,true))
            JsonResponse::error('Brand not found',404);
        JsonResponse::success(['models'=>$this->models->byBrand((int)$brandId,true)],'Models retrieved successfully',200);
    }

    public function store():void{
        $this->requireAdminApi();
        $d=$this->requestData();
        $brandId=(int)($d['brand_id']??0);
        $name=trim((string)($d['name']??''));
        if(!$this->brand->findById($brandId,true))
            JsonResponse::error('Valid brand_id is required',422);
        if($name==='')JsonResponse::error('Model name is required',422);
        $d['brand_id']=$brandId;
        $d['name']=$name;
        $d['slug']=trim((string)($d['slug']??''))?:$this->slugify($name);
        $id=$this->models->create($d);
        if(!$id)
            JsonResponse::error('Unable to create model',500);
        JsonResponse::success(['model'=>$this->models->findById((int)$id,true)],'Model created successfully',201);
    }
    public function update(string $id):void{
        $this->requireAdminApi();
        $id=(int)$id;
        if(!$this->models->findById($id,true))
            JsonResponse::error('Model not found',404);
        $d=$this->requestData();
        if(isset($d['brand_id']) && !$this->brand->findById((int)$d['brand_id'],true))
            JsonResponse::error('Brand not found',422);
        if(isset($d['name']) && empty($d['slug']))$d['slug']=$this->slugify((string)$d['name']);
        $this->models->update($id,$d);
        JsonResponse::success(['model'=>$this->models->findById($id,true)],'Model updated successfully',200);
    }

    public function destroy(string $id):void{
        $this->requireAdminApi();
        $id=(int)$id;
        if(!$this->models->findById($id,true))
            JsonResponse::error('Model not found',404);
        if($this->models->hasVersions($id))
            JsonResponse::error('Cannot delete model because it still contains vehicle versions',409);
        $this->models->softDelete($id);
        JsonResponse::success([],'Model deleted successfully',200);
    }
}
