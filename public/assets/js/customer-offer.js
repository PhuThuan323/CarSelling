(function () {
  "use strict";

  var config = window.OFFER_CONFIG || {};

  var acceptModal = document.getElementById("acceptModal");
  var declineModal = document.getElementById("declineModal");

  function openModal(modal) {
    if (modal) {
      modal.hidden = false;
      document.body.classList.add("has-modal");
    }
  }

  function closeModal(modal) {
    if (modal) {
      modal.hidden = true;
      document.body.classList.remove("has-modal");
    }
  }

  function post(url, button, loadingLabel) {
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
      body: JSON.stringify({
        valuation_request_id: config.valuationRequestId,
      }),
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

  function fail(error) {
    window.alert(error.message);
  }

  /* ---------------- Đồng ý bán xe ---------------- */
  var acceptBtn = document.getElementById("acceptOfferBtn");

  if (acceptBtn) {
    acceptBtn.addEventListener("click", function () {
      openModal(acceptModal);
    });
  }

  var acceptCancel = document.getElementById("acceptCancel");

  if (acceptCancel) {
    acceptCancel.addEventListener("click", function () {
      closeModal(acceptModal);
    });
  }

  var acceptConfirm = document.getElementById("acceptConfirm");

  if (acceptConfirm) {
    acceptConfirm.addEventListener("click", function () {
      post(config.acceptUrl, acceptConfirm, "Đang gửi...")
        .then(function () {
          window.location.reload();
        })
        .catch(fail);
    });
  }

  /* ---------------- Không đồng ý ---------------- */
  var declineBtn = document.getElementById("declineOfferBtn");

  if (declineBtn) {
    declineBtn.addEventListener("click", function () {
      openModal(declineModal);
    });
  }

  var declineCancel = document.getElementById("declineCancel");

  if (declineCancel) {
    declineCancel.addEventListener("click", function () {
      closeModal(declineModal);
    });
  }

  var declineConfirm = document.getElementById("declineConfirm");

  if (declineConfirm) {
    declineConfirm.addEventListener("click", function () {
      post(config.declineUrl, declineConfirm, "Đang hủy...")
        .then(function () {
          window.location.reload();
        })
        .catch(fail);
    });
  }

  /* ---------------- Đóng modal khi bấm nền / ESC ---------------- */
  [acceptModal, declineModal].forEach(function (modal) {
    if (!modal) {
      return;
    }

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal(modal);
      }
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key !== "Escape") {
      return;
    }

    closeModal(acceptModal);
    closeModal(declineModal);
  });
})();
