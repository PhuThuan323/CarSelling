<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/banxe.css">
<link rel="stylesheet" href="/assets/css/my-selling-cars.css">

{include file="../home/topbar.tpl"}

<div class="mycars">

    <header class="mycars-head">
        <h1>Xe bạn đã đăng bán</h1>
        <p>Theo dõi trạng thái thẩm định và xem kết quả định giá của FastCar.</p>
    </header>

    {if $cars|@count == 0}

        <div class="mycars-empty">
            <i class="fa-solid fa-car-side"></i>
            <strong>Bạn chưa đăng bán chiếc xe nào</strong>
            <span>Hãy gửi thông tin xe để FastCar định giá miễn phí.</span>
            <a href="/sell-car" class="mycars-btn mycars-btn-primary">
                Đăng bán xe
            </a>
        </div>

    {else}

        <div class="mycars-grid">

            {foreach $cars as $car}
                <article class="mycar-card">

                    <header>
                        <div>
                            <strong>{$car.reference_code|escape}</strong>
                            <h2>{$car.vehicle_label|escape}</h2>
                        </div>

                        <span class="mycar-status {$car.status|escape}">
                            {$car.status_label|escape}
                        </span>
                    </header>

                    <dl class="mycar-facts">
                        <dt>Đời xe</dt>
                        <dd>{$car.manufacture_year|escape}</dd>

                        <dt>ODO</dt>
                        <dd>
                            {if $car.odometer_km}
                                {$car.odometer_km|number_format:0:",":"."} km
                            {else}
                                —
                            {/if}
                        </dd>

                        <dt>Ngày gửi</dt>
                        <dd>
                            {if $car.submitted_at}
                                {$car.submitted_at|date_format:"%d/%m/%Y"}
                            {else}
                                —
                            {/if}
                        </dd>
                    </dl>

                    {if $car.status eq 'estimated' && $car.estimated_price_min}
                        <div class="mycar-price">
                            <span>Giá FastCar đề xuất</span>
                            <strong>
                                {$car.estimated_price_min|number_format:0:",":"."} đ
                                -
                                {$car.estimated_price_max|number_format:0:",":"."} đ
                            </strong>
                        </div>
                    {/if}

                    <a class="mycars-btn mycars-btn-primary" href="/my-selling-cars/{$car.id}">
                        <i class="fa-solid fa-eye"></i> Xem chi tiết
                    </a>

                </article>
            {/foreach}

        </div>

    {/if}

</div>

{include file="../home/footer.tpl"}