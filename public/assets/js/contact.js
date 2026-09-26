(function () {

    "use strict";


    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const page =
        document.getElementById(
            "contactPage"
        );


    if (!page) {

        console.error(
            "Không tìm thấy #contactPage."
        );

        return;
    }


    const requestId =
        Number(
            page.dataset
                .valuationRequestId
        );


    const nameInput =
        document.getElementById(
            "name"
        );


    const phoneInput =
        document.getElementById(
            "phone"
        );


    const emailInput =
        document.getElementById(
            "email"
        );


    const continueButton =
        document.getElementById(
            "continueButton"
        );


    const backButton =
        document.getElementById(
            "backButton"
        );


    let isSubmitting = false;


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PAGE
    |--------------------------------------------------------------------------
    */

    if (
        !Number.isInteger(requestId) ||
        requestId <= 0
    ) {

        alert(
            "Hồ sơ không hợp lệ."
        );


        window.location.href =
            "/sell-car";


        return;
    }


    if (
        !nameInput ||
        !phoneInput ||
        !emailInput ||
        !continueButton
    ) {

        console.error(
            "Trang contact thiếu element bắt buộc."
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | API HELPER
    |--------------------------------------------------------------------------
    */

    async function request(
        url,
        options = {}
    ) {

        const headers = {

            Accept:
                "application/json",

            ...(options.headers || {})
        };


        const response =
            await fetch(
                url,
                {
                    ...options,

                    credentials:
                        "same-origin",

                    headers
                }
            );


        /*
         * Luôn đọc text trước.
         *
         * Vì nếu PHP trả HTML/text lỗi
         * thì response.json() sẽ gây:
         *
         * Unexpected token ...
         */
        const rawBody =
            await response.text();


        console.log(
            "===== CONTACT API ====="
        );

        console.log(
            "URL:",
            url
        );

        console.log(
            "HTTP:",
            response.status
        );

        console.log(
            "RAW:",
            rawBody
        );

        console.log(
            "======================="
        );


        let body = {};


        if (
            rawBody.trim() !== ""
        ) {

            try {

                body =
                    JSON.parse(
                        rawBody
                    );

            } catch (error) {

                throw new Error(
                    "Server không trả JSON hợp lệ. "
                    +
                    "HTTP "
                    +
                    response.status
                    +
                    ". Response: "
                    +
                    rawBody.substring(
                        0,
                        500
                    )
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | AUTH
        |--------------------------------------------------------------------------
        */

        if (
            response.status === 401
        ) {

            window.location.href =
                "/auth/login?redirect="
                +
                encodeURIComponent(
                    window.location.pathname
                );


            throw new Error(
                "Bạn cần đăng nhập."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | HTTP ERROR
        |--------------------------------------------------------------------------
        */

        if (
            !response.ok
        ) {

            const error =
                new Error(
                    body.message ||
                    body.error ||
                    "HTTP "
                    +
                    response.status
                );


            error.status =
                response.status;


            error.body =
                body;


            throw error;
        }


        return body;
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD / VALIDATE VALUATION
    |--------------------------------------------------------------------------
    */

    async function checkValuation()
    {
        try {

            const result =
                await request(
                    "/api/v1/valuations/detail?id="
                    +
                    encodeURIComponent(
                        requestId
                    )
                );


            const valuation =
                result.data?.valuation;


            if (!valuation) {

                throw new Error(
                    "Không tìm thấy hồ sơ định giá."
                );
            }


            console.log(
                "Valuation:",
                valuation
            );


            /*
             * Trang này chỉ dùng cho contact_pending.
             */
            if (
                valuation.status
                !==
                "contact_pending"
            ) {

                let message =
                    "Hồ sơ chưa sẵn sàng để nhập thông tin liên hệ.";


                if (
                    valuation.status
                    ===
                    "ready_for_estimate"
                ) {

                    message =
                        "Hồ sơ này đã được gửi thành công.";


                    alert(
                        message
                    );


                    window.location.href =
                        "/sell-car-success/"
                        +
                        encodeURIComponent(
                            requestId
                        );


                    return;
                }


                throw new Error(
                    message
                    +
                    " Trạng thái hiện tại: "
                    +
                    valuation.status
                );
            }


            /*
             * Cho phép submit.
             */
            continueButton.disabled =
                false;


        } catch (error) {

            console.error(
                "Check valuation error:",
                error
            );


            alert(
                error.message
            );


            continueButton.disabled =
                true;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BACK
    |--------------------------------------------------------------------------
    */

    if (backButton) {

        backButton
            .addEventListener(
                "click",
                function () {

                    /*
                     * Dùng history trước
                     * để tránh tạo hồ sơ mới không cần thiết.
                     */
                    if (
                        window.history.length
                        > 1
                    ) {

                        window.history.back();

                        return;
                    }


                    window.location.href =
                        "/sell-car";
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | SUBMIT CONTACT
    |--------------------------------------------------------------------------
    */

    continueButton
        .addEventListener(
            "click",
            async function () {

                if (
                    isSubmitting
                ) {

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | FORM DATA
                |--------------------------------------------------------------------------
                */

                const fullName =
                    nameInput
                        .value
                        .trim();


                const phone =
                    phoneInput
                        .value
                        .trim();


                const email =
                    emailInput
                        .value
                        .trim();


                /*
                |--------------------------------------------------------------------------
                | VALIDATE NAME
                |--------------------------------------------------------------------------
                */

                if (!fullName) {

                    alert(
                        "Vui lòng nhập tên chủ xe."
                    );


                    nameInput.focus();


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDATE PHONE
                |--------------------------------------------------------------------------
                */

                if (!phone) {

                    alert(
                        "Vui lòng nhập số điện thoại."
                    );


                    phoneInput.focus();


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDATE EMAIL
                |--------------------------------------------------------------------------
                */

                if (
                    email &&
                    !emailInput
                        .checkValidity()
                ) {

                    alert(
                        "Email không hợp lệ."
                    );


                    emailInput.focus();


                    return;
                }


                try {

                    /*
                    |--------------------------------------------------------------------------
                    | LOCK BUTTON
                    |--------------------------------------------------------------------------
                    */

                    isSubmitting =
                        true;


                    this.disabled =
                        true;


                    this.textContent =
                        "Đang đăng ký...";


                    /*
                    |--------------------------------------------------------------------------
                    | SAVE CONTACT
                    |--------------------------------------------------------------------------
                    */

                    const result =
                        await request(
                            "/api/v1/valuations/contact",
                            {
                                method:
                                    "POST",

                                headers: {

                                    "Content-Type":
                                        "application/json"
                                },

                                body:
                                    JSON.stringify({

                                        valuation_request_id:
                                            requestId,

                                        full_name:
                                            fullName,

                                        phone:
                                            phone,

                                        email:
                                            email !== ""
                                                ? email
                                                : null
                                    })
                            }
                        );


                    console.log(
                        "CONTACT SUCCESS:",
                        result
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDATE SERVER RESULT
                    |--------------------------------------------------------------------------
                    */

                    const returnedId =
                        Number(
                            result.data
                                ?.valuation_request_id
                                ??
                            requestId
                        );


                    if (
                        !Number.isInteger(
                            returnedId
                        ) ||
                        returnedId <= 0
                    ) {

                        throw new Error(
                            "Server không trả ID hồ sơ hợp lệ."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CLEAN SESSION
                    |--------------------------------------------------------------------------
                    */

                    sessionStorage
                        .removeItem(
                            "valuation_request_id"
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS PAGE
                    |--------------------------------------------------------------------------
                    */

                    window.location.href =
                        "/sell-car-success/"
                        +
                        encodeURIComponent(
                            returnedId
                        );


                } catch (error) {

                    console.error(
                        "Contact submit error:",
                        error
                    );


                    alert(
                        error.message ||
                        "Không thể đăng ký."
                    );


                    isSubmitting =
                        false;


                    this.disabled =
                        false;


                    this.textContent =
                        "Xác nhận";
                }
            }
        );


    /*
    |--------------------------------------------------------------------------
    | INIT
    |--------------------------------------------------------------------------
    */

    continueButton.disabled =
        true;


    checkValuation();

})();