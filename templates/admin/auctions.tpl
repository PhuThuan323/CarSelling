{extends file="layouts/admin.tpl"}

{block name="content"}

    <link rel="stylesheet" href="/assets/css/auction.css">

    <div class="admin-auction">

        <!-- Tab trạng thái -->
        <div class="admin-auction-tabs">
            <a class="admin-auction-tab{if $status_filter eq ''} is-active{/if}" href="/admin/auctions">
                Tất cả
                <span class="admin-auction-tab-count">{$counts.all|default:0}</span>
            </a>

            {foreach $status_labels as $key => $label}
                <a class="admin-auction-tab{if $status_filter eq $key} is-active{/if}"
                    href="/admin/auctions?status={$key|escape:'url'}">
                    {$label|escape}
                    <span class="admin-auction-tab-count">{$counts[$key]|default:0}</span>
                </a>
            {/foreach}
        </div>

        <!-- Mo phien dau gia -->
        <section class="admin-auction-panel">
            <header class="admin-auction-panel-head">
                <h2><i class="fa-solid fa-plus"></i> Mở phiên đấu giá</h2>
                <p>
                    Chọn một hồ sơ khách đã đồng ý bán, đặt giá gốc và thời gian
                    ({$min_duration_days}–{$max_duration_days} ngày).
                </p>
            </header>

            {if $eligible_requests|@count == 0}
                <div class="admin-auction-empty">
                    Chưa có hồ sơ nào ở trạng thái “khách đã đồng ý bán”.
                </div>
            {else}
                <form class="admin-auction-form" id="adminAuctionForm">
                    <div class="admin-auction-field">
                        <label for="auctionRequest">Hồ sơ bán xe</label>
                        <select id="auctionRequest" name="valuation_request_id" required>
                            <option value="">-- Chọn hồ sơ --</option>
                            {foreach $eligible_requests as $req}
                                <option value="{$req.id}" {if $req.already_auctioned}disabled{/if}>
                                    {if isset($req.vehicle_snapshot.brand_name)}
                                        {$req.vehicle_snapshot.brand_name|escape}
                                        {$req.vehicle_snapshot.model_name|escape}
                                    {else}
                                        Xe #{$req.reference_code|escape}
                                    {/if}
                                    — {$req.manufacture_year|escape}
                                    {if $req.already_auctioned} (đã có phiên){/if}
                                </option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="admin-auction-field">
                        <label for="auctionStartPrice">Giá gốc (đ)</label>
                        <input type="number" id="auctionStartPrice" name="start_price" min="1" step="1000"
                            placeholder="VD: 425000000" required>
                    </div>

                    <div class="admin-auction-field">
                        <label for="auctionDuration">Thời gian (ngày)</label>
                        <input type="number" id="auctionDuration" name="duration_days" min="{$min_duration_days}"
                            max="{$max_duration_days}" value="{$min_duration_days}" required>
                    </div>

                    <button type="submit" class="auction-btn auction-btn-primary">
                        <i class="fa-solid fa-gavel"></i>
                        Tạo phiên đấu giá
                    </button>
                </form>

                <div class="auction-form-msg" id="adminAuctionMsg" hidden></div>
            {/if}
        </section>

        <!-- Danh sach phien -->
        <section class="admin-auction-panel">
            <header class="admin-auction-panel-head">
                <h2><i class="fa-solid fa-list"></i> Danh sách phiên đấu giá</h2>
            </header>

            {if $items|@count == 0}
                <div class="admin-auction-empty">Chưa có phiên đấu giá nào.</div>
            {else}
                <div class="admin-auction-table-wrap">
                    <table class="admin-auction-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Xe</th>
                                <th>Giá gốc</th>
                                <th>Giá hiện tại</th>
                                <th>Bước giá</th>
                                <th>Người thắng</th>
                                <th>Kết thúc</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $items as $item}
                                <tr>
                                    <td>{$item.id}</td>
                                    <td class="admin-auction-cell-title">
                                        <a href="/cars/{$item.id}">{$item.title|escape}</a>
                                    </td>
                                    <td>{$item.start_price|number_format:0:",":"."} đ</td>
                                    <td>
                                        <strong>
                                            {$item.current_price|number_format:0:",":"."} đ
                                        </strong>
                                    </td>
                                    <td>+{$item.min_increment|number_format:0:",":"."} đ</td>
                                    <td>
                                        {if $item.winner_name}
                                            {$item.winner_name|escape}
                                        {else}
                                            —
                                        {/if}
                                    </td>
                                    <td>{$item.end_at|date_format:"%d/%m/%Y %H:%M"}</td>
                                    <td>
                                        <span class="auction-status-pill {$item.status|escape}">
                                            {$item.status_label|escape}
                                        </span>
                                    </td>
                                    <td>
                                        {if $item.status eq 'awaiting_payment'}

                                            <button type="button" class="admin-auction-pay-btn" data-auction-id="{$item.id}"
                                                data-status="paid">
                                                Xác nhận đã thanh toán
                                            </button>

                                            <button type="button" class="admin-auction-cancel-btn" data-auction-id="{$item.id}"
                                                data-status="cancelled">
                                                Hủy
                                            </button>

                                        {elseif $item.payment_status eq 'paid'}
                                            <span class="auction-status-pill paid">Đã thanh toán</span>

                                        {elseif $item.status eq 'paid'}
                                            <span class="auction-status-pill paid">Đã thanh toán</span>

                                        {else}
                                            —
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {/if}
        </section>

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