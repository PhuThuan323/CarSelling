<?php 
declare(strict_types=1);
namespace App\Controllers\Product;
use App\Core\JsonResponse;
use App\Models\Product\Brand;
use App\Models\Product\VehicleModel;
use App\Models\Product\VehicleVersion;

class PublicCatalog {
    private Brand $brand;
    private VehicleModel $model;
    private VehicleVersion $version;
    public function __construct(){
        $this->brand = new Brand();
        $this->model = new VehicleModel();
        $this->version = new VehicleVersion();
    }
    public function brands():void{
        JsonResponse::success([
            'brands'=>$this->brand->all(false)
        ], "Brands Retrieved Successfully", 200);
    }
    public function brand(string $id):void{
        $d = $this->brand->findById((int)$id,false);
        if(!$d){
            JsonResponse::error('Brand not found', 404);
            return;
        }
        JsonResponse::success(['brand'=>$d], 'Brand Retrieved Sucessfully', 200);
    }
    public function modelsByBrand(string $brandId):void{
        if(!$this->brand->findById((int)$brandId,false)){
            JsonResponse::error('Brand not found',404);
            return;
        }
        JsonResponse::success(['models'=>$this->model->byBrand((int)$brandId,false)],
        'Models retrieved successfully',200);
    }
    public function model(string $id):void{
        $x=$this->model->findById((int)$id,false);
        if(!$x){
            JsonResponse::error('Model not found',404);
            return;
        }
        JsonResponse::success(['model'=>$x],'Model retrieved successfully',200);
    }
    public function versionsByModel(string $modelId):void{
        if(!$this->model->findById((int)$modelId,false)){
            JsonResponse::error('Model not found',404);
            return;
        }
        JsonResponse::success(['versions'=>$this->version->byModel((int)$modelId,false)],'Vehicle versions retrieved successfully',200);
    }
    public function version(string $id):void{
        $x=$this->version->findById((int)$id,false);
        if(!$x){
            JsonResponse::error('Vehicle version not found',404);
            return;
        }
        JsonResponse::success(['version'=>$x],'Vehicle version retrieved successfully',200);
    }

}