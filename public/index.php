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
use App\Controllers\WebRender\AdminDashboard;
use App\Controllers\Product\AdminBrandApi;
use App\Controllers\Product\AdminModelApi;
use App\Controllers\Product\AdminVehicleVersionApi;
use App\Controllers\Product\PublicCatalog;

$router = new Router();
$router->get('/',Homepage::class,'index');

// Trang quản trị (chỉ tài khoản có role=admin trong bảng users)
$router->get('/admin', AdminDashboard::class, 'index');

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
$router->get('/auth/register', Register::class, 'signup');
$router->get('/auth/signup', Register::class, 'signup');
$router->post('/auth/register',Register::Class, "register");

// API danh mục xe cho khách hàng (chỉ trả dữ liệu đang active)
$router->get('/api/v1/brands', PublicCatalog::class, 'brands');
$router->get('/api/v1/brands/{id}', PublicCatalog::class, 'brand');
$router->get('/api/v1/brands/{id}/models', PublicCatalog::class, 'modelsByBrand');
$router->get('/api/v1/models/{id}', PublicCatalog::class, 'model');
$router->get('/api/v1/models/{id}/versions', PublicCatalog::class, 'versionsByModel');
$router->get('/api/v1/versions/{id}', PublicCatalog::class, 'version');

// API quản trị (bắt buộc role=admin)
$router->get('/api/v1/admin/brands', AdminBrandApi::class, 'index');
$router->post('/api/v1/admin/brands', AdminBrandApi::class, 'store');
$router->get('/api/v1/admin/brands/{id}', AdminBrandApi::class, 'show');
$router->put('/api/v1/admin/brands/{id}', AdminBrandApi::class, 'update');
$router->delete('/api/v1/admin/brands/{id}', AdminBrandApi::class, 'destroy');

$router->get('/api/v1/admin/models', AdminModelApi::class, 'index');
$router->post('/api/v1/admin/models', AdminModelApi::class, 'store');
$router->get('/api/v1/admin/models/{id}', AdminModelApi::class, 'show');
$router->put('/api/v1/admin/models/{id}', AdminModelApi::class, 'update');
$router->delete('/api/v1/admin/models/{id}', AdminModelApi::class, 'destroy');
$router->get('/api/v1/admin/brands/{id}/models', AdminModelApi::class, 'byBrand');

$router->get('/api/v1/admin/versions', AdminVehicleVersionApi::class, 'index');
$router->post('/api/v1/admin/versions', AdminVehicleVersionApi::class, 'store');
$router->get('/api/v1/admin/versions/{id}', AdminVehicleVersionApi::class, 'show');
$router->put('/api/v1/admin/versions/{id}', AdminVehicleVersionApi::class, 'update');
$router->delete('/api/v1/admin/versions/{id}', AdminVehicleVersionApi::class, 'destroy');
$router->get('/api/v1/admin/models/{id}/versions', AdminVehicleVersionApi::class, 'byModel');

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
