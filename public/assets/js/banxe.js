(function () {
  "use strict";

  /*
   * Ảnh chỉ được giữ tạm trong trình duyệt.
   * Chỉ khi người dùng bấm "Tiếp tục" mới:
   * 1) tạo valuation request
   * 2) upload ảnh
   * 3) xác nhận hoàn thành bước ảnh
   */

  const selectedPhotos = new Map();

  let valuationRequestId = null;
  let versions = [];
  let photosComplete = false;
  let isSubmitting = false;

  const API = {
    brands: "/api/v1/brands",

    modelsByBrand: function (brandId) {
      return "/api/v1/brands/" + encodeURIComponent(brandId) + "/models";
    },

    versionsByModel: function (modelId) {
      return "/api/v1/models/" + encodeURIComponent(modelId) + "/versions";
    },

    createValuation: "/api/v1/valuations/create",

    uploadImage: "/api/v1/valuations/images/upload",

    replaceImage: "/api/v1/valuations/images/replace",

    completePhotos: "/api/v1/valuations/complete-photos",
  };

  /*
  |--------------------------------------------------------------------------
  | API HELPER
  |--------------------------------------------------------------------------
  */

  async function request(url, options = {}) {
    const headers = {
      Accept: "application/json",

      ...(options.headers || {}),
    };

    const response = await fetch(url, {
      ...options,

      credentials: "same-origin",

      headers,
    });

    const rawBody = await response.text();

    console.log("===== API RESPONSE =====");

    console.log("URL:", url);

    console.log("Status:", response.status);

    console.log("Raw response:", rawBody);

    console.log("========================");

    let body = {};

    if (rawBody.trim()) {
      try {
        body = JSON.parse(rawBody);
      } catch (error) {
        const err = new Error(
          `Server không trả JSON hợp lệ. HTTP ${response.status}. Response: ${rawBody.substring(
            0,
            500,
          )}`,
        );

        err.status = response.status;

        throw err;
      }
    }

    if (response.status === 401) {
      window.location.href = "/auth/login?redirect=/sell-car";

      const err = new Error("Bạn cần đăng nhập.");

      err.status = 401;

      throw err;
    }

    if (!response.ok) {
      const err = new Error(
        body.message || body.error || `HTTP ${response.status}`,
      );

      err.status = response.status;

      err.body = body;

      throw err;
    }

    return body;
  }

  /*
  |--------------------------------------------------------------------------
  | ELEMENTS
  |--------------------------------------------------------------------------
  */

  const brandSelect =
    document.getElementById("BrandSelect") ||
    document.getElementById("brandSelect");

  const modelSelect = document.getElementById("modelSelect");

  const yearSelect = document.getElementById("yearSelect");

  const versionSelect = document.getElementById("versionSelect");

  const odometer = document.getElementById("odometer");

  const continueButton = document.getElementById("continueButton");

  const consent = document.getElementById("valuationConsent");

  if (
    !brandSelect ||
    !modelSelect ||
    !yearSelect ||
    !versionSelect ||
    !odometer ||
    !continueButton ||
    !consent
  ) {
    console.error("Thiếu element bắt buộc trên trang sell-car.");

    return;
  }

  /*
  |--------------------------------------------------------------------------
  | VEHICLE CATALOG
  |--------------------------------------------------------------------------
  */

  async function loadBrands() {
    try {
      const response = await request(API.brands);

      const brands = response.data?.brands || response.data || [];

      brandSelect.innerHTML = '<option value="">-- Chọn hãng xe --</option>';

      brands.forEach(function (brand) {
        if (brand.status && brand.status !== "active") {
          return;
        }

        const option = document.createElement("option");

        option.value = brand.id;

        option.textContent = brand.name;

        brandSelect.appendChild(option);
      });
    } catch (error) {
      console.error(error);

      alert("Không tải được hãng xe: " + error.message);
    }
  }

  brandSelect.addEventListener("change", async function () {
    const brandId = this.value;

    versions = [];

    resetSelect(modelSelect, "-- Chọn dòng xe --");

    resetSelect(yearSelect, "-- Chọn dòng xe trước --");

    resetSelect(versionSelect, "-- Chọn đời xe trước --");

    if (!brandId) {
      return;
    }

    try {
      const response = await request(API.modelsByBrand(brandId));

      const models = response.data?.models || response.data || [];

      modelSelect.disabled = false;

      models.forEach(function (model) {
        const option = document.createElement("option");

        option.value = model.id;

        option.textContent = model.name;

        modelSelect.appendChild(option);
      });
    } catch (error) {
      console.error(error);

      alert("Không tải được dòng xe: " + error.message);
    }
  });

  modelSelect.addEventListener("change", async function () {
    const modelId = this.value;

    versions = [];

    resetSelect(yearSelect, "-- Chọn đời xe --");

    resetSelect(versionSelect, "-- Chọn đời xe trước --");

    if (!modelId) {
      return;
    }

    try {
      const response = await request(API.versionsByModel(modelId));

      versions = response.data?.versions || response.data || [];

      buildYears(versions);
    } catch (error) {
      console.error(error);

      alert("Không tải được phiên bản: " + error.message);
    }
  });

  function buildYears(versionRows) {
    const years = new Set();

    const currentYear = new Date().getFullYear();

    versionRows.forEach(function (version) {
      const from = Number(version.production_year_from);

      const to = Number(version.production_year_to) || currentYear;

      if (!from) {
        return;
      }

      for (let year = from; year <= to; year++) {
        years.add(year);
      }
    });

    Array.from(years)
      .sort(function (a, b) {
        return b - a;
      })
      .forEach(function (year) {
        const option = document.createElement("option");

        option.value = year;

        option.textContent = year;

        yearSelect.appendChild(option);
      });

    yearSelect.disabled = false;
  }

  yearSelect.addEventListener("change", function () {
    const year = Number(this.value);

    resetSelect(versionSelect, "-- Chọn phiên bản --");

    if (!year) {
      return;
    }

    const matched = versions.filter(function (version) {
      const from = Number(version.production_year_from) || 0;

      const to = Number(version.production_year_to) || 9999;

      return year >= from && year <= to;
    });

    matched.forEach(function (version) {
      const option = document.createElement("option");

      option.value = version.id;

      option.textContent = version.name;

      versionSelect.appendChild(option);
    });

    versionSelect.disabled = false;
  });

  /*
  |--------------------------------------------------------------------------
  | CREATE DRAFT
  |--------------------------------------------------------------------------
  */

  async function ensureDraft() {
    if (valuationRequestId) {
      return valuationRequestId;
    }

    const versionId = Number(versionSelect.value);

    const year = Number(yearSelect.value);

    /*
     * Number("") = 0,
     * nên phải kiểm tra chuỗi rỗng trước.
     */

    const kmRaw = String(odometer.value).trim();

    if (!brandSelect.value || !modelSelect.value || !year || !versionId) {
      throw new Error(
        "Vui lòng chọn đầy đủ hãng xe, dòng xe, đời xe và phiên bản.",
      );
    }

    if (kmRaw === "") {
      throw new Error("Vui lòng nhập số km đã đi.");
    }

    const km = Number(kmRaw);

    if (!Number.isFinite(km) || km < 0) {
      throw new Error("Số km đã đi không hợp lệ.");
    }

    const response = await request(API.createValuation, {
      method: "POST",

      headers: {
        "Content-Type": "application/json",
      },

      body: JSON.stringify({
        vehicle_version_id: versionId,

        manufacture_year: year,

        odometer_km: km,
      }),
    });

    valuationRequestId = Number(response.data?.valuation_request_id);

    if (!Number.isInteger(valuationRequestId) || valuationRequestId <= 0) {
      throw new Error("Server không trả valuation_request_id hợp lệ.");
    }

    sessionStorage.setItem("valuation_request_id", String(valuationRequestId));

    return valuationRequestId;
  }

  /*
  |--------------------------------------------------------------------------
  | LOCAL PHOTO SELECTION
  |--------------------------------------------------------------------------
  */

  document.querySelectorAll(".photo-slot").forEach(function (slot) {
    renderEmptySlot(slot);
  });

  function renderEmptySlot(slot) {
    const slotKey = slot.dataset.slot;

    slot.classList.remove("has-image");

    slot.innerHTML = `
      <div class="photo-empty">

        <span class="photo-empty-icon">
          <i class="fa-solid fa-camera"></i>
        </span>

        <strong class="photo-empty-label">
          ${photoLabel(slotKey)}
        </strong>

        <span class="photo-empty-hint">
          ${photoHint(slotKey)}
        </span>

        <button
          type="button"
          class="photo-upload-btn js-pick-photo"
        >
          <i class="fa-solid fa-cloud-arrow-up"></i>
          Chọn ảnh
        </button>

        <span class="photo-empty-meta">
          JPG, PNG, WEBP - tối đa 8MB
        </span>

        <input
          type="file"
          accept="image/jpeg,image/png,image/webp"
          capture="environment"
          hidden
        >

      </div>
    `;

    const input = slot.querySelector('input[type="file"]');

    const button = slot.querySelector(".js-pick-photo");

    button.addEventListener("click", function () {
      input.click();
    });

    input.addEventListener("change", function () {
      const file = this.files?.[0];

      if (!file) {
        return;
      }

      selectPhoto(slot, file);
    });
  }

  function selectPhoto(slot, file) {
    const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

    const maxSize = 8 * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
      alert("Chỉ hỗ trợ JPG, PNG hoặc WEBP.");

      return;
    }

    if (file.size > maxSize) {
      alert("Ảnh không được vượt quá 8MB.");

      return;
    }

    const slotKey = slot.dataset.slot;

    /*
     * Giải phóng preview cũ nếu người dùng thay ảnh.
     */

    const old = selectedPhotos.get(slotKey);

    if (old?.previewUrl) {
      URL.revokeObjectURL(old.previewUrl);
    }

    const previewUrl = URL.createObjectURL(file);

    selectedPhotos.set(slotKey, {
      file: file,

      previewUrl: previewUrl,
    });

    renderLocalPreview(slot, file, previewUrl);

    updateLocalProgress();
  }

  function renderLocalPreview(slot, file, previewUrl) {
    const slotKey = slot.dataset.slot;

    slot.classList.add("has-image");

    slot.innerHTML = `
      <img
        class="photo-preview"
        src="${previewUrl}"
        alt="${photoLabel(slotKey)}"
      >

      <div class="photo-actions">

        <button
          type="button"
          class="photo-action js-replace-photo"
        >
          <i class="fa-solid fa-rotate"></i>
          Thay ảnh
        </button>


        <button
          type="button"
          class="
            photo-action
            photo-action-delete
            js-delete-photo
          "
        >
          <i class="fa-solid fa-trash"></i>
          Xóa
        </button>

      </div>


      <input
        class="replace-photo-input"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        capture="environment"
        hidden
      >
    `;

    const replaceInput = slot.querySelector(".replace-photo-input");

    slot
      .querySelector(".js-replace-photo")
      .addEventListener("click", function () {
        replaceInput.click();
      });

    replaceInput.addEventListener("change", function () {
      const newFile = this.files?.[0];

      if (!newFile) {
        return;
      }

      selectPhoto(slot, newFile);
    });

    slot
      .querySelector(".js-delete-photo")
      .addEventListener("click", function () {
        const selected = selectedPhotos.get(slotKey);

        if (selected?.previewUrl) {
          URL.revokeObjectURL(selected.previewUrl);
        }

        selectedPhotos.delete(slotKey);

        renderEmptySlot(slot);

        updateLocalProgress();
      });
  }

  /*
  |--------------------------------------------------------------------------
  | LOCAL PHOTO PROGRESS
  |--------------------------------------------------------------------------
  */

  function updateLocalProgress() {
    const photoSlots = document.querySelectorAll(".photo-slot");

    const total = photoSlots.length;

    const completed = selectedPhotos.size;

    const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

    const progressText = document.getElementById("photoProgressText");

    if (progressText) {
      progressText.textContent = `${completed} / ${total}`;
    }

    const progressBar = document.getElementById("photoProgressBar");

    if (progressBar) {
      progressBar.style.width = `${percent}%`;
    }

    /*
     * Cập nhật số ảnh theo nhóm.
     */

    document.querySelectorAll(".photo-group").forEach(function (group) {
      const groupName = group.dataset.group;

      const slots = group.querySelectorAll(".photo-slot");

      let count = 0;

      slots.forEach(function (slot) {
        if (selectedPhotos.has(slot.dataset.slot)) {
          count++;
        }
      });

      const counter = document.querySelector(
        `[data-group-count="${groupName}"]`,
      );

      if (counter) {
        counter.textContent = String(count);
      }
    });

    photosComplete = total > 0 && completed === total;

    syncContinueButton();
  }

  /*
  |--------------------------------------------------------------------------
  | CONTINUE BUTTON
  |--------------------------------------------------------------------------
  */

  function syncContinueButton() {
    continueButton.disabled =
      !photosComplete || !consent.checked || isSubmitting;
  }

  consent.addEventListener("change", syncContinueButton);

  async function uploadOnePhoto(requestId, slotKey, file) {
    const form = new FormData();

    form.append("valuation_request_id", String(requestId));

    form.append("slot_key", slotKey);

    form.append("image", file);

    try {
      return await request(API.uploadImage, {
        method: "POST",

        body: form,
      });
    } catch (error) {
     

      if (error.status !== 409) {
        throw error;
      }

      const replaceForm = new FormData();

      replaceForm.append("valuation_request_id", String(requestId));

      replaceForm.append("slot_key", slotKey);

      replaceForm.append("image", file);

      return await request(API.replaceImage, {
        method: "POST",

        body: replaceForm,
      });
    }
  }

  async function uploadAllPhotos(requestId) {
    const slots = Array.from(document.querySelectorAll(".photo-slot"));

    for (let i = 0; i < slots.length; i++) {
      const slot = slots[i];

      const slotKey = slot.dataset.slot;

      const selected = selectedPhotos.get(slotKey);

      if (!selected?.file) {
        throw new Error(`Thiếu ảnh: ${photoLabel(slotKey)}`);
      }

      /*
       * Upload tuần tự.
       *
       * Không upload 17 ảnh song song
       * để tránh quá tải server / Cloudinary.
       */

      await uploadOnePhoto(requestId, slotKey, selected.file);

      /*
       * Hiển thị tiến độ
       * trực tiếp trên nút.
       */

      continueButton.textContent = `Đang tải ảnh ${i + 1}/${slots.length}...`;
    }
  }

  /*
  |--------------------------------------------------------------------------
  | CLICK CONTINUE
  |--------------------------------------------------------------------------
  */

  continueButton.addEventListener("click", async function () {
    if (isSubmitting) {
      return;
    }

    /*
     * Kiểm tra đủ ảnh.
     */

    if (!photosComplete) {
      alert("Vui lòng chọn đầy đủ hình ảnh xe.");

      return;
    }

    /*
     * Kiểm tra consent.
     */

    if (!consent.checked) {
      alert("Bạn cần đồng ý với Chính sách bảo mật và Quy chế hoạt động.");

      return;
    }

    try {
      isSubmitting = true;

      syncContinueButton();

      this.textContent = "Đang tạo hồ sơ...";

      /*
       * BƯỚC 1
       *
       * Tạo valuation_requests.
       */

      const requestId = await ensureDraft();

      /*
       * BƯỚC 2
       *
       * Upload toàn bộ ảnh.
       */

      await uploadAllPhotos(requestId);

      /*
       * BƯỚC 3
       *
       * Xác nhận hoàn thành ảnh.
       */

      this.textContent = "Đang hoàn tất...";

      await request(API.completePhotos, {
        method: "POST",

        headers: {
          "Content-Type": "application/json",
        },

        body: JSON.stringify({
          valuation_request_id: requestId,

          privacy_accepted: true,

          terms_accepted: true,
        }),
      });

      /*
       * BƯỚC 4
       *
       * Giữ ID request cho trang contact.
       */

      sessionStorage.setItem("valuation_request_id", String(requestId));

      /*
       * BƯỚC 5
       *
       * Redirect sang contact.
       */

      window.location.href =
        "/sell-car-contact/" + encodeURIComponent(requestId);
    } catch (error) {
      console.error(error);

      alert(error.message || "Không thể gửi hồ sơ.");

      isSubmitting = false;

      this.textContent = "Tiếp tục";

      syncContinueButton();
    }
  });

  /*
  |--------------------------------------------------------------------------
  | HELPERS
  |--------------------------------------------------------------------------
  */

  function resetSelect(select, text) {
    select.innerHTML = '<option value="">' + text + "</option>";

    select.disabled = true;
  }

  function photoLabel(slot) {
    const labels = {
      front_left_45: "Góc trước trái 45°",

      front_right_45: "Góc trước phải 45°",

      rear_left_45: "Góc sau trái 45°",

      rear_right_45: "Góc sau phải 45°",

      front: "Đầu xe",

      rear: "Đuôi xe",

      roof: "Nóc xe",

      odometer: "Đồng hồ ODO",

      cockpit: "Khoang lái",

      driver_seat: "Ghế lái",

      passenger_seat: "Ghế hành khách",

      rear_seat_headliner: "Hàng ghế sau và trần",

      engine_bay: "Khoang động cơ",

      wheel_front_left: "Bánh trước trái",

      wheel_front_right: "Bánh trước phải",

      wheel_rear_left: "Bánh sau trái",

      wheel_rear_right: "Bánh sau phải",
    };

    return labels[slot] || slot;
  }

  function photoHint(slot) {
    const hints = {
      front_left_45:
        "Đứng chếch trước bên trái, chụp trọn đầu xe và hông trái.",

      front_right_45:
        "Đứng chếch trước bên phải, chụp trọn đầu xe và hông phải.",

      rear_left_45: "Đứng chếch sau bên trái, chụp trọn đuôi xe và hông trái.",

      rear_right_45: "Đứng chếch sau bên phải, chụp trọn đuôi xe và hông phải.",

      front: "Chụp thẳng mặt trước xe, thấy rõ biển số và đèn.",

      rear: "Chụp thẳng mặt sau xe, thấy rõ biển số và cốp.",

      roof: "Chụp từ trên cao hoặc nghiêng để thấy toàn bộ nóc xe.",

      odometer: "Bật khóa điện, chụp rõ số km trên đồng hồ (số ODO).",

      cockpit: "Ngồi ghế sau hoặc mở cửa, chụp toàn cảnh táp-lô và vô lăng.",

      driver_seat: "Chụp rõ mặt ghế lái, thấy được độ mòn của da/nỉ.",

      passenger_seat: "Chụp rõ mặt ghế hành khách phía trước.",

      rear_seat_headliner: "Chụp hàng ghế sau và trần xe phía trên.",

      engine_bay: "Mở nắp ca-pô, chụp rõ toàn bộ khoang động cơ.",

      wheel_front_left: "Chụp thẳng bánh trước bên trái, thấy rõ mâm và lốp.",

      wheel_front_right: "Chụp thẳng bánh trước bên phải, thấy rõ mâm và lốp.",

      wheel_rear_left: "Chụp thẳng bánh sau bên trái, thấy rõ mâm và lốp.",

      wheel_rear_right: "Chụp thẳng bánh sau bên phải, thấy rõ mâm và lốp.",
    };

    return hints[slot] || "Chụp rõ chi tiết theo tên ô ảnh.";
  }

  sessionStorage.removeItem("valuation_request_id");

  updateLocalProgress();

  loadBrands();
})();
