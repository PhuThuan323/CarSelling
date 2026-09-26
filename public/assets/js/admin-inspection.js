(function () {
  "use strict";

  var config = window.INSPECTION_CONFIG || {};

  function notify(message, type) {
    var box = document.getElementById("adminAlert");
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

  function post(url, payload, button) {
    var original = button ? button.innerHTML : "";

    if (button) {
      button.disabled = true;
      button.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
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

  /* -------------------- Phân công staff -------------------- */
  var assignForm = document.getElementById("assignForm");

  if (assignForm) {
    assignForm.addEventListener("submit", function (event) {
      event.preventDefault();

      var staffSelect = document.getElementById("staffUserId");

      if (!staffSelect.value) {
        notify("Vui lòng chọn nhân viên inspection.", "error");
        return;
      }

      var button = document.getElementById("assignSubmit");

      post(
        config.assignUrl,
        {
          staff_user_id: Number(staffSelect.value),
          scheduled_at: document.getElementById("scheduledAt").value || null,
          admin_note: document.getElementById("adminNote").value || null,
        },
        button,
      )
        .then(function () {
          window.location.reload();
        })
        .catch(function (error) {
          notify(error.message, "error");
        });
    });
  }

  /* -------------------- Chốt giá + feedback -------------------- */
  var approveForm = document.getElementById("approveForm");

  if (approveForm) {
    approveForm.addEventListener("submit", function (event) {
      event.preventDefault();

      var priceMin = document.getElementById("priceMin").value.trim();
      var priceMax = document.getElementById("priceMax").value.trim();
      var message = document.getElementById("feedbackMessage").value.trim();

      if (!priceMin || !priceMax) {
        notify("Vui lòng nhập đủ range giá đề xuất.", "error");
        return;
      }

      if (!message) {
        notify("Vui lòng nhập feedback gửi khách hàng.", "error");
        return;
      }

      var minValue = Number(priceMin.replace(/[^\d]/g, ""));
      var maxValue = Number(priceMax.replace(/[^\d]/g, ""));

      if (maxValue < minValue) {
        notify("Giá cao nhất phải lớn hơn hoặc bằng giá thấp nhất.", "error");
        return;
      }

      var button = document.getElementById("approveSubmit");

      post(
        config.approveUrl,
        {
          price_min: minValue,
          price_max: maxValue,
          message: message,
        },
        button,
      )
        .then(function () {
          window.location.reload();
        })
        .catch(function (error) {
          notify(error.message, "error");
        });
    });
  }
})();
