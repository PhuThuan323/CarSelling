<?php
declare(strict_types=1);
namespace App\Controllers\Product;
use App\Core\JsonResponse;
use App\Models\Product\Brand;
use PDOException;
class AdminBrandApi {
    use CatalogAdminSupport;
    private Brand $brands;
    public function __construct(){
        $this->brands = new Brand();
    }
    public function brands()
    {
        return $this->view->render('admin/brands', [
            'page_title' => 'Quản lý hãng xe',
            'admin_section' => 'brands',
        ]);
    }
    public function index():void{
        $this->requireAdminApi();
        JsonResponse::success([
            'brands'=>$this->brands->all(true)],'Brands retrieved successfully',200);
    }
    public function show(string $id):void{
        $this->requireAdminApi();
        $x=$this->brands->findById((int)$id,true);
        if(!$x) JsonResponse::error('Brand not found',404);
        JsonResponse::success(['brand'=>$x],'Brand retrieved successfully',200);
    }

    public function store():void{
        $this->requireAdminApi();
        $d=$this->requestData();
        $name=trim((string)($d['name']??''));
        if($name==='') JsonResponse::error('Brand name is required',422);
        $d['name']=$name;
        $d['slug']=trim((string)($d['slug']??''))?:$this->slugify($name);
        $d['status']=in_array(($d['status']??'active'),['active','inactive'],true)?$d['status']:'active';
        try{
            $id=$this->brands->create($d);
        } catch(PDOException $e){
            if((string)$e->getCode()==='23000')
                JsonResponse::error('Brand name or slug already exists',409);
            throw $e;
        }
        if(!$id) JsonResponse::error('Unable to create brand',500);
        JsonResponse::success(['brand'=>$this->brands->findById((int)$id,true)],'Brand created successfully',201);
    }

    public function update(string $id):void{
        $this->requireAdminApi();
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
        if(isset($d['status']) && !in_array($d['status'],['active','inactive'],true))
            JsonResponse::error('Invalid status',422);
        try{
            $this->brands->update($id,$d);
        } catch(PDOException $e){
            if((string)$e->getCode()==='23000')
                JsonResponse::error('Brand name or slug already exists',409);
            throw $e;
        }
        JsonResponse::success(['brand'=>$this->brands->findById($id,true)],'Brand updated successfully',200);
    }

    public function destroy(string $id):void{
        $this->requireAdminApi();
        $id=(int)$id;
        if(!$this->brands->findById($id,true)) 
            JsonResponse::error('Brand not found',404);
        if($this->brands->hasModels($id))
            JsonResponse::error('Cannot delete brand because it still contains vehicle models',409);
        $this->brands->softDelete($id);
        JsonResponse::success([],'Brand deleted successfully',200);
    }
}
