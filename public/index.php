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
use App\Controllers\Product\AdminBrandApi;
use App\Controllers\Product\AdminModelApi;
use App\Controllers\Product\AdminVehicleVersionApi;
use App\Controllers\Product\PublicCatalog;
use App\Controllers\Product\AdminMedia;
use App\Controllers\Valuation\ValuationContactController;
use App\Controllers\Valuation\ValuationController;

use App\Controllers\Inspection\AdminInspectionController;
use App\Controllers\Inspection\CustomerOfferController;
use App\Controllers\Inspection\StaffInspectionController;

use App\Controllers\WebRender\SellCarSuccess;
use App\Controllers\WebRender\SellCar;
use App\Controllers\WebRender\Homepage;
use App\Controllers\WebRender\AdminDashboard;
use App\Controllers\WebRender\AdminInspection;
use App\Controllers\WebRender\ContactInfor;
use App\Controllers\WebRender\MySellingCar;
use App\Controllers\WebRender\StaffInspection;
use App\Controllers\WebRender\AdminRequests;
use App\Controllers\WebRender\AdminStaff;
use App\Controllers\WebRender\AdminFeedback;
use App\Controllers\WebRender\CustomerStory;
use App\Controllers\UserManagement\AdminStaffController;
use App\Controllers\Feedback\FeedbackController;

use App\Controllers\Auction\AuctionApi;
use App\Controllers\Auction\AdminAuctionApi;
use App\Controllers\WebRender\AuctionPage;

$router = new Router();
$router->get('/',Homepage::class,'index');

// Trang quản trị (chỉ tài khoản có role=admin trong bảng users)
$router->get('/admin', AdminDashboard::class, 'index');

// Danh sách toàn bộ hồ sơ bán xe
$router->get('/admin/requests', AdminRequests::class, 'index');

// Quản lý nhân sự & phân quyền
$router->get('/admin/staff', AdminStaff::class, 'index');

// Câu chuyện khách hàng (testimonial) - lưu file JSON, không dùng database
$router->get('/admin/feedback', AdminFeedback::class, 'index');
$router->get('/api/v1/admin/feedback-stories', FeedbackController::class, 'index');
$router->post('/api/v1/admin/feedback-stories', FeedbackController::class, 'store');
$router->delete('/api/v1/admin/feedback-stories/{id}', FeedbackController::class, 'destroy');

// Trang khách hàng xem câu chuyện
$router->get('/customer-stories', CustomerStory::class, 'index');
$router->get('/customer-stories/{slug}', CustomerStory::class, 'detail');

// Khu vực Inspection của Admin
$router->get('/admin/inspections', AdminInspection::class, 'index');
$router->get('/admin/inspections/review', AdminInspection::class, 'review');
$router->get('/admin/inspections/{id}', AdminInspection::class, 'detail');

// Khu vực Inspection của Staff (nhân viên thẩm định)
$router->get('/staff/inspections', StaffInspection::class, 'index');
$router->get('/staff/inspections/{id}', StaffInspection::class, 'detail');

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
$router->post('/api/v1/admin/uploads/brand-logo',AdminMedia::class,'uploadBrandLogo');

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

//Xem các chính sách
$router->get('/policy',App\Controllers\PolicyController::class,'show');

// APi để đánh giá tình trạng xe khi bán

$router->post('/api/v1/valuations/create',ValuationController::class,'createDraft');
$router->get('/api/v1/valuations/detail',ValuationController::class,'detail');
$router->post('/api/v1/valuations/images/upload',ValuationController::class,'uploadImage');
$router->post('/api/v1/valuations/images/replace',ValuationController::class,'replaceImage');
$router->post('/api/v1/valuations/images/delete',ValuationController::class,'deleteImage');
$router->post('/api/v1/valuations/submit',ValuationController::class,'submit');
$router->get('/sell-car',SellCar::class,'index');
$router->get('/sell-car-contact/{id}',ContactInfor::class,'index');
$router->post('/api/v1/valuations/complete-photos',ValuationController::class,'completePhotos');
$router->post('/api/v1/valuations/contact',ValuationContactController::class,'save');
$router->get('/sell-car-success/{id}',SellCarSuccess::class,'index');
$router->get('/my-selling-cars',MySellingCar::class,'index');
$router->get('/my-selling-cars/{id}',MySellingCar::class,'detail');

// ===== Đấu giá xe =====
// Trang khách hàng
$router->get('/cars', AuctionPage::class, 'index');
$router->get('/cars/{id}', AuctionPage::class, 'detail');
$router->get('/my-auctions', AuctionPage::class, 'myAuctions');

// API đấu giá (khách hàng)
$router->get('/api/v1/auctions', AuctionApi::class, 'index');
$router->get('/api/v1/auctions/{id}', AuctionApi::class, 'show');
$router->post('/api/v1/auctions/{id}/bid', AuctionApi::class, 'bid');
$router->get('/api/v1/my-auctions', AuctionApi::class, 'myAuctions');
$router->post('/api/v1/auctions/{id}/pay', AuctionApi::class, 'markReadyToPay');

// Khu vực quản trị đấu giá (admin)
$router->get('/admin/auctions', App\Controllers\WebRender\AdminAuction::class, 'index');
$router->get('/api/v1/admin/auctions', AdminAuctionApi::class, 'index');
$router->post('/api/v1/admin/auctions', AdminAuctionApi::class, 'store');
$router->get('/api/v1/admin/auctions/{id}', AdminAuctionApi::class, 'show');
$router->post('/api/v1/admin/auctions/{id}/payment', AdminAuctionApi::class, 'updatePayment');
$router->post('/api/v1/admin/auctions/{id}/cancel', AdminAuctionApi::class, 'cancel');

// Khách hàng phản hồi kết quả định giá
$router->post('/api/v1/valuations/accept-offer',CustomerOfferController::class,'accept');
$router->post('/api/v1/valuations/decline-offer',CustomerOfferController::class,'decline');

// API workflow inspection (admin)
$router->get('/api/v1/admin/inspections',AdminInspectionController::class,'index');
$router->get('/api/v1/admin/inspections/{id}',AdminInspectionController::class,'show');
$router->post('/api/v1/admin/inspections/{id}/request',AdminInspectionController::class,'requestInspection');
$router->post('/api/v1/admin/inspections/{id}/assign',AdminInspectionController::class,'assignStaff');
$router->post('/api/v1/admin/inspections/{id}/approve',AdminInspectionController::class,'approve');

// API quản lý nhân sự & phân quyền (admin)
$router->get('/api/v1/admin/users',AdminStaffController::class,'index');
$router->post('/api/v1/admin/users/{id}/role',AdminStaffController::class,'updateRole');
$router->post('/api/v1/admin/users/{id}/status',AdminStaffController::class,'updateStatus');

// API workflow inspection (staff)
$router->get('/api/v1/staff/inspections',StaffInspectionController::class,'index');
$router->get('/api/v1/staff/inspections/{id}',StaffInspectionController::class,'show');
$router->post('/api/v1/staff/inspections/{id}/accept',StaffInspectionController::class,'accept');
$router->post('/api/v1/staff/inspections/{id}/start',StaffInspectionController::class,'start');
$router->post('/api/v1/staff/inspections/{id}/submit',StaffInspectionController::class,'submit');

// Parse URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

try {
    $router->dispatch($path, $method);
} catch (\Throwable $e) {
    http_response_code(500);

    $isApi =
        str_starts_with(
            $path,
            '/api/'
        );

    if ($isApi) {
        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            [
                'success' => false,

                'message' =>
                    $e->getMessage(),

                'debug' => [
                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),
                ],
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    echo '<pre>';
    echo 'Error: '
        . htmlspecialchars(
            $e->getMessage()
        );

    echo "\n\n";

    echo 'File: '
        . htmlspecialchars(
            $e->getFile()
        );

    echo "\n";

    echo 'Line: '
        . $e->getLine();

    echo '</pre>';
}
