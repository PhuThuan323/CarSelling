(function () {
    'use strict';

    var API = {
        brands: '/api/v1/admin/brands',
        models: '/api/v1/admin/models',
        versions: '/api/v1/admin/versions',
        brand: function (id) { return API.brands + '/' + id; },
        model: function (id) { return API.models + '/' + id; },
        version: function (id) { return API.versions + '/' + id; }
    };

    var state = { brands: [], models: [], versions: [] };
    var currentSection = 'brands';
    var currentForm = 'brand';

    function csrfToken() {
        return (window.ADMIN_CONFIG && window.ADMIN_CONFIG.csrfToken) || '';
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function notify(message, type) {
        var box = document.getElementById('adminAlert');
        box.textContent = message;
        box.className = 'admin-alert ' + (type === 'error' ? 'is-error' : 'is-success');
        box.hidden = false;
        window.clearTimeout(notify._timer);
        notify._timer = window.setTimeout(function () { box.hidden = true; }, 4200);
    }

    function request(method, url, payload) {
        var options = {
            method: method,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken()
            }
        };

        if (payload) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(payload);
        }

        return fetch(url, options).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) {
                if (response.status === 401) {
                    window.location.href = '/auth/login';
                    throw new Error('Phiên đăng nhập đã hết hạn.');
                }
                if (!response.ok) {
                    throw new Error(body.message || ('Lỗi ' + response.status));
                }
                return body;
            });
        });
    }

    function statusPill(status) {
        var active = status === 'active';
        return '<span class="admin-pill ' + (active ? 'admin-pill-active' : 'admin-pill-inactive') + '">'
            + (active ? 'active' : 'inactive') + '</span>';
    }

    function emptyRow(colspan, text) {
        return '<tr><td colspan="' + colspan + '" class="admin-empty">' + escapeHtml(text) + '</td></tr>';
    }

    /* ===================== BRANDS ===================== */
    function renderBrands() {
        var keyword = document.getElementById('brandSearch').value.trim().toLowerCase();
        var status = document.getElementById('brandStatus').value;
        var rows = state.brands.filter(function (brand) {
            var matchedKeyword = !keyword || String(brand.name || '').toLowerCase().indexOf(keyword) !== -1;
            var matchedStatus = !status || brand.status === status;
            return matchedKeyword && matchedStatus;
        });

        var tbody = document.getElementById('brandTableBody');
        if (!rows.length) {
            tbody.innerHTML = emptyRow(7, 'Chưa có hãng xe nào phù hợp.');
            return;
        }

        tbody.innerHTML = rows.map(function (brand) {
            var logo = brand.logo || brand.logo_url;
            return '<tr>'
                + '<td>' + escapeHtml(brand.id) + '</td>'
                + '<td>' + (logo
                    ? '<img class="admin-thumb" src="' + escapeHtml(logo) + '" alt="' + escapeHtml(brand.name) + '">'
                    : '<span class="admin-pill admin-pill-inactive">no logo</span>') + '</td>'
                + '<td><strong>' + escapeHtml(brand.name) + '</strong></td>'
                + '<td>' + escapeHtml(brand.slug) + '</td>'
                + '<td>' + escapeHtml(brand.country || '—') + '</td>'
                + '<td>' + statusPill(brand.status) + '</td>'
                + '<td class="admin-cell-actions">'
                + '<button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-edit-brand="' + escapeHtml(brand.id) + '">Sửa</button>'
                + '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-delete-brand="' + escapeHtml(brand.id) + '">Xóa</button>'
                + '</td></tr>';
        }).join('');
    }

    function loadBrands() {
        return request('GET', API.brands).then(function (body) {
            state.brands = (body.data && body.data.brands) || [];
            fillBrandSelects();
            renderBrands();
        });
    }

    /* ===================== MODELS ===================== */
    function renderModels() {
        var brandId = document.getElementById('modelBrandFilter').value;
        var status = document.getElementById('modelStatus').value;
        var rows = state.models.filter(function (model) {
            return (!brandId || String(model.brand_id) === brandId) && (!status || model.status === status);
        });

        var tbody = document.getElementById('modelTableBody');
        if (!rows.length) {
            tbody.innerHTML = emptyRow(7, 'Chưa có dòng xe nào phù hợp.');
            return;
        }

        tbody.innerHTML = rows.map(function (model) {
            return '<tr>'
                + '<td>' + escapeHtml(model.id) + '</td>'
                + '<td>' + escapeHtml(model.brand_name || '—') + '</td>'
                + '<td><strong>' + escapeHtml(model.name) + '</strong></td>'
                + '<td>' + escapeHtml(model.slug) + '</td>'
                + '<td>' + escapeHtml(model.body_type || '—') + '</td>'
                + '<td>' + statusPill(model.status) + '</td>'
                + '<td class="admin-cell-actions">'
                + '<button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-edit-model="' + escapeHtml(model.id) + '">Sửa</button>'
                + '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-delete-model="' + escapeHtml(model.id) + '">Xóa</button>'
                + '</td></tr>';
        }).join('');
    }

    function loadModels() {
        return request('GET', API.models).then(function (body) {
            state.models = (body.data && body.data.models) || [];
            fillModelSelects();
            renderModels();
        });
    }

    /* ===================== VERSIONS ===================== */
    function renderVersions() {
        var modelId = document.getElementById('versionModelFilter').value;
        var status = document.getElementById('versionStatus').value;
        var rows = state.versions.filter(function (version) {
            return (!modelId || String(version.model_id) === modelId) && (!status || version.status === status);
        });

        var tbody = document.getElementById('versionTableBody');
        if (!rows.length) {
            tbody.innerHTML = emptyRow(8, 'Chưa có phiên bản nào phù hợp.');
            return;
        }

        tbody.innerHTML = rows.map(function (version) {
            var years = [version.production_year_from, version.production_year_to].filter(Boolean).join(' - ');
            var engine = version.engine_displacement_cc
                ? (version.engine_displacement_cc + ' cc')
                : (version.fuel_type || '—');
            return '<tr>'
                + '<td>' + escapeHtml(version.id) + '</td>'
                + '<td>' + escapeHtml((version.brand_name || '') + ' / ' + (version.model_name || '')) + '</td>'
                + '<td><strong>' + escapeHtml(version.name) + '</strong></td>'
                + '<td>' + escapeHtml(years || '—') + '</td>'
                + '<td>' + escapeHtml(engine) + '</td>'
                + '<td>' + escapeHtml(version.transmission || '—') + '</td>'
                + '<td>' + statusPill(version.status) + '</td>'
                + '<td class="admin-cell-actions">'
                + '<button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-edit-version="' + escapeHtml(version.id) + '">Sửa</button>'
                + '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-delete-version="' + escapeHtml(version.id) + '">Xóa</button>'
                + '</td></tr>';
        }).join('');
    }

    function loadVersions() {
        return request('GET', API.versions).then(function (body) {
            state.versions = (body.data && body.data.versions) || [];
            renderVersions();
        });
    }

    /* ===================== SELECTS ===================== */
    function options(items, valueKey, labelBuilder, keepPlaceholder, placeholder) {
        var html = keepPlaceholder ? '<option value="">' + escapeHtml(placeholder || 'Tất cả') + '</option>' : '';
        return html + items.map(function (item) {
            return '<option value="' + escapeHtml(item[valueKey]) + '">' + escapeHtml(labelBuilder(item)) + '</option>';
        }).join('');
    }

    function brandLabel(brand) {
        return brand.name + (brand.status === 'active' ? '' : ' (inactive)');
    }

    function modelLabel(model) {
        return (model.brand_name ? model.brand_name + ' - ' : '') + model.name + (model.status === 'active' ? '' : ' (inactive)');
    }

    function keepValue(select, html) {
        var previous = select.value;
        select.innerHTML = html;
        if (previous) select.value = previous;
    }

    function fillBrandSelects() {
        var active = state.brands.filter(function (b) { return b.status === 'active'; });
        var filter = document.getElementById('modelBrandFilter');
        keepValue(filter, '<option value="">Tất cả hãng</option>'
            + options(state.brands, 'id', brandLabel, false));

        var form = document.getElementById('modelBrandId');
        var previous = form.value;
        form.innerHTML = '<option value="">-- Chọn hãng xe --</option>' + options(active, 'id', brandLabel, false);
        if (previous) form.value = previous;
    }

    function fillModelSelects() {
        var active = state.models.filter(function (m) { return m.status === 'active'; });
        var filter = document.getElementById('versionModelFilter');
        keepValue(filter, '<option value="">Tất cả dòng xe</option>'
            + options(state.models, 'id', modelLabel, false));

        var form = document.getElementById('versionModelId');
        var previous = form.value;
        form.innerHTML = '<option value="">-- Chọn dòng xe --</option>' + options(active, 'id', modelLabel, false);
        if (previous) form.value = previous;
    }

    /* ===================== MODAL ===================== */
    function openModal(formName, title) {
        currentForm = formName;
        document.getElementById('modalTitle').textContent = title;
        ['brandForm', 'modelForm', 'versionForm'].forEach(function (id) {
            document.getElementById(id).hidden = (id !== formName + 'Form');
        });
        document.getElementById('adminModal').hidden = false;
    }

    function closeModal() {
        document.getElementById('adminModal').hidden = true;
        ['brandForm', 'modelForm', 'versionForm'].forEach(function (id) {
            document.getElementById(id).reset();
            document.getElementById(id).querySelector('input[name="id"]').value = '';
        });
        // Thu gọn lại khu vực upload logo để lần mở sau bắt đầu ở trạng thái gọn.
        if (window.BrandLogoUploader) {
            window.BrandLogoUploader.reset();
        }
    }

    function numberOrNull(form, name) {
        var value = form.elements[name].value.trim();
        if (value === '') return null;
        var parsed = Number(value);
        return isNaN(parsed) ? null : parsed;
    }

    function textOrNull(form, name) {
        var value = form.elements[name].value.trim();
        return value === '' ? null : value;
    }

    function brandPayload(form) {
        return {
            name: form.elements['name'].value.trim(),
            slug: form.elements['slug'].value.trim(),
            country: textOrNull(form, 'country'),
            logo: textOrNull(form, 'logo'),
            description: textOrNull(form, 'description'),
            status: form.elements['status'].value
        };
    }

    function modelPayload(form) {
        return {
            brand_id: numberOrNull(form, 'brand_id'),
            name: form.elements['name'].value.trim(),
            slug: form.elements['slug'].value.trim(),
            body_type: textOrNull(form, 'body_type'),
            description: textOrNull(form, 'description'),
            status: form.elements['status'].value
        };
    }

    function versionPayload(form) {
        return {
            model_id: numberOrNull(form, 'model_id'),
            name: form.elements['name'].value.trim(),
            slug: form.elements['slug'].value.trim(),
            status: form.elements['status'].value,
            production_year_from: numberOrNull(form, 'production_year_from'),
            production_year_to: numberOrNull(form, 'production_year_to'),
            engine_name: textOrNull(form, 'engine_name'),
            engine_displacement_cc: numberOrNull(form, 'engine_displacement_cc'),
            fuel_type: textOrNull(form, 'fuel_type'),
            transmission: textOrNull(form, 'transmission'),
            drivetrain: textOrNull(form, 'drivetrain'),
            horsepower: numberOrNull(form, 'horsepower'),
            seats: numberOrNull(form, 'seats'),
            description: textOrNull(form, 'description')
        };
    }

    function save() {
        var form = document.getElementById(currentForm + 'Form');
        var id = form.querySelector('input[name="id"]').value;
        var payload;
        var method;
        var url;

        if (currentForm === 'brand') {
            payload = brandPayload(form);
            if (!payload.name) { notify('Vui lòng nhập tên hãng xe.', 'error'); return; }
            url = id ? API.brand(id) : API.brands;
        } else if (currentForm === 'model') {
            payload = modelPayload(form);
            if (!payload.name || !payload.brand_id) { notify('Vui lòng chọn hãng xe và nhập tên dòng xe.', 'error'); return; }
            url = id ? API.model(id) : API.models;
        } else {
            payload = versionPayload(form);
            if (!payload.name || !payload.model_id) { notify('Vui lòng chọn dòng xe và nhập tên phiên bản.', 'error'); return; }
            url = id ? API.version(id) : API.versions;
        }

        method = id ? 'PUT' : 'POST';

        request(method, url, payload)
            .then(function () {
                notify('Lưu thành công. Khách hàng sẽ thấy thay đổi ngay trên trang chủ.', 'success');
                closeModal();
                return reloadAll();
            })
            .catch(function (error) {
                notify(error.message, 'error');
            });
    }

    function removeOne(kind, id) {
        if (!window.confirm('Bạn chắc chắn muốn xóa bản ghi này?')) return;

        var url = kind === 'brand' ? API.brand(id) : (kind === 'model' ? API.model(id) : API.version(id));

        request('DELETE', url, null)
            .then(function () {
                notify('Đã xóa thành công.', 'success');
                return reloadAll();
            })
            .catch(function (error) {
                notify(error.message, 'error');
            });
    }

    function fillForm(kind, record) {
        var form = document.getElementById(kind + 'Form');
        form.reset();
        form.querySelector('input[name="id"]').value = record.id;

        Object.keys(record).forEach(function (key) {
            var field = form.elements[key];
            if (!field) return;
            field.value = record[key] == null ? '' : record[key];
        });

        if (kind === 'brand') {
            form.elements['logo'].value = record.logo || record.logo_url || '';
        }
        if (kind === 'model') {
            fillBrandSelects();
            form.elements['brand_id'].value = record.brand_id;
        }
        if (kind === 'version') {
            fillModelSelects();
            form.elements['model_id'].value = record.model_id;
        }
    }

    function findById(collection, id) {
        var found = null;
        collection.forEach(function (item) {
            if (String(item.id) === String(id)) found = item;
        });
        return found;
    }

    function reloadAll() {
        return Promise.all([loadBrands(), loadModels(), loadVersions()]);
    }

    /* ===================== EVENTS ===================== */
    function switchSection(section) {
        currentSection = section;
        ['brands', 'models', 'versions'].forEach(function (name) {
            document.getElementById('panel-' + name).classList.toggle('is-hidden', name !== section);
            document.querySelector('.admin-nav-item[data-section="' + name + '"]').classList.toggle('is-active', name === section);
        });
    }

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-section],[data-action],[data-edit-brand],[data-delete-brand],[data-edit-model],[data-delete-model],[data-edit-version],[data-delete-version]');
        if (!target) return;

        if (target.dataset.section) return switchSection(target.dataset.section);

        if (target.dataset.action === 'create-brand') return openModal('brand', 'Thêm hãng xe');
        if (target.dataset.action === 'create-model') return openModal('model', 'Thêm dòng xe');
        if (target.dataset.action === 'create-version') return openModal('version', 'Thêm phiên bản xe');

        var brandId = target.dataset.editBrand || target.dataset.deleteBrand;
        if (brandId) {
            if (target.dataset.deleteBrand) return removeOne('brand', brandId);
            var brand = findById(state.brands, brandId);
            if (brand) openModal('brand', 'Sửa hãng xe #' + brand.id);
            return fillForm('brand', brand || {});
        }

        var modelId = target.dataset.editModel || target.dataset.deleteModel;
        if (modelId) {
            if (target.dataset.deleteModel) return removeOne('model', modelId);
            var model = findById(state.models, modelId);
            if (model) openModal('model', 'Sửa dòng xe #' + model.id);
            return fillForm('model', model || {});
        }

        var versionId = target.dataset.editVersion || target.dataset.deleteVersion;
        if (versionId) {
            if (target.dataset.deleteVersion) return removeOne('version', versionId);
            var version = findById(state.versions, versionId);
            if (version) openModal('version', 'Sửa phiên bản xe #' + version.id);
            return fillForm('version', version || {});
        }
    });

    document.getElementById('modalSubmit').addEventListener('click', save);
    document.getElementById('modalCancel').addEventListener('click', closeModal);
    document.getElementById('modalClose').addEventListener('click', closeModal);
    document.getElementById('adminModal').addEventListener('click', function (event) {
        if (event.target === this) closeModal();
    });

    document.getElementById('brandSearch').addEventListener('input', renderBrands);
    document.getElementById('brandStatus').addEventListener('change', renderBrands);
    document.getElementById('modelBrandFilter').addEventListener('change', renderModels);
    document.getElementById('modelStatus').addEventListener('change', renderModels);
    document.getElementById('versionModelFilter').addEventListener('change', renderVersions);
    document.getElementById('versionStatus').addEventListener('change', renderVersions);

    switchSection(currentSection);
    reloadAll().catch(function (error) {
        notify(error.message, 'error');
    });
})();
