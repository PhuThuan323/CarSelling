/**
 * Trang admin: Quan ly cau chuyen khach hang (testimonial).
 *
 * Du lieu luu file JSON qua API, khong dung database.
 *   GET    /api/v1/admin/feedback-stories
 *   POST   /api/v1/admin/feedback-stories
 *   DELETE /api/v1/admin/feedback-stories/{id}
 */
(function () {
  "use strict";

  var API = "/api/v1/admin/feedback-stories";

  var stories = [];
  var pendingDeleteId = null;

  function csrfToken() {
    return (window.ADMIN_CONFIG && window.ADMIN_CONFIG.csrfToken) || "";
  }

  function byId(id) {
    return document.getElementById(id);
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(
      /[&<>"']/g,
      function (ch) {
        return {
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        }[ch];
      },
    );
  }

  function notify(message, type) {
    var box = byId("adminAlert");
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
          if (response.status === 401) {
            window.location.href = "/auth/login";
            throw new Error("Phiên đăng nhập đã hết hạn.");
          }

          if (!response.ok || body.status === "error") {
            throw new Error(body.message || "Thao tác không thành công.");
          }

          return body;
        });
    });
  }

  /* ===================== RENDER DANH SACH ===================== */

  function formatDate(timestamp) {
    if (!timestamp) return "";

    var date = new Date(timestamp * 1000);

    if (isNaN(date.getTime())) return "";

    return (
      String(date.getDate()).padStart(2, "0") +
      "/" +
      String(date.getMonth() + 1).padStart(2, "0") +
      "/" +
      date.getFullYear()
    );
  }

  function storyCard(story) {
    var isHidden = story.status === "hidden";
    var image = story.image
      ? '<img class="story-admin-thumb" src="' +
        escapeHtml(story.image) +
        '" alt="' +
        escapeHtml(story.author_name) +
        '" onerror="this.style.display=\'none\'">'
      : '<span class="story-admin-thumb is-empty"><i class="fa-regular fa-image"></i></span>';

    return (
      '<article class="story-admin-card" data-story-id="' +
      story.id +
      '" data-search="' +
      escapeHtml(
        [
          story.author_name,
          story.title,
          story.excerpt,
          story.story,
          story.location,
        ].join(" "),
      ) +
      '">' +
      image +
      '<div class="story-admin-body">' +
      '<div class="story-admin-flags">' +
      '<span class="admin-pill ' +
      (isHidden ? "admin-pill-inactive" : "admin-pill-active") +
      '">' +
      (isHidden ? "hidden" : "active") +
      "</span>" +
      (story.location
        ? '<span class="story-admin-location">' +
          escapeHtml(story.location) +
          "</span>"
        : "") +
      "</div>" +
      '<h3 class="story-admin-title">' +
      escapeHtml(story.title) +
      "</h3>" +
      '<p class="story-admin-excerpt">' +
      escapeHtml(story.excerpt) +
      "</p>" +
      '<div class="story-admin-meta">' +
      '<span><i class="fa-regular fa-user"></i> ' +
      escapeHtml(story.author_name) +
      "</span>" +
      '<span><i class="fa-regular fa-calendar"></i> ' +
      escapeHtml(formatDate(story.created_at)) +
      "</span>" +
      "</div>" +
      "</div>" +
      '<div class="story-admin-actions">' +
      '<a class="admin-btn admin-btn-ghost admin-btn-sm" href="/customer-stories/' +
      encodeURIComponent(story.slug) +
      '" target="_blank" rel="noopener">' +
      '<i class="fa-solid fa-up-right-from-square"></i> Xem' +
      "</a>" +
      '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-delete-story="' +
      story.id +
      '" data-story-name="' +
      escapeHtml(story.title) +
      '">' +
      '<i class="fa-solid fa-trash"></i> Xóa' +
      "</button>" +
      "</div>" +
      "</article>"
    );
  }

  function applySearch() {
    var input = byId("storySearch");
    var list = byId("storyList");
    var noResult = byId("storyNoResult");

    if (!input || !list) return;

    var keyword = input.value.trim().toLowerCase();
    var visible = 0;

    Array.prototype.forEach.call(
      list.querySelectorAll(".story-admin-card"),
      function (card) {
        var haystack = (card.getAttribute("data-search") || "").toLowerCase();
        var match = keyword === "" || haystack.indexOf(keyword) !== -1;

        card.hidden = !match;
        if (match) visible += 1;
      },
    );

    if (noResult) {
      noResult.hidden = !(visible === 0 && stories.length > 0);
    }
  }

  function render() {
    var list = byId("storyList");
    var empty = byId("storyEmpty");
    var loading = byId("storyLoading");

    if (loading) loading.hidden = true;

    if (!list) return;

    if (!stories.length) {
      list.hidden = true;
      list.innerHTML = "";
      if (empty) empty.hidden = false;
      applySearch();
      return;
    }

    if (empty) empty.hidden = true;

    list.innerHTML = stories.map(storyCard).join("");
    list.hidden = false;

    applySearch();
  }

  function refreshStats() {
    var totalEl = document.querySelector(".story-stat strong");
    var stats = document.querySelectorAll(".story-stat strong");

    if (stats.length < 2) return;

    var active = stories.filter(function (story) {
      return story.status === "active";
    }).length;

    stats[0].textContent = String(stories.length);
    stats[1].textContent = String(active);
  }

  /* ===================== TAI DU LIEU ===================== */

  function loadStories() {
    return request("GET", API)
      .then(function (body) {
        stories = (body.data && body.data.stories) || [];
        render();
        refreshStats();
      })
      .catch(function (error) {
        var loading = byId("storyLoading");
        if (loading) loading.hidden = true;
        notify(error.message, "error");
      });
  }

  /* ===================== MODAL THEM ===================== */

  function openCreateModal() {
    var modal = byId("storyModal");
    var form = byId("storyForm");

    if (form) form.reset();
    if (modal) modal.hidden = false;

    var first = byId("storyAuthor");
    if (first) first.focus();
  }

  function closeCreateModal() {
    var modal = byId("storyModal");
    if (modal) modal.hidden = true;
  }

  function submitStory() {
    var form = byId("storyForm");

    if (!form) return;

    var payload = {
      author_name: byId("storyAuthor").value.trim(),
      location: byId("storyLocation").value.trim(),
      title: byId("storyTitle").value.trim(),
      image: byId("storyImage").value.trim(),
      excerpt: byId("storyExcerpt").value.trim(),
      story: byId("storyContent").value.trim(),
      status: byId("storyStatus").value,
    };

    if (!payload.author_name || !payload.title || !payload.story) {
      notify(
        "Vui lòng nhập tên khách hàng, tiêu đề và nội dung câu chuyện.",
        "error",
      );
      return;
    }

    var button = byId("storyModalSubmit");
    if (button) button.disabled = true;

    request("POST", API, payload)
      .then(function (body) {
        notify(body.message || "Đã thêm câu chuyện khách hàng.");
        closeCreateModal();
        return loadStories();
      })
      .catch(function (error) {
        notify(error.message, "error");
      })
      .then(function () {
        if (button) button.disabled = false;
      });
  }

  /* ===================== XOA ===================== */

  function openDeleteModal(id, name) {
    pendingDeleteId = id;

    var modal = byId("storyDeleteModal");
    var nameEl = byId("storyDeleteName");

    if (nameEl) nameEl.textContent = name;
    if (modal) modal.hidden = false;
  }

  function closeDeleteModal() {
    pendingDeleteId = null;

    var modal = byId("storyDeleteModal");
    if (modal) modal.hidden = true;
  }

  function confirmDelete() {
    if (!pendingDeleteId) return;

    var button = byId("storyDeleteConfirm");
    if (button) button.disabled = true;

    request("DELETE", API + "/" + pendingDeleteId)
      .then(function (body) {
        notify(body.message || "Đã xóa câu chuyện.");
        closeDeleteModal();
        return loadStories();
      })
      .catch(function (error) {
        notify(error.message, "error");
      })
      .then(function () {
        if (button) button.disabled = false;
      });
  }

  /* ===================== KHOI TAO ===================== */

  function init() {
    stories = (window.STORY_ADMIN && window.STORY_ADMIN.initial) || [];
    render();
    refreshStats();

    var createBtn = byId("storyCreateBtn");
    if (createBtn) createBtn.addEventListener("click", openCreateModal);

    var cancelBtn = byId("storyModalCancel");
    if (cancelBtn) cancelBtn.addEventListener("click", closeCreateModal);

    var closeBtn = byId("storyModalClose");
    if (closeBtn) closeBtn.addEventListener("click", closeCreateModal);

    var submitBtn = byId("storyModalSubmit");
    if (submitBtn) submitBtn.addEventListener("click", submitStory);

    var modal = byId("storyModal");
    if (modal) {
      modal.addEventListener("click", function (event) {
        if (event.target === modal) closeCreateModal();
      });
    }

    var deleteModal = byId("storyDeleteModal");
    if (deleteModal) {
      deleteModal.addEventListener("click", function (event) {
        if (event.target === deleteModal) closeDeleteModal();
      });
    }

    ["storyDeleteClose", "storyDeleteCancel"].forEach(function (id) {
      var el = byId(id);
      if (el) el.addEventListener("click", closeDeleteModal);
    });

    var deleteConfirm = byId("storyDeleteConfirm");
    if (deleteConfirm) deleteConfirm.addEventListener("click", confirmDelete);

    var search = byId("storySearch");
    if (search) search.addEventListener("input", applySearch);

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeCreateModal();
        closeDeleteModal();
      }
    });

    // Nut xoa nam trong danh sach duoc render dong.
    var list = byId("storyList");
    if (list) {
      list.addEventListener("click", function (event) {
        var button = event.target.closest("[data-delete-story]");
        if (!button) return;

        openDeleteModal(
          parseInt(button.getAttribute("data-delete-story"), 10),
          button.getAttribute("data-story-name") || "",
        );
      });
    }

    // Doi chieu du lieu server de chac chan dong bo.
    loadStories();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
