{extends file="layouts/admin.tpl"}

{block name="content"}

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>{$screen_title|escape}</h2>
                <p>{$screen_subtitle|escape}</p>
            </div>
        </div>

        {* Tab loc theo quyen *}
        {if isset($role_filters) && $role_filters|@count > 0}
            <div class="admin-tabs">
                {foreach $role_filters as $tab}
                    {if $tab.key eq 'all'}
                        {assign var="tabUrl" value="/admin/staff"}
                    {else}
                        {assign var="tabUrl" value="/admin/staff?role=`$tab.key`"}
                    {/if}

                    <a class="admin-tab{if $tab.is_active} is-active{/if}" href="{$tabUrl}">
                        {$tab.label|escape}
                        <span class="admin-tab-count">{$tab.total}</span>
                    </a>
                {/foreach}
            </div>
        {/if}

        {* Thanh tim kiem (loc phia client, khong can tai lai) *}
        <div class="admin-filters">
            <div class="admin-field">
                <label for="staffSearch">Tìm kiếm</label>
                <input type="search" class="admin-input" id="staffSearch" placeholder="Tên, email hoặc số điện thoại...">
            </div>
        </div>

        {if $members|@count == 0}
            <div class="admin-empty">
                <i class="fa-solid fa-users"></i>
                <strong>Không có người dùng nào</strong>
                <span>Thử đổi bộ lọc hoặc từ khóa tìm kiếm.</span>
            </div>
        {else}
            <div class="admin-table-wrap">
                <table class="admin-table staff-table">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Liên hệ</th>
                            <th>Quyền hiện tại</th>
                            <th>Trạng thái</th>
                            <th>Hồ sơ đang xử lý</th>
                            <th>Phân quyền</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody">
                        {foreach $members as $member}
                            {assign var="isStaff" value=($member.role eq 'staff')}
                            {assign var="isAdmin" value=($member.role eq 'admin')}
                            {assign var="isBlocked" value=($member.status ne 'active')}

                            <tr data-search="{$member.name|escape} {$member.email|escape} {$member.phone|escape}"
                                data-user-id="{$member.id}">
                                <td>
                                    <div class="staff-user">
                                        <span class="staff-avatar">
                                            {$member.name|escape|truncate:1:''|upper}
                                        </span>
                                        <div>
                                            <strong>{$member.name|escape}</strong>
                                            <small class="admin-muted">
                                                ID #{$member.id}
                                                {if $member.is_self} · bạn{/if}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="admin-muted">{$member.email|escape}</small>
                                    {if $member.phone}
                                        <small class="admin-muted">{$member.phone|escape}</small>
                                    {/if}
                                </td>
                                <td>
                                    {if $isAdmin}
                                        <span class="admin-pill is-admin">
                                            <i class="fa-solid fa-shield-halved"></i> Quản trị viên
                                        </span>
                                    {elseif $isStaff}
                                        <span class="admin-pill is-staff">
                                            <i class="fa-solid fa-user-check"></i> Nhân viên Inspection
                                        </span>
                                    {else}
                                        <span class="admin-pill">
                                            <i class="fa-solid fa-user"></i> Khách hàng
                                        </span>
                                    {/if}
                                </td>
                                <td>
                                    {if $member.status eq 'active'}
                                        <span class="admin-pill is-active">Đang hoạt động</span>
                                    {elseif $member.status eq 'blocked'}
                                        <span class="admin-pill is-danger">Đã khóa</span>
                                    {else}
                                        <span class="admin-pill is-muted">Tạm ngưng</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $isStaff && $member.open_inspections > 0}
                                        <span class="staff-warn">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            {$member.open_inspections} hồ sơ
                                        </span>
                                    {else}
                                        <span class="admin-muted">—</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $member.is_self}
                                        <span class="admin-muted">Tài khoản của bạn</span>
                                    {else}
                                        <div class="staff-actions">
                                            {if $isStaff}
                                                <button type="button" class="admin-btn admin-btn-danger-soft" data-role-action="customer"
                                                    data-user-name="{$member.name|escape}">
                                                    Thu hồi quyền
                                                </button>
                                            {else}
                                                <button type="button" class="admin-btn admin-btn-primary" data-role-action="staff"
                                                    data-user-name="{$member.name|escape}">
                                                    <i class="fa-solid fa-user-check"></i>
                                                    Cấp quyền Staff
                                                </button>
                                            {/if}

                                            {if $isBlocked}
                                                <button type="button" class="admin-btn admin-btn-ghost" data-status-action="active"
                                                    data-user-name="{$member.name|escape}">
                                                    Mở lại
                                                </button>
                                            {else}
                                                <button type="button" class="admin-btn admin-btn-ghost" data-status-action="blocked"
                                                    data-user-name="{$member.name|escape}">
                                                    Khóa
                                                </button>
                                            {/if}
                                        </div>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            <div class="admin-empty" id="staffNoResult" hidden>
                <i class="fa-solid fa-magnifying-glass"></i>
                <strong>Không tìm thấy người dùng phù hợp</strong>
            </div>
        {/if}
    </section>

{/block}

{block name="scripts"}
    <script src="/assets/js/admin-staff.js"></script>
{/block}