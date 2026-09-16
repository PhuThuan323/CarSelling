<?php 
namespace App\Controllers\WebRender;
use App\Core\View;

class Homepage{
    private View $view; 
    public function __construct(){ 
        $this->view=new View(); 
    } 
    public function index():void{
        $this->view->assign('page_tilte','FastCar - Tìm chiếc xe phù hợp với bạn');
        $this->view->display('home/homepage');
    }
}