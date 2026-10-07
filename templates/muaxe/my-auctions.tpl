<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'Xe đã đấu giá thành công - FastCar'}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/auction.css">
</head>

<body>

    {include file="home/topbar.tpl"}

    <main class="auction-page">

        <header class="auction-head">
            <div class="auction-head-copy">
                <h1>Xe đã đấu giá thành công</h1>
                <p>
                    Những chiếc xe bạn đã thắng đấu giá. Xe đang chờ thanh toán sẽ được
                    FastCar liên hệ để hoàn tất thủ tục.
                </p>
            </div>

            <a class="auction-head-link" href="/cars">
                <i class="fa-solid fa-gavel"></i>
                Tiếp tục đấu giá
            </a>
        </header>

        {if $won|@count == 0}

            <div class="auction-empty">
                <i class="fa-solid fa-trophy"></i>
                <strong>Bạn chưa thắng phiên đấu giá nào</strong>
                <span>Hãy tham gia đấu giá để sở hữu chiếc xe bạn yêu thích.</span>
                <a href="/cars" class="auction-btn auction-btn-primary">
                    Xem các phiên đấu giá
                </a>
            </div>

        {else}

            <div class="auction-grid">

                {foreach $won as $item}
                    <article class="auction-card auction-won-card">

                        <div class="auction-card-media">
                            <div class="auction-card-placeholder">
                                <i class="fa-solid fa-car-side"></i>
                            </div>
                            <span class="auction-badge {$item.status|escape}">
                                {$item.status_label|escape}
                            </span>
                        </div>

                        <div class="auction-card-body">

                            <h2 class="auction-card-title">{$item.title|escape}</h2>

                            <div class="auction-card-meta">
                                {if $item.manufacture_year}
                                    <span><i class="fa-solid fa-calendar"></i> {$item.manufacture_year|escape}</span>
                                {/if}
                                {if $item.reference_code}
                                    <span><i class="fa-solid fa-hashtag"></i> {$item.reference_code|escape}</span>
                                {/if}
                            </div>

                            <div class="auction-card-prices">
                                <div>
                                    <span>Giá thắng</span>
                                    <strong class="is-current">
                                        {if $item.final_price}
                                            {$item.final_price|number_format:0:",":"."} đ
                                        {else}
                                            {$item.current_price|number_format:0:",":"."} đ
                                        {/if}
                                    </strong>
                                </div>
                            </div>

                            {if $item.status eq 'awaiting_payment'}

                                <div class="auction-note is-waiting">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                    Đang chờ thanh toán. FastCar sẽ liên hệ với bạn.
                                </div>

                            {elseif $item.status eq 'paid'}

                                <div class="auction-note is-paid">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Đã thanh toán thành công.
                                </div>

                            {elseif $item.status eq 'cancelled'}

                                <div class="auction-note is-cancelled">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                    Phiên/thanh toán đã bị hủy.
                                </div>

                            {/if}

                            <a class="auction-btn auction-btn-primary auction-btn-block" href="/cars/{$item.id}">
                                <i class="fa-solid fa-eye"></i>
                                Xem chi tiết phiên
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