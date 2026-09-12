<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

use App\Core\Router;
use App\Controllers\AuthController;

$router = new Router();
$router->get('/',AuthController::class,'login');
// Authentication SSR
$router->get('/auth/login', AuthController::class, 'login'); 
$router->post('/auth/login', AuthController::Class, 'loginPost');
$router->get('/auth/success',AuthController::Class,'success'); 
$router->get('/auth/logout', AuthController::Class,"logout");


//Password
$router->post('/auth/forgot-password', AuthController::Class, "forgotPasswordPost");
$router->get('/auth/forgot-password',AuthController::Class,'forgotPassword');
$router->get('/auth/verify-reset-code', AuthController::Class,'verifyResetCode');
$router->post('/auth/verify-reset-code', AuthController::Class, 'verifyResetCodePost');
$router->get('/auth/reset-password',AuthController::Class,'resetPassword');
$router->post('/auth/reset-password',AuthController::Class,'resetPasswordPost');

$router->post('/auth/register',AuthController::Class, "register");

//Authentication API 
$router->post('/api/auth/login', AuthController::class, 'userLogin'); 


// Parse URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

try {
    $router->dispatch($path, $method);
} catch (\Throwable $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
