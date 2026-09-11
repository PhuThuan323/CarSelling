<?php

// Start session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Import core classes
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\CartController;

// Create router
$router = new Router();

// Define routes
$router->get('/', HomeController::class, 'index');
$router->get('/about', HomeController::class, 'about');
$router->get('/contact', HomeController::class, 'contact');

$router->get('/products', ProductController::class, 'index');
$router->get('/products/api', ProductController::class, 'api');
$router->get('/products/search', ProductController::class, 'search');

$router->get('/cart', CartController::class, 'index');
$router->post('/cart/add', CartController::class, 'add');
$router->post('/cart/remove', CartController::class, 'remove');
$router->get('/cart/checkout', CartController::class, 'checkout');

// Parse URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Dispatch request
try {
    $router->dispatch($path, $method);
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
