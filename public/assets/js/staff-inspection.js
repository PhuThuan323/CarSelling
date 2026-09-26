(function () {
  "use strict";

  var config = window.INSPECTION_CONFIG || {};

  function notify(message, type) {
    var box = document.getElementById("staffAlert");
    if (!box) {
      window.alert(message);
      return;
    }

    box.textContent = message;
    box.className =
      "admin-alert " + (type === "error" ? "is-error" : "is-success");
    box.hidden = false;

    window.clearTimeout(notify._timer);
    notify._timer = window.setTimeout(function () {
      box.hidden = true;
    }, 5000);
  }

  function post(url, payload, button, loadingLabel) {
    var original = button ? button.innerHTML : "";

    if (button) {
      button.disabled = true;
      button.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> ' +
        (loadingLabel || "Đang xử lý...");
    }

    return fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-Token": config.csrfToken || "",
      },
      body: JSON.stringify(payload || {}),
    })
      .then(function (response) {
        return response.json().then(function (body) {
          return { ok: response.ok, body: body };
        });
      })
      .then(function (result) {
        if (!result.ok) {
          throw new Error(result.body.message || "Có lỗi xảy ra.");
        }

        return result.body;
      })
      .finally(function () {
        if (button) {
          button.disabled = false;
          button.innerHTML = original;
        }
      });
  }

  /* -------------------- Nhận nhiệm vụ -------------------- */
  var acceptBtn = document.getElementById("acceptBtn");

  if (acceptBtn) {
    acceptBtn.addEventListener("click", function () {
      post(config.acceptUrl, {}, acceptBtn, "Đang nhận...")
        .then(function () {
          window.location.reload();
        })
        .catch(function (error) {
          notify(error.message, "error");
        });
    });
  }

  /* -------------------- Bắt đầu inspection -------------------- */
  var startBtn = document.getElementById("startBtn");

  if (startBtn) {
    startBtn.addEventListener("click", function () {
      post(config.startUrl, {}, startBtn, "Đang bắt đầu...")
        .then(function () {
          window.location.reload();
        })
        .catch(function (error) {
          notify(error.message, "error");
        });
    });
  }

  /* -------------------- Gửi kết quả thẩm định -------------------- */
  var resultForm = document.getElementById("resultForm");

  if (resultForm) {
    resultForm.addEventListener("submit", function (event) {
      event.preventDefault();

      var summary = document.getElementById("summary").value.trim();

      if (!summary) {
        notify("Vui lòng nhập nhận xét tổng quan.", "error");
        return;
      }

      var minRaw = document.getElementById("suggestedMin").value.trim();
      var maxRaw = document.getElementById("suggestedMax").value.trim();

      var minValue = Number(minRaw.replace(/[^\d]/g, ""));
      var maxValue = Number(maxRaw.replace(/[^\d]/g, ""));

      if (!minValue || !maxValue) {
        notify("Vui lòng nhập range giá đề xuất.", "error");
        return;
      }

      if (maxValue < minValue) {
        notify("Giá cao phải lớn hơn hoặc bằng giá thấp.", "error");
        return;
      }

      var payload = {
        odometer_actual:
          document.getElementById("odometerActual").value || null,
        summary: summary,
        staff_note: document.getElementById("staffNote").value || null,
        suggested_price_min: minValue,
        suggested_price_max: maxValue,
      };

      // Thu thập toàn bộ hạng mục đánh giá
      resultForm
        .querySelectorAll('select[name$="_rating"]')
        .forEach(function (select) {
          payload[select.name] = select.value || null;
        });

      var button = document.getElementById("resultSubmit");

      post(config.submitUrl, payload, button, "Đang gửi...")
        .then(function () {
          window.location.reload();
        })
        .catch(function (error) {
          notify(error.message, "error");
        });
    });
  }
})();