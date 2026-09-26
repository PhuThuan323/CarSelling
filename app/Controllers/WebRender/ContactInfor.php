<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\View;

class ContactInfor
{
    private View $view;

    public function __construct()
    {
        $this->view =
            new View();
    }

    public function index(
        string $id
    ): void {

        /*
        |--------------------------------------------------------------------------
        | AUTH
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $_SESSION['user_id']
            )
        ) {

            $_SESSION[
                'redirect_after_login'
            ] =
                '/sell-car-contact/'
                . urlencode($id);

            header(
                'Location: /auth/login'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | REQUEST ID
        |--------------------------------------------------------------------------
        */

        $valuationRequestId =
            (int) $id;


        if (
            $valuationRequestId <= 0
        ) {

            header(
                'Location: /sell-car'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | VIEW DATA
        |--------------------------------------------------------------------------
        */

        $this->view->assign(
            'page_title',
            'Thông tin liên hệ chủ xe'
        );


        $this->view->assign(
            'current_user',
            $_SESSION['user']
            ?? null
        );


        $this->view->assign(
            'valuation_request_id',
            $valuationRequestId
        );


        $this->view->display(
            'banxe/contact'
        );
    }
}