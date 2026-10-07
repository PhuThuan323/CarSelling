(function () {
    "use strict";

    function $(selector) {
        return document.querySelector(selector);
    }

    function config() {
        return window.ADMIN_AUCTION_CONFIG || {};
    }


    /* ============================================================
       MESSAGE
       ============================================================ */

    function showMsg(text, type) {
        var msg = $("#adminAuctionMsg");

        if (!msg) {
            return;
        }

        msg.hidden = false;
        msg.textContent = text;

        msg.className =
            "auction-form-msg is-" + (type || "info");
    }


    /* ============================================================
       API
       ============================================================ */

    function postJSON(url, body) {
        return fetch(url, {
            method: "POST",

            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-Token": config().csrfToken || ""
            },

            body: JSON.stringify(body || {})
        }).then(function (response) {

            return response.json().then(function (payload) {

                return {
                    ok: response.ok,
                    body: payload
                };

            });

        });
    }


    /* ============================================================
       FORMAT
       ============================================================ */

    function formatMoney(value) {

        var number = Number(value || 0);

        if (!number) {
            return "—";
        }

        return new Intl.NumberFormat("vi-VN").format(number) + " đ";
    }


    function formatNumber(value) {

        var number = Number(value || 0);

        if (!number) {
            return "—";
        }

        return new Intl.NumberFormat("vi-VN").format(number);
    }


    function setText(id, value) {

        var element = document.getElementById(id);

        if (!element) {
            return;
        }

        if (
            value !== undefined &&
            value !== null &&
            String(value).trim() !== ""
        ) {
            element.textContent = value;
        } else {
            element.textContent = "—";
        }
    }


    /* ============================================================
       AUCTION REQUEST PREVIEW
       ============================================================ */

    function initRequestPreview() {

        var requestSelect =
            document.getElementById("auctionRequest");

        if (!requestSelect) {
            return;
        }

        var preview =
            document.getElementById("auctionRequestPreview");

        var startPriceInput =
            document.getElementById("auctionStartPrice");


        function resetPreview() {

            if (preview) {
                preview.hidden = true;
            }

            setText("previewReference", "—");

            setText("previewBrand", "—");

            setText("previewModel", "—");

            setText("previewYear", "—");

            setText("previewOdometer", "—");

            setText("previewLicensePlate", "—");

            setText("previewExteriorColor", "—");

            setText("previewContactName", "—");

            setText("previewContactPhone", "—");

            setText("previewContactEmail", "—");

            setText("previewEstimatedMin", "—");

            setText("previewEstimatedMax", "—");


            if (startPriceInput) {
                startPriceInput.value = "";
            }
        }


        function fillPreview(option) {

            if (!option || !option.value) {

                resetPreview();

                return;
            }


            var data = option.dataset;


            /* ---------------------------------------------
               Hồ sơ
               --------------------------------------------- */

            setText(
                "previewReference",
                data.referenceCode
                    ? data.referenceCode
                    : "#" + option.value
            );


            /* ---------------------------------------------
               Xe
               --------------------------------------------- */

            setText(
                "previewBrand",
                data.brand
            );


            setText(
                "previewModel",
                data.model
            );


            setText(
                "previewYear",
                data.manufactureYear
            );


            setText(
                "previewOdometer",
                data.odometer
                    ? formatNumber(data.odometer) + " km"
                    : "—"
            );


            setText(
                "previewLicensePlate",
                data.licensePlate
            );


            setText(
                "previewExteriorColor",
                data.exteriorColor
            );


            /* ---------------------------------------------
               Khách hàng
               --------------------------------------------- */

            setText(
                "previewContactName",
                data.contactName
            );


            setText(
                "previewContactPhone",
                data.contactPhone
            );


            setText(
                "previewContactEmail",
                data.contactEmail
            );


            /* ---------------------------------------------
               Định giá
               --------------------------------------------- */

            setText(
                "previewEstimatedMin",
                formatMoney(data.estimatedMin)
            );


            setText(
                "previewEstimatedMax",
                formatMoney(data.estimatedMax)
            );


            /* ---------------------------------------------
               Giá khởi điểm mặc định
               --------------------------------------------- */

            var estimatedMin =
                Number(data.estimatedMin || 0);


            if (
                startPriceInput &&
                estimatedMin > 0
            ) {

                startPriceInput.value =
                    Math.round(estimatedMin);

            }


            /* ---------------------------------------------
               Hiện preview
               --------------------------------------------- */

            if (preview) {
                preview.hidden = false;
            }
        }


        requestSelect.addEventListener(
            "change",
            function () {

                var option =
                    requestSelect.options[
                        requestSelect.selectedIndex
                    ];

                fillPreview(option);
            }
        );


        /* ---------------------------------------------
           Restore nếu select đã có value
           --------------------------------------------- */

        if (requestSelect.value) {

            var option =
                requestSelect.options[
                    requestSelect.selectedIndex
                ];

            fillPreview(option);
        }
    }


    /* ============================================================
       CREATE AUCTION FORM
       ============================================================ */

    function initCreateForm() {

        var form =
            $("#adminAuctionForm");

        if (!form) {
            return;
        }


        form.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                var requestSelect =
                    $("#auctionRequest");

                var startPriceInput =
                    $("#auctionStartPrice");

                var durationInput =
                    $("#auctionDuration");

                var startAtInput =
                    $("#auctionStartAt");


                if (!requestSelect) {
                    return;
                }


                var requestId =
                    parseInt(
                        requestSelect.value,
                        10
                    );


                var startPrice =
                    String(
                        startPriceInput
                            ? startPriceInput.value
                            : ""
                    ).replace(/[^\d]/g, "");


                var duration =
                    parseInt(
                        durationInput
                            ? durationInput.value
                            : "0",
                        10
                    );


                var startAt =
                    startAtInput
                        ? startAtInput.value
                        : "";


                /* -----------------------------------------
                   Validate request
                   ----------------------------------------- */

                if (!requestId) {

                    showMsg(
                        "Vui lòng chọn hồ sơ bán xe.",
                        "error"
                    );

                    return;
                }


                /* -----------------------------------------
                   Validate price
                   ----------------------------------------- */

                if (!startPrice || Number(startPrice) <= 0) {

                    showMsg(
                        "Vui lòng nhập giá khởi điểm hợp lệ.",
                        "error"
                    );

                    return;
                }


                /* -----------------------------------------
                   Validate duration
                   ----------------------------------------- */

                if (!duration || duration <= 0) {

                    showMsg(
                        "Vui lòng nhập thời gian đấu giá hợp lệ.",
                        "error"
                    );

                    return;
                }


                /* -----------------------------------------
                   Submit button
                   ----------------------------------------- */

                var button =
                    form.querySelector(
                        "button[type='submit']"
                    );


                if (button) {
                    button.disabled = true;
                }


                showMsg(
                    "Đang tạo phiên đấu giá...",
                    "info"
                );


                /* -----------------------------------------
                   API
                   ----------------------------------------- */

                postJSON(
                    "/api/v1/admin/auctions",
                    {
                        valuation_request_id: requestId,

                        start_price: startPrice,

                        duration_days: duration,

                        start_at: startAt || null
                    }
                )

                    .then(function (result) {

                        if (
                            !result.ok ||
                            !result.body ||
                            result.body.status !== "success"
                        ) {

                            throw new Error(
                                (
                                    result.body &&
                                    result.body.message
                                ) ||
                                "Không thể tạo phiên đấu giá."
                            );
                        }


                        showMsg(
                            "Đã tạo phiên đấu giá thành công.",
                            "success"
                        );


                        setTimeout(
                            function () {
                                window.location.reload();
                            },
                            800
                        );

                    })

                    .catch(function (error) {

                        showMsg(
                            error.message ||
                            "Đã xảy ra lỗi.",
                            "error"
                        );


                        if (button) {
                            button.disabled = false;
                        }

                    });

            }
        );
    }


    /* ============================================================
       PAYMENT BUTTONS
       ============================================================ */

    function initPaymentButtons() {

        document.addEventListener(
            "click",
            function (event) {

                var target =
                    event.target.closest(
                        ".admin-auction-pay-btn, .admin-auction-cancel-btn"
                    );


                if (!target) {
                    return;
                }


                var auctionId =
                    target.getAttribute(
                        "data-auction-id"
                    );


                var status =
                    target.getAttribute(
                        "data-status"
                    );


                var confirmText =
                    status === "paid"

                        ? "Xác nhận đã thanh toán cho phiên này?"

                        : "Hủy thanh toán của phiên này?";


                if (!window.confirm(confirmText)) {
                    return;
                }


                var note = null;


                if (status === "cancelled") {

                    note =
                        window.prompt(
                            "Lý do hủy (có thể để trống):",
                            ""
                        ) || null;
                }


                target.disabled = true;


                postJSON(
                    "/api/v1/admin/auctions/" +
                    auctionId +
                    "/payment",

                    {
                        status: status,
                        note: note
                    }
                )

                    .then(function (result) {

                        if (
                            !result.ok ||
                            !result.body ||
                            result.body.status !== "success"
                        ) {

                            throw new Error(
                                (
                                    result.body &&
                                    result.body.message
                                ) ||
                                "Không thể cập nhật."
                            );
                        }


                        showMsg(
                            result.body.message ||
                            "Đã cập nhật.",
                            "success"
                        );


                        setTimeout(
                            function () {
                                window.location.reload();
                            },
                            800
                        );

                    })

                    .catch(function (error) {

                        showMsg(
                            error.message ||
                            "Đã xảy ra lỗi.",
                            "error"
                        );


                        target.disabled = false;

                    });

            }
        );
    }


    /* ============================================================
       INIT
       ============================================================ */

    function init() {

        initRequestPreview();

        initCreateForm();

        initPaymentButtons();

    }


    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            init
        );

    } else {

        init();

    }

})();