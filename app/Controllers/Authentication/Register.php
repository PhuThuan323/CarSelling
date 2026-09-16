<?php

namespace App\Controllers\Authentication;

use App\Models\UserManagement\User;
use App\Core\View;

class Register
{
    private User $userModel;
    private View $view;

    public function __construct()
    {
        $this->userModel = new User();
        $this->view = new View();
    }
    //Hàm tạo token cho người dùng 
    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
    //Hàm xác nhận token cho người dùng 
    private function validateCsrfToken(string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // Tùy chọn: GET /auth/signup - hàm render giao diện 
    // Vẫn dùng giao diện auth/login hiện tại nhưng mở panel Sign Up.
    public function signup(): void
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        $this->view->assign('page_title', 'Sign Up - CarSelling');
        $this->view->assign('csrf_token', $this->generateCsrfToken());
        $this->view->assign('email', '');
        $this->view->assign('remember', false);
        $this->view->assign('signup_name', '');
        $this->view->assign('signup_email', '');
        $this->view->assign('show_signup', true);

        $this->view->display('auth/login');
    }

    // Hàm gửi thông tin đăng ký
    // POST /auth/register
    public function register(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: /auth/login');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!$this->validateCsrfToken($csrfToken)) {
            $this->renderRegisterError(
                'Invalid security token. Please try again.'
            );
            return;
        }

        $name = trim($_POST['fullname'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);

        if ($name === '') {
            $this->renderRegisterError(
                'Please enter your full name.',
                $name,
                $email
            );
            return;
        }

        if ($email === '') {
            $this->renderRegisterError(
                'Please enter your email.',
                $name,
                $email
            );
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderRegisterError(
                'Invalid email address.',
                $name,
                $email
            );
            return;
        }

        if ($password === '') {
            $this->renderRegisterError(
                'Please enter your password.',
                $name,
                $email
            );
            return;
        }

        if (strlen($password) < 8) {
            $this->renderRegisterError(
                'Password must be at least 8 characters.',
                $name,
                $email
            );
            return;
        }

        if ($password !== $confirmPassword) {
            $this->renderRegisterError(
                'Password confirmation does not match.',
                $name,
                $email
            );
            return;
        }

        if (!$terms) {
            $this->renderRegisterError(
                'Please agree to the Terms & Privacy Policy.',
                $name,
                $email
            );
            return;
        }

        $existingUser = $this->userModel->findByEmail($email);

        if ($existingUser) {
            $this->renderRegisterError(
                'Email is already registered.',
                $name,
                $email
            );
            return;
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'role' => 'customer',
            'status' => 'active',
        ]);

        if (!$userId) {
            $this->renderRegisterError(
                'Unable to create account. Please try again.',
                $name,
                $email
            );
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $userId;
        $_SESSION['user'] = [
            'id' => (int) $userId,
            'name' => $name,
            'email' => $email,
            'role' => 'customer',
        ];

        header('Location: /');
        exit;
    }
    // Hàm reder lỗi đăng ký
    private function renderRegisterError(
        string $message,
        string $name = '',
        string $email = ''
    ): void {
        $this->view->assign(
            'page_title',
            'Register - CarSelling'
        );

        $this->view->assign(
            'signup_error',
            $message
        );

        $this->view->assign(
            'signup_name',
            $name
        );

        $this->view->assign(
            'signup_email',
            $email
        );

        $this->view->assign(
            'csrf_token',
            $this->generateCsrfToken()
        );

        $this->view->assign('email', '');
        $this->view->assign('remember', false);
        $this->view->assign('show_signup', true);

        $this->view->display('auth/login');
    }
}
