<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title|default:'FastCar Staff'}</title>
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
                    <small>Nhân viên Inspection</small>
                </div>
            </div>

            <nav class="admin-nav">
                <p class="admin-nav-title">Tổng quan</p>
                <a class="admin-nav-item" href="/staff/inspections">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>

                <p class="admin-nav-title">Inspection</p>
                <a class="admin-nav-item{if isset($active_menu) && $active_menu eq 'staff_tasks'} is-active{/if}"
                    href="/staff/inspections">
                    <i class="fa-solid fa-clipboard-list"></i> Nhiệm vụ của tôi
                    {if isset($counts.total) && $counts.total > 0}
                        <span class="admin-nav-badge">{$counts.total}</span>
                    {/if}
                </a>
                <a class="admin-nav-item admin-nav-sub" href="/staff/inspections">
                    Chờ nhận
                    {if isset($counts.assigned) && $counts.assigned > 0}
                        <span class="admin-nav-badge">{$counts.assigned}</span>
                    {/if}
                </a>
                <a class="admin-nav-item admin-nav-sub" href="/staff/inspections">
                    Đang thực hiện
                    {if isset($counts.in_progress) && $counts.in_progress > 0}
                        <span class="admin-nav-badge">{$counts.in_progress}</span>
                    {/if}
                </a>
                <a class="admin-nav-item admin-nav-sub" href="/staff/inspections">
                    Đã hoàn tất
                    {if isset($counts.completed) && $counts.completed > 0}
                        <span class="admin-nav-badge">{$counts.completed}</span>
                    {/if}
                </a>

                <p class="admin-nav-title">Thông báo</p>
                <a class="admin-nav-item" href="/staff/inspections">
                    <i class="fa-solid fa-bell"></i> Thông báo
                    {if isset($unread_notifications) && $unread_notifications > 0}
                        <span class="admin-nav-badge is-warning">{$unread_notifications}</span>
                    {/if}
                </a>

                <p class="admin-nav-title">Khác</p>
                <a class="admin-nav-item" href="/"><i class="fa-solid fa-store"></i> Xem trang khách hàng</a>
                <a class="admin-nav-item" href="/auth/logout"><i class="fa-solid fa-right-from-bracket"></i> Đăng
                    xuất</a>
            </nav>

            {if isset($staff_user)}
                <div class="admin-user">
                    <span class="admin-user-avatar">{$staff_user.name|escape|truncate:1:''|upper}</span>
                    <div>
                        <strong>{$staff_user.name|escape}</strong>
                        <small>staff · {$staff_user.email|escape}</small>
                    </div>
                </div>
            {/if}
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div>
                    <h1>Khu vực Inspection</h1>
                    <p>Bạn đánh giá tình trạng xe và <strong>đề xuất range giá</strong>. Admin là người chốt giá cuối
                        cùng.</p>
                </div>
                <span class="admin-header-badge">
                    <i class="fa-solid fa-user-check"></i> Xin chào, {$staff_user.name|escape}
                </span>
            </header>

            <div class="admin-alert" id="staffAlert" hidden></div>

            {block name="content"}{/block}
        </main>
    </div>

    <script>
        window.STAFF_CONFIG = {
            csrfToken: '{$csrf_token|escape:'javascript'}'
        };
    </script>
    {block name="scripts"}{/block}
</body>

</html>