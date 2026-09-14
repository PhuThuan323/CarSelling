<?php

namespace App\Controllers\Authentication;

use App\Models\UserManagement\User;
use App\Core\JsonResponse;
use App\Core\View;

class Login
{
    private User $userModel;
    private View $view;

    public function __construct()
    {
        $this->userModel = new User();
        $this->view = new View();
    }

    // Hàm nhận dữ liệu từ người smarty
    private function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);

            return is_array($data) ? $data : [];
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            return $_POST;
        }

        $raw = file_get_contents('php://input');
        $data = [];
        parse_str($raw, $data);

        return $data;
    }
    //Hàm tạo token cho người dùng
    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
    //Hàm xác nhận token của người dùng
    private function validateCsrfToken(string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
    //Hàm xử lý đăng nhập sai
    private function handleLoginError(
        string $message,
        string $email = '',
        bool $remember = false
    ): void {
        $this->view->assign('page_title', 'Login - CarSelling');
        $this->view->assign('error', $message);
        $this->view->assign('email', $email);
        $this->view->assign('remember', $remember);
        $this->view->assign('csrf_token', $this->generateCsrfToken());

        $this->view->display('auth/login');
    }

    //Hàm hiển thị trang đăng nhập 
    // GET /auth/login
    public function login(): void
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /auth/success');
            exit;
        }

        $this->view->assign('page_title', 'Login - CarSelling');
        $this->view->assign('csrf_token', $this->generateCsrfToken());
        $this->view->assign('email', '');
        $this->view->assign('remember', false);

        if (!empty($_SESSION['login_success_message'])) {
            $this->view->assign(
                'success',
                $_SESSION['login_success_message']
            );

            unset($_SESSION['login_success_message']);
        }

        $this->view->display('auth/login');
    }

    // Hàm gọi api đăng nhập
    // POST /auth/login
    public function loginPost(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!$this->validateCsrfToken($csrfToken)) {
            $this->handleLoginError(
                'Invalid security token. Please try again.',
                trim($_POST['email'] ?? ''),
                isset($_POST['remember'])
            );
            return;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if ($email === '' || $password === '') {
            $this->handleLoginError(
                'Please enter both email and password.',
                $email,
                $remember
            );
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->handleLoginError(
                'Please enter a valid email address.',
                $email,
                $remember
            );
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if (
            !$user ||
            !password_verify($password, $user['password'])
        ) {
            $this->handleLoginError(
                'Email or password is incorrect.',
                $email,
                $remember
            );
            return;
        }

        if (($user['status'] ?? '') !== 'active') {
            $this->handleLoginError(
                'Your account is not active.',
                $email,
                $remember
            );
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];

        if ($remember) {
            $_SESSION['remember_requested'] = true;
        }

        header('Location: /auth/success');
        exit;
    }

    // POST /api/auth/login
    public function userLogin(): void
    {
        $data = $this->getRequestData();

        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            JsonResponse::error(
                'Email and password are required',
                400
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            JsonResponse::error(
                'Invalid email address',
                422
            );
        }

        $user = $this->userModel->findByEmail($email);

        if (
            !$user ||
            !password_verify($password, $user['password'])
        ) {
            JsonResponse::error(
                'Email or Password is not correct.',
                401
            );
        }

        if (($user['status'] ?? '') !== 'active') {
            JsonResponse::error(
                'Your account is not active',
                403
            );
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];

        $safeUser = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'status' => $user['status'],
        ];

        JsonResponse::success(
            [
                'user' => $safeUser,
            ],
            'Login Successful',
            200
        );
    }

    // Hàm chuyển đến trang sau khi đăng nhập thành công 
    // GET /auth/success
    public function success(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        $user = $_SESSION['user'] ?? [];

        echo '<h1>Login Successful ✅</h1>';

        echo '<p>Welcome '
            . htmlspecialchars(
                $user['name'] ?? 'User',
                ENT_QUOTES,
                'UTF-8'
            )
            . '</p>';

        echo '<p>Email: '
            . htmlspecialchars(
                $user['email'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            )
            . '</p>';

        echo '<p><a href="/auth/logout">Logout</a></p>';
    }
}
