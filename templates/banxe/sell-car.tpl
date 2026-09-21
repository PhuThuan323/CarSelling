link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/banxe.css">
{include file="../home/topbar.tpl"}
<div class="thong-tin-xe">
    <div class="header-container">
        <section class="header">
            <div class="heaeder-top">
                <span class="buoc-1"> Bước 1 - Thông tin xe </span>
                <h1> Cho FastCar biết về chiếc xe để có thể xem khoảng giá tham khảo bạn nhé!</h1>
                <h2> Khoảng giá ở bước sau chỉ để tham khảo, chưa phải mức giá bên mua sẽ đồng ý để mua xe. </h2>
            </div>
            <div class="thong-tin-cac-buoc">
                <div class="feature-item">
                    <div class="icon-wrapper"> <i class="fa-light fa-tags"></i> </div>   
                    <div class="text-content">
                        <h3> Bạn cung cấp </h3>
                        <p> Hãng xe, phiên bản xe, năm sản xuất, số km, và một số hình ảnh ban đầu theo mẫu</p>
                    </div>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-wrapper">
                    <i class="fa-regular fa-money-check-dollar"></i>
                </div>
                <div class="text-content">
                    <h3> Bạn nhận </h3>
                    <p> Khoảng giá tham khảo từ chuyên viên của chúng tôi.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-wrapper">
                    <i class="fa-regular fa-gavel"></i>
                </div>
                <div class="text-content">
                    <h3> Bạn quyết định </h3>
                    <p> Có tiếp tục sang bước kiểm định hay không.</p>
                </div>
            </div>
        </section>
        <section class="vehicle-basic-card">
            <h2> Thông tin xe cơ bản </h2>
                <div class="vehicle-basic-grid">
                    <div class="form-field">
                        <label for="BrandSelect"> Hãng xe <span>*</span> </label>
                        <select id="BrandSelect" required>
                            <option value=""> -- Chọn hãng xe -- </option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="modelSelect"> Dòng xe <span>*</span> </label>
                        <select id="modelSelect" required disabled>
                            <option value=""> -- Chọn hãng trước -- </option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="yearSelect"> Đời xe <span>*</span> </label>
                        <select id="yearSelect" required disabled>
                            <option value=""> -- Chọn dòng xe trước -- </option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="versionSelect"> Phiên bản <span>*</span> </label>
                        <select id="versionSelect" required disabled> 
                            <option value=""> -- Chọn đời xe trước -- </option> 
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="odometer"> Số km đã đi <span>*</span> </label>
                        <input id="odometer" type="number" min="0" placeholder="Ví dụ: 65.000 km" required>
                    </div>
                </div>
            
        </section>   
        <section class="vehicle-photo-card">
            <div class="photo-section-head">
                <div>
                    <span class="photo-step"> Hình ảnh xe </span>
                    <h2> Tải ảnh lên theo hướng dẫn </h2>
                    <p> Vui lòng cung cấp đầy đủ các hình ảnh bắt buộc. Bạn có thể xem trước, thay ảnh hoặc xóa ảnh trước khi gửi định giá. </p>
                </div>
                <div class="photo-progress-box">
                    <strong id="photoProgressText"> 0/21 </strong>
                    <span> Ảnh bắt buộc </span>
                </div>
            </div>
            <div class="photo-progress">
                <div class="photo-progress-bar" id="photoProgressBar"> </div>
            </div>
            <div class="photo-group" data-group="exterior">
                <div class="photo-group-head">
                    <div>
                        <h3> 1. Ngoại thất </h3>
                        <p> Chụp rõ toàn bộ thân xe </p>
                    </div>
                    <span class="photo-group-count">
                        <strong data-group-count="exterior"> 0 </strong> /7
                    </span>
                </div>
                <div class="photo-grid">
                    <div class="photo-slot" data-slot="front_left_45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="front_right_45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="rear_left_45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="rear_right_45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="front" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="rear" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="roof" data-category="exterior" data-required="1"> </div>
                </div>
            </div>
            <div class="photo-group" data-group="interior">
                <div class="photo-group-head">
                    <div>
                        <h3> 2. Nội thất </h3>
                        <p> Quý khách chụp rõ khoang lái, ghế và số ODO </p>
                    </div>
                    <span class="photo-group-count">
                        <strong data-group-count="interior"> 0 </strong> /5
                    </span>
                </div>
                <div class="photo-grid">
                    <div class="photo-slot" data-slot="odometer" data-category="interior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="cockpit" data-category="interior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="driver_seat" data-category="interior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="passenger_seat" data-category="interior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="rear_seat_headliner" data-category="interior" data-required="1"> </div>
                </div>
            </div>
            <div class="photo-group" data-group="mechanical">
                <div class="photo-group-head">
                    <div>
                        <h3> 3. Động cơ và mâm lốp </h3>
                        <p> Chụp rõ khoang máy và 4 bánh xe </p>
                    </div>
                    <span class="photo-group-count">
                        <strong data-group-count="mechanical"> 0 </strong> /5
                    </span>
                </div>
                <div class="photo-grid">
                    <div class="photo-slot" data-slot="engine_bay" data-category="mechanical" data-required="1"> </div>
                    <div class="photo-slot" data-slot="wheel_front_left" data-category="mechanical" data-required="1"> </div>
                    <div class="photo-slot" data-slot="wheel_front_right" data-category="mechanical" data-required="1"> </div>
                    <div class="photo-slot" data-slot="wheel_rear_left" data-category="mechanical" data-required="1"> </div>
                    <div class="photo-slot" data-slot="wheel_rear_right" data-category="mechanical" data-required="1"> </div>
                </div>
            </div>
            <div class="photo-group" data-group="legal">
                <div class="photo-group-head">
                    <div>
                        <h3> 4. Giấy tờ xe </h3>
                        <p> Có thể che tên, mặt. Nhưng cần nhìn rõ thông tin xe </p>
                    </div>
                    <span class="photo-group-count">
                        <strong data-group-count="legal"> 0 </strong> /4
                    </span>
                </div>
                <div class="photo-grid">
                    <div class="photo-slot" data-slot="registration_front" data-category="legal" data-required="1"> </div>
                    <div class="photo-slot" data-slot="registration_back" data-category="legal" data-required="1"> </div>
                    <div class="photo-slot" data-slot="inspection_expiry" data-category="legal" data-required="1"> </div>
                    <div class="photo-slot" data-slot="inspection_spec" data-category="legal" data-required="1"> </div>
                </div>
            </div>
            <label class="valuation-consent">
                <input type="checkbox" id="valuationConsent">
                <span>
                    Tôi đã đọc, hiểu rõ và đồng ý với
                    <a href="/policy?name=chinh-sach-bao-mat" target="_blank">
                        Chính sách bảo mật
                    </a>
                    và
                    <a href="/policy?name=quy-che-hoat-dong" target="_blank">
                        Quy chế hoạt động
                    </a>
                    của FastCar.
                </span>
            </label>
            <div class="valuation-actions">
                <button type="button" class="back-button"> Quay lại </button>
                <button type="button" class="continue-button" id="continueButton" disabled> Tiếp tục </button>
            </div>
        </section> 
    </div>
</div>

{include file="../home/footer.tpl"}
<script src="/assets/js/banxe.js"></script>