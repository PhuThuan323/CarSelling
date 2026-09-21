<?php

namespace App\Controllers;

use App\Core\View;

class PolicyController
{
    private View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public function show(): void
    {
        $name = trim($_GET['name'] ?? '');


        $policies = [

            'chinh-sach-bao-mat' => [
                'title' => 'Chính sách bảo mật thông tin cá nhân',
                'file'  => 'chinh-sach-bao-mat.txt',
            ],

            'chinh-sach-ho-tro-khach-hang' => [
                'title' => 'Chính sách hỗ trợ khách hàng',
                'file'  => 'chinh-sach-ho-tro-khach-hang.txt',
            ],

            'chinh-sach-hau-mai' => [
                'title' => 'Chính sách hậu mãi',
                'file'  => 'chinh-sach-hau-mai.txt',
            ],

            'chinh-sach-thanh-toan' => [
                'title' => 'Chính sách thanh toán',
                'file'  => 'chinh-sach-thanh-toan.txt',
            ],

            'quy-che-hoat-dong' => [
                'title' => 'Quy chế hoạt động',
                'file'  => 'quy-che-hoat-dong.txt',
            ],

        ];

        if (
            $name === ''
            ||
            !isset($policies[$name])
        ) {
            http_response_code(404);

            echo 'Không tìm thấy chính sách.';

            return;
        }


        $policy = $policies[$name];

        $filePath =
            BASE_PATH
            . DIRECTORY_SEPARATOR
            . 'policy'
            . DIRECTORY_SEPARATOR
            . $policy['file'];


        if (!is_file($filePath)) {

            http_response_code(404);

            echo 'Không tìm thấy file chính sách: '
                . htmlspecialchars(
                    $policy['file'],
                    ENT_QUOTES,
                    'UTF-8'
                );

            return;
        }


        $content = file_get_contents($filePath);


        if ($content === false) {

            http_response_code(500);

            echo 'Không thể đọc nội dung chính sách.';

            return;
        }


        $this->view->assign(
            'policy_title',
            $policy['title']
        );

        $this->view->assign(
            'policy_content',
            $content
        );



        $this->view->display(
            'policy/show'
        );
    }
}