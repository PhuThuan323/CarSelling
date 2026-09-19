<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'CarSelling Admin'}</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <span class="admin-brand-mark">FC</span>
            <div>
                <strong>FastCar</strong>
                <small>Trang quản trị</small>
            </div>
        </div>

        <nav class="admin-nav">
            <p class="admin-nav-title">Danh mục xe</p>
            <button type="button" class="admin-nav-item is-active" data-section="brands">
                <i class="fa-solid fa-tags"></i> Hãng xe
            </button>
            <button type="button" class="admin-nav-item" data-section="models">
                <i class="fa-solid fa-car-side"></i> Dòng xe
            </button>
            <button type="button" class="admin-nav-item" data-section="versions">
                <i class="fa-solid fa-layer-group"></i> Phiên bản xe
            </button>

            <p class="admin-nav-title">Khác</p>
            <a class="admin-nav-item" href="/"><i class="fa-solid fa-store"></i> Xem trang khách hàng</a>
            <a class="admin-nav-item" href="/auth/logout"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </nav>

        {if isset($admin_user)}
        <div class="admin-user">
            <span class="admin-user-avatar">{$admin_user.name|escape|truncate:1:''|upper}</span>
            <div>
                <strong>{$admin_user.name|escape}</strong>
                <small>admin · {$admin_user.email|escape}</small>
            </div>
        </div>
        {/if}
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1>Dashboard quản trị</h1>
                <p>Sản phẩm thêm hoặc sửa ở đây sẽ hiển thị ngay cho khách hàng khi trạng thái là <strong>active</strong>.</p>
            </div>
            <span class="admin-header-badge"><i class="fa-solid fa-shield-halved"></i> Xin chào, {$admin_user.name|escape}</span>
        </header>

        <div class="admin-alert" id="adminAlert" hidden></div>

        {block name="content"}{/block}
    </main>
</div>

<script>
    window.ADMIN_CONFIG = {
        csrfToken: '{$csrf_token|escape:'javascript'}'
    };
</script>
<script src="/assets/js/admin.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="/assets/js/brand-logo-uploader.js"></script>
{block name="scripts"}{/block}
</body>
</html>
