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
                <i class="fa-solid fa-clipboard-check"></i>
                <strong>Chưa có hồ sơ nào trong luồng Inspection</strong>
                <span>Hồ sơ sẽ xuất hiện ở đây sau khi khách gửi đăng bán xe.</span>
            </div>
        {else}
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã hồ sơ</th>
                            <th>Xe</th>
                            <th>Khách hàng</th>
                            <th>Trạng thái</th>
                            <th>Nhân viên</th>
                            <th>Lịch inspection</th>
                            <th>Range staff</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $items as $item}
                            <tr>
                                <td>
                                    <strong>{$item.reference_code|escape}</strong>
                                    {if $item.submitted_at}
                                        <small class="admin-muted">
                                            {$item.submitted_at|date_format:"%d/%m/%Y"}
                                        </small>
                                    {/if}
                                </td>
                                <td>
                                    {assign var="snap" value=$item.vehicle_snapshot}
                                    <strong>
                                        {if isset($snap.brand_name)}{$snap.brand_name|escape}{/if}
                                        {if isset($snap.model_name)} {$snap.model_name|escape}{/if}
                                    </strong>
                                    <small class="admin-muted">
                                        Đời {$item.manufacture_year|escape}
                                        {if $item.license_plate} · {$item.license_plate|escape}{/if}
                                        {if $item.odometer_km} · {$item.odometer_km|number_format:0:",":"."} km{/if}
                                    </small>
                                </td>
                                <td>
                                    {if $item.contact_name}
                                        <strong>{$item.contact_name|escape}</strong>
                                    {else}
                                        <span class="admin-muted">Chưa có</span>
                                    {/if}
                                    {if $item.contact_phone}
                                        <small class="admin-muted">{$item.contact_phone|escape}</small>
                                    {/if}
                                </td>
                                <td><span class="admin-pill">{$item.status|escape}</span></td>
                                <td>
                                    {if $item.staff_name}
                                        {$item.staff_name|escape}
                                    {else}
                                        <span class="admin-muted">Chưa phân công</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $item.scheduled_at}
                                        {$item.scheduled_at|date_format:"%d/%m/%Y %H:%M"}
                                    {else}
                                        <span class="admin-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $item.suggested_price_min && $item.suggested_price_max}
                                        {$item.suggested_price_min|number_format:0:",":"."}
                                        -
                                        {$item.suggested_price_max|number_format:0:",":"."}
                                    {else}
                                        <span class="admin-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    <a class="admin-btn admin-btn-primary admin-btn-sm" href="/admin/inspections/{$item.id}">
                                        <i class="fa-solid fa-arrow-right"></i> Xử lý
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    </section>

{/block}