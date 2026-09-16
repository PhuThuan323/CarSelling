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
use App\Controllers\Authentication\Login;
use App\Controllers\Authentication\Logout;
use App\Controllers\Authentication\Register;
use App\Controllers\Authentication\ResetPassword;
use App\Controllers\WebRender\Homepage;

$router = new Router();
$router->get('/',Homepage::class,'index');

// Đăng nhập    
$router->get('/auth/login', Login::class, 'login'); 
$router->post('/auth/login', Login::Class, 'loginPost');
$router->get('/auth/success', Login::Class,'success'); 
$router->post('/api/auth/login', Login::Class, 'userLogin'); 

//Đăng xuất
$router->get('/auth/logout', Logout::Class,"logout");


//Quên mật khẩu - Xác nhận mã OTP - Thay đổi mật khẩu
$router->post('/auth/forgot-password', ResetPassword::Class, "forgotPasswordPost");
$router->get('/auth/forgot-password',ResetPassword::Class,'forgotPassword');
$router->get('/auth/verify-reset-code', ResetPassword::Class,'verifyResetCode');
$router->post('/auth/verify-reset-code', ResetPassword::Class, 'verifyResetCodePost');
$router->get('/auth/reset-password',ResetPassword::Class,'resetPassword');
$router->post('/auth/reset-password',ResetPassword::Class,'resetPasswordPost');

//Đăng ký tài khoản
$router->post('/auth/register',Register::Class, "register");

// Parse URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

try {
    $router->dispatch($path, $method);
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<pre>';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo "\n\n";
    echo 'File: ' . htmlspecialchars($e->getFile());
    echo "\n";
    echo 'Line: ' . $e->getLine();
    echo '</pre>';
}
