<?php

namespace App\Controllers;

use App\Core\View;

/**
 * Home Controller
 */
class HomeController {
    private $view;

    public function __construct() {
        $this->view = new View();
    }

    public function index() {
        $this->view->assign('page_title', 'Welcome to PHP Smarty Shop');
        $this->view->assign('message', 'This is the home page');
        $this->view->display('home/index');
    }

    public function about() {
        $this->view->assign('page_title', 'About Us');
        $this->view->display('home/about');
    }

    public function contact() {
        $this->view->assign('page_title', 'Contact Us');
        $this->view->display('home/contact');
    }
}
