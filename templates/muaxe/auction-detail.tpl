<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'Chi tiết đấu giá - FastCar'}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/auction.css">
</head>

<body>

    {include file="home/topbar.tpl"}

    <main class="auction-page auction-detail-page">

        <nav class="auction-breadcrumb">
            <a href="/cars"><i class="fa-solid fa-arrow-left"></i> Danh sách đấu giá</a>
        </nav>

        <div class="auction-detail">

            <section class="auction-detail-main">

                <div class="auction-detail-media">
                    <div class="auction-detail-placeholder">
                        <i class="fa-solid fa-car-side"></i>
                    </div>
                    <span class="auction-badge {$auction.status|escape}">
                        {$status_label|escape}
                    </span>
                </div>

                <h1 class="auction-detail-title">{$auction.title|escape}</h1>

                <div class="auction-detail-meta">
                    {if $auction.manufacture_year}
                        <span><i class="fa-solid fa-calendar"></i> Đời xe {$auction.manufacture_year|escape}</span>
                    {/if}
                    {if $auction.odometer_km}
                        <span><i class="fa-solid fa-road"></i> {$auction.odometer_km|number_format:0:",":"."} km</span>
                    {/if}
                    {if $auction.exterior_color}
                        <span><i class="fa-solid fa-palette"></i> Màu {$auction.exterior_color|escape}</span>
                    {/if}
                    {if $auction.reference_code}
                        <span><i class="fa-solid fa-hashtag"></i> {$auction.reference_code|escape}</span>
                    {/if}
                </div>

                <div class="auction-detail-facts">
                    <div class="auction-fact">
                        <span>Giá gốc</span>
                        <strong>{$auction.start_price|number_format:0:",":"."} đ</strong>
                    </div>
                    <div class="auction-fact">
                        <span>Bước giá tối thiểu</span>
                        <strong>+{$auction.min_increment|number_format:0:",":"."} đ</strong>
                    </div>
                    <div class="auction-fact">
                        <span>Người tham gia</span>
                        <strong>{$auction.participant_count}</strong>
                    </div>
                    <div class="auction-fact">
                        <span>Lượt đặt giá</span>
                        <strong>{$auction.bid_count}</strong>
                    </div>
                </div>

            </section>

            <aside class="auction-detail-side">

                <div class="auction-bid-panel">

                    <div class="auction-bid-current">
                        <span>Giá hiện tại</span>
                        <strong id="auctionCurrentPrice">
                            {$auction.current_price|number_format:0:",":"."} đ
                        </strong>
                    </div>

                    <div class="auction-countdown auction-countdown-lg" data-end="{$auction.end_at|escape}">
                        <span>Kết thúc sau</span>
                        <strong class="auction-countdown-text">Đang tính thời gian…</strong>
                    </div>

                    {if $is_winner}
                        <div class="auction-note is-winning">
                            <i class="fa-solid fa-crown"></i>
                            Bạn đang giữ giá cao nhất.
                        </div>
                    {/if}

                    {if $is_active}

                        {if isset($current_user) && $current_user}

                            <form class="auction-bid-form" id="auctionBidForm">
                                <label for="bidAmount">
                                    Giá đặt của bạn (tối thiểu
                                    <span id="minNextBidText">{$min_next_bid|number_format:0:",":"."} đ</span>)
                                </label>
                                <input type="number" id="bidAmount" name="amount" class="auction-bid-input"
                                    min="{$min_next_bid|number_format:0:''}0" step="1000"
                                    data-min-next="{$min_next_bid|number_format:0:''}0" placeholder="Nhập giá đặt" required>
                                <button type="submit" class="auction-btn auction-btn-primary">
                                    <i class="fa-solid fa-gavel"></i>
                                    + Thêm tiền
                                </button>
                            </form>

                            <div class="auction-form-msg" id="auctionFormMsg" hidden></div>

                        {else}

                            <a class="auction-btn auction-btn-primary auction-btn-block" href="/auth/login">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Đăng nhập để đấu giá
                            </a>

                        {/if}

                    {elseif $auction.status eq 'awaiting_payment'}

                        <div class="auction-note is-waiting">
                            <i class="fa-solid fa-hourglass-half"></i>
                            Phiên đã kết thúc, đang chờ thanh toán.
                        </div>

                        {if isset($current_user) && $current_user}
                            <a class="auction-btn auction-btn-primary auction-btn-block" href="/my-auctions">
                                <i class="fa-solid fa-credit-card"></i>
                                Xem trạng thái thanh toán
                            </a>
                        {/if}

                    {else}

                        <div class="auction-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Phiên đấu giá đã đóng.
                        </div>

                    {/if}

                </div>

                <div class="auction-history">
                    <h2><i class="fa-solid fa-clock-rotate-left"></i> Lịch sử đặt giá</h2>

                    <ul class="auction-history-list" id="auctionHistoryList">
                        {foreach $bids as $bid}
                            <li class="auction-history-item{if $bid.is_winning} is-winning{/if}">
                                <span class="auction-history-user">
                                    {$bid.user_name|escape}
                                </span>
                                <span class="auction-history-amount">
                                    {$bid.amount|number_format:0:",":"."} đ
                                </span>
                                <span class="auction-history-time">
                                    {$bid.created_at|date_format:"%d/%m/%Y %H:%M"}
                                </span>
                            </li>
                        {foreachelse}
                            <li class="auction-history-empty">
                                Chưa có lượt đặt giá nào. Hãy là người đầu tiên!
                            </li>
                        {/foreach}
                    </ul>
                </div>

            </aside>

        </div>

    </main>

    {include file="home/footer.tpl"}

    <script>
        window.AUCTION_CONFIG = {
            auctionId: {$auction.id},
            minNextBid: {$min_next_bid|number_format:0:''}0,
            isActive: {if $is_active}true{else}false{/if},
            isLoggedIn: {if isset($current_user) && $current_user}true{else}false{/if},
            csrfToken: '{$csrf_token|escape:'javascript'}'
        };
    </script>
    <script src="/assets/js/auction.js"></script>

</body>

</html>