link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/banxe.css">
{include file="../home/topbar.tpl"}
<div class="thong-tin-xe">
    <div class="header-container">
        <section class="header">
            <span class="buoc-1"> Bước 1 - Thông tin xe </span>
            <h1> Cho FastCar biết về chiếc xe để có thể xem khoảng giá tham khảo bạn nhé!</h1>
            <h2> Khoảng giá ở bước sau chỉ để tham khảo, chưa phải mức giá bên mua sẽ đồng ý để mua xe. </h2>
            <div class="thong-tin-cac-buoc">
                <i class="fa-light fa-tags"></i>
                <h3> Bạn cung cấp </h3>
                <a> Hãng xe, phiên bản xe, năm sản xuất, số km, và một số hình ảnh ban đầu theo mẫu</a>
                <i class="fa-regular fa-money-check-dollar"></i>
                <h3> Bạn nhận </h3>
                <a> Khoảng giá tham khảo từ chuyên viên của chúng tôi </a>
                <i class="fa-regular fa-gavel"></i>
                <h3> Bạn quyết định </h3>
                <a> Có tiếp tục sang bước kiểm định hay không </a>
            </div> 
        </section>
        <section class="vehicle-basic-card">
            <h2> Thông tin xe cơ bản </h2>
                <div class="vehicle-basic-grid">
                    <div class="form-filed">
                        <label for="BrandSelect"> Hãng xe <span>*</span> </label>
                        <select id="BrandSeclect" required>
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
                    <h2> tải ảnh lên theo hướng dẫn </h2>
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
                    <div class="photo-slot" data-slot="front-left-45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="front-right-45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="rear-left-45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="rear-right-45" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="front" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="rear" data-category="exterior" data-required="1"> </div>
                    <div class="photo-slot" data-slot="roof" data-category="exterior" data-required="1"> </div>
                </div>
            </div>
        </section> 
    </div>
</div>

{include file="../home/footer.tpl"}
