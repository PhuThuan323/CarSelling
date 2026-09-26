{extends file="layouts/staff.tpl"}

{block name="content"}

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Nhiệm vụ Inspection</h2>
                <p>Các xe được admin phân công cho bạn.</p>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <span class="kpi-icon"><i class="fa-solid fa-inbox"></i></span>
                <strong>{$counts.assigned|default:0}</strong>
                <span>Chờ nhận</span>
            </div>
            <div class="kpi-card">
                <span class="kpi-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                <strong>{$counts.accepted|default:0}</strong>
                <span>Đã nhận</span>
            </div>
            <div class="kpi-card is-warning">
                <span class="kpi-icon"><i class="fa-solid fa-car-side"></i></span>
                <strong>{$counts.in_progress|default:0}</strong>
                <span>Đang thực hiện</span>
            </div>
            <div class="kpi-card is-success">
                <span class="kpi-icon"><i class="fa-solid fa-circle-check"></i></span>
                <strong>{$counts.completed|default:0}</strong>
                <span>Đã hoàn tất</span>
            </div>
        </div>

        {if $items|@count == 0}
            <div class="admin-empty">
                <i class="fa-solid fa-clipboard-list"></i>
                <strong>Bạn chưa có nhiệm vụ inspection nào</strong>
                <span>Khi admin phân công, bạn sẽ nhận được thông báo tại đây.</span>
            </div>
        {else}
            <div class="review-list">
                {foreach $items as $item}
                    <article class="review-card">
                        <header>
                            <div>
                                <strong>{$item.reference_code|escape}</strong>
                                <h3>{$item.vehicle_label|escape}</h3>
                            </div>
                            <span class="admin-pill">{$item.assignment_status_label|escape}</span>
                        </header>

                        <dl class="inspection-facts">
                            <dt>Khách hàng</dt>
                            <dd>
                                {$item.contact_name|default:'—'|escape}
                                {if $item.contact_phone} · {$item.contact_phone|escape}{/if}
                            </dd>

                            <dt>Biển số</dt>
                            <dd>{$item.license_plate|default:'—'|escape}</dd>

                            <dt>ODO khai báo</dt>
                            <dd>
                                {if $item.odometer_km}
                                    {$item.odometer_km|number_format:0:",":"."} km
                                {else}—
                                {/if}
                            </dd>

                            <dt>Ngày inspection</dt>
                            <dd>
                                {if $item.scheduled_at}
                                    {$item.scheduled_at|date_format:"%d/%m/%Y %H:%M"}
                                {else}
                                    <span class="admin-muted">Chưa hẹn lịch</span>
                                {/if}
                            </dd>
                        </dl>

                        {if $item.admin_note}
                            <div class="inspection-note">
                                <strong>Ghi chú từ admin</strong>
                                <p>{$item.admin_note|escape|nl2br}</p>
                            </div>
                        {/if}

                        <a class="admin-btn admin-btn-primary" href="/staff/inspections/{$item.assignment_id}">
                            <i class="fa-solid fa-arrow-right"></i>
                            {if $item.assignment_status eq 'assigned'}
                                Nhận nhiệm vụ
                            {elseif $item.assignment_status eq 'completed'}
                                Xem kết quả
                            {else}
                                Tiếp tục
                            {/if}
                        </a>
                    </article>
                {/foreach}
            </div>
        {/if}
    </section>

{/block}