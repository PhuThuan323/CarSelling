<link rel="stylesheet" href="../assets/css/topbar.css">

<header class="topbar-section">

    <div class="topbar-inner">

        <!-- LOGO -->
        <a href="/" class="topbar-logo" aria-label="FASTCAR - Trang chủ">
            {* Nếu đã có logo ảnh thì dùng đoạn này:
            <img src="/assets/img/logo.png" alt="FASTCAR">
            *}

            <span class="topbar-logo-text">
                FASTCAR
            </span>
        </a>


        <!-- MENU -->
        <nav class="redirect-url" aria-label="Điều hướng chính">

            <a href="/how-it-works">
                Cách Thức Hoạt Động
            </a>
            
            <a href="/sell-car">
                Bán Xe
            </a>

            <a href="/cars">
                Mua Xe
            </a>

            <a href="/account">
                Tài Khoản
            </a>

        </nav>


        <!-- ACCOUNT -->
        <div class="user-management">

            {if isset($current_user) && $current_user}

                <a
                    class="admin-entry-link"
                    href="{if $current_user.role eq 'admin'}/admin{else}/account{/if}"
                >
                    Xin chào,
                    {$current_user.name|escape}
                </a>


                <div
                    class="user-settings"
                    id="userSettings"
                >

                    <button
                        type="button"
                        class="user-settings-btn"
                        id="userSettingsToggle"
                        aria-label="Cài đặt tài khoản"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <i class="fa-solid fa-gear"></i>
                    </button>


                    <div
                        class="user-settings-menu"
                        id="userSettingsMenu"
                    >

                        {if $current_user.role eq 'admin'}

                            <a href="/admin">
                                <i class="fa-solid fa-gauge"></i>
                                Trang quản trị
                            </a>

                        {/if}


                        <a href="/account">
                            <i class="fa-solid fa-user"></i>
                            Tài khoản của tôi
                        </a>


                        <a href="/auth/logout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Đăng xuất
                        </a>

                    </div>

                </div>

            {else}

                <a
                    class="user-management-btn"
                    href="/auth/login"
                >
                    Đăng nhập
                </a>


                <a
                    class="
                        user-management-btn
                        user-management-btn-primary
                    "
                    href="/auth/register"
                >
                    Đăng ký
                </a>

            {/if}

        </div>

    </div>

</header>


{*
    Topbar dùng position: fixed nên cần spacer.
    Nhờ spacer này các trang khác không cần tự padding-top.
*}

<div
    class="topbar-spacer"
    aria-hidden="true"
></div>


<script src="/assets/js/topbar.js"></script>