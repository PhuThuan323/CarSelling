<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/banxe.css">
{include file="../home/topbar.tpl"}
<div class="thong-tin-xe">
    <div class="header-container">
        <section class="header">
            <div class="heaeder-top">
                <span class="buoc-1"> Bước 2 - Thông tin liên hệ </span>
                <h1> Cho FastCar biết về chiếc xe để có thể xem khoảng giá tham khảo bạn nhé!</h1>
                <h2> Khoảng giá ở bước sau chỉ để tham khảo, chưa phải mức giá bên mua sẽ đồng ý để mua xe. </h2>
            </div>
            <div class="thong-tin-cac-buoc">
                <div class="feature-item">
                    <div class="icon-wrapper"> <i class="fa-solid fa-tags"></i> </div>   
                    <div class="text-content">
                        <h3> Bạn cung cấp </h3>
                        <p> Hãng xe, phiên bản xe, năm sản xuất, số km, và một số hình ảnh ban đầu theo mẫu</p>
                    </div>
                </div>
                <div class="feature-item">
                <div class="icon-wrapper">
                    <i class="fa-solid fa-money-check-dollar"></i>
                </div>
                <div class="text-content">
                    <h3> Bạn nhận </h3>
                    <p> Khoảng giá tham khảo từ chuyên viên của chúng tôi.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="icon-wrapper">
                    <i class="fa-solid fa-gavel"></i>
                </div>
                <div class="text-content">
                    <h3> Bạn quyết định </h3>
                    <p> Có tiếp tục sang bước kiểm định hay không.</p>
                </div>
            </div>
            </div>
            
        </section>
        <section class="vehicle-basic-card">
            <h2> Thông liên hệ đến chủ xe </h2>
                <div class="contact-information-basic-grid">
                    <div class="form-field">
                        <h3> Tên Chủ Xe: </h3>
                        <inhput id="name" placeholder="Ví dụ: Trần Nguyễn Phú Thuận" required>
                    </div>
                    <div class="form-field">
                        <h3> Số Điện Thoại: </h3>
                        <input id="phone" placeholder="Ví dụ: 0812930689" required>
                    </div>
                    <div class="form-field">
                        <h3> Email: </h3>
                        <input id="email" placeholder="Ví dụ: abc@vinatekshd.com" required>
                    </div>
                </div>
        </section>   
        <label class="contact-consent">
            <div class="valuation-actions">
                <button type="button" class="back-button"> Quay lại </button>
                <button type="button" class="continue-button" id="continueButton" disabled > Xác nhận </button> 
            </div>
         
    </div>
</div>

{include file="../home/footer.tpl"}