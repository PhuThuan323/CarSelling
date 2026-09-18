<?php

namespace App\Controllers\Authentication;

use App\Models\UserManagement\User;
use App\Core\JsonResponse;
use App\Core\View;
use App\Core\Auth;

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
    //Hàm tạo token cho người dùng (dùng chung Auth)
    private function generateCsrfToken(): string
    {
        return Auth::csrfToken();
    }
    //Hàm xác nhận token của người dùng (dùng chung Auth)
    private function validateCsrfToken(string $token): bool
    {
        return Auth::validateCsrfToken($token);
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

    // Shared credential check: returns the active user, or null plus a reason.
    private function verifyCredentials(
        string $email,
        string $password,
        string &$error = null
    ): ?array {
        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            $error = 'missing';
            return null;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'invalid_email';
            return null;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'bad_credentials';
            return null;
        }

        if (($user['status'] ?? '') !== 'active') {
            $error = 'inactive';
            return null;
        }

        return $user;
    }

    // Shared session hydration for every successful login path.
    private function establishSession(array $user): array
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'customer',
        ];

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $_SESSION['user'];
    }

    private function redirectPathFor(array $user): string
    {
        return ($user['role'] ?? '') === 'admin' ? '/admin' : '/';
    }

    // Hàm gọi api đăng nhập
    // POST /auth/login
    public function loginPost(): void
    {
        $remember = isset($_POST['remember']);

        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!$this->validateCsrfToken($csrfToken)) {
            $this->handleLoginError(
                'Invalid security token. Please try again.',
                trim($_POST['email'] ?? ''),
                $remember
            );
            return;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $user = $this->verifyCredentials($email, $password, $error);

        if (!$user) {
            $this->handleLoginError(
                $this->errorMessageFor($error),
                $email,
                $remember
            );
            return;
        }

        $this->establishSession($user);

        if ($remember) {
            $_SESSION['remember_requested'] = true;
        }

        header('Location: ' . $this->redirectPathFor($user));
        exit;
    }

    // Maps a credential failure reason to the user-facing message.
    private function errorMessageFor(?string $reason): string
    {
        switch ($reason) {
            case 'missing':
                return 'Please enter both email and password.';
            case 'invalid_email':
                return 'Please enter a valid email address.';
            case 'inactive':
                return 'Your account is not active.';
            default:
                return 'Email or password is incorrect.';
        }
    }

    // POST /api/auth/login
    public function userLogin(): void
    {
        $data = $this->getRequestData();

        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        $user = $this->verifyCredentials($email, $password, $error);

        if (!$user) {
            JsonResponse::error(
                $this->apiMessageFor($error),
                $this->apiStatusFor($error)
            );
        }

        $safeUser = $this->establishSession($user);
        $safeUser['status'] = $user['status'];

        JsonResponse::success(
            [
                'user' => $safeUser,
                'redirect' => $this->redirectPathFor($user),
                'csrf_token' => Auth::csrfToken(),
            ],
            'Login Successful',
            200
        );
    }

    // Maps a credential failure reason to the API message.
    private function apiMessageFor(?string $reason): string
    {
        switch ($reason) {
            case 'missing':
                return 'Email and password are required';
            case 'invalid_email':
                return 'Invalid email address';
            case 'inactive':
                return 'Your account is not active';
            default:
                return 'Email or Password is not correct.';
        }
    }

    // Maps a credential failure reason to the API status code.
    private function apiStatusFor(?string $reason): int
    {
        switch ($reason) {
            case 'missing':
                return 400;
            case 'invalid_email':
                return 422;
            case 'inactive':
                return 403;
            default:
                return 401;
        }
    }

    // Hàm chuyển đến trang sau khi đăng nhập thành công
    // GET /auth/success
    public function success(): void
    {
        $user = Auth::user();
        header('Location: ' . (!$user ? '/auth/login' : ($user['role'] === 'admin' ? '/admin' : '/')));
        exit;
    }
}
