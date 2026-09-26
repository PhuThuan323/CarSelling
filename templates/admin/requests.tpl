{extends file="layouts/admin.tpl"}

{block name="content"}

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>{$screen_title|escape}</h2>
                <p>{$screen_subtitle|escape}</p>
            </div>
        </div>

        {* Tab loc theo nhom trang thai *}
        {if isset($filters) && $filters|@count > 0}
            <div class="admin-tabs">
                {foreach $filters as $tab}
                    {if $tab.key eq 'all'}
                        {assign var="tabUrl" value="/admin/requests"}
                    {else}
                        {assign var="tabUrl" value="/admin/requests?filter=`$tab.key`"}
                    {/if}

                    <a
                        class="admin-tab{if $tab.is_active} is-active{/if}"
                        href="{$tabUrl}"
                    >
                        {$tab.label|escape}
                        {if $tab.total > 0}
                            <span class="admin-tab-count">{$tab.total}</span>
                        {/if}
                    </a>
                {/foreach}
            </div>
        {/if}

        {if $items|@count == 0}
            <div class="admin-empty">
                <i class="fa-solid fa-file-invoice"></i>
                <strong>Chưa có hồ sơ nào</strong>
                <span>Hồ sơ sẽ xuất hiện ở đây khi khách gửi đăng bán xe.</span>
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
                            <th>Giá gửi khách</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $items as $item}
                            <tr>
                                <td>
                                    <strong>{$item.reference_code|escape}</strong>
                                    <small class="admin-muted">
                                        {if $item.created_at}
                                            {$item.created_at|date_format:"%d/%m/%Y"}
                                        {/if}
                                    </small>
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
                                <td>
                                    <span class="admin-pill status-{$item.status|escape}">
                                        {$item.status_label|escape}
                                    </span>
                                    {if isset($item.cancellation_reason) && $item.cancellation_reason eq 'customer_declined_offer'}
                                        <small class="admin-muted">Khách không đồng ý giá</small>
                                    {/if}
                                </td>
                                <td>
                                    {if $item.staff_name}
                                        {$item.staff_name|escape}
                                    {else}
                                        <span class="admin-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $item.estimated_min_price && $item.estimated_max_price}
                                        <strong>
                                            {$item.estimated_min_price|number_format:0:",":"."}
                                            -
                                            {$item.estimated_max_price|number_format:0:",":"."} đ
                                        </strong>
                                    {else}
                                        <span class="admin-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    <a class="admin-btn admin-btn-ghost" href="/admin/inspections/{$item.id}">
                                        <i class="fa-solid fa-eye"></i> Xem
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