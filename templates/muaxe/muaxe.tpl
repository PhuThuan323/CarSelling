<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'Tham gia phiên đấu giá và chọn ngay chiếc xe yêu thích của mình'}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="">
</head>

{include file="home/topbar.tpl"}
<div class="filter-container">
    <div class="filter-header">
        <h1>Bộ lộc phiên kết nối</h1>
    </div>
    <div class="filter-body">
        <form class="filter-form" method="get" action="/muaxe">
            <div class="filter-group">
                <label for="brand">Hãng xe:</label>
                <select name="brand" id="brand">
                    <option value="">Tất cả</option>
                    {foreach $brands as $brand}
                        <option value="{$brand}" {if $selected_brand == $brand}selected{/if}>{$brand}</option>
                    {/foreach}
                </select>
            </div>

            <div class="filter-group">
                <label for="model">Mẫu xe:</label>
                <select name="model" id="model">
                    <option value="">Tất cả</option>
                    {foreach $models as $model}
                        <option value="{$model}" {if $selected_model == $model}selected{/if}>{$model}</option>
                    {/foreach}
                </select>
            </div>

            <div class="filter-group">
                <label for="year">Năm sản xuất:</label>
                <select name="year" id="year">
                    <option value="">Tất cả</option>
                    {foreach $years as $year}
                        <option value="{$year}" {if $selected_year == $year}selected{/if}>{$year}</option>
                    {/foreach}
                </select>
            </div>

            <div class="filter-group">
                <label for="vitri">Vị trí:</label>
                <select name="vitri" id="vitri">
                    <option value="">Tất cả</option>
                    {foreach $locations as $location}
                        <option value="{$location}" {if $selected_location == $location}selected{/if}>{$location}</option>
                    {/foreach}
                </select>
            </div>
            <div class="filter-group">
                <label for="sort">Sắp xếp theo:</label>
                <select name="sort" id="sort">
                    <option value="">Mặc định</option>
                    <option value="date_asc" {if $selected_sort == 'date_asc'}selected{/if}>Ngày đăng tăng dần</option>
                    <option value="nhieu_luot_tra_gia" {if $selected_sort == 'nhieu_luot_tra_gia'}selected{/if}>Nhiều
                        lượt trả giá</option>
                    <option value="price_asc" {if $selected_sort == 'price_asc'}selected{/if}>Giá tăng dần</option>
                    <option value="price_desc" {if $selected_sort == 'price_desc'}selected{/if}>Giá giảm dần</option>
                    <option value="year_asc" {if $selected_sort == 'year_asc'}selected{/if}>Năm sản xuất tăng dần
                    </option>
                    <option value="year_desc" {if $selected_sort == 'year_desc'}selected{/if}>Năm sản xuất giảm dần
                    </option>
                </select>
            </div>
            <div class="filter-group">
                <label for="trang_thai_phien_dau_gia">Trạng thái phiên đấu giá </label>
                <input type="checkbox" name="trang_thai_phien_dau_gia" id="trang_thai_phien_dau_gia" value="1"
                    {if $selected_trang_thai_phien_dau_gia == '1'}checked{/if}> Đang diễn ra
                <input type="checkbox" name="trang_thai_phien_dau_gia" id="trang_thai_phien_dau_gia" value="0"
                    {if $selected_trang_thai_phien_dau_gia == '0'}checked{/if}> Đã kết thúc
                <input type="checkbox" name="trang_thai_phien_dau_gia" id="trang_thai_phien_dau_gia" value="2"
                    {if $selected_trang_thai_phien_dau_gia == '2'}checked{/if}> Đã kết thúc có trả giá
            </div>
            <button type="submit">Lọc</button>
        </form>
    </div>

    <div class="filter-results-header">
        <h2>Kết quả các phiên đấu giá</h2>
    </div>
    <div class="filter-results">
        {if $muaxe|@count == 0}
            <p>Không tìm thấy kết quả phù hợp với bộ lọc.</p>
        {else}
            <ul class="muaxe-list">
                {foreach $muaxe as $item}
                    <li class="muaxe-item">
                        <a href="/muaxe/{$item.slug|escape:'url'}">
                            <h2>{$item.title|escape}</h2>
                            <p>{$item.description|escape}</p>
                        </a>
                    </li>
                {/foreach}
            </ul>
        {/if}
    </div>

</div>
<include file="home/footer.tpl" />