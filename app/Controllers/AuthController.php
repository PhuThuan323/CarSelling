<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PasswordReset;

use App\Core\JsonResponse;
use App\Core\View;
use App\Core\Mailer;

class AuthController
{ 
    private User $userModel;
    private View $view;
    private PasswordReset $passwordResetModel;

    private function getRequestData(): array{
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
    private function generateCsrfToken(): string
    {
        if (
            empty($_SESSION['csrf_token'])
        ) {
            $_SESSION['csrf_token'] =
                bin2hex(
                    random_bytes(32)
                );
        }

        return $_SESSION['csrf_token'];
    }
    private function validateCsrfToken(string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'],$token);
    }
    private function handleLoginError(string $message,string $email = '',bool $remember = false): void {

        $this->view->assign(
            'page_title',
            'Login - CarSelling'
        );

        $this->view->assign(
            'error',
            $message
        );

        $this->view->assign(
            'email',
            $email
        );

        $this->view->assign(
            'remember',
            $remember
        );

        $this->view->assign(
            'csrf_token',
            $this->generateCsrfToken()
        );

        $this->view->display(
            'auth/login'
        );
    }
    public function __construct()
    {
        $this->userModel = new User();
        $this->view = new View();
        $this->passwordResetModel = new PasswordReset();
    }

    // Hiển thị trang đăng nhập + đăng ký
    public function login(): void
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /auth/success');
            exit;
        }

        $csrfToken = $this->generateCsrfToken();

        $this->view->assign('page_title', 'Login - CarSelling');
        $this->view->assign('csrf_token', $csrfToken);
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

    // SSR Login POST /auth/login
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

        if (!$user || !password_verify($password, $user['password'])) {
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

        // TODO: triển khai Remember Me bằng token riêng trong DB.
        // Tuyệt đối không lưu password trong cookie.
        if ($remember) {
            $_SESSION['remember_requested'] = true;
        }

        header('Location: /auth/success');
        exit;
    }

    //API Login POST api/auth/login
     public function userLogin(): void
    {
        $data = $this->getRequestData();

        $email = strtolower(
            trim($data['email'] ?? '')
        );

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
            !password_verify(
                $password,
                $user['password']
            )
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
            'status' => $user['status']
        ];

        JsonResponse::success(
            [
                'user' => $safeUser
            ],
            'Login Successful',
            200
        );
    }
    //Success Page
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
                $user['name'] ?? 'User'
            )
            . '</p>';

        echo '<p>Email: '
            . htmlspecialchars(
                $user['email'] ?? ''
            )
            . '</p>';

        echo '<p><a href="/auth/logout">Logout</a></p>';
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: /auth/login');
        exit;
    }

    public function register(): void
    {
        $data = $this->getRequestData();
        $name = trim($data['fullname'] ?? $data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            JsonResponse::error('Name, email and password are required.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            JsonResponse::error('Invalid email address.', 422);
        }

        if (strlen($password) < 8) {
            JsonResponse::error('Password must be at least 8 characters.', 422);
        }

        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            JsonResponse::error('Password confirmation does not match.', 422);
        }

        $existingUser = $this->userModel->findByEmail($email);

        if ($existingUser) {
            JsonResponse::error(
                'Email is already registered. Please use a different email.',
                409
            );
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'status' => 'active',
        ]);

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $userId;

        $user = $this->userModel->findById((int) $userId);

        JsonResponse::success(
            [
                'user' => $user,
                'message' => 'Registration successful. Welcome, ' . htmlspecialchars($name) . '!',
            ],
            'Registration successful',
            201
        );
    }

    // Mở form đặt lại mật khẩu
    public function forgotPassword(): void
    {
        $this->view->assign(
            'page_title',
            'Reset Password - CarSelling'
        );

        $this->view->assign(
            'csrf_token',
            $this->generateCsrfToken()
        );

        $this->view->assign(
            'email',
            ''
        );

        if (!empty($_SESSION['forgot_message'])) {
            $this->view->assign(
                'message',
                $_SESSION['forgot_message']
            );
            unset($_SESSION['forgot_message']);
        }

        $this->view->display('auth/forgot-password');
    }

    /**
     * Process forgot password request
     * POST /auth/forgot-password
     */
    public function forgotPasswordPost(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!$this->validateCsrfToken($csrfToken)) {
            $this->renderForgotPasswordError(
                'Invalid security token.'
            );
            return;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderForgotPasswordError(
                'Please enter a valid email address.',
                $email
            );
            return;
        }

        $user = $this->userModel->findByEmail($email);

        /*
         * Không tiết lộ email có tồn tại trong hệ thống hay không.
         * Nếu không tồn tại, vẫn hiển thị thông báo chung.
         */
        if (!$user) {
            $_SESSION['forgot_message'] =
                'If this email exists, a reset code has been sent.';

            header('Location: /auth/forgot-password');
            exit;
        }

        $code = (string) random_int(100000, 999999);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600);

        $this->passwordResetModel->deleteByUserId(
            (int) $user['id']
        );

        $created = $this->passwordResetModel->create(
            (int) $user['id'],
            $codeHash,
            $expiresAt
        );

        if (!$created) {
            $this->renderForgotPasswordError(
                'Unable to create password reset request.',
                $email
            );
            return;
        }

        $sent = Mailer::sendPasswordResetCode(
            $user['email'],
            $user['name'],
            $code
        );

        if (!$sent) {
            $this->passwordResetModel->deleteByUserId(
                (int) $user['id']
            );

            $this->renderForgotPasswordError(
                'Unable to send email. Please try again.',
                $email
            );
            return;
        }

        $_SESSION['password_reset_user_id'] = (int) $user['id'];
        $_SESSION['password_reset_email'] = $user['email'];

        header('Location: /auth/verify-reset-code');
        exit;
    }

    public function verifyResetCode(): void
    {
        if (
            empty(
                $_SESSION['password_reset_user_id']
            )
        ) {

            header(
                'Location: /auth/forgot-password'
            );

            exit;
        }

        $this->view->assign(
            'page_title',
            'Verify Reset Code'
        );

        $this->view->assign(
            'csrf_token',
            $this->generateCsrfToken()
        );

        $this->view->assign(
            'email',
            $_SESSION['password_reset_email']
            ?? ''
        );

        $this->view->display(
            'auth/verify-reset-code'
        );
    }
    public function verifyResetCodePost(): void
    {
        if (
            !$this->validateCsrfToken(
                $_POST['csrf_token'] ?? ''
            )
        ) {

            $this->renderVerifyCodeError(
                'Invalid security token.'
            );

            return;
        }

        $userId =
            (int) (
                $_SESSION[
                    'password_reset_user_id'
                ] ?? 0
            );

        if ($userId <= 0) {

            header(
                'Location: /auth/forgot-password'
            );

            exit;
        }

        $code =
            trim($_POST['code'] ?? '');

        if (
            !preg_match(
                '/^\d{6}$/',
                $code
            )
        ) {

            $this->renderVerifyCodeError(
                'Please enter the 6-digit code.'
            );

            return;
        }

        $reset =
            $this->passwordResetModel
                ->findLatestByUserId(
                    $userId
                );

        if (!$reset) {

            $this->renderVerifyCodeError(
                'Reset code does not exist.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Maximum attempts
        |--------------------------------------------------------------------------
        */

        if (
            (int) $reset['attempts'] >= 5
        ) {

            $this->renderVerifyCodeError(
                'Too many incorrect attempts. Please request a new code.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Expired
        |--------------------------------------------------------------------------
        */

        if (
            strtotime(
                $reset['expires_at']
            ) < time()
        ) {

            $this->renderVerifyCodeError(
                'This reset code has expired.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verify
        |--------------------------------------------------------------------------
        */

        if (
            !password_verify(
                $code,
                $reset['code_hash']
            )
        ) {

            $this->passwordResetModel
                ->incrementAttempts(
                    (int) $reset['id']
                );

            $this->renderVerifyCodeError(
                'Verification code is incorrect.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verified
        |--------------------------------------------------------------------------
        */

        $this->passwordResetModel
            ->markVerified(
                (int) $reset['id']
            );

        $_SESSION['password_reset_verified'] =
            true;

        $_SESSION['password_reset_verified_at'] =
            time();

        header(
            'Location: /auth/reset-password'
        );

        exit;
    }
    public function resetPasswordPost(): void
    {
        if (
            !$this->validateCsrfToken(
                $_POST['csrf_token'] ?? ''
            )
        ) {

            $this->renderResetPasswordError(
                'Invalid security token.'
            );

            return;
        }

        if (
            empty(
                $_SESSION[
                    'password_reset_verified'
                ]
            )
        ) {
            header('Location: /auth/forgot-password');
            exit;
        }

        $verifiedAt = (int) (
            $_SESSION['password_reset_verified_at'] ?? 0
        );

        if ($verifiedAt <= 0 || time() - $verifiedAt > 900) {
            $this->clearPasswordResetSession();
            header('Location: /auth/forgot-password');
            exit;
        }

        $userId =
            (int) (
                $_SESSION[
                    'password_reset_user_id'
                ] ?? 0
            );

        $password =
            $_POST['password'] ?? '';

        $confirmPassword =
            $_POST['confirm_password'] ?? '';

        if (
            strlen($password) < 8
        ) {

            $this->renderResetPasswordError(
                'Password must be at least 8 characters.'
            );

            return;
        }

        if (
            $password !==
            $confirmPassword
        ) {

            $this->renderResetPasswordError(
                'Password confirmation does not match.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Hash NEW PASSWORD
        |--------------------------------------------------------------------------
        */

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        /*
        |--------------------------------------------------------------------------
        | Update users table
        |--------------------------------------------------------------------------
        */

        $updated =
            $this->userModel
                ->updatePassword(
                    $userId,
                    $passwordHash
                );

        if (!$updated) {

            $this->renderResetPasswordError(
                'Unable to update password.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Delete reset code
        |--------------------------------------------------------------------------
        */

        $this->passwordResetModel
            ->deleteByUserId(
                $userId
            );

        /*
        |--------------------------------------------------------------------------
        | Clear reset session
        |--------------------------------------------------------------------------
        */

        $this->clearPasswordResetSession();

        /*
        |--------------------------------------------------------------------------
        | Success message
        |--------------------------------------------------------------------------
        */

        $_SESSION['login_success_message'] =
            'Password changed successfully. Please sign in with your new password.';

        /*
        |--------------------------------------------------------------------------
        | Return to login
        |--------------------------------------------------------------------------
        */

        header(
            'Location: /auth/login'
        );

        exit;
    }
    public function resetPassword(): void
    {
        if (
            empty(
                $_SESSION[
                    'password_reset_verified'
                ]
            )
        ) {

            header(
                'Location: /auth/forgot-password'
            );

            exit;
        }

        /*
        | Chỉ cho reset trong 15 phút
        */

        $verifiedAt =
            $_SESSION[
                'password_reset_verified_at'
            ] ?? 0;

        if (
            time() - $verifiedAt > 900
        ) {

            $this->clearPasswordResetSession();

            header(
                'Location: /auth/forgot-password'
            );

            exit;
        }

        $this->view->assign(
            'page_title',
            'Create New Password'
        );

        $this->view->assign(
            'csrf_token',
            $this->generateCsrfToken()
        );

        $this->view->display(
            'auth/reset-password'
        );
    }



//    
//    

    
    /**
     * Check if user is logged in
     * @return bool
     */
    private function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

//     /**
//      * Handle login error - redirect back with error message
//      * @param string $message
//      */
//     private function handleLoginError(string $message): void
//     {
//         $email = $_POST['email'] ?? '';
//         header("Location: /auth/login?error=" . urlencode($message) . "&email=" . urlencode($email));
//         exit;
//     }

//     /**
//      * Handle API error response
//      * @param string $message
//      * @param int $code
//      */
//     private function handleApiError(string $message, int $code = 400): void
//     {
//         header('Content-Type: application/json');
//         http_response_code($code);
//         JsonResponse::error($message, $code);
//         exit;
//     }

//     /**
//      * Validate CSRF token
//      * @param string $token
//      * @return bool
//      */
//     private function validateCsrfToken(string $token): bool
//     {
//         // TODO: Implement CSRF token validation
//         // For now, always return true
//         return true;
//     }
private function renderForgotPasswordError(
    string $error,
    string $email = ''
): void {

    $this->view->assign(
        'page_title',
        'Reset Password - CarSelling'
    );

    $this->view->assign(
        'error',
        $error
    );

    $this->view->assign(
        'email',
        $email
    );

    $this->view->assign(
        'csrf_token',
        $this->generateCsrfToken()
    );

    $this->view->display(
        'auth/forgot-password'
    );
}
private function renderVerifyCodeError(
    string $error
): void {

    $this->view->assign(
        'page_title',
        'Verify Reset Code'
    );

    $this->view->assign(
        'error',
        $error
    );

    $this->view->assign(
        'email',
        $_SESSION[
            'password_reset_email'
        ] ?? ''
    );

    $this->view->assign(
        'csrf_token',
        $this->generateCsrfToken()
    );

    $this->view->display(
        'auth/verify-reset-code'
    );
}
private function renderResetPasswordError(
    string $error
): void {

    $this->view->assign(
        'page_title',
        'Create New Password'
    );

    $this->view->assign(
        'error',
        $error
    );

    $this->view->assign(
        'csrf_token',
        $this->generateCsrfToken()
    );

    $this->view->display(
        'auth/reset-password'
    );
}
private function clearPasswordResetSession(): void
{
    unset(
        $_SESSION[
            'password_reset_user_id'
        ],
        $_SESSION[
            'password_reset_email'
        ],
        $_SESSION[
            'password_reset_verified'
        ],
        $_SESSION[
            'password_reset_verified_at'
        ]
    );
}

}
