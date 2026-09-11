<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\JsonResponse;

/**
 * Cart Controller
 */
class CartController {
    private $view;

    public function __construct() {
        $this->view = new View();
    }

    public function index() {
        $cart = $_SESSION['cart'] ?? [];
        $this->view->assign('cart_items', $cart);
        $this->view->assign('page_title', 'Shopping Cart');
        $this->view->display('cart/index');
    }

    public function add() {
        $product_id = getParam('product_id');
        $quantity = (int) getParam('quantity', 1);

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }

        JsonResponse::success(['cart' => $_SESSION['cart']], 'Item added to cart');
    }

    public function remove() {
        $product_id = getParam('product_id');

        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }

        JsonResponse::success(['cart' => $_SESSION['cart']], 'Item removed from cart');
    }

    public function checkout() {
        $this->view->assign('page_title', 'Checkout');
        $this->view->display('cart/checkout');
    }
}
