<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/banxe.css">
<link rel="stylesheet" href="/assets/css/my-selling-cars.css">

{include file="../home/topbar.tpl"}

<div class="mycars">

    <a class="mycars-back" href="/my-selling-cars">
        <i class="fa-solid fa-arrow-left"></i> Danh sách xe đã đăng bán
    </a>

    <header class="mycars-head">
        <h1>{$vehicle_label|escape}</h1>
        <p>
            Mã hồ sơ <strong>{$request.reference_code|escape}</strong>
            · Đời {$request.manufacture_year|escape}
            {if $request.license_plate} · {$request.license_plate|escape}{/if}
        </p>

        <div class="mydetail-status">
            <span class="mycar-status {$status|escape}">{$status_label|escape}</span>
        </div>
    </header>

    {* ===================== THÔNG TIN XE ĐÃ GỬI ===================== *}
    <section class="mydetail-card">
        <h2><i class="fa-solid fa-car"></i> Thông tin xe bạn đã gửi</h2>

        {assign var="snap" value=$request.vehicle_snapshot}

        <dl class="mydetail-facts">
            <dt>Dòng xe</dt>
            <dd>
                {if isset($snap.brand_name)}{$snap.brand_name|escape}{/if}
                {if isset($snap.model_name)} {$snap.model_name|escape}{/if}
                {if isset($snap.version_name)} · {$snap.version_name|escape}{/if}
            </dd>

            <dt>ODO khai báo</dt>
            <dd>
                {if $request.odometer_km}
                    {$request.odometer_km|number_format:0:",":"."} km
                {else}—
                {/if}
            </dd>

            <dt>Màu ngoại thất</dt>
            <dd>{$request.exterior_color|default:'—'|escape}</dd>

            <dt>Số chủ đã qua</dt>
            <dd>{$request.owners_count|default:'—'|escape}</dd>
        </dl>
    </section>

    {* ===================== KẾT QUẢ ĐỊNH GIÁ ===================== *}
    {if $status eq 'estimated' || $status eq 'accepted' || $status eq 'cancelled'}

        {if $rating_rows|@count > 0}
            <section class="mydetail-card">
                <h2><i class="fa-solid fa-clipboard-list"></i> Kết quả đánh giá</h2>

                <div class="mydetail-ratings">
                    {foreach $rating_rows as $row}
                        <div class="mydetail-rating">
                            <span>{$row.label|escape}</span>
                            <strong>{$row.text|escape}</strong>
                        </div>
                    {/foreach}
                </div>
            </section>
        {/if}

        {if $feedback}
            <section class="mydetail-card mydetail-offer">
                <h2><i class="fa-solid fa-tags"></i> Giá đề xuất</h2>

                <div class="mydetail-price">
                    <strong>
                        {if $request.estimated_min_price}
                            {$request.estimated_min_price|number_format:0:",":"."} đ
                            -
                            {$request.estimated_max_price|number_format:0:",":"."} đ
                        {elseif $feedback.price_min}
                            {$feedback.price_min|number_format:0:",":"."} đ
                            -
                            {$feedback.price_max|number_format:0:",":"."} đ
                        {/if}
                    </strong>
                    <small>
                        Gửi ngày {$feedback.sent_at|date_format:"%d/%m/%Y %H:%M"}
                    </small>
                </div>

                <div class="mydetail-feedback">
                    <h3>Nhận xét từ FastCar</h3>
                    <p>{$feedback.message|escape|nl2br}</p>
                </div>
            </section>
        {/if}

        {* ===================== ĐỒNG Ý / KHÔNG ĐỒNG Ý ===================== *}
        {if $can_respond}
            <section class="mydetail-card mydetail-answer">
                <h2>Bạn có đồng ý bán xe với mức định giá trên không?</h2>

                <div class="mycars-btn-row">
                    <button type="button" class="mycars-btn mycars-btn-primary" id="acceptOfferBtn">
                        <i class="fa-solid fa-handshake"></i> Đồng ý bán xe
                    </button>

                    <button type="button" class="mycars-btn mycars-btn-ghost" id="declineOfferBtn">
                        Không đồng ý
                    </button>
                </div>
            </section>
        {/if}

    {elseif $status eq 'ready_for_estimate' || $status eq 'inspection_requested' || $status eq 'inspection_assigned' || $status eq 'inspection_in_progress' || $status eq 'inspection_completed' || $status eq 'estimating' %}

        <section class="mydetail-card mydetail-waiting">
            <i class="fa-solid fa-hourglass-half"></i>
            <h2>FastCar đang thẩm định xe của bạn</h2>
            <p>
                Chuyên viên của chúng tôi đang kiểm tra và đánh giá tình trạng xe.
                Kết quả định giá sẽ được gửi tới bạn trong thời gian sớm nhất.
            </p>
        </section>

    {elseif $status eq 'rejected'}

        <section class="mydetail-card mydetail-waiting is-error">
            <i class="fa-solid fa-circle-xmark"></i>
            <h2>Hồ sơ chưa phù hợp</h2>
            <p>
                Rất tiếc, hồ sơ bán xe này chưa phù hợp với tiêu chí thu mua của FastCar.
                Vui lòng liên hệ hotline để được hỗ trợ thêm.
            </p>
        </section>

    {/if}

    {* ===================== TIMELINE ===================== *}
    {if $timeline|@count > 0}
        <section class="mydetail-card">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Tiến trình hồ sơ</h2>

            <ul class="mydetail-timeline">
                {foreach $timeline as $entry}
                    <li>
                        <span class="mydetail-timeline-dot"></span>
                        <div>
                            <strong>{$entry.new_status|escape}</strong>
                            <small>{$entry.created_at|date_format:"%d/%m/%Y %H:%M"}</small>
                            {if $entry.note}<p>{$entry.note|escape}</p>{/if}
                        </div>
                    </li>
                {/foreach}
            </ul>
        </section>
    {/if}

</div>

{* ===================== CONFIRM POPUP: KHÔNG ĐỒNG Ý ===================== *}
<div class="mycars-modal" id="declineModal" hidden>
    <div class="mycars-modal-card">
        <div class="mycars-modal-icon is-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3>Bạn không đồng ý với mức định giá?</h3>

        <p>
            Nếu tiếp tục, hồ sơ bán xe này sẽ được chuyển sang trạng thái
            <strong>Đã hủy</strong>. Bạn có thể đăng bán lại bất cứ lúc nào.
        </p>

        <div class="mycars-btn-row">
            <button type="button" class="mycars-btn mycars-btn-ghost" id="declineCancel">
                Quay lại
            </button>
            <button type="button" class="mycars-btn mycars-btn-danger" id="declineConfirm">
                Xác nhận không bán
            </button>
        </div>
    </div>
</div>

{* ===================== CONFIRM POPUP: ĐỒNG Ý ===================== *}
<div class="mycars-modal" id="acceptModal" hidden>
    <div class="mycars-modal-card">
        <div class="mycars-modal-icon is-success">
            <i class="fa-solid fa-handshake"></i>
        </div>

        <h3>Xác nhận đồng ý bán xe</h3>

        <p>
            FastCar sẽ liên hệ với bạn để hoàn tất thủ tục mua bán theo mức giá trên.
        </p>

        <div class="mycars-btn-row">
            <button type="button" class="mycars-btn mycars-btn-ghost" id="acceptCancel">
                Quay lại
            </button>
            <button type="button" class="mycars-btn mycars-btn-primary" id="acceptConfirm">
                Xác nhận đồng ý bán
            </button>
        </div>
    </div>
</div>

{include file="../home/footer.tpl"}

<script>
    window.OFFER_CONFIG = {
        valuationRequestId: {$request.id},
        csrfToken: '{$csrf_token|escape:'javascript'}',
        acceptUrl: '/api/v1/valuations/accept-offer',
        declineUrl: '/api/v1/valuations/decline-offer'
    };
</script>
<script src="/assets/js/customer-offer.js"></script>