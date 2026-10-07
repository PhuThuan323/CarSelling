<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'Chi phí và điều kiện áp dụng khi bán xe với FastCar'}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<include file="home/topbar.tpl" />
<div class="chiphi-container">
    <div class="return-button">
        <a class="mycars-back" href="/muaxe">
            <i class="fa-solid fa-arrow-left"></i> Quay lại
        </a>
    </div>
    <div class="chiphi-header">
        <h1>Chi phí và điều kiện áp dụng khi bán xe với FastCar</h1>
    </div>
    <div class="chiphi-body">
        <a href="/policy?name=bang_chi_phi.txt" target="_blank">Xem bảng chi phí</a>    

    </div>
    <div class="quytrinh-banxe">
        <button class="quytrinh-button" onclick="window.location.href='/policy?name=quy_trinh_ban_xe.txt'">Tìm hiểu quy trình bán xe</button>
    </div>
</div>