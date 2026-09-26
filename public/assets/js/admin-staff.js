/**
 * Trang admin: Quan ly nhan su & phan quyen.
 *
 * Xu ly:
 *   - loc nhanh phia client theo tu khoa
 *   - cap / thu hoi quyen staff
 *   - khoa / mo lai tai khoan
 */
(function () {
  "use strict";

  function csrfToken() {
    return (window.ADMIN_CONFIG && window.ADMIN_CONFIG.csrfToken) || "";
  }

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
    }, 4500);
  }

  function request(method, url, payload) {
    var options = {
      method: method,
      headers: {
        Accept: "application/json",
        "X-CSRF-Token": csrfToken(),
      },
    };

    if (payload) {
      options.headers["Content-Type"] = "application/json";
      options.body = JSON.stringify(payload);
    }

    return fetch(url, options).then(function (response) {
      return response
        .json()
        .catch(function () {
          throw new Error("Máy chủ trả về dữ liệu không hợp lệ.");
        })
        .then(function (body) {
          if (!response.ok || body.status === "error") {
            throw new Error(body.message || "Thao tác không thành công.");
          }
          return body;
        });
    });
  }

  /* ===================== TIM KIEM (client) ===================== */

  function initSearch() {
    var input = document.getElementById("staffSearch");
    var body = document.getElementById("staffTableBody");
    var noResult = document.getElementById("staffNoResult");

    if (!input || !body) {
      return;
    }

    input.addEventListener("input", function () {
      var keyword = input.value.trim().toLowerCase();
      var rows = body.querySelectorAll("tr[data-search]");
      var visible = 0;

      rows.forEach(function (row) {
        var haystack = (row.getAttribute("data-search") || "").toLowerCase();
        var match = keyword === "" || haystack.indexOf(keyword) !== -1;
        row.hidden = !match;
        if (match) {
          visible += 1;
        }
      });

      if (noResult) {
        noResult.hidden = visible !== 0;
      }
    });
  }

  /* ===================== PHAN QUYEN ===================== */

  function changeRole(button) {
    var row = button.closest("tr");
    var userId = row ? row.getAttribute("data-user-id") : null;
    var role = button.getAttribute("data-role-action");
    var name = button.getAttribute("data-user-name") || "người dùng này";

    if (!userId) {
      return;
    }

    var confirmText =
      role === "staff"
        ? "Cấp quyền Nhân viên Inspection cho " + name + "?"
        : "Thu hồi quyền Nhân viên Inspection của " + name + "?";

    if (role === "customer") {
      confirmText +=
        "\n\nNgười này sẽ trở về quyền khách hàng và không truy cập được khu vực inspection.";
    }

    if (!window.confirm(confirmText)) {
      return;
    }

    button.disabled = true;

    request("POST", "/api/v1/admin/users/" + userId + "/role", { role: role })
      .then(function (body) {
        var warning = body.data && body.data.warning;
        notify(body.message + (warning ? " Lưu ý: " + warning : ""), "success");
        window.setTimeout(
          function () {
            window.location.reload();
          },
          warning ? 2200 : 900,
        );
      })
      .catch(function (error) {
        notify(error.message, "error");
        button.disabled = false;
      });
  }

  function changeStatus(button) {
    var row = button.closest("tr");
    var userId = row ? row.getAttribute("data-user-id") : null;
    var status = button.getAttribute("data-status-action");
    var name = button.getAttribute("data-user-name") || "người dùng này";

    if (!userId) {
      return;
    }

    var confirmText =
      status === "blocked"
        ? "Khóa tài khoản của " + name + "?"
        : "Mở lại tài khoản của " + name + "?";

    if (!window.confirm(confirmText)) {
      return;
    }

    button.disabled = true;

    request("POST", "/api/v1/admin/users/" + userId + "/status", {
      status: status,
    })
      .then(function (body) {
        notify(body.message, "success");
        window.setTimeout(function () {
          window.location.reload();
        }, 900);
      })
      .catch(function (error) {
        notify(error.message, "error");
        button.disabled = false;
      });
  }

  document.addEventListener("click", function (event) {
    var roleBtn = event.target.closest("[data-role-action]");
    if (roleBtn) {
      changeRole(roleBtn);
      return;
    }

    var statusBtn = event.target.closest("[data-status-action]");
    if (statusBtn) {
      changeStatus(statusBtn);
    }
  });

  initSearch();
})();
