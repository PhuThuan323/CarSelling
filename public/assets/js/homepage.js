(function ($) {
    'use strict';

    var API = {
        brands: '/api/v1/brands',
        modelsByBrand: function (brandId) {
            return '/api/v1/brands/' + brandId + '/models';
        }
    };

    var demoVehicles = [
        {id:1,brand:'Toyota',name:'Toyota Camry 2.5Q 2024',meta:'12.000 km • Tự động • Xăng',price:1235000000,tags:['Sedan','2024','5 chỗ'],image:'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=900&q=82'},
        {id:2,brand:'Mercedes-Benz',name:'Mercedes-Benz C200 2023',meta:'18.500 km • Tự động • Xăng',price:1489000000,tags:['Sedan','2023','5 chỗ'],image:'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?auto=format&fit=crop&w=900&q=82'},
        {id:3,brand:'Mazda',name:'Mazda CX-5 Premium 2024',meta:'6.800 km • Tự động • Xăng',price:879000000,tags:['SUV','2024','5 chỗ'],image:'https://images.unsplash.com/photo-1551830820-330a71b99659?auto=format&fit=crop&w=900&q=82'},
        {id:4,brand:'Honda',name:'Honda Accord 2022',meta:'28.000 km • Tự động • Xăng',price:995000000,tags:['Sedan','2022','5 chỗ'],image:'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=900&q=82'}
    ];

    var demoReviews = [
        {name:'Anh Nam',title:'Đã chọn chiếc xe phù hợp trong ngày',text:'Thông tin xe rõ ràng, tư vấn dễ hiểu và quy trình xem xe thuận tiện.',image:'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=700&q=80'},
        {name:'Anh Minh',title:'Xe thực tế đúng với thông tin đăng',text:'Mình yên tâm hơn vì được kiểm tra xe trực tiếp và xem đầy đủ thông tin trước khi quyết định.',image:'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=700&q=80'},
        {name:'Anh Tuấn',title:'Giá bán rõ ràng, tư vấn nhanh',text:'Quá trình trao đổi nhanh gọn, các thông tin cần thiết được trình bày khá minh bạch.',image:'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=700&q=80'},
        {name:'Chị Hằng',title:'Trải nghiệm mua xe thuận tiện',text:'Từ lúc xem thông tin đến khi đặt lịch xem xe đều dễ thao tác và tiết kiệm thời gian.',image:'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=700&q=80'}
    ];

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
    }

    function brandLogo(brand) {
        var logoUrl = brand.logo || brand.logo_url || '';
        if (logoUrl) {
            return '<img src="' + escapeHtml(logoUrl) + '" alt="' + escapeHtml(brand.name) + '">';
        }
        return escapeHtml((brand.name || '?').trim().charAt(0).toUpperCase());
    }

    function renderBrands(brands) {
        var $picker = $('#brandPicker');
        $picker.empty();

        if (!brands.length) {
            $picker.html('<div class="brand-loading">Chưa có hãng xe.</div>');
            $('#brandCountText').text('0 hãng xe');
            return;
        }

        brands.slice(0, 10).forEach(function (brand, index) {
            var $button = $('<button type="button" class="brand-chip">');
            $button.attr('data-brand-id', brand.id);
            if (index === 0) $button.addClass('is-active');

            $button.html(
                '<span class="brand-chip-logo">' + brandLogo(brand) + '</span>' +
                '<span class="brand-chip-name">' + escapeHtml(brand.name) + '</span>'
            );

            $button.on('click', function () {
                $('.brand-chip').removeClass('is-active');
                $(this).addClass('is-active');
                loadModels(brand.id);
            });

            $picker.append($button);
        });

        $('#brandCountText').text('Xem tất cả ' + brands.length + ' hãng xe');
        loadModels(brands[0].id);
    }

    function loadBrands() {
        $.getJSON(API.brands)
            .done(function (response) {
                var brands = response && response.data && Array.isArray(response.data.brands)
                    ? response.data.brands
                    : [];
                renderBrands(brands);
            })
            .fail(function () {
                $('#brandPicker').html('<div class="brand-loading">Không tải được danh sách hãng xe.</div>');
                $('#brandCountText').text('Vui lòng thử lại sau');
            });
    }

    function loadModels(brandId) {
        var $list = $('#modelQuickList');
        $list.html('<span class="model-quick-item">Đang tải dòng xe...</span>');

        $.getJSON(API.modelsByBrand(brandId))
            .done(function (response) {
                var models = response && response.data && Array.isArray(response.data.models)
                    ? response.data.models
                    : [];

                $list.empty();
                models.slice(0, 7).forEach(function (model) {
                    $list.append($('<span>').addClass('model-quick-item').text(model.name));
                });

                if (!models.length) {
                    $list.html('<span class="model-quick-item">Chưa có dòng xe</span>');
                }
            })
            .fail(function () {
                $list.empty();
            });
    }

    function vehicleCard(vehicle) {
        var tags = vehicle.tags.map(function (tag) {
            return '<span class="vehicle-tag">' + escapeHtml(tag) + '</span>';
        }).join('');

        return '<article class="vehicle-card">' +
            '<div class="vehicle-image-wrap">' +
                '<img class="vehicle-image" src="' + escapeHtml(vehicle.image) + '" alt="' + escapeHtml(vehicle.name) + '">' +
                '<button type="button" class="vehicle-favorite" aria-label="Yêu thích">♡</button>' +
            '</div>' +
            '<div class="vehicle-body">' +
                '<h3 class="vehicle-name">' + escapeHtml(vehicle.name) + '</h3>' +
                '<div class="vehicle-meta">' + escapeHtml(vehicle.meta) + '</div>' +
                '<div class="vehicle-price">' + formatMoney(vehicle.price) + '</div>' +
                '<div class="vehicle-tags">' + tags + '</div>' +
            '</div>' +
            '<div class="vehicle-actions">' +
                '<button class="vehicle-action" type="button">Cửa hàng</button>' +
                '<button class="vehicle-action" type="button">Đặt lịch lái thử</button>' +
            '</div>' +
        '</article>';
    }

    function reviewCard(review) {
        return '<article class="review-card">' +
            '<img class="review-image" src="' + escapeHtml(review.image) + '" alt="' + escapeHtml(review.name) + '">' +
            '<div class="review-body">' +
                '<span class="review-badge">' + escapeHtml(review.name) + '</span>' +
                '<h3 class="review-title">' + escapeHtml(review.title) + '</h3>' +
                '<p class="review-text">' + escapeHtml(review.text) + '</p>' +
                '<a href="#" class="review-link">Đọc câu chuyện đầy đủ →</a>' +
            '</div>' +
        '</article>';
    }

    $(function () {
        loadBrands();
        $('#featuredVehicleGrid').html(demoVehicles.map(vehicleCard).join(''));
        $('#reviewGrid').html(demoReviews.map(reviewCard).join(''));
    });

})(jQuery);
