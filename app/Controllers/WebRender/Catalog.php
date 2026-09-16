<?php
declare(strict_types=1);
namespace App\Controllers\Product\WebRender;
use App\Core\View;
class CatalogPage { 
    private View $view; 
    public function __construct(){ 
        $this->view= new View(); 
    } 
    public function index():void{
        $this->view->assign('page_title','Vehicle Catalog - CarSelling');
        $this->view->display('product/catalog');
    } 
}
