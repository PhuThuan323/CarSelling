<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'Mua xe đấu giá - FastCar'}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/auction.css">
</head>

<body>

{include file="home/topbar.tpl"}

<main class="auction-page">

    <header class="auction-head">
        <div class="auction-head-copy">
            <h1>Mua xe đấu giá</h1>
            <p>
                Tham gia đấu giá những chiếc xe đã được FastCar thẩm định.
                Mỗi lượt đặt giá tối thiểu tăng thêm 0,05% giá gốc.
            </p>
        </div>

        {if isset($current_user) && $current_user}
            <a class="auction-head-link" href="/my-auctions">
                <i class="fa-solid fa-trophy"></i>
                Xe đã đấu giá thành công
            </a>
        {/if}
    </header>

    {if $auctions|@count == 0}

        <div class="auction-empty">
            <i class="fa-solid fa-gavel"></i>
            <strong>Hiện chưa có phiên đấu giá nào đang diễn ra</strong>
            <span>Vui lòng quay lại sau để đón những phiên đấu giá mới nhất.</span>
        </div>

    {else}

        <div class="auction-grid">

            {foreach $auctions as $item}
                <article class="auction-card">

                    <div class="auction-card-media">
                        <div class="auction-card-placeholder">
                            <i class="fa-solid fa-car-side"></i>
                        </div>
                        <span class="auction-badge">Đang đấu giá</span>
                    </div>

                    <div class="auction-card-body">

                        <h2 class="auction-card-title">
                            {$item.title|escape}
                        </h2>

                        <div class="auction-card-meta">
                            {if $item.manufacture_year}
                                <span><i class="fa-solid fa-calendar"></i> {$item.manufacture_year|escape}</span>
                            {/if}
                            {if $item.odometer_km}
                                <span><i class="fa-solid fa-road"></i> {$item.odometer_km|number_format:0:",":"."} km</span>
                            {/if}
                            {if $item.exterior_color}
                                <span><i class="fa-solid fa-palette"></i> {$item.exterior_color|escape}</span>
                            {/if}
                        </div>

                        <div class="auction-card-prices">
                            <div>
                                <span>Giá gốc</span>
                                <strong>{$item.start_price|number_format:0:",":"."} đ</strong>
                            </div>
                            <div>
                                <span>Giá hiện tại</span>
                                <strong class="is-current">
                                    {$item.current_price|number_format:0:",":"."} đ
                                </strong>
                            </div>
                        </div>

                        <div class="auction-card-stats">
                            <span>
                                <i class="fa-solid fa-users"></i>
                                {$item.participant_count} người tham gia
                            </span>
                            <span>
                                <i class="fa-solid fa-gavel"></i>
                                {$item.bid_count} lượt đặt giá
                            </span>
                        </div>

                        <div class="auction-card-countdown"
                             data-end="{$item.end_at|escape}">
                            <i class="fa-regular fa-clock"></i>
                            <span class="auction-countdown-text">Đang tính thời gian…</span>
                        </div>

                        <a class="auction-btn auction-btn-primary"
                           href="/cars/{$item.id}">
                            <i class="fa-solid fa-gavel"></i>
                            Tham gia đấu giá
                        </a>

                    </div>

                </article>
            {/foreach}

        </div>

    {/if}

</main>

{include file="home/footer.tpl"}

<script src="/assets/js/auction.js"></script>

</body>
</html>
