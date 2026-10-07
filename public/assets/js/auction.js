(function () {
  "use strict";

  function $(selector) {
    return document.querySelector(selector);
  }

  function $$(selector) {
    return Array.prototype.slice.call(document.querySelectorAll(selector));
  }

  function formatMoney(value) {
    return new Intl.NumberFormat("vi-VN").format(value) + " đ";
  }

  /* ------------------------------------------------------------------ *
   * Countdown: cap nhat moi .auction-countdown theo data-end
   * ------------------------------------------------------------------ */
  function initCountdowns() {
    var nodes = $$(".auction-countdown");

    if (!nodes.length) return;

    function tick() {
      var now = Date.now();

      nodes.forEach(function (node) {
        var end = new Date(node.getAttribute("data-end").replace(" ", "T"));
        var target = end.getTime();

        if (isNaN(target)) return;

        var diff = target - now;

        var text = node.querySelector(".auction-countdown-text");

        if (!text) return;

        if (diff <= 0) {
          text.textContent = "Đã kết thúc";
          return;
        }

        var days = Math.floor(diff / 86400000);
        var hours = Math.floor((diff % 86400000) / 3600000);
        var minutes = Math.floor((diff % 3600000) / 60000);
        var seconds = Math.floor((diff % 60000) / 1000);

        var parts = [];

        if (days > 0) parts.push(days + " ngày");
        parts.push(hours + " giờ");
        parts.push(minutes + " phút");
        parts.push(seconds + " giây");

        text.textContent = parts.join(" ");
      });
    }

    tick();
    setInterval(tick, 1000);
  }

  /* ------------------------------------------------------------------ *
   * Trang chi tiet: dat gia
   * ------------------------------------------------------------------ */
  function initBidForm() {
    var config = window.AUCTION_CONFIG;
    var form = $("#auctionBidForm");

    if (!config || !form) return;

    var msg = $("#auctionFormMsg");
    var input = $("#bidAmount");
    var currentPriceEl = $("#auctionCurrentPrice");
    var minNextEl = $("#minNextBidText");

    function showMsg(text, type) {
      if (!msg) return;
      msg.hidden = false;
      msg.textContent = text;
      msg.className = "auction-form-msg is-" + (type || "info");
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var raw = input.value.replace(/[^\d]/g, "");
      var amount = parseInt(raw, 10);

      if (!amount || amount <= 0) {
        showMsg("Vui lòng nhập giá đặt hợp lệ.", "error");
        return;
      }

      if (amount < config.minNextBid) {
        showMsg(
          "Giá đặt tối thiểu là " + formatMoney(config.minNextBid) + ".",
          "error",
        );
        return;
      }

      var button = form.querySelector("button[type=submit]");
      button.disabled = true;

      fetch("/api/v1/auctions/" + config.auctionId + "/bid", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-Token": config.csrfToken,
        },
        body: JSON.stringify({ amount: amount }),
      })
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, body: body };
          });
        })
        .then(function (result) {
          if (!result.ok || result.body.status !== "success") {
            throw new Error(
              (result.body && result.body.message) || "Không thể đặt giá.",
            );
          }

          showMsg("Đặt giá thành công!", "success");

          var newPrice = result.body.data.current_price;

          if (currentPriceEl) {
            currentPriceEl.textContent = formatMoney(newPrice);
          }

          // Cap nhat lai gia toi thieu cho lan dat tiep theo.
          config.minNextBid = newPrice + 1;

          if (minNextEl) {
            minNextEl.textContent = formatMoney(config.minNextBid);
          }

          // Tai lai trang de dong bo lich su + gia.
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

  function init() {
    initCountdowns();
    initBidForm();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
