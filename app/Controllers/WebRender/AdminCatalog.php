<?php
declare(strict_types=1);
namespace App\Controllers\Product\WebRender;
use App\Core\View;
class AdminCatalogPage { 
    private View $view; 
    public function __construct(){ 
        $this->view=new View(); 
    } 
    public function index():void{
        \App\Core\Auth::requireAdmin(false);
        $this->view->assign('page_title','Catalog Management - CarSelling');
        $this->view->display('admin/product/catalog');
    } 
}

