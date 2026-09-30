<?php 
namespace App\Controllers\WebRender;
use App\Core\Auth;
use App\Core\View;
use App\Models\Feedback\FeedbackStory;

class Homepage{
    private View $view;
    private FeedbackStory $stories;
    public function __construct(){
        $this->view=new View();
        $this->stories=new FeedbackStory();
    }
    public function index():void{
        $this->view->assign('page_title','FastCar - Tìm chiếc xe phù hợp với bạn');
        // Truyền thông tin người đang đăng nhập (nếu có) để header hiển thị đúng.
        $this->view->assign('current_user', Auth::user());
        // Câu chuyện khách hàng do admin quản lý (lưu file JSON, không cần database).
        $this->view->assign('stories', $this->stories->featured(4));
        $this->view->display('home/homepage');
    }
}