<?php
declare(strict_types=1);
namespace App\Controllers\Product\WebRender;
use App\Core\View;
class AdminCatalogPage { 
    private View $view; 
    public function __construct(){ 
        $this->view=new View(); 
    } 
    private function requireAdmin():void{
        if(empty($_SESSION['user_id'])){
            header('Location: /auth/login');
            exit;
        }
        $role=$_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? null;
        if($role!=='admin'){
            http_response_code(403);
            echo '403 - Forbidden';
            exit;
        }
    } 
    public function index():void{
        $this->requireAdmin();
        $this->view->assign('page_title','Catalog Management - CarSelling');
        $this->view->display('admin/product/catalog');
    } 
}

