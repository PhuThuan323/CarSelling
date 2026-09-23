(function () {
    'use strict';

    const API = {
        brands:
            '/api/v1/brands',

        modelsByBrand:
            function (brandId) {
                return (
                    '/api/v1/brands/'
                    + encodeURIComponent(brandId)
                    + '/models'
                );
            },

        versionsByModel:
            function (modelId) {
                return (
                    '/api/v1/models/'
                    + encodeURIComponent(modelId)
                    + '/versions'
                );
            },

        createValuation:
            '/api/v1/valuations/create',

        valuationDetail:
            '/api/v1/valuations/detail',

        uploadImage:
            '/api/v1/valuations/images/upload',

        replaceImage:
            '/api/v1/valuations/images/replace',

        deleteImage:
            '/api/v1/valuations/images/delete',

        submit:
            '/api/v1/valuations/submit'
    };


    let valuationRequestId = null;

    let versions = [];

    let photosComplete =
        false;


    /*
    |--------------------------------------------------------------------------
    | API HELPER
    |--------------------------------------------------------------------------
    */

    async function request(url, options = {}) {
    const response = await fetch(
        url,
        {
            credentials: 'same-origin',

            headers: {
                Accept: 'application/json',

                ...(options.headers || {})
            },

            ...options
        }
    );

    const contentType =
        response.headers.get('content-type') || '';

    const rawBody =
        await response.text();

    console.log('===== API RESPONSE =====');
    console.log('URL:', url);
    console.log('Status:', response.status);
    console.log('Content-Type:', contentType);
    console.log('Raw response:', rawBody);
    console.log('========================');

    let body = {};

    if (rawBody.trim()) {
        try {
            body = JSON.parse(rawBody);
        } catch (error) {
            console.error(
                'JSON parse error:',
                error
            );

            throw new Error(
                `Server không trả JSON hợp lệ. HTTP ${response.status}. Response: ${rawBody.substring(0, 500)}`
            );
        }
    }

    if (response.status === 401) {
        window.location.href =
            '/auth/login?redirect=/sell-car';

        throw new Error(
            'Bạn cần đăng nhập.'
        );
    }

    if (!response.ok) {
        throw new Error(
            body.message
            || body.error
            || `HTTP ${response.status}`
        );
    }

    return body;
}


    /*
    |--------------------------------------------------------------------------
    | ELEMENT
    |--------------------------------------------------------------------------
    */

    const brandSelect =
        document.getElementById(
            'BrandSelect'
        )
        || document.getElementById(
            'brandSelect'
        );

    const modelSelect =
        document.getElementById(
            'modelSelect'
        );

    const yearSelect =
        document.getElementById(
            'yearSelect'
        );

    const versionSelect =
        document.getElementById(
            'versionSelect'
        );

    const odometer =
        document.getElementById(
            'odometer'
        );

    const continueButton =
        document.getElementById(
            'continueButton'
        );

    const consent =
        document.getElementById(
            'valuationConsent'
        );

    /*
    |--------------------------------------------------------------------------
    | LOAD BRANDS
    |--------------------------------------------------------------------------
    */

    async function loadBrands()
    {
        try {

            const response =
                await request(
                    API.brands
                );

            const brands =
                response.data?.brands
                || response.data
                || [];


            brandSelect.innerHTML =
                '<option value="">'
                + '-- Chọn hãng xe --'
                + '</option>';


            brands.forEach(
                function (brand) {

                    if (
                        brand.status
                        && brand.status
                            !== 'active'
                    ) {
                        return;
                    }

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        brand.id;

                    option.textContent =
                        brand.name;

                    brandSelect
                        .appendChild(
                            option
                        );
                }
            );

        } catch (error) {

            console.error(error);

            alert(
                'Không tải được hãng xe: '
                + error.message
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BRAND → MODEL
    |--------------------------------------------------------------------------
    */

    brandSelect.addEventListener(
        'change',
        async function () {

            const brandId =
                this.value;


            resetSelect(
                modelSelect,
                '-- Chọn dòng xe --'
            );

            resetSelect(
                yearSelect,
                '-- Chọn dòng xe trước --'
            );

            resetSelect(
                versionSelect,
                '-- Chọn đời xe trước --'
            );


            if (!brandId) {
                return;
            }


            try {

                const response =
                    await request(
                        API.modelsByBrand(
                            brandId
                        )
                    );

                const models =
                    response.data?.models
                    || response.data
                    || [];


                modelSelect.disabled =
                    false;


                models.forEach(
                    function (model) {

                        const option =
                            document.createElement(
                                'option'
                            );

                        option.value =
                            model.id;

                        option.textContent =
                            model.name;

                        modelSelect
                            .appendChild(
                                option
                            );
                    }
                );

            } catch (error) {

                alert(
                    'Không tải được dòng xe: '
                    + error.message
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | MODEL → VERSION → YEARS
    |--------------------------------------------------------------------------
    */

    modelSelect.addEventListener(
        'change',
        async function () {

            const modelId =
                this.value;


            versions = [];


            resetSelect(
                yearSelect,
                '-- Chọn đời xe --'
            );

            resetSelect(
                versionSelect,
                '-- Chọn đời xe trước --'
            );


            if (!modelId) {
                return;
            }


            try {

                const response =
                    await request(
                        API.versionsByModel(
                            modelId
                        )
                    );


                versions =
                    response.data?.versions
                    || response.data
                    || [];
                buildYears(
                    versions
                );
            } catch (error) {

                alert(
                    'Không tải được phiên bản: '
                    + error.message
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | YEAR
    |--------------------------------------------------------------------------
    */

    function buildYears(
        versionRows
    ) {
        const years =
            new Set();

        const currentYear =
            new Date()
                .getFullYear();


        versionRows.forEach(
            function (version) {

                const from =
                    Number(
                        version
                            .production_year_from
                    );

                const to =
                    Number(
                        version
                            .production_year_to
                    )
                    || currentYear;


                if (!from) {
                    return;
                }


                for (
                    let year = from;
                    year <= to;
                    year++
                ) {
                    years.add(year);
                }
            }
        );


        Array
            .from(years)
            .sort(
                function (a, b) {
                    return b - a;
                }
            )
            .forEach(
                function (year) {

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        year;

                    option.textContent =
                        year;

                    yearSelect
                        .appendChild(
                            option
                        );
                }
            );


        yearSelect.disabled =
            false;
    }


    /*
    |--------------------------------------------------------------------------
    | YEAR → VERSION
    |--------------------------------------------------------------------------
    */

    yearSelect.addEventListener(
        'change',
        function () {

            const year =
                Number(this.value);


            resetSelect(
                versionSelect,
                '-- Chọn phiên bản --'
            );


            if (!year) {
                return;
            }


            const matched =
                versions.filter(
                    function (version) {

                        const from =
                            Number(
                                version
                                    .production_year_from
                            )
                            || 0;

                        const to =
                            Number(
                                version
                                    .production_year_to
                            )
                            || 9999;


                        return (
                            year >= from
                            && year <= to
                        );
                    }
                );


            matched.forEach(
                function (version) {

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        version.id;

                    option.textContent =
                        version.name;

                    versionSelect
                        .appendChild(
                            option
                        );
                }
            );


            versionSelect.disabled =
                false;
        }
    );


    /*
    |--------------------------------------------------------------------------
    | CREATE DRAFT
    |--------------------------------------------------------------------------
    */

    async function ensureDraft()
    {
        if (
            valuationRequestId
        ) {
            return valuationRequestId;
        }


        const versionId =
            Number(
                versionSelect.value
            );

        const year =
            Number(
                yearSelect.value
            );

        const km =
            Number(
                odometer.value
            );


        if (
            !brandSelect.value
            || !modelSelect.value
            || !year
            || !versionId
        ) {
            throw new Error(
                'Vui lòng chọn đầy đủ hãng xe, dòng xe, đời xe và phiên bản.'
            );
        }


        if (
            !Number.isFinite(km)
            || km < 0
        ) {
            throw new Error(
                'Vui lòng nhập số km đã đi.'
            );
        }


        const response =
            await request(
                API.createValuation,
                {
                    method:
                        'POST',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body:
                        JSON.stringify({
                            vehicle_version_id:
                                versionId,

                            manufacture_year:
                                year,

                            odometer_km:
                                km
                        })
                }
            );


        valuationRequestId =
            response.data
                .valuation_request_id;


        sessionStorage.setItem(
            'valuation_request_id',
            String(
                valuationRequestId
            )
        );


        return valuationRequestId;
    }


    /*
    |--------------------------------------------------------------------------
    | PHOTO SLOTS
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '.photo-slot'
        )
        .forEach(
            function (slot) {

                renderEmptySlot(
                    slot
                );
            }
        );


    function renderEmptySlot(
        slot
    ) {
        const slotKey =
            slot.dataset.slot;

        slot.classList.remove(
            'has-image'
        );


        slot.innerHTML = `
            <div class="photo-empty">

                <span class="photo-empty-icon">
                    <i class="fa-solid fa-camera"></i>
                </span>

                <strong class="photo-empty-label">
                    ${photoLabel(slotKey)}
                </strong>

                <span class="photo-empty-hint">
                    ${photoHint(slotKey)}
                </span>

                <button
                    type="button"
                    class="photo-upload-btn js-pick-photo"
                >
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </button>

                <span class="photo-empty-meta">
                    JPG, PNG, WEBP - tối đa 8MB
                </span>

                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    capture="environment"
                    hidden
                >

            </div>
        `;


        const input =
            slot.querySelector(
                'input[type="file"]'
            );


        slot
            .querySelector(
                '.js-pick-photo'
            )
            .addEventListener(
                'click',
                function () {
                    input.click();
                }
            );


        input.addEventListener(
            'change',
            function () {

                if (
                    this.files
                    && this.files[0]
                ) {
                    uploadPhoto(
                        slot,
                        this.files[0]
                    );
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPLOAD
    |--------------------------------------------------------------------------
    */

    async function uploadPhoto(
        slot,
        file
    ) {
        try {

            const requestId =
                await ensureDraft();


            const form =
                new FormData();


            form.append(
                'valuation_request_id',
                requestId
            );

            form.append(
                'slot_key',
                slot.dataset.slot
            );

            form.append(
                'image',
                file
            );


            slot.classList.add(
                'is-uploading'
            );


            const response =
                await request(
                    API.uploadImage,
                    {
                        method:
                            'POST',

                        body:
                            form
                    }
                );


            renderUploadedSlot(
                slot,
                response.data.image
            );


            updateProgress(
                response.data
                    .photo_progress
            );

        } catch (error) {

            alert(
                error.message
            );

        } finally {

            slot.classList.remove(
                'is-uploading'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | RENDER UPLOADED IMAGE
    |--------------------------------------------------------------------------
    */

    function renderUploadedSlot(
    slot,
    image
) {
    slot.classList.add(
        'has-image'
    );

    slot.innerHTML = `
        <img
            class="photo-preview"
            src="${image.image_url}"
            alt="${photoLabel(image.slot_key)}"
        >

        <div class="photo-actions">

            <button
                type="button"
                class="photo-action js-replace-photo"
            >
                <i class="fa-solid fa-rotate"></i>
                Thay thế
            </button>

            <button
                type="button"
                class="
                    photo-action
                    photo-action-delete
                    js-delete-photo
                "
            >
                <i class="fa-solid fa-trash"></i>
                Xóa
            </button>

        </div>

        <input
            class="replace-photo-input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            capture="environment"
            hidden
        >
    `;


    /*
     * THAY THẾ
     */

    const replaceInput =
        slot.querySelector(
            '.replace-photo-input'
        );


    slot
        .querySelector(
            '.js-replace-photo'
        )
        .addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                replaceInput.click();
            }
        );


    replaceInput.addEventListener(
        'change',
        function () {

            if (
                this.files
                && this.files[0]
            ) {

                replacePhoto(
                    slot,
                    this.files[0]
                );
            }
        }
    );


    /*
     * XÓA
     */

    slot
        .querySelector(
            '.js-delete-photo'
        )
        .addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                deletePhoto(
                    slot
                );
            }
        );
}


    /*
    |--------------------------------------------------------------------------
    | REPLACE
    |--------------------------------------------------------------------------
    */

    async function replacePhoto(
        slot,
        file
    ) {
        const form =
            new FormData();


        form.append(
            'valuation_request_id',
            valuationRequestId
        );

        form.append(
            'slot_key',
            slot.dataset.slot
        );

        form.append(
            'image',
            file
        );


        try {

            const response =
                await request(
                    API.replaceImage,
                    {
                        method:
                            'POST',

                        body:
                            form
                    }
                );


            renderUploadedSlot(
                slot,
                response.data.image
            );


            updateProgress(
                response.data
                    .photo_progress
            );

        } catch (error) {

            alert(
                error.message
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    async function deletePhoto(
        slot
    ) {
        if (
            !confirm(
                'Bạn có chắc muốn xóa ảnh này?'
            )
        ) {
            return;
        }


        try {

            const response =
                await request(
                    API.deleteImage,
                    {
                        method:
                            'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body:
                            JSON.stringify({
                                valuation_request_id:
                                    valuationRequestId,
                                slot_key:
                                    slot.dataset.slot
                            })
                    }
                );


            renderEmptySlot(
                slot
            );


            updateProgress(
                response.data
                    .photo_progress
            );

        } catch (error) {

            alert(
                error.message
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PROGRESS
    |--------------------------------------------------------------------------
    */

    function updateProgress(
        progress
    ) {
        if (!progress) {
            return;
        }


        document
            .getElementById(
                'photoProgressText'
            )
            .textContent =
                progress.completed
                + ' / '
                + progress.required;


        document
            .getElementById(
                'photoProgressBar'
            )
            .style.width =
                progress.percent
                + '%';


        Object
            .entries(
                progress.groups
                || {}
            )
            .forEach(
                function (
                    [group, data]
                ) {
                    const el =
                        document
                            .querySelector(
                                '[data-group-count="'
                                + group
                                + '"]'
                            );

                    if (el) {
                        el.textContent =
                            data.completed;
                    }
                }
            );


        if (continueButton) {
            continueButton.disabled =
                !progress.complete;
        }


        photosComplete =
            Boolean(
                progress.complete
            );


        syncContinueButton();
    }


    /*
    |--------------------------------------------------------------------------
    | NÚT TIẾP TỤC = ĐỦ ẢNH + ĐÃ ĐỒNG Ý CHÍNH SÁCH
    |--------------------------------------------------------------------------
    */

    function syncContinueButton() {
        if (!continueButton) {
            return;
        }

        continueButton.disabled =
            !(
                photosComplete
                && consent
                && consent.checked
            );
    }


    if (consent) {
        consent.addEventListener(
            'change',
            syncContinueButton
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE DRAFT
    |--------------------------------------------------------------------------
    */

    async function restoreDraft()
    {
        const storedId =
            Number(
                sessionStorage.getItem(
                    'valuation_request_id'
                )
            );


        if (!storedId) {
            return;
        }


        try {

            const response =
                await request(
                    API.valuationDetail
                    + '?id='
                    + encodeURIComponent(
                        storedId
                    )
                );


            valuationRequestId =
                storedId;


            const images =
                response.data.images
                || [];


            images.forEach(
                function (image) {

                    const slot =
                        document
                            .querySelector(
                                '[data-slot="'
                                + image.slot_key
                                + '"]'
                            );

                    if (slot) {
                        renderUploadedSlot(
                            slot,
                            image
                        );
                    }
                }
            );


            updateProgress(
                response.data
                    .photo_progress
            );

        } catch (error) {

            sessionStorage.removeItem(
                'valuation_request_id'
            );

            valuationRequestId =
                null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SUBMIT
    |--------------------------------------------------------------------------
    */

    if (continueButton) {

        continueButton.addEventListener(
            'click',
            async function () {

                if (
                    !valuationRequestId
                ) {
                    return;
                }


                if (
                    !consent
                    || !consent.checked
                ) {
                    alert(
                        'Bạn cần đồng ý với Chính sách bảo mật và Quy chế hoạt động trước khi gửi định giá.'
                    );

                    return;
                }


                try {

                    this.disabled =
                        true;


                    const response =
                        await request(
                            API.submit,
                            {
                                method:
                                    'POST',

                                headers: {
                                    'Content-Type':
                                        'application/json'
                                },

                                body:
                                    JSON.stringify({
                                        valuation_request_id:
                                            valuationRequestId,

                                        privacy_accepted:
                                            true,

                                        terms_accepted:
                                            true
                                    })
                            }
                        );


                    sessionStorage
                        .removeItem(
                            'valuation_request_id'
                        );


                    /*
                     * BƯỚC 2
                     */

                    window.location.href =
                        '/valuation/result?id='
                        + encodeURIComponent(
                            response.data
                                .valuation_request_id
                        );

                } catch (error) {

                    alert(
                        error.message
                    );

                    this.disabled =
                        false;
                }
            }
        );
    }


    function resetSelect(
        select,
        text
    ) {
        select.innerHTML =
            '<option value="">'
            + text
            + '</option>';

        select.disabled =
            true;
    }


    function photoLabel(
        slot
    ) {
        const labels = {
            front_left_45:
                'Góc trước trái 45°',

            front_right_45:
                'Góc trước phải 45°',

            rear_left_45:
                'Góc sau trái 45°',

            rear_right_45:
                'Góc sau phải 45°',

            front:
                'Đầu xe',

            rear:
                'Đuôi xe',

            roof:
                'Nóc xe',

            odometer:
                'Đồng hồ ODO',

            cockpit:
                'Khoang lái',

            driver_seat:
                'Ghế lái',

            passenger_seat:
                'Ghế hành khách',

            rear_seat_headliner:
                'Hàng ghế sau và trần',

            engine_bay:
                'Khoang động cơ',

            wheel_front_left:
                'Bánh trước trái',

            wheel_front_right:
                'Bánh trước phải',

            wheel_rear_left:
                'Bánh sau trái',

            wheel_rear_right:
                'Bánh sau phải',

            registration_front:
                'Cà vẹt mặt trước',

            registration_back:
                'Cà vẹt mặt sau',

            inspection_spec:
                'Đăng kiểm - thông số',

            inspection_expiry:
                'Đăng kiểm - thời hạn'
        };

        return labels[slot]
            || slot;
    }


    /*
    |--------------------------------------------------------------------------
    | HƯỚNG DẪN CHỤP CHO TỪNG Ô ẢNH
    |--------------------------------------------------------------------------
    */

    function photoHint(
        slot
    ) {
        const hints = {
            front_left_45:
                'Đứng chếch trước bên trái, chụp trọn đầu xe và hông trái.',

            front_right_45:
                'Đứng chếch trước bên phải, chụp trọn đầu xe và hông phải.',

            rear_left_45:
                'Đứng chếch sau bên trái, chụp trọn đuôi xe và hông trái.',

            rear_right_45:
                'Đứng chếch sau bên phải, chụp trọn đuôi xe và hông phải.',

            front:
                'Chụp thẳng mặt trước xe, thấy rõ biển số và đèn.',

            rear:
                'Chụp thẳng mặt sau xe, thấy rõ biển số và cốp.',

            roof:
                'Chụp từ trên cao hoặc nghiêng để thấy toàn bộ nóc xe.',

            odometer:
                'Bật khóa điện, chụp rõ số km trên đồng hồ (số ODO).',

            cockpit:
                'Ngồi ghế sau hoặc mở cửa, chụp toàn cảnh táp-lô và vô lăng.',

            driver_seat:
                'Chụp rõ mặt ghế lái, thấy được độ mòn của da/nỉ.',

            passenger_seat:
                'Chụp rõ mặt ghế hành khách phía trước.',

            rear_seat_headliner:
                'Chụp hàng ghế sau và trần xe phía trên.',

            engine_bay:
                'Mở nắp ca-pô, chụp rõ toàn bộ khoang động cơ.',

            wheel_front_left:
                'Chụp thẳng bánh trước bên trái, thấy rõ mâm và lốp.',

            wheel_front_right:
                'Chụp thẳng bánh trước bên phải, thấy rõ mâm và lốp.',

            wheel_rear_left:
                'Chụp thẳng bánh sau bên trái, thấy rõ mâm và lốp.',

            wheel_rear_right:
                'Chụp thẳng bánh sau bên phải, thấy rõ mâm và lốp.',

            registration_front:
                'Chụp mặt trước cà vẹt, thấy rõ số khung và số máy.',

            registration_back:
                'Chụp mặt sau cà vẹt, thấy rõ ngày đăng ký.',

            inspection_spec:
                'Chụp phần thông số kỹ thuật trên giấy đăng kiểm.',

            inspection_expiry:
                'Chụp phần thời hạn đăng kiểm còn hiệu lực.'
        };

        return hints[slot]
            || 'Chụp rõ chi tiết theo tên ô ảnh.';
    }


    loadBrands();

    restoreDraft();

})();