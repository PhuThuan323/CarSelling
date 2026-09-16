<?php
declare(strict_types=1);
namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;

class AdminDashboard
{
    private View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public function index(): void
    {
        $user = Auth::requireAdmin(false);
        $this->view->assign('page_title', 'Bảng điều khiển quản trị - CarSelling');
        $this->view->assign('admin_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->display('admin/dashboard');
    }
}
