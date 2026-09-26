{extends file="layouts/staff.tpl"}

{block name="content"}

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>{$vehicle_label|escape}</h2>
                <p>
                    Mã hồ sơ: <strong>{$request.reference_code|escape}</strong>
                    · <span class="admin-pill">{$assignment_status_label|escape}</span>
                </p>
            </div>
            <a class="admin-btn admin-btn-ghost" href="/staff/inspections">
                <i class="fa-solid fa-arrow-left"></i> Danh sách
            </a>
        </div>

        {assign var="snap" value=$request.vehicle_snapshot}

        <div class="inspection-grid">
            <div class="inspection-card">
                <h3><i class="fa-solid fa-user"></i> Khách hàng</h3>
                <dl class="inspection-facts">
                    <dt>Họ tên</dt>
                    <dd>{$request.contact_name|default:'—'|escape}</dd>

                    <dt>SĐT</dt>
                    <dd>{$request.contact_phone|default:'—'|escape}</dd>

                    <dt>Khu vực</dt>
                    <dd>{$request.registration_province|default:'—'|escape}</dd>
                </dl>
            </div>

            <div class="inspection-card">
                <h3><i class="fa-solid fa-car"></i> Xe cần kiểm tra</h3>
                <dl class="inspection-facts">
                    <dt>Phiên bản</dt>
                    <dd>
                        {if isset($snap.version_name)}
                            {$snap.version_name|escape}
                        {else}
                            {$snap.model_name|default:'—'|escape}
                        {/if}
                    </dd>

                    <dt>Đời xe</dt>
                    <dd>{$request.manufacture_year|escape}</dd>

                    <dt>Biển số</dt>
                    <dd>{$request.license_plate|default:'—'|escape}</dd>

                    <dt>Màu xe</dt>
                    <dd>{$request.exterior_color|default:'—'|escape}</dd>

                    <dt>ODO khai báo</dt>
                    <dd>
                        {if $request.odometer_km}
                            {$request.odometer_km|number_format:0:",":"."} km
                        {else}—
                        {/if}
                    </dd>

                    <dt>Số chủ</dt>
                    <dd>{$request.owners_count|default:'—'|escape}</dd>
                </dl>
            </div>
        </div>

        {if $assignment.admin_note}
            <div class="inspection-card">
                <h3><i class="fa-solid fa-note-sticky"></i> Ghi chú từ Admin</h3>
                <p>{$assignment.admin_note|escape|nl2br}</p>
            </div>
        {/if}

        {* ==================== BƯỚC NHẬN NHIỆM VỤ ==================== *}
        {if $assignment.status eq 'assigned'}
            <div class="inspection-card is-action">
                <h3><i class="fa-solid fa-hand"></i> Nhận nhiệm vụ</h3>
                <p class="admin-muted">
                    Bạn cần nhận nhiệm vụ trước khi bắt đầu kiểm tra xe.
                </p>
                <button type="button" class="admin-btn admin-btn-primary" id="acceptBtn">
                    <i class="fa-solid fa-check"></i> Nhận nhiệm vụ
                </button>
            </div>
        {/if}

        {* ==================== BƯỚC BẮT ĐẦU ==================== *}
        {if $assignment.status eq 'accepted'}
            <div class="inspection-card is-action">
                <h3><i class="fa-solid fa-play"></i> Bắt đầu Inspection</h3>
                <p class="admin-muted">
                    Sau khi bấm bắt đầu, hồ sơ chuyển sang trạng thái Đang inspection.
                </p>
                <button type="button" class="admin-btn admin-btn-primary" id="startBtn">
                    <i class="fa-solid fa-flag-checkered"></i> Bắt đầu inspection
                </button>
            </div>
        {/if}

        {* ==================== FORM KẾT QUẢ ==================== *}
        {if $assignment.status eq 'accepted' || $assignment.status eq 'in_progress'}
            <form id="resultForm" class="inspection-card">
                <h3><i class="fa-solid fa-clipboard-list"></i> Phiếu đánh giá tình trạng xe</h3>

                <h4 class="inspection-subtitle">Thông tin chung</h4>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="odometerActual">ODO thực tế (km)</label>
                        <input type="number" class="admin-input" id="odometerActual" name="odometer_actual" min="0"
                            value="{if $result}{$result.odometer_actual|escape}{/if}" placeholder="45000">
                    </div>
                </div>

                {* Nhóm hạng mục đánh giá *}
                {assign var="currentGroup" value=""}

                {foreach $rating_fields as $field}
                    {if $field.group neq $currentGroup}
                        {assign var="currentGroup" value=$field.group}
                        <h4 class="inspection-subtitle">{$field.group|escape}</h4>
                        <div class="rating-grid">
                        {/if}

                        <div class="rating-field">
                            <label for="rating_{$field.key}">{$field.label|escape}</label>
                            <select class="admin-select" id="rating_{$field.key}" name="{$field.key}">
                                <option value="">-- Chọn --</option>
                                {foreach $field.options as $option}
                                    <option value="{$option.value}" {if $field.value eq $option.value}selected{/if}>
                                        {$option.label|escape}
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        {* Đóng nhóm khi hạng mục kế tiếp thuộc nhóm mới hoặc là cuối *}
                        {if $field@last}
                        </div>
                    {else}
                        {assign var="nextIndex" value=$field@index+1}
                        {if $rating_fields[$nextIndex].group neq $currentGroup}
                            </div>
                        {/if}
                    {/if}
                {/foreach}

                <h4 class="inspection-subtitle">Nhận xét tổng quan *</h4>
                <div class="admin-form-grid">
                    <div class="admin-field admin-field-full">
                        <textarea class="admin-textarea" id="summary" name="summary" rows="5"
                            placeholder="Xe vận hành ổn định. Ngoại thất có trầy xước nhẹ. Lốp trước còn khoảng 50%..."
                            required>{if $result}{$result.summary|escape}{/if}</textarea>
                    </div>

                    <div class="admin-field admin-field-full">
                        <label for="staffNote">Ghi chú nội bộ (chỉ admin xem)</label>
                        <textarea class="admin-textarea" id="staffNote" name="staff_note" rows="3"
                            placeholder="Thông tin không gửi cho khách...">{if $result}{$result.staff_note|escape}{/if}</textarea>
                    </div>
                </div>

                <h4 class="inspection-subtitle">
                    <i class="fa-solid fa-tags"></i> Range giá bạn đề xuất
                </h4>
                <p class="admin-muted">
                    Đây là mức giá bạn <strong>đề xuất</strong>. Admin sẽ là người chốt giá cuối cùng gửi khách.
                </p>

                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="suggestedMin">Giá thấp (đ) *</label>
                        <input type="text" class="admin-input" id="suggestedMin" name="suggested_price_min" inputmode="numeric"
                            placeholder="420000000"
                            value="{if $result}{$result.suggested_price_min|number_format:0:''|escape}{/if}" required>
                    </div>

                    <div class="admin-field">
                        <label for="suggestedMax">Giá cao (đ) *</label>
                        <input type="text" class="admin-input" id="suggestedMax" name="suggested_price_max" inputmode="numeric"
                            placeholder="445000000"
                            value="{if $result}{$result.suggested_price_max|number_format:0:''|escape}{/if}" required>
                    </div>

                    <div class="admin-field admin-field-full">
                        <button type="submit" class="admin-btn admin-btn-primary" id="resultSubmit">
                            <i class="fa-solid fa-paper-plane"></i> Gửi kết quả cho Admin
                        </button>
                    </div>
                </div>
            </form>
        {/if}

        {* ==================== ĐÃ HOÀN TẤT ==================== *}
        {if $assignment.status eq 'completed'}
            <div class="inspection-card">
                <h3><i class="fa-solid fa-circle-check"></i> Bạn đã gửi kết quả</h3>
                <div class="admin-alert is-success">
                    Kết quả đã được gửi cho admin lúc
                    {$assignment.completed_at|date_format:"%d/%m/%Y %H:%M"}.
                </div>

                {if $result}
                    <dl class="inspection-facts">
                        <dt>ODO thực tế</dt>
                        <dd>
                            {if $result.odometer_actual}
                                {$result.odometer_actual|number_format:0:",":"."} km
                            {else}—
                            {/if}
                        </dd>
                    </dl>

                    <div class="inspection-note">
                        <strong>Nhận xét tổng quan</strong>
                        <p>{$result.summary|escape|nl2br}</p>
                    </div>

                    <div class="inspection-range">
                        <span>Range giá bạn đề xuất</span>
                        <strong>
                            {$result.suggested_price_min|number_format:0:",":"."} đ
                            -
                            {$result.suggested_price_max|number_format:0:",":"."} đ
                        </strong>
                    </div>
                {/if}
            </div>
        {/if}
    </section>

{/block}

{block name="scripts"}
    <script>
        window.INSPECTION_CONFIG = {
            assignmentId: {$assignment.id},
            status: '{$assignment.status|escape:'javascript'}',
            csrfToken: '{$csrf_token|escape:'javascript'}',
            acceptUrl: '/api/v1/staff/inspections/{$assignment.id}/accept',
            startUrl: '/api/v1/staff/inspections/{$assignment.id}/start',
            submitUrl: '/api/v1/staff/inspections/{$assignment.id}/submit'
        };
    </script>
    <script src="/assets/js/staff-inspection.js"></script>
{/block}