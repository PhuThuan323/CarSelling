(function () {
  "use strict";

  var API = {
    brands: "/api/v1/admin/brands",
    models: "/api/v1/admin/models",
    versions: "/api/v1/admin/versions",
    brand: function (id) {
      return API.brands + "/" + id;
    },
    model: function (id) {
      return API.models + "/" + id;
    },
    version: function (id) {
      return API.versions + "/" + id;
    },
  };

  var state = { brands: [], models: [], versions: [] };
  var currentSection = "brands";
  var currentForm = "brand";

  function csrfToken() {
    return (window.ADMIN_CONFIG && window.ADMIN_CONFIG.csrfToken) || "";
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
    }, 4200);
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
          return {};
        })
        .then(function (body) {
          if (response.status === 401) {
            window.location.href = "/auth/login";
            throw new Error("Phiên đăng nhập đã hết hạn.");
          }

          if (!response.ok) {
            throw new Error(body.message || "Lỗi " + response.status);
          }

          return body;
        });
    });
  }

  function statusPill(status) {
    var active = status === "active";

    return (
      '<span class="admin-pill ' +
      (active ? "admin-pill-active" : "admin-pill-inactive") +
      '">' +
      (active ? "active" : "inactive") +
      "</span>"
    );
  }

  function emptyRow(colspan, text) {
    return (
      '<tr><td colspan="' +
      colspan +
      '" class="admin-empty">' +
      escapeHtml(text) +
      "</td></tr>"
    );
  }

  /* ===================== BRANDS ===================== */

  function renderBrands() {
    var search = document.getElementById("brandSearch");
    var statusSelect = document.getElementById("brandStatus");

    if (!search || !statusSelect) return;

    var keyword = search.value.trim().toLowerCase();
    var status = statusSelect.value;

    var rows = state.brands.filter(function (brand) {
      var matchedKeyword =
        !keyword ||
        String(brand.name || "")
          .toLowerCase()
          .indexOf(keyword) !== -1;

      var matchedStatus = !status || brand.status === status;

      return matchedKeyword && matchedStatus;
    });

    var tbody = document.getElementById("brandTableBody");
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = emptyRow(7, "Chưa có hãng xe nào phù hợp.");
      return;
    }

    tbody.innerHTML = rows
      .map(function (brand) {
        var logo = brand.logo || brand.logo_url;

        return (
          "<tr>" +
          "<td>" +
          escapeHtml(brand.id) +
          "</td>" +
          "<td>" +
          (logo
            ? '<img class="admin-thumb" src="' +
              escapeHtml(logo) +
              '" alt="' +
              escapeHtml(brand.name) +
              '">'
            : '<span class="admin-pill admin-pill-inactive">no logo</span>') +
          "</td>" +
          "<td><strong>" +
          escapeHtml(brand.name) +
          "</strong></td>" +
          "<td>" +
          escapeHtml(brand.slug) +
          "</td>" +
          "<td>" +
          escapeHtml(brand.country || "—") +
          "</td>" +
          "<td>" +
          statusPill(brand.status) +
          "</td>" +
          '<td class="admin-cell-actions">' +
          '<button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-edit-brand="' +
          escapeHtml(brand.id) +
          '">Sửa</button>' +
          '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-delete-brand="' +
          escapeHtml(brand.id) +
          '">Xóa</button>' +
          "</td>" +
          "</tr>"
        );
      })
      .join("");
  }

  function loadBrands() {
    return request("GET", API.brands).then(function (body) {
      state.brands = (body.data && body.data.brands) || [];
      fillBrandSelects();
      renderBrands();
    });
  }

  /* ===================== MODELS ===================== */

  function renderModels() {
    var brandFilter = document.getElementById("modelBrandFilter");
    var statusSelect = document.getElementById("modelStatus");

    if (!brandFilter || !statusSelect) return;

    var brandId = brandFilter.value;
    var status = statusSelect.value;

    var rows = state.models.filter(function (model) {
      return (
        (!brandId || String(model.brand_id) === brandId) &&
        (!status || model.status === status)
      );
    });

    var tbody = document.getElementById("modelTableBody");
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = emptyRow(7, "Chưa có dòng xe nào phù hợp.");
      return;
    }

    tbody.innerHTML = rows
      .map(function (model) {
        return (
          "<tr>" +
          "<td>" +
          escapeHtml(model.id) +
          "</td>" +
          "<td>" +
          escapeHtml(model.brand_name || "—") +
          "</td>" +
          "<td><strong>" +
          escapeHtml(model.name) +
          "</strong></td>" +
          "<td>" +
          escapeHtml(model.slug) +
          "</td>" +
          "<td>" +
          escapeHtml(model.body_type || "—") +
          "</td>" +
          "<td>" +
          statusPill(model.status) +
          "</td>" +
          '<td class="admin-cell-actions">' +
          '<button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-edit-model="' +
          escapeHtml(model.id) +
          '">Sửa</button>' +
          '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-delete-model="' +
          escapeHtml(model.id) +
          '">Xóa</button>' +
          "</td>" +
          "</tr>"
        );
      })
      .join("");
  }

  function loadModels() {
    return request("GET", API.models).then(function (body) {
      state.models = (body.data && body.data.models) || [];
      fillModelSelects();
      renderModels();
    });
  }

  /* ===================== VERSIONS ===================== */

  function renderVersions() {
    var modelFilter = document.getElementById("versionModelFilter");
    var statusSelect = document.getElementById("versionStatus");

    if (!modelFilter || !statusSelect) return;

    var modelId = modelFilter.value;
    var status = statusSelect.value;

    var rows = state.versions.filter(function (version) {
      return (
        (!modelId || String(version.model_id) === modelId) &&
        (!status || version.status === status)
      );
    });

    var tbody = document.getElementById("versionTableBody");
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = emptyRow(8, "Chưa có phiên bản nào phù hợp.");
      return;
    }

    tbody.innerHTML = rows
      .map(function (version) {
        var years = [version.production_year_from, version.production_year_to]
          .filter(Boolean)
          .join(" - ");

        var engine = version.engine_displacement_cc
          ? version.engine_displacement_cc + " cc"
          : version.fuel_type || "—";

        return (
          "<tr>" +
          "<td>" +
          escapeHtml(version.id) +
          "</td>" +
          "<td>" +
          escapeHtml(
            (version.brand_name || "") + " / " + (version.model_name || ""),
          ) +
          "</td>" +
          "<td><strong>" +
          escapeHtml(version.name) +
          "</strong></td>" +
          "<td>" +
          escapeHtml(years || "—") +
          "</td>" +
          "<td>" +
          escapeHtml(engine) +
          "</td>" +
          "<td>" +
          escapeHtml(version.transmission || "—") +
          "</td>" +
          "<td>" +
          statusPill(version.status) +
          "</td>" +
          '<td class="admin-cell-actions">' +
          '<button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-edit-version="' +
          escapeHtml(version.id) +
          '">Sửa</button>' +
          '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-delete-version="' +
          escapeHtml(version.id) +
          '">Xóa</button>' +
          "</td>" +
          "</tr>"
        );
      })
      .join("");
  }

  function loadVersions() {
    return request("GET", API.versions).then(function (body) {
      state.versions = (body.data && body.data.versions) || [];
      renderVersions();
    });
  }

  /* ===================== SELECT HELPERS ===================== */

  function options(
    items,
    valueKey,
    labelBuilder,
    keepPlaceholder,
    placeholder,
  ) {
    var html = keepPlaceholder
      ? '<option value="">' + escapeHtml(placeholder || "Tất cả") + "</option>"
      : "";

    return (
      html +
      items
        .map(function (item) {
          return (
            '<option value="' +
            escapeHtml(item[valueKey]) +
            '">' +
            escapeHtml(labelBuilder(item)) +
            "</option>"
          );
        })
        .join("")
    );
  }

  function brandLabel(brand) {
    return brand.name + (brand.status === "active" ? "" : " (inactive)");
  }

  function modelLabel(model) {
    return (
      (model.brand_name ? model.brand_name + " - " : "") +
      model.name +
      (model.status === "active" ? "" : " (inactive)")
    );
  }

  function keepValue(select, html) {
    if (!select) return;

    var previous = select.value;
    select.innerHTML = html;

    if (previous) {
      select.value = previous;
    }
  }

  function fillBrandSelects() {
    var active = state.brands.filter(function (brand) {
      return brand.status === "active";
    });

    var filter = document.getElementById("modelBrandFilter");
    if (filter) {
      keepValue(
        filter,
        '<option value="">Tất cả hãng</option>' +
          options(state.brands, "id", brandLabel, false),
      );
    }

    var modelBrand = document.getElementById("modelBrandId");
    if (modelBrand) {
      var previous = modelBrand.value;

      modelBrand.innerHTML =
        '<option value="">-- Chọn hãng xe --</option>' +
        options(active, "id", brandLabel, false);

      if (previous) {
        modelBrand.value = previous;
      }
    }

    var versionBrand = document.getElementById("versionBrandId");
    if (versionBrand) {
      var oldValue = versionBrand.value;

      versionBrand.innerHTML =
        '<option value="">-- Chọn hãng xe --</option>' +
        options(active, "id", brandLabel, false);

      if (oldValue) {
        versionBrand.value = oldValue;
      }
    }
  }

  function fillModelSelects() {
    var filter = document.getElementById("versionModelFilter");

    if (filter) {
      keepValue(
        filter,
        '<option value="">Tất cả dòng xe</option>' +
          options(state.models, "id", modelLabel, false),
      );
    }

    refreshVersionModelSelect();
  }

  function ensureVersionBrandSelect() {
    var existing = document.getElementById("versionBrandId");
    if (existing) return existing;

    var modelSelect = document.getElementById("versionModelId");
    if (!modelSelect) return null;

    var modelField = modelSelect.closest(".admin-field");
    if (!modelField) return null;

    var brandField = document.createElement("div");
    brandField.className = "admin-field";
    brandField.id = "versionBrandField";

    brandField.innerHTML =
      '<label for="versionBrandId">Hãng xe *</label>' +
      '<select id="versionBrandId" class="admin-select" autocomplete="off">' +
      '<option value="">-- Chọn hãng xe --</option>' +
      "</select>";

    modelField.parentNode.insertBefore(brandField, modelField);

    var brandSelect = document.getElementById("versionBrandId");

    brandSelect.addEventListener("change", function () {
      refreshVersionModelSelect("");
    });

    fillBrandSelects();

    return brandSelect;
  }

  function refreshVersionModelSelect(selectedModelId) {
    var brandSelect = document.getElementById("versionBrandId");
    var modelSelect = document.getElementById("versionModelId");

    if (!modelSelect) return;

    var brandId = brandSelect ? brandSelect.value : "";

    if (!brandId) {
      modelSelect.innerHTML =
        '<option value="">-- Chọn hãng xe trước --</option>';
      modelSelect.value = "";
      modelSelect.disabled = true;
      return;
    }

    var filteredModels = state.models.filter(function (model) {
      return (
        String(model.brand_id) === String(brandId) && model.status === "active"
      );
    });

    modelSelect.innerHTML =
      '<option value="">-- Chọn dòng xe --</option>' +
      options(
        filteredModels,
        "id",
        function (model) {
          return model.name;
        },
        false,
      );

    modelSelect.disabled = false;

    if (selectedModelId) {
      modelSelect.value = String(selectedModelId);
    }
  }

  function brandIdByModelId(modelId) {
    var model = findById(state.models, modelId);
    return model ? model.brand_id : "";
  }

  /* ===================== MODAL ===================== */

  function forceFormVisibility(formName) {
    ["brandForm", "modelForm", "versionForm"].forEach(function (id) {
      var form = document.getElementById(id);
      if (!form) return;

      var shouldShow = id === formName + "Form";

      form.hidden = !shouldShow;
      form.classList.toggle("is-hidden-form", !shouldShow);
      form.style.display = shouldShow ? "" : "none";
    });
  }

  function prepareCreateForm(formName) {
    var form = document.getElementById(formName + "Form");
    if (!form) return;

    form.reset();

    var idInput = form.querySelector('input[name="id"]');
    if (idInput) {
      idInput.value = "";
    }

    if (formName === "model") {
      fillBrandSelects();
    }

    if (formName === "version") {
      ensureVersionBrandSelect();

      var versionBrand = document.getElementById("versionBrandId");
      if (versionBrand) {
        versionBrand.value = "";
      }

      refreshVersionModelSelect("");
    }

    if (formName === "brand" && window.BrandLogoUploader) {
      window.BrandLogoUploader.reset();
    }
  }

  function openModal(formName, title, isCreate) {
    currentForm = formName;

    var titleEl = document.getElementById("modalTitle");
    if (titleEl) {
      titleEl.textContent = title;
    }

    forceFormVisibility(formName);

    if (isCreate) {
      prepareCreateForm(formName);
    }

    var modal = document.getElementById("adminModal");
    if (modal) {
      modal.hidden = false;
    }
  }

  function closeModal() {
    var modal = document.getElementById("adminModal");
    if (modal) {
      modal.hidden = true;
    }

    ["brandForm", "modelForm", "versionForm"].forEach(function (id) {
      var form = document.getElementById(id);
      if (!form) return;

      form.reset();

      var idInput = form.querySelector('input[name="id"]');
      if (idInput) {
        idInput.value = "";
      }

      form.hidden = true;
      form.classList.add("is-hidden-form");
      form.style.display = "none";
    });

    var versionBrand = document.getElementById("versionBrandId");
    if (versionBrand) {
      versionBrand.value = "";
    }

    refreshVersionModelSelect("");

    if (window.BrandLogoUploader) {
      window.BrandLogoUploader.reset();
    }
  }

  /* ===================== PAYLOAD ===================== */

  function numberOrNull(form, name) {
    var field = form.elements[name];
    if (!field) return null;

    var value = String(field.value || "").trim();

    if (value === "") return null;

    var parsed = Number(value);

    return isNaN(parsed) ? null : parsed;
  }

  function textOrNull(form, name) {
    var field = form.elements[name];
    if (!field) return null;

    var value = String(field.value || "").trim();

    return value === "" ? null : value;
  }

  function brandPayload(form) {
    return {
      name: form.elements["name"].value.trim(),
      slug: form.elements["slug"].value.trim(),
      country: textOrNull(form, "country"),
      logo: textOrNull(form, "logo"),
      description: textOrNull(form, "description"),
      status: form.elements["status"].value,
    };
  }

  function modelPayload(form) {
    return {
      brand_id: numberOrNull(form, "brand_id"),
      name: form.elements["name"].value.trim(),
      slug: form.elements["slug"].value.trim(),
      body_type: textOrNull(form, "body_type"),
      description: textOrNull(form, "description"),
      status: form.elements["status"].value,
    };
  }

  function versionPayload(form) {
    return {
      model_id: numberOrNull(form, "model_id"),
      name: form.elements["name"].value.trim(),
      slug: form.elements["slug"].value.trim(),
      status: form.elements["status"].value,
      production_year_from: numberOrNull(form, "production_year_from"),
      production_year_to: numberOrNull(form, "production_year_to"),
      engine_name: textOrNull(form, "engine_name"),
      engine_displacement_cc: numberOrNull(form, "engine_displacement_cc"),
      fuel_type: textOrNull(form, "fuel_type"),
      transmission: textOrNull(form, "transmission"),
      drivetrain: textOrNull(form, "drivetrain"),
      horsepower: numberOrNull(form, "horsepower"),
      seats: numberOrNull(form, "seats"),
      description: textOrNull(form, "description"),
    };
  }

  function save() {
    var form = document.getElementById(currentForm + "Form");
    if (!form) return;

    var idInput = form.querySelector('input[name="id"]');
    var id = idInput ? idInput.value : "";

    var payload;
    var url;

    if (currentForm === "brand") {
      payload = brandPayload(form);

      if (!payload.name) {
        notify("Vui lòng nhập tên hãng xe.", "error");
        return;
      }

      url = id ? API.brand(id) : API.brands;
    } else if (currentForm === "model") {
      payload = modelPayload(form);

      if (!payload.name || !payload.brand_id) {
        notify("Vui lòng chọn hãng xe và nhập tên dòng xe.", "error");
        return;
      }

      url = id ? API.model(id) : API.models;
    } else {
      var versionBrand = document.getElementById("versionBrandId");

      if (!versionBrand || !versionBrand.value) {
        notify("Vui lòng chọn hãng xe.", "error");
        return;
      }

      payload = versionPayload(form);

      if (!payload.name || !payload.model_id) {
        notify("Vui lòng chọn dòng xe và nhập tên phiên bản.", "error");
        return;
      }

      url = id ? API.version(id) : API.versions;
    }

    var method = id ? "PUT" : "POST";

    request(method, url, payload)
      .then(function () {
        notify(id ? "Cập nhật thành công." : "Thêm mới thành công.", "success");

        closeModal();
        return reloadAll();
      })
      .catch(function (error) {
        notify(error.message, "error");
      });
  }

  /* ===================== DELETE ===================== */

  function removeOne(kind, id) {
    if (!window.confirm("Bạn chắc chắn muốn xóa bản ghi này?")) return;

    var url =
      kind === "brand"
        ? API.brand(id)
        : kind === "model"
          ? API.model(id)
          : API.version(id);

    request("DELETE", url, null)
      .then(function () {
        notify("Đã xóa thành công.", "success");
        return reloadAll();
      })
      .catch(function (error) {
        notify(error.message, "error");
      });
  }

  /* ===================== EDIT ===================== */

  function fillForm(kind, record) {
    var form = document.getElementById(kind + "Form");
    if (!form) return;

    form.reset();

    var idInput = form.querySelector('input[name="id"]');
    if (idInput) {
      idInput.value = record.id || "";
    }

    Object.keys(record || {}).forEach(function (key) {
      var field = form.elements[key];

      if (!field) return;

      field.value = record[key] == null ? "" : record[key];
    });

    if (kind === "brand") {
      var logoValue = record.logo || record.logo_url || "";

      if (form.elements["logo"]) {
        form.elements["logo"].value = logoValue;
      }

      if (window.BrandLogoUploader) {
        window.BrandLogoUploader.setExisting(
          logoValue,
          record.logo_public_id || record.public_id || "",
        );
      }
    }

    if (kind === "model") {
      fillBrandSelects();

      if (form.elements["brand_id"]) {
        form.elements["brand_id"].value = record.brand_id;
      }
    }

    if (kind === "version") {
      ensureVersionBrandSelect();
      fillBrandSelects();

      var brandId = record.brand_id || brandIdByModelId(record.model_id);
      var brandSelect = document.getElementById("versionBrandId");

      if (brandSelect) {
        brandSelect.value = brandId ? String(brandId) : "";
      }

      refreshVersionModelSelect(record.model_id);

      if (form.elements["model_id"]) {
        form.elements["model_id"].value = record.model_id;
      }
    }
  }

  function findById(collection, id) {
    var found = null;

    collection.forEach(function (item) {
      if (String(item.id) === String(id)) {
        found = item;
      }
    });

    return found;
  }

  function reloadAll() {
    return Promise.all([loadBrands(), loadModels(), loadVersions()]);
  }

  /* ===================== SECTION ===================== */

  function switchSection(section) {
    currentSection = section;

    ["brands", "models", "versions"].forEach(function (name) {
      var panel = document.getElementById("panel-" + name);
      if (panel) {
        panel.classList.toggle("is-hidden", name !== section);
      }

      var nav = document.querySelector(
        '.admin-nav-item[data-section="' + name + '"]',
      );
      if (nav) {
        nav.classList.toggle("is-active", name === section);
      }
    });
  }

  /* ===================== EVENTS ===================== */

  document.addEventListener("click", function (event) {
    var target = event.target.closest(
      "[data-section]," +
        "[data-action]," +
        "[data-edit-brand]," +
        "[data-delete-brand]," +
        "[data-edit-model]," +
        "[data-delete-model]," +
        "[data-edit-version]," +
        "[data-delete-version]",
    );

    if (!target) return;

    if (target.dataset.section) {
      switchSection(target.dataset.section);
      return;
    }

    if (target.dataset.action === "create-brand") {
      openModal("brand", "Thêm hãng xe", true);
      return;
    }

    if (target.dataset.action === "create-model") {
      openModal("model", "Thêm dòng xe", true);
      return;
    }

    if (target.dataset.action === "create-version") {
      ensureVersionBrandSelect();
      openModal("version", "Thêm phiên bản xe", true);
      return;
    }

    var brandId = target.dataset.editBrand || target.dataset.deleteBrand;

    if (brandId) {
      if (target.dataset.deleteBrand) {
        removeOne("brand", brandId);
        return;
      }

      var brand = findById(state.brands, brandId);

      if (!brand) {
        notify("Không tìm thấy hãng xe.", "error");
        return;
      }

      openModal("brand", "Sửa hãng xe #" + brand.id, false);
      fillForm("brand", brand);
      return;
    }

    var modelId = target.dataset.editModel || target.dataset.deleteModel;

    if (modelId) {
      if (target.dataset.deleteModel) {
        removeOne("model", modelId);
        return;
      }

      var model = findById(state.models, modelId);

      if (!model) {
        notify("Không tìm thấy dòng xe.", "error");
        return;
      }

      openModal("model", "Sửa dòng xe #" + model.id, false);
      fillForm("model", model);
      return;
    }

    var versionId = target.dataset.editVersion || target.dataset.deleteVersion;

    if (versionId) {
      if (target.dataset.deleteVersion) {
        removeOne("version", versionId);
        return;
      }

      var version = findById(state.versions, versionId);

      if (!version) {
        notify("Không tìm thấy phiên bản xe.", "error");
        return;
      }

      ensureVersionBrandSelect();
      openModal("version", "Sửa phiên bản xe #" + version.id, false);
      fillForm("version", version);
    }
  });

  var submitButton = document.getElementById("modalSubmit");
  if (submitButton) {
    submitButton.addEventListener("click", save);
  }

  var cancelButton = document.getElementById("modalCancel");
  if (cancelButton) {
    cancelButton.addEventListener("click", closeModal);
  }

  var closeButton = document.getElementById("modalClose");
  if (closeButton) {
    closeButton.addEventListener("click", closeModal);
  }

  var modal = document.getElementById("adminModal");
  if (modal) {
    modal.addEventListener("click", function (event) {
      if (event.target === this) {
        closeModal();
      }
    });
  }

  var brandSearch = document.getElementById("brandSearch");
  if (brandSearch) {
    brandSearch.addEventListener("input", renderBrands);
  }

  var brandStatus = document.getElementById("brandStatus");
  if (brandStatus) {
    brandStatus.addEventListener("change", renderBrands);
  }

  var modelBrandFilter = document.getElementById("modelBrandFilter");
  if (modelBrandFilter) {
    modelBrandFilter.addEventListener("change", renderModels);
  }

  var modelStatus = document.getElementById("modelStatus");
  if (modelStatus) {
    modelStatus.addEventListener("change", renderModels);
  }

  var versionModelFilter = document.getElementById("versionModelFilter");
  if (versionModelFilter) {
    versionModelFilter.addEventListener("change", renderVersions);
  }

  var versionStatus = document.getElementById("versionStatus");
  if (versionStatus) {
    versionStatus.addEventListener("change", renderVersions);
  }

  ensureVersionBrandSelect();
  forceFormVisibility("__none__");
  switchSection(currentSection);

  reloadAll().catch(function (error) {
    notify(error.message, "error");
  });
})();
