<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\JsonResponse;
use App\Models\Product;

/**
 * Product Controller
 */
class ProductController {
    private $view;
    private $product;

    public function __construct() {
        $this->view = new View();
        $this->product = new Product();
    }

    public function index() {
        $products = $this->product->getAll();
        $this->view->assign('products', $products);
        $this->view->assign('page_title', 'Products');
        $this->view->display('products/index');
    }

    public function show($id) {
        $product = $this->product->getById($id);
        if (!$product) {
            http_response_code(404);
            $this->view->display('errors/404');
            return;
        }
        $this->view->assign('product', $product);
        $this->view->assign('page_title', $product['name']);
        $this->view->display('products/show');
    }

    public function search() {
        $term = getParam('q', '');
        $results = $this->product->search($term);
        JsonResponse::success($results, 'Search results');
    }

    public function api() {
        $products = $this->product->getAll();
        JsonResponse::success($products, 'Products retrieved');
    }
}
