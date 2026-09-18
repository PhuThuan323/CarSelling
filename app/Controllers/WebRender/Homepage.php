<?php 
namespace App\Controllers\WebRender;
use App\Core\Auth;
use App\Core\View;

class Homepage{
    private View $view;
    public function __construct(){
        $this->view=new View();
    }
    public function index():void{
        $this->view->assign('page_title','FastCar - Tìm chiếc xe phù hợp với bạn');
        // Truyền thông tin người đang đăng nhập (nếu có) để header hiển thị đúng.
        $this->view->assign('current_user', Auth::user());
        $this->view->display('home/homepage');
    }
}