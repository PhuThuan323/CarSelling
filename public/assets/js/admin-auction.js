(function () {
  "use strict";

  function $(selector) {
    return document.querySelector(selector);
  }

  function config() {
    return window.ADMIN_AUCTION_CONFIG || {};
  }

  function showMsg(text, type) {
    var msg = $("#adminAuctionMsg");

    if (!msg) return;

    msg.hidden = false;
    msg.textContent = text;
    msg.className = "auction-form-msg is-" + (type || "info");
  }

  function postJSON(url, body) {
    return fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-Token": config().csrfToken || "",
      },
      body: JSON.stringify(body || {}),
    }).then(function (res) {
      return res.json().then(function (payload) {
        return { ok: res.ok, body: payload };
      });
    });
  }

  function initCreateForm() {
    var form = $("#adminAuctionForm");

    if (!form) return;

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var requestId = parseInt($("#auctionRequest").value, 10);
      var startPrice = $("#auctionStartPrice").value.replace(/[^\d]/g, "");
      var duration = parseInt($("#auctionDuration").value, 10);

      if (!requestId) {
        showMsg("Vui lòng chọn hồ sơ bán xe.", "error");
        return;
      }

      if (!startPrice) {
        showMsg("Vui lòng nhập giá gốc.", "error");
        return;
      }

      var button = form.querySelector("button[type=submit]");
      button.disabled = true;

      postJSON("/api/v1/admin/auctions", {
        valuation_request_id: requestId,
        start_price: startPrice,
        duration_days: duration,
      })
        .then(function (result) {
          if (!result.ok || result.body.status !== "success") {
            throw new Error(
              (result.body && result.body.message) || "Không thể tạo phiên.",
            );
          }

          showMsg("Đã tạo phiên đấu giá.", "success");

          setTimeout(function () {
            window.location.reload();
          }, 800);
        })
        .catch(function (error) {
          showMsg(error.message, "error");
          button.disabled = false;
        });
    });
  }

  function initPaymentButtons() {
    document.addEventListener("click", function (event) {
      var target = event.target;

      if (
        !target.classList.contains("admin-auction-pay-btn") &&
        !target.classList.contains("admin-auction-cancel-btn")
      ) {
        return;
      }

      var auctionId = target.getAttribute("data-auction-id");
      var status = target.getAttribute("data-status");

      var confirmText =
        status === "paid"
          ? "Xác nhận đã thanh toán cho phiên này?"
          : "Hủy thanh toán của phiên này?";

      if (!window.confirm(confirmText)) {
        return;
      }

      var note =
        status === "cancelled"
          ? window.prompt("Lý do hủy (có thể để trống):", "") || null
          : null;

      target.disabled = true;

      postJSON("/api/v1/admin/auctions/" + auctionId + "/payment", {
        status: status,
        note: note,
      })
        .then(function (result) {
          if (!result.ok || result.body.status !== "success") {
            throw new Error(
              (result.body && result.body.message) || "Không thể cập nhật.",
            );
          }

          showMsg(result.body.message || "Đã cập nhật.", "success");

          setTimeout(function () {
            window.location.reload();
          }, 800);
        })
        .catch(function (error) {
          showMsg(error.message, "error");
          target.disabled = false;
        });
    });
  }

  function init() {
    initCreateForm();
    initPaymentButtons();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
