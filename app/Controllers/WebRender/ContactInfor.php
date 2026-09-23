<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\View;

class ContactInfor
{
    private View $view;
    public function __construct()
    {
        $this->view = new View();
    }

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = '/sell-car';
            header('Location: /auth/login');
            exit;
        }
        $this->view->assign('page_title','Thông tin liên hệ chủ xe');
        $this->view->assign('current_user',$_SESSION['user'] ?? null
        );
        $this->view->display('banxe/contact');
    }
}