{extends file="layouts/admin.tpl"}

{block name="content"}

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>{$screen_title|escape}</h2>
                <p>{$screen_subtitle|escape}</p>
            </div>
            <button type="button" class="admin-btn admin-btn-primary" id="storyCreateBtn">
                <i class="fa-solid fa-plus"></i> Thêm câu chuyện
            </button>
        </div>

        <div class="story-stats">
            <div class="story-stat">
                <strong>{$story_total}</strong>
                <span>câu chuyện</span>
            </div>
            <div class="story-stat">
                <strong>{$story_active}</strong>
                <span>đang hiển thị</span>
            </div>
            <div class="story-stat story-stat-file">
                <i class="fa-regular fa-file-lines"></i>
                <span>Lưu tại <code>storage/feedback-stories.json</code></span>
            </div>
        </div>

        <div class="admin-filters">
            <div class="admin-field">
                <label for="storySearch">Tìm kiếm</label>
                <input type="search" class="admin-input" id="storySearch" placeholder="Tên khách, tiêu đề hoặc nội dung...">
            </div>
        </div>

        <div class="admin-loading" id="storyLoading">
            <i class="fa-solid fa-spinner fa-spin"></i> Đang tải câu chuyện...
        </div>

        <div id="storyList" class="story-admin-list" hidden></div>

        <div class="admin-empty" id="storyEmpty" hidden>
            <i class="fa-solid fa-comment-dots"></i>
            <strong>Chưa có câu chuyện khách hàng nào</strong>
            <span>Bấm “Thêm câu chuyện” để tạo câu chuyện đầu tiên hiển thị ở trang chủ.</span>
        </div>

        <div class="admin-empty" id="storyNoResult" hidden>
            <i class="fa-solid fa-magnifying-glass"></i>
            <strong>Không tìm thấy câu chuyện phù hợp</strong>
        </div>
    </section>

    {* ====================== MODAL THEM CAU CHUYEN ====================== *}
    <div class="admin-modal" id="storyModal" hidden>
        <div class="admin-modal-card">
            <div class="admin-modal-head">
                <h3>Thêm câu chuyện khách hàng</h3>
                <button type="button" class="admin-modal-close" id="storyModalClose" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="storyForm" class="admin-form-grid">
                <div class="admin-field">
                    <label for="storyAuthor">Tên khách hàng *</label>
                    <input type="text" class="admin-input" id="storyAuthor" name="author_name" maxlength="120"
                        placeholder="Ví dụ: Anh Nam" required>
                </div>

                <div class="admin-field">
                    <label for="storyLocation">Khu vực / dòng xe</label>
                    <input type="text" class="admin-input" id="storyLocation" name="location" maxlength="120"
                        placeholder="Ví dụ: TP.HCM · Mazda CX-5">
                </div>

                <div class="admin-field admin-field-full">
                    <label for="storyTitle">Tiêu đề câu chuyện *</label>
                    <input type="text" class="admin-input" id="storyTitle" name="title"
                        maxlength="160" placeholder="Ví dụ: Bán VinFast Lux A2.0 2022 được 575 triệu trong 1 ngày" required>
                </div>

                <div class="admin-field admin-field-full">
                    <label for="storyExcerpt">Mô tả ngắn (hiển thị ở trang chủ và danh sách)</label>
                    <textarea class="admin-textarea" id="storyExcerpt" name="excerpt" rows="2"
                        placeholder="Bỏ trống sẽ tự lấy 200 ký tự đầu của câu chuyện."></textarea>
                </div>

                <div class="admin-field admin-field-full">
                    <label for="storySummary">Đoạn mở đầu trang chi tiết</label>
                    <textarea class="admin-textarea" id="storySummary" name="summary" rows="3"
                        placeholder="Ví dụ: Anh Phát chuẩn bị định cư nước ngoài nên cần bán lại chiếc VinFast Lux A2.0 2022. Bỏ trống sẽ dùng mô tả ngắn."></textarea>
                    <small class="admin-muted">Hiển thị ngay dưới tiêu đề lớn ở đầu trang chi tiết.</small>
                </div>

                <div class="admin-field admin-field-full">
                    <label for="storyImage">Ảnh thẻ (danh sách, trang chủ)</label>
                    <input type="text" class="admin-input" id="storyImage" name="image"
                        placeholder="Ví dụ: https://... hoặc /assets/img/testimonials/AnhPhat.png">
                </div>

                <div class="admin-field admin-field-full">
                    <label for="storyStoryImage">Ảnh lớn đầu trang chi tiết (ảnh đồng xe)</label>
                    <input type="text" class="admin-input" id="storyStoryImage" name="story_image"
                        placeholder="Bỏ trống sẽ dùng lại ảnh thẻ ở trên.">
                </div>

                <div class="admin-field admin-field-full">
                    <label>Thông số nổi bật (hiển thị thành 3 ô ở đầu trang chi tiết)</label>

                    <div class="admin-inline-grid">
                        <input type="text" class="admin-input" id="storyHighlightPrice" name="highlight_price"
                            maxlength="40" placeholder="Giá chốt bán — Ví dụ: 575 triệu">

                        <input type="text" class="admin-input" id="storyHighlightTime" name="highlight_time"
                            maxlength="40" placeholder="Thời gian bán — Ví dụ: 1 ngày">

                        <input type="text" class="admin-input" id="storyHighlightFee" name="highlight_fee"
                            maxlength="40" placeholder="Phí dịch vụ — Ví dụ: 1%">
                    </div>

                    <small class="admin-muted">Ô nào để trống sẽ không hiển thị.</small>
                </div>

                <div class="admin-field admin-field-full">
                    <label for="storyProblems">Những vấn đề khách gặp phải (mỗi dòng một ý)</label>
                    <textarea class="admin-textarea" id="storyProblems" name="problems" rows="3"
                        placeholder="Chuẩn bị đi cứ nước ngoài nên cần hoàn tất việc bán xe trong thời gian ngắn.&#10;Mới bắt đầu tham khảo các phương án bán xe trên thị trường."></textarea>
                    <small class="admin-muted">Tối đa 8 dòng. Để trống sẽ ẩn cả khối này.</small>
                </div>

                <div class="admin-field admin-field-full">
                    <label for="storyContent">Nội dung câu chuyện đầy đủ *</label>
                    <textarea class="admin-textarea" id="storyContent" name="story" rows="8"
                        placeholder="Kể lại trải nghiệm thực tế của khách hàng với FastCar..." required></textarea>
                </div>

                <div class="admin-field">
                    <label for="storyStatus">Trạng thái</label>
                    <select class="admin-select" id="storyStatus" name="status">
                        <option value="active">active - hiển thị cho khách hàng</option>
                        <option value="hidden">hidden - tạm ẩn</option>
                    </select>
                </div>
            </form>

            <div class="admin-modal-foot">
                <button type="button" class="admin-btn admin-btn-ghost" id="storyModalCancel">Hủy</button>
                <button type="button" class="admin-btn admin-btn-primary" id="storyModalSubmit">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu câu chuyện
                </button>
            </div>
        </div>
    </div>

    {* ====================== MODAL XAC NHAN XOA ====================== *}
    <div class="admin-modal" id="storyDeleteModal" hidden>
        <div class="admin-modal-card story-confirm-card">
            <div class="admin-modal-head">
                <h3>Xóa câu chuyện?</h3>
                <button type="button" class="admin-modal-close" id="storyDeleteClose" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <p class="story-confirm-text">
                Câu chuyện <strong id="storyDeleteName"></strong> sẽ bị xóa khỏi trang khách hàng.
                Hành động này không thể hoàn tác.
            </p>
            <div class="admin-modal-foot">
                <button type="button" class="admin-btn admin-btn-ghost" id="storyDeleteCancel">Hủy</button>
                <button type="button" class="admin-btn admin-btn-danger" id="storyDeleteConfirm">
                    <i class="fa-solid fa-trash"></i> Xóa câu chuyện
                </button>
            </div>
        </div>
    </div>

{/block}

{block name="scripts"}
    <link rel="stylesheet" href="/assets/css/feedback-stories.css">
    <script>
        window.STORY_ADMIN = {
            initial: {$stories|json_encode nofilter}
        };
    </script>
    <script src="/assets/js/admin-feedback.js"></script>
{/block}