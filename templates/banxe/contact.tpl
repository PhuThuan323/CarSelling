<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link rel="stylesheet" href="/assets/css/banxe.css">

{include file="../home/topbar.tpl"}


<div class="thong-tin-xe" id="contactPage" data-valuation-request-id="{$valuation_request_id}">

    <div class="header-container">

        <section class="header">

            <div class="heaeder-top">

                <span class="buoc-1">
                    Bước 2 - Thông tin liên hệ
                </span>

                <h1>
                    Thông tin liên hệ chủ xe
                </h1>

                <h2>
                    FastCar sẽ sử dụng thông tin này
                    để liên hệ về hồ sơ định giá xe
                    của bạn.
                </h2>

            </div>

        </section>


        <section class="vehicle-basic-card">

            <h2>
                Thông tin liên hệ
            </h2>


            <div class="contact-information-basic-grid">

                <div class="form-field">

                    <label for="name">
                        Tên chủ xe
                        <span>*</span>
                    </label>

                    <input id="name" name="full_name" type="text" maxlength="150"
                        placeholder="Ví dụ: Trần Nguyễn Phú Thuận" required>

                </div>


                <div class="form-field">

                    <label for="phone">
                        Số điện thoại
                        <span>*</span>
                    </label>

                    <input id="phone" name="phone" type="tel" maxlength="30" inputmode="tel" autocomplete="tel"
                        placeholder="Ví dụ: 0812930689" required>

                </div>


                <div class="form-field">

                    <label for="email">
                        Email
                    </label>

                    <input id="email" name="email" type="email" maxlength="255" autocomplete="email"
                        placeholder="Ví dụ: abc@gmail.com">

                </div>

            </div>

        </section>


        <div class="valuation-actions">

            <button type="button" class="back-button" id="backButton">
                Quay lại
            </button>

            <button type="button" class="continue-button" id="continueButton">
                Xác nhận
            </button>

        </div>

    </div>

</div>


{include file="../home/footer.tpl"}

<script src="/assets/js/contact.js"></script>