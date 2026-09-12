{extends file="layouts/auth.tpl"}

{block name="content"}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="login-container">

    <div class="auth-card" id="authCard">
        <div class="form-panel sign-in-panel">
            <div class="login-header">
                <h1>FastCar</h1>
                <p>Thanks for being our valued customer.</p>
            </div>

            {if isset($error)}
                <div class="alert alert-error">
                    {$error|escape}
                </div>
            {/if}

            <form class="login-form" method="POST" action="/auth/login" id="loginForm">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        placeholder="your@email.com"
                        class="form-input"
                        value="{$email}"
                        autocomplete="email"
                    >
                    <small class="error-message" id="email-error"></small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            placeholder="••••••••"
                            class="form-input"
                            autocomplete="current-password"
                        >
                        <button type="button" class="btn-toggle-password" data-target="password" aria-label="Toggle password visibility">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <small class="error-message" id="password-error"></small>
                </div>

                <div class="form-group checkbox">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="checkbox-input"
                        {if isset($remember) && $remember}checked{/if}
                    >
                    <label for="remember" class="checkbox-label">Remember me for 30 days</label>
                </div>

                <button type="submit" class="btn btn-primary btn-login" id="submitBtn">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-loader" style="display: none;">
                        <span class="spinner"></span> Signing in...
                    </span>
                </button>
            </form>

            <div class="login-links">
                <a href="/auth/forgot-password" class="link">Forgot Password?</a>
                <span class="divider">•</span>
                <a href="#" class="link" id="linkToSignUp">Create Account</a>
            </div>

            <div class="social-login">
                <p>Or sign in with</p>
                <div class="social-buttons">
                    <button type="button" class="btn-social btn-google" id="googleLogin">
                        <span class="icon">G</span> Google
                    </button>
                </div>
            </div>

            <div class="security-notice">
                <small>🔒 Your login is secure and encrypted</small>
            </div>
        </div>

        {* ================= SIGN UP PANEL ================= *}
        <div class="form-panel sign-up-panel" >
            {if isset($signup_error)}
                <div class="alert alert-error">
                    {$signup_error|escape}
                </div>
            {/if}

            <form class="login-form" method="POST" action="/auth/register" id="register">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        required
                        placeholder="Nguyen Van A"
                        class="form-input"
                        autocomplete="name"
                    >
                </div>

                <div class="form-group">
                    <label for="signup-email">Email Address</label>
                    <input
                        type="email"
                        id="signup-email"
                        name="email"
                        required
                        placeholder="your@email.com"
                        class="form-input"
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="signup-password"
                            name="password"
                            required
                            placeholder="••••••••"
                            class="form-input"
                            autocomplete="new-password"
                        >
                        <button type="button" class="btn-toggle-password" data-target="signup-password" aria-label="Toggle password visibility">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    {* Thanh kiểm tra độ mạnh mật khẩu *}
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <span class="strength-fill" id="strengthFill"></span>
                        </div>
                        <small class="strength-label" id="strengthLabel">&nbsp;</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="confirm-password"
                            name="confirm_password"
                            required
                            placeholder="••••••••"
                            class="form-input"
                            autocomplete="new-password"
                        >
                        <button type="button" class="btn-toggle-password" data-target="confirm-password" aria-label="Toggle password visibility">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <small class="error-message" id="confirm-password-error"></small>
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="terms" name="terms" class="checkbox-input" required>
                    <label for="terms" class="checkbox-label">I agree to the Terms &amp; Privacy Policy</label>
                </div>

                <button type="submit" class="btn btn-primary btn-login" id="registerBtn">
                    <span class="btn-text">Sign Up</span>
                    <span class="btn-loader" style="display: none;">
                        <span class="spinner"></span> Creating account...
                    </span>
                </button>
            </form>

            <div class="login-links">
                <span>Already have an account?</span>
                <a href="#" class="link" id="linkToSignIn">Sign In</a>
            </div>

            <div class="social-login">
                <p>Or sign up with</p>
                <div class="social-buttons">
                    <button type="button" class="btn-social btn-google" id="googleSignup">
                        <span class="icon">G</span> Google
                    </button>
                </div>
            </div>
        </div>

        {* ================= TOGGLE OVERLAY (chỉ hiện trên desktop) ================= *}
        <div class="toggle-overlay">
            <div class="toggle-overlay-inner">
                <div class="toggle-panel toggle-left">
                    <h2>Create Account</h2>
                    <p>Start your new journey with us.</p>
                    <button type="button" class="btn-ghost" id="showSignIn">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h2>Hello, Friend!</h2>
                    <p>Enter your details and start your journey with us.</p>
                    <button type="button" class="btn-ghost" id="showSignUp">Sign Up</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    var authCard = document.getElementById('authCard');
    var signInPanel = document.querySelector('.sign-in-panel');
    var signUpPanel = document.querySelector('.sign-up-panel');

    function syncHeight(panel) {
        if (!panel || window.innerWidth <= 768) {
            authCard.style.height = ''; 
            return;
        }
        authCard.style.height = panel.scrollHeight + 'px';
    }

    function activateSignUp() {
        authCard.classList.add('right-active');
        syncHeight(signUpPanel);
    }
    function activateSignIn() {
        authCard.classList.remove('right-active');
        syncHeight(signInPanel);
    }

    document.getElementById('showSignUp').addEventListener('click', activateSignUp);
    document.getElementById('showSignIn').addEventListener('click', activateSignIn);
    document.getElementById('linkToSignUp').addEventListener('click', function (e) {
        e.preventDefault();
        activateSignUp();
    });
    document.getElementById('linkToSignIn').addEventListener('click', function (e) {
        e.preventDefault();
        activateSignIn();
    });

    syncHeight(signInPanel);
    window.addEventListener('resize', function () {
        syncHeight(authCard.classList.contains('right-active') ? signUpPanel : signInPanel);
    });

    document.querySelectorAll('.btn-toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-target'));
            var icon = btn.querySelector('i');
            if (!target) return;
            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    var pwInput = document.getElementById('signup-password');
    var fill = document.getElementById('strengthFill');
    var label = document.getElementById('strengthLabel');

    function checkStrength(value) {
        var score = 0;
        if (value.length >= 8) score++;
        if (value.length >= 12) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        var levels = [
            { pct: 0,   text: '',            cls: '' },
            { pct: 20,  text: 'Rất yếu',      cls: 'weak' },
            { pct: 40,  text: 'Yếu',          cls: 'weak' },
            { pct: 60,  text: 'Trung bình',   cls: 'medium' },
            { pct: 80,  text: 'Mạnh',         cls: 'strong' },
            { pct: 100, text: 'Rất mạnh',     cls: 'strong' }
        ];
        var level = levels[Math.min(score, 5)];

        fill.style.width = level.pct + '%';
        fill.className = 'strength-fill ' + level.cls;
        label.textContent = value.length ? level.text : '\u00A0';
    }

    if (pwInput) {
        pwInput.addEventListener('input', function () {
            checkStrength(pwInput.value);
        });
    }

    var confirmInput = document.getElementById('confirm-password');
    var confirmError = document.getElementById('confirm-password-error');
    function checkMatch() {
        if (!confirmInput.value) {
            confirmError.style.display = 'none';
            return;
        }
        if (confirmInput.value !== pwInput.value) {
            confirmError.textContent = 'Mật khẩu xác nhận không khớp';
            confirmError.style.display = 'block';
        } else {
            confirmError.style.display = 'none';
        }
    }
    if (confirmInput) {
        confirmInput.addEventListener('input', checkMatch);
        pwInput.addEventListener('input', checkMatch);
    }
})();
</script>
{/block}
