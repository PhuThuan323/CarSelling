<?php
namespace App\Controllers\Authentication;

use App\Models\UserManagement\PasswordReset;
use App\Models\UserManagement\User;
use App\Core\View;
use App\Core\Mailer;

class ResetPassword{
    private User $userModel;
    private View $view;
    private PasswordReset $passwordResetModel;
    public function __construct(){
        $this->userModel = new User();
        $this->passwordResetModel = new PasswordReset();
        $this->view = new View();
    }
    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function validateCsrfToken(string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // GET /auth/forgot-password
    public function forgotPassword():void{
        $this->view->assign('page_title','Reset Password - CarSelling');
        $this->view->assign('csrf_token',$this->generateCsrfToken());
        $this->view->assign('email','');
        if(!empty($_SESSION['forgot_message'])){
            $this->view->assign('message',$_SESSION['forgot_message']);
            unset($_SESSION['forgot_message']);
        }
        $this->view->display('auth/forgot-password');
    }

    //POST /auth/forgot-password
    public function forgotPasswordPost(): void{
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrfToken($csrfToken)) {
            $this->renderForgotPasswordError(
                'Invalid security token.'
            );
            return;
        }
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (
            $email === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $this->renderForgotPasswordError(
                'Please enter a valid email address.',
                $email
            );
            return;
        }
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            $_SESSION['forgot_message'] =
                'If this email exists, a reset code has been sent.';

            header('Location: /auth/forgot-password');
            exit;
        }

        $code = (string) random_int(100000, 999999);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + 600
        );

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

        $_SESSION['password_reset_user_id'] =
            (int) $user['id'];

        $_SESSION['password_reset_email'] =
            $user['email'];

        header('Location: /auth/verify-reset-code');
        exit;
    }

    // GET /auth/verify-reset-code
    public function verifyResetCode(): void
    {
        if (empty($_SESSION['password_reset_user_id'])) {
            header('Location: /auth/forgot-password');
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
            $_SESSION['password_reset_email'] ?? ''
        );

        $this->view->display(
            'auth/verify-reset-code'
        );
    }
    // POST /auth/verify-reset-code
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

        $userId = (int) (
            $_SESSION['password_reset_user_id'] ?? 0
        );

        if ($userId <= 0) {
            header('Location: /auth/forgot-password');
            exit;
        }

        $code = trim($_POST['code'] ?? '');

        if (!preg_match('/^\d{6}$/', $code)) {
            $this->renderVerifyCodeError(
                'Please enter the 6-digit code.'
            );
            return;
        }

        $reset = $this->passwordResetModel
            ->findLatestByUserId($userId);

        if (!$reset) {
            $this->renderVerifyCodeError(
                'Reset code does not exist.'
            );
            return;
        }

        if ((int) $reset['attempts'] >= 5) {
            $this->renderVerifyCodeError(
                'Too many incorrect attempts. Please request a new code.'
            );
            return;
        }

        if (
            strtotime($reset['expires_at']) < time()
        ) {
            $this->renderVerifyCodeError(
                'This reset code has expired.'
            );
            return;
        }

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

        $this->passwordResetModel
            ->markVerified(
                (int) $reset['id']
            );

        $_SESSION['password_reset_verified'] = true;
        $_SESSION['password_reset_verified_at'] = time();

        header('Location: /auth/reset-password');
        exit;
    }
    // GET /auth/reset-password
    public function resetPassword(): void
    {
        if (
            empty($_SESSION['password_reset_verified'])
        ) {
            header('Location: /auth/forgot-password');
            exit;
        }

        $verifiedAt = (int) (
            $_SESSION['password_reset_verified_at'] ?? 0
        );

        if (
            $verifiedAt <= 0 ||
            time() - $verifiedAt > 900
        ) {
            $this->clearPasswordResetSession();

            header('Location: /auth/forgot-password');
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

    // POST /auth/reset-password
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
            empty($_SESSION['password_reset_verified'])
        ) {
            header('Location: /auth/forgot-password');
            exit;
        }

        $verifiedAt = (int) (
            $_SESSION['password_reset_verified_at'] ?? 0
        );

        if (
            $verifiedAt <= 0 ||
            time() - $verifiedAt > 900
        ) {
            $this->clearPasswordResetSession();

            header('Location: /auth/forgot-password');
            exit;
        }

        $userId = (int) (
            $_SESSION['password_reset_user_id'] ?? 0
        );

        $password = $_POST['password'] ?? '';
        $confirmPassword =
            $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $this->renderResetPasswordError(
                'Password must be at least 8 characters.'
            );
            return;
        }

        if ($password !== $confirmPassword) {
            $this->renderResetPasswordError(
                'Password confirmation does not match.'
            );
            return;
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $updated = $this->userModel
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

        $this->passwordResetModel
            ->deleteByUserId($userId);

        $this->clearPasswordResetSession();

        $_SESSION['login_success_message'] =
            'Password changed successfully. Please sign in with your new password.';

        header('Location: /auth/login');
        exit;
    }

    private function renderForgotPasswordError(
        string $error,
        string $email = ''
    ): void {
        $this->view->assign(
            'page_title',
            'Reset Password - CarSelling'
        );

        $this->view->assign('error', $error);
        $this->view->assign('email', $email);

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

        $this->view->assign('error', $error);

        $this->view->assign(
            'email',
            $_SESSION['password_reset_email'] ?? ''
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

        $this->view->assign('error', $error);

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
            $_SESSION['password_reset_user_id'],
            $_SESSION['password_reset_email'],
            $_SESSION['password_reset_verified'],
            $_SESSION['password_reset_verified_at']
        );
    }


}