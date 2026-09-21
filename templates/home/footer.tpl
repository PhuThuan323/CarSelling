<link rel="stylesheet" href="../assets/css/footer.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<footer class="site-footer">

    <div class="footer-container">

        <!-- TOP BRAND -->
        <div class="footer-brand-row">

            <div class="footer-brand">
                <a href="/" class="footer-logo">
                    <span class="footer-logo-mark">🚘</span>
                    <span class="footer-logo-text">FASTCAR</span>
                </a>

                <span class="footer-tagline">
                    Nền tảng mua bán xe minh bạch
                </span>
            </div>

        </div>


        <!-- PARTNER -->
        <div class="footer-partner">

            <div class="footer-partner-info">
                <div class="footer-partner-icon">
                    <i class="fa-solid fa-handshake"></i>
                </div>

                <div>
                    <strong>Hợp tác cùng đối tác quốc tế</strong>

                    <p>
                        Kết nối hệ sinh thái ô tô, phụ tùng
                        và dữ liệu thị trường.
                    </p>
                </div>
            </div>

            <div class="footer-partner-logo">
                ĐỐI TÁC
            </div>

        </div>


        <!-- ACCOUNT -->
        <div class="footer-account">

            <strong>Tài khoản</strong>

            {if isset($current_user) && $current_user}

                <a href="/" class="footer-account-btn">
                    <i class="fa-regular fa-user"></i>
                    {$current_user.name|escape}
                </a>

            {else}

                <a href="/auth/register" class="footer-account-btn">
                    <i class="fa-regular fa-user"></i>
                    Tạo tài khoản
                </a>

            {/if}

        </div>


        <!-- MAIN COLUMNS -->
        <div class="footer-grid">

            <!-- DỊCH VỤ -->
            <div class="footer-column">

                <h3>Dịch vụ</h3>

                <a href="/cars">
                    Mua xe
                </a>

                <a href="#">
                    Bán xe
                </a>

                <a href="#">
                    FAQ
                </a>

                <a href="#">
                    Quy trình người bán
                </a>

                <a href="#">
                    Quy trình người mua
                </a>

                <a href="#">
                    Bảng giá
                </a>

            </div>


            <!-- ĐƯỢC QUAN TÂM -->
            <div class="footer-column">

                <h3>Được quan tâm</h3>

                <a href="/cars">
                    Bán xe giá tốt
                </a>

                <a href="#">
                    Sự kiện ưu đãi
                </a>

                <a href="#">
                    Dành cho Người mua
                </a>

                <a href="#">
                    Dành cho Người bán
                </a>

                <a href="#">
                    Liên hệ
                </a>

            </div>


            <!-- CHÍNH SÁCH -->
            <div class="footer-column">

                <h3>Chính sách</h3>

                <a href="/policy?name=chinh-sach-bao-mat">
                    Chính sách bảo mật thông tin cá nhân
                </a>

                <a href="/policy?name=chinh-sach-ho-tro-khach-hang">
                    Chính sách hỗ trợ khách hàng
                </a>

                <a href="/policy?name=chinh-sach-hau-mai">
                    Chính sách hậu mãi
                </a>

                <a href="/policy?name=chinh-sach-thanh-toan">
                    Chính sách thanh toán
                </a>

                <a href="/policy?name=quy-che-hoat-dong">
                    Quy chế hoạt động
                </a>

            </div>


            <!-- LIÊN HỆ -->
            <div class="footer-column footer-contact">
                <h3>Liên hệ chúng tôi</h3>
                <a href="tel:84812930689">
                    <i class="fa-solid fa-phone"></i>
                    (84+) 812 930 689
                </a>
                
                <a
                    href="https://zalo.me/0812930689"
                    target="_blank"
                    rel="noopener"
                >
                    <i class="fa-solid fa-comment"></i>
                    Zalo
                </a>

                <!-- THAY LINK FACEBOOK TẠI ĐÂY -->
                <a
                    href="https://www.facebook.com/man.iron.12914216/"
                    target="_blank"
                    rel="noopener"
                >
                    <i class="fa-brands fa-facebook-f"></i>
                    Facebook
                </a>

                <a
                    href="https://www.linkedin.com/in/phuthuan323/"
                    target="_blank"
                    rel="noopener"
                >
                    <i class="fa-brands fa-linkedin-in"></i>
                    LinkedIn
                </a>

                <a href="mailto:phuthuan323@gmail.com">
                    <i class="fa-regular fa-envelope"></i>
                    phuthuan323@gmail.com
                </a>

            </div>

        </div>


        <!-- NEWSLETTER -->
        <div class="footer-newsletter">

            <h3>Nhận thông tin mới nhất</h3>

            <form class="footer-newsletter-form" action="#" method="post">

                <input
                    type="email"
                    name="email"
                    placeholder="Nhập email..."
                    required
                >

                <button type="submit">
                    Đăng ký
                </button>

            </form>

            <label class="footer-consent">

                <input
                    type="checkbox"
                    required
                >

                <span>
                    Tôi đã đọc, hiểu và đồng ý với
                    <a href="/policy?name=chinh-sach-bao-mat">
                        Chính sách bảo mật
                    </a>
                    và
                    <a href="/policy?name=quy-che-hoat-dong">
                        Quy chế hoạt động
                    </a>
                    của FASTCAR.
                </span>

            </label>

        </div>


        <!-- DIVIDER -->
        <div class="footer-divider"></div>


        <!-- COMPANY -->
        <div class="footer-company">

            <div class="footer-company-intro">

                <h3>CÔNG TY CP FASTCAR</h3>

                <p>
                    FASTCAR kết nối người bán xe với người mua
                    trên toàn quốc, giúp quá trình mua bán xe
                    nhanh chóng, minh bạch và thuận tiện.
                </p>

            </div>


            <div class="footer-company-info">

                <p>
                    <strong>Mã số thuế:</strong>
                    060812930689
                </p>

                <p>
                    <strong>Địa chỉ:</strong>
                    S102.0706,  Vinhomes Grandpark, Long Bình, Thủ Đức, Thành phố Hồ Chí Minh. 
                </p>

                <p>
                    <strong>Số điện thoại:</strong>
                    0812930689
                </p>

                <p>
                    <strong>Email:</strong>
                    phuthuan323@gmail.com
                </p>

                <p>
                    <strong>Người đại diện theo pháp luật:</strong>
                    Mr.Ryan - Thuan. Tran Nguyen Phu
                </p>

            </div>

        </div>


        <!-- COPYRIGHT -->
        <div class="footer-bottom">

            <span>
                © {$smarty.now|date_format:"%Y"} FASTCAR.
                All rights reserved.
            </span>

        </div>

    </div>

</footer>