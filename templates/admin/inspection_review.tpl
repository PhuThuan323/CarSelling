{extends file="layouts/admin.tpl"}

{block name="content"}

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>{$screen_title|escape}</h2>
                <p>{$screen_subtitle|escape}</p>
            </div>
        </div>

        {if $items|@count == 0}
            <div class="admin-empty">
                <i class="fa-solid fa-circle-check"></i>
                <strong>Không có hồ sơ nào chờ duyệt</strong>
                <span>Khi nhân viên gửi kết quả thẩm định, hồ sơ sẽ xuất hiện ở đây.</span>
            </div>
        {else}
            <div class="review-list">
                {foreach $items as $item}
                    {assign var="snap" value=$item.vehicle_snapshot}
                    <article class="review-card">
                        <header>
                            <div>
                                <strong>{$item.reference_code|escape}</strong>
                                <h3>
                                    {if isset($snap.brand_name)}{$snap.brand_name|escape}{/if}
                                    {if isset($snap.model_name)} {$snap.model_name|escape}{/if}
                                    {$item.manufacture_year|escape}
                                </h3>
                            </div>
                            <span class="admin-pill">{$item.status|escape}</span>
                        </header>

                        <dl class="inspection-facts">
                            <dt>Nhân viên</dt>
                            <dd>{$item.staff_name|default:'—'|escape}</dd>

                            <dt>Khách hàng</dt>
                            <dd>
                                {$item.contact_name|default:'—'|escape}
                                {if $item.contact_phone}
                                    · {$item.contact_phone|escape}
                                {/if}
                            </dd>

                            <dt>Gửi kết quả lúc</dt>
                            <dd>
                                {if $item.result_submitted_at}
                                    {$item.result_submitted_at|date_format:"%d/%m/%Y %H:%M"}
                                {else}—
                                {/if}
                            </dd>
                        </dl>

                        <div class="inspection-range">
                            <span>Range staff đề xuất</span>
                            <strong>
                                {if $item.suggested_price_min && $item.suggested_price_max}
                                    {$item.suggested_price_min|number_format:0:",":"."} đ
                                    -
                                    {$item.suggested_price_max|number_format:0:",":"."} đ
                                {else}
                                    Chưa đề xuất
                                {/if}
                            </strong>
                        </div>

                        <a class="admin-btn admin-btn-primary" href="/admin/inspections/{$item.id}">
                            <i class="fa-solid fa-gavel"></i> Duyệt
                        </a>
                    </article>
                {/foreach}
            </div>
        {/if}
    </section>

{/block}