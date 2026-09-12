

class LoginVideoManager {
    constructor() {
        this.form = document.getElementById('loginForm');
        this.init();
    }
    
    /**
     * Setup password visibility toggle
     */
    setupPasswordToggle() {
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (!toggleBtn || !passwordInput) return;

        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();

            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';

            // Update button icon
            toggleBtn.textContent = isPassword ? '🙈' : '👁️';
            toggleBtn.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );
        });
    }

    /**
     * Setup form validation
     */
    setupFormValidation() {
        if (!this.form) return;

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const submitBtn = document.getElementById('submitBtn');

        // Real-time validation on blur
        if (emailInput) {
            emailInput.addEventListener('blur', () => {
                this.validateEmail(emailInput);
            });

            // Remove error on focus
            emailInput.addEventListener('focus', () => {
                emailInput.classList.remove('error');
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('blur', () => {
                this.validatePassword(passwordInput);
            });

            passwordInput.addEventListener('focus', () => {
                passwordInput.classList.remove('error');
            });
        }

        // Form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailValid = this.validateEmail(emailInput);
            const passwordValid = this.validatePassword(passwordInput);
            if(!emailValid||!passwordValid){
                e.preventDefault(); return;
            }
            this.showLoading(submitBtn)
        });
    }

    /**
     * Validate email format
     * @param {HTMLElement} input
     * @returns {boolean}
     */
    validateEmail(input) {
        const email = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(email);

        const errorEl = document.getElementById('email-error');

        if (!isValid && email.length > 0 ) {
            input.classList.add('error');
            if (errorEl) {
                errorEl.textContent = email.includes('@')
                    ? 'Please enter a valid email address'
                    : 'Email must include @ symbol';
            }
            return false;
        } else {
            input.classList.remove('error');
            if (errorEl) {
                errorEl.textContent = '';
            }
            return true;
        }
    }

    /**
     * Validate password
     * @param {HTMLElement} input
     * @returns {boolean}
     */
    validatePassword(input) {
        const password = input.value;
        const minLength = 6;
        const isValid = password.length >= minLength;

        const errorEl = document.getElementById('password-error');

        if (!isValid && password.length > 0) {
            input.classList.add('error');
            if (errorEl) {
                errorEl.textContent = `Password must be at least ${minLength} characters`;
            }
            return false;
        } else {
            input.classList.remove('error');
            if (errorEl) {
                errorEl.textContent = '';
            }
            return true;
        }
    }

    /**
     * Submit form with loading state
     * @param {HTMLElement} submitBtn
     */
     showLoading(submitBtn) {

    console.log(
        '📤 Submitting login form...'
    );

    const btnText =
        submitBtn.querySelector(
            '.btn-text'
        );

    const btnLoader =
        submitBtn.querySelector(
            '.btn-loader'
        );

    if (btnText) {
        btnText.style.display =
            'none';
    }

    if (btnLoader) {
        btnLoader.style.display =
            'flex';
    }
}

    /**
     * Monitor performance metrics
     */
    monitorPerformance() {
        if (!window.performance || !window.performance.timing) {
            console.warn('⚠️  Performance API not available');
            return;
        }

        window.addEventListener('load', () => {
            const perfData = window.performance.timing;
            const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
            const domContentLoaded =
                perfData.domContentLoadedEventEnd - perfData.navigationStart;

            console.log('⏱️  Performance Metrics:');
            console.log(`   Total Load Time: ${pageLoadTime}ms`);
            console.log(`   DOM Content Loaded: ${domContentLoaded}ms`);

            // Get resource timings
            const resources = performance.getEntriesByType('resource');
            console.log(`   Resources Loaded: ${resources.length}`);

            // Calculate total transfer size
            let totalSize = 0;
            resources.forEach(resource => {
                if (resource.transferSize) {
                    totalSize += resource.transferSize;
                }
            });
            console.log(`   Total Transfer Size: ${(totalSize / 1024).toFixed(2)}KB`);

            // Warning if load time is high
            if (pageLoadTime > 3000) {
                console.warn(
                    `⚠️  Page load time is high (${pageLoadTime}ms). Consider optimizing video size.`
                );
            }
        });

        // Log video metrics when available
        if (this.video) {
            this.video.addEventListener('loadedmetadata', () => {
                const duration = this.video.duration;
                console.log(`📹 Video metadata loaded (Duration: ${duration.toFixed(2)}s)`);
            });

            this.video.addEventListener('progress', () => {
                if (this.video.buffered.length > 0) {
                    const bufferedEnd = this.video.buffered.end(
                        this.video.buffered.length - 1
                    );
                    const percent = (bufferedEnd / this.video.duration) * 100;
                    if (percent === 100) {
                        console.log('📹 Video fully buffered');
                    }
                }
            });
        }
    }
}



// Optional: Setup social login handlers (if needed)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupSocialLoginHandlers();
    });
} else {
    setupSocialLoginHandlers();
}

/**
 * Setup social login button handlers
 */
function setupSocialLoginHandlers() {
    const googleBtn = document.getElementById('googleLogin');
    const githubBtn = document.getElementById('githubLogin');

    if (googleBtn) {
        googleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('🔴 Google login clicked');
            alert('Google login not yet implemented');
        });
    }


}

