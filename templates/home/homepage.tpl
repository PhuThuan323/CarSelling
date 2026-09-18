

{block name="content"}
<link rel="stylesheet" href="../../assets/css/homepage.css">

<main class="home-page">
    <section class="topbar-section">
        <div class="topbar-inner">
        <div class="logo"></div>
        <div class="redirect-url">
            <href>Cách Thức Hoạt Động</href>
            <href>Mua Xe</href>
            <href>Tài Khoản</href>
        </div>
        <div class="user-management">
            {if isset($current_user) && $current_user}
                <a class="admin-entry-link" href="{if $current_user.role eq 'admin'}/admin{else}/{/if}">
                    Xin chào, {$current_user.name|escape}
                </a>
                <div class="user-settings" id="userSettings">
                    <button type="button" class="user-settings-btn" id="userSettingsToggle" aria-label="Cài đặt tài khoản" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-gear"></i>
                    </button>
                    <div class="user-settings-menu" id="userSettingsMenu">
                        {if $current_user.role eq 'admin'}
                            <a href="/admin"><i class="fa-solid fa-gauge"></i> Trang quản trị</a>
                        {/if}
                        <a href="/"><i class="fa-solid fa-user"></i> Tài khoản của tôi</a>
                        <a href="/auth/logout"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
                    </div>
                </div>
            {else}
                <a class="user-management-btn" href="/auth/login">Đăng nhập</a>
                <a class="user-management-btn user-management-btn-primary" href="/auth/register">Đăng ký</a>
            {/if}
        </div>
        </div>
    </section>
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-copy">
                <span class="hero-kicker">FASTCAR</span>
                <h1>Bắt đầu hành trình mới<br>với chiếc xe yêu thích của bạn</h1>
                <p>Quy trình mua xe rõ ràng, nhanh chóng, minh bạch và phù hợp với nhu cầu của bạn.</p>

                <div class="brand-picker-card">
                    <div class="brand-picker-title">Chọn hãng xe để tìm chiếc xe yêu thích của chính mình</div>
                    <div class="brand-picker" id="brandPicker" aria-live="polite">
                        <div class="brand-loading">Đang tải hãng xe...</div>
                    </div>
                    <div class="model-quick-list" id="modelQuickList"></div>
                    <div class="brand-picker-footer"><span id="brandCountText">Đang tải dữ liệu...</span></div>
                </div>
            </div>

            <div class="hero-car-wrap">
                <div class="hero-glow"></div>
            </div>
        </div>
    </section>

    <section class="section-container featured-section">
        <div class="section-heading">
            <h2>Xe nổi bật</h2>
            <a href="/cars" class="section-link">Xem tất cả xe →</a>
        </div>
        <div class="vehicle-grid" id="featuredVehicleGrid"></div>
    </section>

    <section class="reviews-section">
        <div class="section-container">
            <div class="reviews-title-wrap">
                <h2>Khách hàng thật, xe thật, giá thật</h2>
                <p>Những trải nghiệm thực tế giúp khách hàng dễ dàng đưa ra lựa chọn phù hợp hơn.</p>
            </div>
            <div class="review-grid" id="reviewGrid"></div>
        </div>
    </section>
</main>

<script src="/assets/js/homepage.js"></script>
<script>
(function () {
    var toggle = document.getElementById('userSettingsToggle');
    var menu = document.getElementById('userSettingsMenu');
    var wrap = document.getElementById('userSettings');

    if (!toggle || !menu || !wrap) return;

    function setOpen(open) {
        wrap.classList.toggle('is-open', open);
        toggle.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        setOpen(!wrap.classList.contains('is-open'));
    });

    document.addEventListener('click', function (event) {
        if (!wrap.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
})();
</script>
{/block}
