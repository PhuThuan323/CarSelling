{extends file="layouts/admin.tpl"}

{block name="content"}

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Hồ sơ {$request.reference_code|escape}</h2>
                <p>Trạng thái: <strong>{$status_label|escape}</strong></p>
            </div>
            <a class="admin-btn admin-btn-ghost" href="/admin/inspections">
                <i class="fa-solid fa-arrow-left"></i> Danh sách
            </a>
        </div>

        <div class="inspection-grid">

            {* ============ THÔNG TIN XE ============ *}
            <div class="inspection-card">
                <h3><i class="fa-solid fa-car"></i> Thông tin xe</h3>

                {assign var="snap" value=$request.vehicle_snapshot}

                <dl class="inspection-facts">
                    <dt>Dòng xe</dt>
                    <dd>
                        {if isset($snap.brand_name)}{$snap.brand_name|escape}{/if}
                        {if isset($snap.model_name)} {$snap.model_name|escape}{/if}
                        {if isset($snap.version_name)} · {$snap.version_name|escape}{/if}
                    </dd>

                    <dt>Đời xe</dt>
                    <dd>{$request.manufacture_year|escape}</dd>

                    <dt>ODO khai báo</dt>
                    <dd>
                        {if $request.odometer_km}
                            {$request.odometer_km|number_format:0:",":"."} km
                        {else}
                            <span class="admin-muted">Chưa có</span>
                        {/if}
                    </dd>

                    <dt>Biển số</dt>
                    <dd>{$request.license_plate|default:'—'|escape}</dd>

                    <dt>Màu ngoại thất</dt>
                    <dd>{$request.exterior_color|default:'—'|escape}</dd>

                    <dt>Số chủ</dt>
                    <dd>{$request.owners_count|default:'—'|escape}</dd>
                </dl>
            </div>

            {* ============ THÔNG TIN KHÁCH ============ *}
            <div class="inspection-card">
                <h3><i class="fa-solid fa-user"></i> Khách hàng</h3>

                <dl class="inspection-facts">
                    <dt>Họ tên</dt>
                    <dd>{$request.contact_name|default:'—'|escape}</dd>

                    <dt>SĐT</dt>
                    <dd>{$request.contact_phone|default:'—'|escape}</dd>

                    <dt>Email</dt>
                    <dd>{$request.contact_email|default:'—'|escape}</dd>

                    <dt>Ghi chú của khách</dt>
                    <dd>{$request.seller_note|default:'—'|escape|nl2br}</dd>
                </dl>
            </div>

        </div>

        {* ============ PHÂN CÔNG ============ *}
        <div class="inspection-card">
            <h3><i class="fa-solid fa-user-gear"></i> Phân công Inspection</h3>

            {if $assignment}
                <div class="admin-alert is-success">
                    Đang phụ trách:
                    <strong>
                        {if $assignment.status eq 'assigned'}Chờ nhận nhiệm vụ{/if}
                        {if $assignment.status eq 'accepted'}Đã nhận nhiệm vụ{/if}
                        {if $assignment.status eq 'in_progress'}Đang thực hiện{/if}
                        {if $assignment.status eq 'completed'}Đã hoàn tất{/if}
                    </strong>
                    {if $assignment.scheduled_at}
                        · Lịch {$assignment.scheduled_at|date_format:"%d/%m/%Y %H:%M"}
                    {/if}
                </div>
            {/if}

            <form id="assignForm" class="admin-form-grid">
                <div class="admin-field">
                    <label for="staffUserId">Nhân viên Inspection *</label>
                    <select class="admin-select" id="staffUserId" name="staff_user_id" required>
                        <option value="">-- Chọn nhân viên --</option>
                        {foreach $staff_options as $staff}
                            <option value="{$staff.id}" {if $assignment && $assignment.staff_user_id == $staff.id}selected{/if}>
                                {$staff.name|escape}
                                {if $staff.phone} · {$staff.phone|escape}{/if}
                            </option>
                        {/foreach}
                    </select>
                    {if $staff_options|@count == 0}
                        <small class="admin-muted">
                            Chưa có nhân viên nào có role = staff. Hãy tạo tài khoản nhân viên trước.
                        </small>
                    {/if}
                </div>

                <div class="admin-field">
                    <label for="scheduledAt">Lịch inspection</label>
                    <input type="datetime-local" class="admin-input" id="scheduledAt" name="scheduled_at"
                        value="{if $assignment && $assignment.scheduled_at}{$assignment.scheduled_at|date_format:"%Y-%m-%dT%H:%M"}{/if}">
                </div>

                <div class="admin-field admin-field-full">
                    <label for="adminNote">Ghi chú cho nhân viên</label>
                    <textarea class="admin-textarea" id="adminNote" name="admin_note" maxlength="500"
                        placeholder="Kiểm tra kỹ phần máy và sơn">{if $assignment}{$assignment.admin_note|escape}{/if}</textarea>
                </div>

                <div class="admin-field admin-field-full">
                    <button type="submit" class="admin-btn admin-btn-primary" id="assignSubmit">
                        <i class="fa-solid fa-user-check"></i>
                        {if $assignment}Đổi nhân viên{else}Phân công{/if}
                    </button>
                </div>
            </form>

            {if $assignment_history|@count > 1}
                <h4 class="inspection-subtitle">Lịch sử phân công</h4>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Trạng thái</th>
                            <th>Lịch</th>
                            <th>Phân công lúc</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $assignment_history as $row}
                            <tr>
                                <td>{$row.staff_name|escape}</td>
                                <td>{$row.status|escape}</td>
                                <td>
                                    {if $row.scheduled_at}
                                        {$row.scheduled_at|date_format:"%d/%m/%Y %H:%M"}
                                    {else}—
                                    {/if}
                                </td>
                                <td>{$row.assigned_at|date_format:"%d/%m/%Y %H:%M"}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            {/if}
        </div>

        {* ============ KẾT QUẢ INSPECTION ============ *}
        <div class="inspection-card">
            <h3><i class="fa-solid fa-clipboard-list"></i> Kết quả thẩm định</h3>

            {if !$result}
                <p class="admin-muted">
                    Nhân viên chưa gửi kết quả thẩm định cho hồ sơ này.
                </p>
            {else}
                <dl class="inspection-facts">
                    <dt>Nhân viên</dt>
                    <dd>{$result.staff_name|default:'—'|escape}</dd>

                    <dt>ODO thực tế</dt>
                    <dd>
                        {if $result.odometer_actual}
                            {$result.odometer_actual|number_format:0:",":"."} km
                        {else}—
                        {/if}
                    </dd>

                    <dt>Gửi lúc</dt>
                    <dd>{$result.submitted_at|date_format:"%d/%m/%Y %H:%M"}</dd>
                </dl>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Hạng mục</th>
                            <th>Nhóm</th>
                            <th>Đánh giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $rating_fields as $field}
                            <tr>
                                <td>{$field.label|escape}</td>
                                <td>{$field.group|escape}</td>
                                <td>
                                    <span
                                        class="admin-pill {if $field.value eq 'good'}is-ok{elseif $field.value eq 'poor' or $field.value eq 'issue'}is-bad{/if}">
                                        {$field.label_value|escape}
                                    </span>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>

                <div class="inspection-note">
                    <strong>Nhận xét tổng quan</strong>
                    <p>{$result.summary|escape|nl2br}</p>
                </div>

                {if $result.staff_note}
                    <div class="inspection-note">
                        <strong>Ghi chú nội bộ của nhân viên</strong>
                        <p>{$result.staff_note|escape|nl2br}</p>
                    </div>
                {/if}

                <div class="inspection-range">
                    <span>Range giá staff đề xuất</span>
                    <strong>
                        {$result.suggested_price_min|number_format:0:",":"."} đ
                        -
                        {$result.suggested_price_max|number_format:0:",":"."} đ
                    </strong>
                </div>

                {* ============ ADMIN CHỐT GIÁ ============ *}
                <h4 class="inspection-subtitle">
                    <i class="fa-solid fa-gavel"></i> Kết quả gửi khách
                </h4>

                <p class="admin-muted">
                    Staff chỉ <strong>đề xuất</strong>. Admin là người chốt range giá cuối cùng
                    và viết feedback gửi khách.
                </p>

                {if $latest_feedback}
                    <div class="admin-alert">
                        Đã gửi khách lúc
                        {$latest_feedback.sent_at|date_format:"%d/%m/%Y %H:%M"}.
                        Gửi lại sẽ tạo một bản ghi feedback mới.
                    </div>
                {/if}

                <form id="approveForm" class="admin-form-grid">
                    <div class="admin-field">
                        <label for="priceMin">Giá đề xuất thấp nhất (đ) *</label>
                        <input type="text" class="admin-input" id="priceMin" name="price_min" inputmode="numeric"
                            placeholder="425000000"
                            value="{if $latest_feedback}{$latest_feedback.price_min|number_format:0:''|escape}{/if}" required>
                    </div>

                    <div class="admin-field">
                        <label for="priceMax">Giá đề xuất cao nhất (đ) *</label>
                        <input type="text" class="admin-input" id="priceMax" name="price_max" inputmode="numeric"
                            placeholder="440000000"
                            value="{if $latest_feedback}{$latest_feedback.price_max|number_format:0:''|escape}{/if}" required>
                    </div>

                    <div class="admin-field admin-field-full">
                        <label for="feedbackMessage">Feedback gửi khách hàng *</label>
                        <textarea class="admin-textarea" id="feedbackMessage" name="message" rows="5"
                            placeholder="Xe có tình trạng tổng thể tốt. Ngoại thất có một số vết xước nhẹ..."
                            required>{if $latest_feedback}{$latest_feedback.message|escape}{/if}</textarea>
                    </div>

                    <div class="admin-field admin-field-full">
                        <button type="submit" class="admin-btn admin-btn-primary" id="approveSubmit"
                            {if $request.status neq 'inspection_completed' && $request.status neq 'estimated'}disabled{/if}>
                            <i class="fa-solid fa-paper-plane"></i> Gửi kết quả cho khách
                        </button>

                        {if $request.status eq 'estimated'}
                            <span class="admin-muted">
                                Hồ sơ đã có kết quả. Gửi lại sẽ cập nhật giá và tạo feedback mới.
                            </span>
                        {elseif $request.status neq 'inspection_completed'}
                            <span class="admin-muted">
                                Cần có kết quả inspection trước khi chốt giá.
                            </span>
                        {/if}
                    </div>
                </form>
            {/if}
        </div>

        {* ============ TIMELINE ============ *}
        <div class="inspection-card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Lịch sử trạng thái</h3>

            {if $history|@count == 0}
                <p class="admin-muted">Chưa có lịch sử.</p>
            {else}
                <ul class="inspection-timeline">
                    {foreach $history as $entry}
                        <li>
                            <span class="inspection-timeline-dot"></span>
                            <div>
                                <strong>{$entry.new_status|escape}</strong>
                                <small>
                                    {$entry.created_at|date_format:"%d/%m/%Y %H:%M"}
                                    · {$entry.changed_by_type|escape}
                                </small>
                                {if $entry.note}<p>{$entry.note|escape}</p>{/if}
                            </div>
                        </li>
                    {/foreach}
                </ul>
            {/if}
        </div>
    </section>

{/block}

{block name="scripts"}
    <script>
        window.INSPECTION_CONFIG = {
            requestId: {$request.id},
            csrfToken: '{$csrf_token|escape:'javascript'}',
            assignUrl: '/api/v1/admin/inspections/{$request.id}/assign',
            approveUrl: '/api/v1/admin/inspections/{$request.id}/approve'
        };
    </script>
    <script src="/assets/js/admin-inspection.js"></script>
{/block}