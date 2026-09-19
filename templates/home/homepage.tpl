

{block name="content"}
<link rel="stylesheet" href="../../assets/css/homepage.css">
{include file="home/topbar.tpl"}
<main class="home-page">
    
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-copy">
                <span class="hero-kicker">FASTCAR</span>
                <h1> Bắt đầu hành trình mới với chiếc xe yêu thích của bạn</h1>
                <p>Quy trình mua xe rõ ràng, nhanh chóng, minh bạch và phù hợp với nhu cầu của bạn. Với đội ngũ chuyên gia và là đối tác hàng đầu của các hãng xe trên thế giới.</p>

                <div class="brand-picker-card">
                    <div class="brand-picker-title">Chọn hãng xe để tìm chiếc xe yêu thích của chính mình</div>
                    <div class="brand-picker" id="brandPicker" aria-live="polite">
                        <div class="brand-loading">Đang tải hãng xe...</div>
                    </div>
                    <div class="model-quick-list" id="modelQuickList"></div>
                </div>
            </div>

        <div class="hero-stats">

            <div class="hero-stat">
                <strong>7.000+</strong>
                <span>người bán xe mỗi tháng</span>
            </div>

            <div class="hero-stat">
                <strong>3 ngày</strong>
                <span>thời gian chốt xe phổ biến</span>
            </div>

            <div class="hero-stat">
                <strong>+5%</strong>
                <span>giá cao hơn thị trường</span>
            </div>

            <div class="hero-stat">
                <strong>200.000+</strong>
                <span>lượt truy cập mỗi tháng</span>
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

{include file="home/footer.tpl"}
{/block}

