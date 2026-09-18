{extends file="layouts/admin.tpl"}

{block name="content"}

    {* ====================== HÃNG XE ====================== *}
    <section class="admin-panel" id="panel-brands">
        <div class="admin-panel-head">
            <div>
                <h2>Hãng xe</h2>
                <p>Dùng API /api/v1/admin/brands để thêm, sửa, xóa hãng xe.</p>
            </div>
            <button type="button" class="admin-btn admin-btn-primary" data-action="create-brand">
                <i class="fa-solid fa-plus"></i> Thêm hãng xe
            </button>
        </div>
        <div class="admin-filters">
            <div class="admin-field">
                <label for="brandSearch">Tìm kiếm</label>
                <input type="search" class="admin-input" id="brandSearch" placeholder="Tên hãng xe...">
            </div>
            <div class="admin-field">
                <label for="brandStatus">Trạng thái</label>
                <select class="admin-select" id="brandStatus">
                    <option value="">Tất cả</option>
                    <option value="active">Đang hiển thị (active)</option>
                    <option value="inactive">Đang ẩn (inactive)</option>
                </select>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Logo</th>
                        <th>Tên hãng</th>
                        <th>Slug</th>
                        <th>Quốc gia</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="brandTableBody">
                    <tr>
                        <td colspan="7" class="admin-loading">Đang tải danh sách hãng xe...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {* ====================== DÒNG XE ====================== *}
    <section class="admin-panel is-hidden" id="panel-models">
        <div class="admin-panel-head">
            <div>
                <h2>Dòng xe</h2>
                <p>Mỗi dòng xe thuộc một hãng. Dùng API /api/v1/admin/models.</p>
            </div>
            <button type="button" class="admin-btn admin-btn-primary" data-action="create-model">
                <i class="fa-solid fa-plus"></i> Thêm dòng xe
            </button>
        </div>
        <div class="admin-filters">
            <div class="admin-field">
                <label for="modelBrandFilter">Hãng xe</label>
                <select class="admin-select" id="modelBrandFilter">
                    <option value="">Tất cả hãng</option>
                </select>
            </div>
            <div class="admin-field">
                <label for="modelStatus">Trạng thái</label>
                <select class="admin-select" id="modelStatus">
                    <option value="">Tất cả</option>
                    <option value="active">Đang hiển thị (active)</option>
                    <option value="inactive">Đang ẩn (inactive)</option>
                </select>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hãng xe</th>
                        <th>Tên dòng xe</th>
                        <th>Slug</th>
                        <th>Kiểu dáng</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="modelTableBody">
                    <tr>
                        <td colspan="7" class="admin-loading">Đang tải danh sách dòng xe...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {* ====================== PHIÊN BẢN XE ====================== *}
    <section class="admin-panel is-hidden" id="panel-versions">
        <div class="admin-panel-head">
            <div>
                <h2>Phiên bản xe</h2>
                <p>Phiên bản thuộc một dòng xe, kèm thông số động cơ. Dùng API /api/v1/admin/versions.</p>
            </div>
            <button type="button" class="admin-btn admin-btn-primary" data-action="create-version">
                <i class="fa-solid fa-plus"></i> Thêm phiên bản
            </button>
        </div>
        <div class="admin-filters">
            <div class="admin-field">
                <label for="versionModelFilter">Dòng xe</label>
                <select class="admin-select" id="versionModelFilter">
                    <option value="">Tất cả dòng xe</option>
                </select>
            </div>
            <div class="admin-field">
                <label for="versionStatus">Trạng thái</label>
                <select class="admin-select" id="versionStatus">
                    <option value="">Tất cả</option>
                    <option value="active">Đang hiển thị (active)</option>
                    <option value="inactive">Đang ẩn (inactive)</option>
                </select>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hãng / Dòng xe</th>
                        <th>Phiên bản</th>
                        <th>Năm SX</th>
                        <th>Động cơ</th>
                        <th>Hộp số</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody id="versionTableBody">
                    <tr>
                        <td colspan="8" class="admin-loading">Đang tải danh sách phiên bản...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {* ====================== FORM MODAL ====================== *}
    <div class="admin-modal" id="adminModal" hidden>
        <div class="admin-modal-card">
            <div class="admin-modal-head">
                <h3 id="modalTitle">Thêm mới</h3>
                <button type="button" class="admin-modal-close" id="modalClose" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="brandForm" class="admin-form-grid" data-form="brand" hidden>
                <input type="hidden" name="id">
                <div class="admin-field">
                    <label for="brandName">Tên hãng xe *</label>
                    <input type="text" class="admin-input" id="brandName" name="name" required>
                </div>
                <div class="admin-field">
                    <label for="brandSlug">Slug (bỏ trống sẽ tự tạo)</label>
                    <input type="text" class="admin-input" id="brandSlug" name="slug">
                </div>
                <div class="admin-field">
                    <label for="brandCountry">Quốc gia</label>
                    <input type="text" class="admin-input" id="brandCountry" name="country">
                </div>
                <div class="admin-field">
                    <label for="brandStatusInput">Trạng thái</label>
                    <select class="admin-select" id="brandStatusInput" name="status">
                        <option value="active">active - hiển thị cho khách hàng</option>
                        <option value="inactive">inactive - ẩn khỏi khách hàng</option>
                    </select>
                </div>
                <div class="admin-field admin-field-full">
                    <label>Logo hãng xe</label>

                    <input
                        type="file"
                        id="brandLogoFile"
                        accept="image/png,image/jpeg,image/webp"
                        hidden
                    >
                    <input type="hidden" id="brandLogo" name="logo" value="">
                    <input type="hidden" id="brandLogoPublicId" name="logo_public_id" value="">
                    <input type="hidden" id="brandLogoRemove" name="logo_remove" value="0">

                    {* Khu vực upload ẩn mặc định, chỉ mở khi bấm nút "Thêm logo". *}
                    <button
                        type="button"
                        class="admin-btn admin-btn-ghost"
                        id="brandLogoOpen"
                    >
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        Thêm logo
                    </button>

                    <div class="brand-logo-upload is-hidden" id="brandLogoDropzone">
                        <div class="brand-logo-empty" id="brandLogoEmpty">
                            <div class="brand-logo-empty-icon">
                                <i class="fa-regular fa-image"></i>
                            </div>
                            <strong>Chọn logo hoặc kéo ảnh vào đây</strong>
                            <span>PNG, JPG hoặc WebP · tối đa 5 MB</span>
                            <button
                                type="button"
                                class="admin-btn admin-btn-ghost"
                                id="brandLogoChoose"
                            >
                                <i class="fa-solid fa-upload"></i>
                                Chọn ảnh
                            </button>
                        </div>

                        <div class="brand-logo-result" id="brandLogoResult" hidden>
                            <div class="brand-logo-preview-box">
                                <img id="logoPreview" src="" alt="Logo preview">
                            </div>

                            <div class="brand-logo-result-info">
                                <strong>Logo đã sẵn sàng</strong>
                                <span id="brandLogoStatus">Đã tải lên Cloudinary</span>

                                <div class="brand-logo-actions">
                                    <button
                                        type="button"
                                        class="admin-btn admin-btn-ghost"
                                        id="brandLogoChange"
                                    >
                                        Đổi / căn chỉnh
                                    </button>

                                    <button
                                        type="button"
                                        class="admin-btn admin-btn-danger-soft"
                                        id="brandLogoClear"
                                    >
                                        Xóa logo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="admin-field admin-field-full">
                    <label for="brandDescription">Mô tả</label>
                    <textarea class="admin-textarea" id="brandDescription" name="description"></textarea>
                </div>
            </form>

            <form id="modelForm" class="admin-form-grid" data-form="model" hidden>
                <input type="hidden" name="id">
                <div class="admin-field">
                    <label for="modelBrandId">Hãng xe *</label>
                    <select class="admin-select" id="modelBrandId" name="brand_id" required></select>
                </div>
                <div class="admin-field">
                    <label for="modelName">Tên dòng xe *</label>
                    <input type="text" class="admin-input" id="modelName" name="name" required>
                </div>
                <div class="admin-field">
                    <label for="modelSlug">Slug (bỏ trống sẽ tự tạo)</label>
                    <input type="text" class="admin-input" id="modelSlug" name="slug">
                </div>
                <div class="admin-field">
                    <label for="modelBodyType">Kiểu dáng</label>
                    <select class="admin-select" id="modelBodyType" name="body_type">
                        <option value="">Không xác định</option>
                        <option value="sedan">sedan</option>
                        <option value="suv">suv</option>
                        <option value="hatchback">hatchback</option>
                        <option value="pickup">pickup</option>
                        <option value="coupe">coupe</option>
                        <option value="mpv">mpv</option>
                        <option value="van">van</option>
                        <option value="wagon">wagon</option>
                        <option value="convertible">convertible</option>
                        <option value="other">other</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="modelStatusInput">Trạng thái</label>
                    <select class="admin-select" id="modelStatusInput" name="status">
                        <option value="active">active - hiển thị cho khách hàng</option>
                        <option value="inactive">inactive - ẩn khỏi khách hàng</option>
                    </select>
                </div>
                <div class="admin-field admin-field-full">
                    <label for="modelDescription">Mô tả</label>
                    <textarea class="admin-textarea" id="modelDescription" name="description"></textarea>
                </div>
            </form>

            <form id="versionForm" class="admin-form-grid" data-form="version" hidden>
                <input type="hidden" name="id">
                <div class="admin-field">
                    <label for="versionModelId">Dòng xe *</label>
                    <select class="admin-select" id="versionModelId" name="model_id" required></select>
                </div>
                <div class="admin-field">
                    <label for="versionName">Tên phiên bản *</label>
                    <input type="text" class="admin-input" id="versionName" name="name" required>
                </div>
                <div class="admin-field">
                    <label for="versionSlug">Slug (bỏ trống sẽ tự tạo)</label>
                    <input type="text" class="admin-input" id="versionSlug" name="slug">
                </div>
                <div class="admin-field">
                    <label for="versionStatusInput">Trạng thái</label>
                    <select class="admin-select" id="versionStatusInput" name="status">
                        <option value="active">active - hiển thị cho khách hàng</option>
                        <option value="inactive">inactive - ẩn khỏi khách hàng</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="versionYearFrom">Năm sản xuất từ</label>
                    <input type="number" class="admin-input" id="versionYearFrom" name="production_year_from" min="1900"
                        max="2100">
                </div>
                <div class="admin-field">
                    <label for="versionYearTo">Năm sản xuất đến</label>
                    <input type="number" class="admin-input" id="versionYearTo" name="production_year_to" min="1900"
                        max="2100">
                </div>
                <div class="admin-field">
                    <label for="versionEngineName">Tên động cơ</label>
                    <input type="text" class="admin-input" id="versionEngineName" name="engine_name">
                </div>
                <div class="admin-field">
                    <label for="versionEngineCc">Dung tích (cc)</label>
                    <input type="number" class="admin-input" id="versionEngineCc" name="engine_displacement_cc" min="0">
                </div>
                <div class="admin-field">
                    <label for="versionFuel">Nhiên liệu</label>
                    <select class="admin-select" id="versionFuel" name="fuel_type">
                        <option value="">Không xác định</option>
                        <option value="gasoline">gasoline</option>
                        <option value="diesel">diesel</option>
                        <option value="hybrid">hybrid</option>
                        <option value="phev">phev</option>
                        <option value="ev">ev</option>
                        <option value="other">other</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="versionTransmission">Hộp số</label>
                    <select class="admin-select" id="versionTransmission" name="transmission">
                        <option value="">Không xác định</option>
                        <option value="manual">manual</option>
                        <option value="automatic">automatic</option>
                        <option value="cvt">cvt</option>
                        <option value="dct">dct</option>
                        <option value="other">other</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="versionDrivetrain">Dẫn động</label>
                    <select class="admin-select" id="versionDrivetrain" name="drivetrain">
                        <option value="">Không xác định</option>
                        <option value="fwd">fwd</option>
                        <option value="rwd">rwd</option>
                        <option value="awd">awd</option>
                        <option value="4wd">4wd</option>
                        <option value="other">other</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="versionHorsepower">Công suất (hp)</label>
                    <input type="number" class="admin-input" id="versionHorsepower" name="horsepower" min="0">
                </div>
                <div class="admin-field">
                    <label for="versionSeats">Số chỗ</label>
                    <input type="number" class="admin-input" id="versionSeats" name="seats" min="1" max="60">
                </div>
                <div class="admin-field admin-field-full">
                    <label for="versionDescription">Mô tả</label>
                    <textarea class="admin-textarea" id="versionDescription" name="description"></textarea>
                </div>
            </form>

            <div class="admin-modal-foot">
                <button type="button" class="admin-btn admin-btn-ghost" id="modalCancel">Hủy</button>
                <button type="button" class="admin-btn admin-btn-primary" id="modalSubmit">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu
                </button>
            </div>
        </div>
    </div>


    {* ====================== CROP LOGO MODAL ====================== *}
    <div class="logo-crop-modal" id="logoCropModal" hidden>
        <div class="logo-crop-dialog">
            <div class="logo-crop-head">
                <div>
                    <h3>Căn chỉnh logo</h3>
                    <p>Kéo, phóng to, xoay và cắt logo trước khi lưu.</p>
                </div>
                <button type="button" class="admin-modal-close" id="logoCropClose" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="logo-crop-body">
                <div class="logo-crop-stage">
                    <img id="logoCropImage" src="" alt="Ảnh đang căn chỉnh">
                </div>

                <div class="logo-crop-toolbar">
                    <div class="logo-crop-group">
                        <span>Tỉ lệ</span>
                        <button type="button" class="logo-tool-btn is-active" data-crop-ratio="free">Tự do</button>
                        <button type="button" class="logo-tool-btn" data-crop-ratio="1">1:1</button>
                        <button type="button" class="logo-tool-btn" data-crop-ratio="1.3333333333">4:3</button>
                    </div>

                    <div class="logo-crop-group">
                        <span>Điều chỉnh</span>
                        <button type="button" class="logo-tool-btn" data-crop-action="zoom-in" title="Phóng to">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" class="logo-tool-btn" data-crop-action="zoom-out" title="Thu nhỏ">
                            <i class="fa-solid fa-magnifying-glass-minus"></i>
                        </button>
                        <button type="button" class="logo-tool-btn" data-crop-action="rotate-left" title="Xoay trái">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button type="button" class="logo-tool-btn" data-crop-action="rotate-right" title="Xoay phải">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        <button type="button" class="logo-tool-btn" data-crop-action="reset" title="Đặt lại">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="logo-crop-foot">
                <span class="logo-upload-progress" id="logoUploadProgress"></span>

                <div>
                    <button type="button" class="admin-btn admin-btn-ghost" id="logoCropCancel">
                        Hủy
                    </button>
                    <button type="button" class="admin-btn admin-btn-primary" id="logoCropUpload">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        Cắt ảnh &amp; tải lên
                    </button>
                </div>
            </div>
        </div>
    </div>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
    >
    <link
        rel="stylesheet"
        href="/assets/css/brand-logo-uploader.css"
    >

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="/assets/js/brand-logo-uploader.js"></script>

{/block}