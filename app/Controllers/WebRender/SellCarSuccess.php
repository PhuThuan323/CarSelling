<?php
declare (strict_types=1);
namespace App\Controllers\WebRender;

use App\Core\View;
class SellCarSuccess{
    private View $view;
    public function __construct(){
        $this->view = new View();
    }
    public function index(string $id): void{
        if(empty($_SESSION['user_id'])){
            header('Location: /auth/login');
            exit;
        }
        $requestId = (int)  $id;
        if($requestId <=0){
            header('Location: /');
            exit;
        }
        $this->view->assign('page_title',"Đăng ký bán xe thành công");
        $this->view->assign('valuation_request_id',$requestId);
        $this->view->display('banxe/success');
    }
}