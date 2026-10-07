{extends file="layouts/admin.tpl"}

{block name="content"}

<link rel="stylesheet" href="/assets/css/auction.css">

<div class="admin-auction">

    {* ============================================================
       TABS
       ============================================================ *}

    <div class="admin-auction-tabs">

        {foreach $status_labels as $status => $label}

            <a
                href="?status={$status|escape:'url'}"
                class="admin-auction-tab {if $status_filter === $status}is-active{/if}"
            >
                {$label|escape}

                <span class="admin-auction-tab-count">
                    {$counts[$status]|default:0}
                </span>
            </a>

        {/foreach}

    </div>


    {* ============================================================
       CREATE AUCTION
       ============================================================ *}

    <section class="admin-auction-panel">

        <div class="admin-auction-panel-head">

            <h2>
                <i class="fa-solid fa-gavel"></i>
                Tạo phiên đấu giá
            </h2>

            <p>
                Chọn hồ sơ mà khách hàng đã đồng ý bán xe.
                Thông tin xe, khách hàng và kết quả định giá sẽ được tự động điền.
            </p>

        </div>


        <form
            id="adminAuctionForm"
            class="admin-auction-form"
            autocomplete="off"
        >

            {* ====================================================
               HỒ SƠ BÁN XE
               ==================================================== *}

            <div class="admin-auction-field admin-auction-field-full">

                <label for="auctionRequest">
                    Hồ sơ bán xe
                </label>

                <select
                    id="auctionRequest"
                    name="valuation_request_id"
                    required
                >

                    <option value="">
                        -- Chọn hồ sơ khách đã đồng ý bán --
                    </option>

                    {foreach $eligible_requests as $req}

                        <option
                            value="{$req.id}"
                            {if $req.already_auctioned}disabled{/if}

                            data-reference-code="{$req.reference_code|default:''|escape:'html'}"

                            data-manufacture-year="{$req.manufacture_year|default:''|escape:'html'}"

                            data-odometer="{$req.odometer_km|default:''|escape:'html'}"

                            data-license-plate="{$req.license_plate|default:''|escape:'html'}"

                            data-exterior-color="{$req.exterior_color|default:''|escape:'html'}"

                            data-brand="{$req.vehicle_snapshot.brand_name|default:''|escape:'html'}"

                            data-model="{$req.vehicle_snapshot.model_name|default:''|escape:'html'}"

                            data-contact-name="{$req.contact_name|default:''|escape:'html'}"

                            data-contact-phone="{$req.contact_phone|default:''|escape:'html'}"

                            data-contact-email="{$req.contact_email|default:''|escape:'html'}"

                            data-estimated-min="{$req.estimated_min_price|default:0}"

                            data-estimated-max="{$req.estimated_max_price|default:0}"
                        >

                            {if isset($req.vehicle_snapshot.brand_name) && $req.vehicle_snapshot.brand_name}

                                {$req.vehicle_snapshot.brand_name|escape:'html'}

                                {if isset($req.vehicle_snapshot.model_name) && $req.vehicle_snapshot.model_name}
                                    - {$req.vehicle_snapshot.model_name|escape:'html'}
                                {/if}

                            {else}

                                Xe #{$req.reference_code|escape:'html'}

                            {/if}

                            {if $req.manufacture_year}
                                - {$req.manufacture_year|escape:'html'}
                            {/if}

                            {if $req.already_auctioned}
                                (Đã có phiên)
                            {/if}

                        </option>

                    {/foreach}

                </select>

                <small class="admin-auction-field-hint">
                    Chỉ các hồ sơ có trạng thái
                    <strong>“Khách đã đồng ý bán”</strong>
                    mới được tạo phiên đấu giá.
                </small>

            </div>


            {* ====================================================
               PREVIEW HỒ SƠ
               ==================================================== *}

            <div
                class="admin-auction-request-preview"
                id="auctionRequestPreview"
                hidden
            >

                {* Header *}

                <div class="auction-preview-header">

                    <div>

                        <span class="auction-preview-label">
                            Hồ sơ bán xe
                        </span>

                        <strong id="previewReference">
                            -
                        </strong>

                    </div>

                    <span
                        class="auction-preview-status"
                        id="previewStatus"
                    >
                        Khách đã đồng ý bán
                    </span>

                </div>


                {* =================================================
                   THÔNG TIN XE
                   ================================================= *}

                <div class="auction-preview-section">

                    <h3>
                        <i class="fa-solid fa-car"></i>
                        Thông tin xe
                    </h3>

                    <div class="auction-preview-grid">

                        <div class="auction-preview-item">

                            <span>
                                Hãng xe
                            </span>

                            <strong id="previewBrand">
                                -
                            </strong>

                        </div>


                        <div class="auction-preview-item">

                            <span>
                                Model
                            </span>

                            <strong id="previewModel">
                                -
                            </strong>

                        </div>


                        <div class="auction-preview-item">

                            <span>
                                Năm sản xuất
                            </span>

                            <strong id="previewYear">
                                -
                            </strong>

                        </div>


                        <div class="auction-preview-item">

                            <span>
                                Số km
                            </span>

                            <strong id="previewOdometer">
                                -
                            </strong>

                        </div>


                        <div class="auction-preview-item">

                            <span>
                                Biển số
                            </span>

                            <strong id="previewLicensePlate">
                                -
                            </strong>

                        </div>


                        <div class="auction-preview-item">

                            <span>
                                Màu ngoại thất
                            </span>

                            <strong id="previewExteriorColor">
                                -
                            </strong>

                        </div>

                    </div>

                </div>


                {* =================================================
                   THÔNG TIN KHÁCH HÀNG
                   ================================================= *}

                <div class="auction-preview-section">

                    <h3>

                        <i class="fa-solid fa-user"></i>

                        Thông tin khách hàng

                    </h3>


                    <div class="auction-preview-grid">

                        <div class="auction-preview-item">

                            <span>
                                Họ tên
                            </span>

                            <strong id="previewContactName">
                                -
                            </strong>

                        </div>


                        <div class="auction-preview-item">

                            <span>
                                Số điện thoại
                            </span>

                            <strong id="previewContactPhone">
                                -
                            </strong>

                        </div>


                        <div class="auction-preview-item">

                            <span>
                                Email
                            </span>

                            <strong id="previewContactEmail">
                                -
                            </strong>

                        </div>

                    </div>

                </div>


                {* =================================================
                   KẾT QUẢ ĐỊNH GIÁ
                   ================================================= *}

                <div class="auction-preview-section">

                    <h3>

                        <i class="fa-solid fa-money-bill-trend-up"></i>

                        Kết quả định giá

                    </h3>


                    <div class="auction-estimate-range">

                        <div>

                            <span>
                                Giá thấp nhất
                            </span>

                            <strong id="previewEstimatedMin">
                                -
                            </strong>

                        </div>


                        <span class="auction-estimate-separator">
                            →
                        </span>


                        <div>

                            <span>
                                Giá cao nhất
                            </span>

                            <strong id="previewEstimatedMax">
                                -
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            {* ====================================================
               GIÁ KHỞI ĐIỂM
               ==================================================== *}

            <div class="admin-auction-field">

                <label for="auctionStartPrice">
                    Giá khởi điểm (đ)
                </label>

                <input
                    type="number"
                    id="auctionStartPrice"
                    name="start_price"
                    min="1"
                    step="1000"
                    placeholder="Ví dụ: 425000000"
                    required
                >

                <small class="admin-auction-field-hint">

                    Mặc định lấy
                    <strong>giá thấp nhất</strong>
                    trong kết quả định giá.

                    Admin có thể kiểm tra và điều chỉnh trước khi tạo phiên.

                </small>

            </div>


            {* ====================================================
               THỜI GIAN ĐẤU GIÁ
               ==================================================== *}

            <div class="admin-auction-field">

                <label for="auctionDuration">
                    Thời gian đấu giá (ngày)
                </label>

                <input
                    type="number"
                    id="auctionDuration"
                    name="duration_days"
                    min="{$min_duration_days}"
                    max="{$max_duration_days}"
                    value="{$min_duration_days}"
                    required
                >

            </div>


            {* ====================================================
               THỜI GIAN BẮT ĐẦU
               ==================================================== *}

            <div class="admin-auction-field">

                <label for="auctionStartAt">
                    Thời gian bắt đầu
                </label>

                <input
                    type="datetime-local"
                    id="auctionStartAt"
                    name="start_at"
                >

                <small class="admin-auction-field-hint">

                    Để trống nếu muốn hệ thống sử dụng thời gian mặc định.

                </small>

            </div>


            {* ====================================================
               MESSAGE
               ==================================================== *}

            <div
                id="adminAuctionMsg"
                class="auction-form-msg"
                hidden
            ></div>


            {* ====================================================
               SUBMIT
               ==================================================== *}

            <div class="admin-auction-form-actions">

                <button
                    type="submit"
                    class="auction-btn auction-btn-primary"
                    id="adminAuctionSubmit"
                >

                    <i class="fa-solid fa-gavel"></i>

                    Tạo phiên đấu giá

                </button>

            </div>

        </form>

    </section>


    {* ============================================================
       AUCTION LIST
       ============================================================ *}

    {* Phần danh sách auction hiện tại của bạn đặt ở đây.
       Không cần thay đổi logic nếu đang hoạt động bình thường. *}

</div>

{/block}


{block name="scripts"}

<script>
    window.ADMIN_AUCTION_CONFIG = {
        csrfToken: '{$csrf_token|escape:'javascript'}'
    };
</script>

<script src="/assets/js/admin-auction.js"></script>

{/block}