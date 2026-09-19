(function ($) {
    'use strict';

    var cropper = null;
    var objectUrl = null;
    var lastChosenFileName = 'brand-logo.webp';

    var MAX_FILE_SIZE = 5 * 1024 * 1024;
    var ALLOWED_TYPES = ['image/jpeg','image/png','image/webp'];

    var $file = $('#brandLogoFile');
    var $dropzone = $('#brandLogoDropzone');
    var $empty = $('#brandLogoEmpty');
    var $result = $('#brandLogoResult');
    var $preview = $('#logoPreview');
    var $logo = $('#brandLogo');
    var $publicId = $('#brandLogoPublicId');
    var $remove = $('#brandLogoRemove');

    var $cropModal = $('#logoCropModal');
    var cropImage = document.getElementById('logoCropImage');
    var $uploadProgress = $('#logoUploadProgress');
    var $uploadButton = $('#logoCropUpload');

    function showToast(message, type) {
        if (typeof window.toast === 'function') {
            window.toast(message, type || 'success');
            return;
        }

        var $toast = $('#adminToast');

        if ($toast.length) {
            $toast
                .removeClass('success error')
                .addClass(type || 'success')
                .text(message)
                .addClass('show');

            clearTimeout(window.__brandLogoToast);

            window.__brandLogoToast = setTimeout(function () {
                $toast.removeClass('show');
            }, 2800);
            return;
        }

        if (type === 'error') {
            window.alert(message);
        }
    }

    function validateFile(file) {
        if (!file) return 'Không tìm thấy file ảnh.';
        if (ALLOWED_TYPES.indexOf(file.type) === -1) {
            return 'Chỉ hỗ trợ PNG, JPG và WebP.';
        }
        if (file.size > MAX_FILE_SIZE) {
            return 'Logo không được lớn hơn 5 MB.';
        }
        return null;
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        cropImage.removeAttribute('src');
    }

    function closeCropModal() {
        destroyCropper();
        $cropModal.prop('hidden', true);
        $uploadProgress.text('');
        $uploadButton.prop('disabled', false);
    }

    function openCropper(file) {
        var error = validateFile(file);

        if (error) {
            showToast(error, 'error');
            return;
        }

        if (typeof Cropper === 'undefined') {
            showToast(
                'CropperJS chưa được tải. Vui lòng kiểm tra thư viện CropperJS.',
                'error'
            );
            return;
        }

        lastChosenFileName =
            (file.name || 'brand-logo')
                .replace(/\.[^.]+$/, '') +
            '.webp';

        // Hủy cropper cũ nếu có
        destroyCropper();

        // Tạo URL ảnh
        objectUrl = URL.createObjectURL(file);

        // QUAN TRỌNG:
        // Đăng ký onload TRƯỚC khi set src
        cropImage.onload = function () {
            try {
                cropper = new Cropper(cropImage, {
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.88,

                    responsive: true,
                    restore: false,

                    guides: true,
                    center: true,
                    highlight: false,
                    background: false,

                    movable: true,
                    rotatable: true,
                    scalable: false,

                    zoomable: true,
                    zoomOnTouch: true,
                    zoomOnWheel: true,

                    cropBoxMovable: true,
                    cropBoxResizable: true,

                    toggleDragModeOnDblclick: false
                });

                console.log('Cropper initialized:', cropper);

                $uploadProgress.text('');

            } catch (e) {
                console.error(
                    'Cropper initialization error:',
                    e
                );

                cropper = null;

                showToast(
                    'Không thể khởi tạo công cụ căn chỉnh ảnh.',
                    'error'
                );
            }
        };

        cropImage.onerror = function () {
            console.error('Không thể load ảnh:', file);

            cropper = null;

            showToast(
                'Không thể đọc ảnh đã chọn.',
                'error'
            );
        };

        // Set src SAU khi đã có onload
        cropImage.src = objectUrl;

        // Hiện modal
        $cropModal.prop('hidden', false);
    }

    function setUploadedLogo(url, publicId) {
        $logo.val(url || '');
        $publicId.val(publicId || '');
        $remove.val('0');

        if (url) {
            $preview.attr('src', url);
            $empty.prop('hidden', true);
            $result.prop('hidden', false);
        } else {
            $preview.attr('src', '');
            $empty.prop('hidden', false);
            $result.prop('hidden', true);
        }
    }

    function clearLogo() {
        setUploadedLogo('', '');
        $remove.val('1');
        $file.val('');
    }

    function uploadCroppedLogo() {
    console.log('uploadCroppedLogo called');
    console.log('cropper:', cropper);

    if (!cropper) {
        showToast(
            'Ảnh chưa sẵn sàng để căn chỉnh.',
            'error'
        );
        return;
    }

    var canvas;

    try {
        canvas = cropper.getCroppedCanvas({
            maxWidth: 1200,
            maxHeight: 1200,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
    } catch (e) {
        console.error(
            'getCroppedCanvas error:',
            e
        );

        showToast(
            'Không thể xử lý ảnh đã cắt.',
            'error'
        );

        return;
    }

    if (!canvas) {
        showToast(
            'Không thể tạo ảnh sau khi cắt.',
            'error'
        );
        return;
    }

    $uploadButton.prop('disabled', true);
    $uploadProgress.text('Đang xử lý ảnh...');

    canvas.toBlob(function (blob) {

        if (!blob) {
            $uploadButton.prop('disabled', false);
            $uploadProgress.text('');

            showToast(
                'Không thể xuất ảnh đã căn chỉnh.',
                'error'
            );

            return;
        }

        console.log(
            'Cropped blob:',
            blob.size,
            blob.type
        );

        var form = new FormData();

        form.append(
            'logo_file',
            blob,
            lastChosenFileName
        );

        $uploadProgress.text(
            'Đang tải logo lên Cloudinary...'
        );

        $.ajax({
            url: '/api/v1/admin/uploads/brand-logo',
            method: 'POST',
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json'
        })

        .done(function (response) {

            console.log(
                'Cloudinary response:',
                response
            );

            var upload =
                response &&
                response.data &&
                response.data.upload
                    ? response.data.upload
                    : null;

            if (!upload || !upload.secure_url) {

                showToast(
                    'Cloudinary không trả về URL ảnh.',
                    'error'
                );

                return;
            }

            setUploadedLogo(
                upload.secure_url,
                upload.public_id || ''
            );

            $('#brandLogoStatus').text(
                (upload.width || '?') +
                ' × ' +
                (upload.height || '?') +
                ' px · Cloudinary'
            );

            closeCropModal();

            showToast(
                'Đã tải logo lên Cloudinary.',
                'success'
            );
        })

        .fail(function (xhr) {

            console.error(
                'Upload failed:',
                xhr
            );

            console.error(
                'Response:',
                xhr.responseText
            );

            var message =
                'Không thể tải logo lên Cloudinary.';

            if (
                xhr.responseJSON &&
                xhr.responseJSON.message
            ) {
                message =
                    xhr.responseJSON.message;
            }

            showToast(
                message,
                'error'
            );
        })

        .always(function () {

            $uploadButton.prop(
                'disabled',
                false
            );

            $uploadProgress.text('');
        });

    }, 'image/webp', 0.92);
}

    // Mở khu vực upload (dropzone) — mặc định đang ẩn trong form.
    function openDropzone() {
        $dropzone.removeClass('is-hidden');
        $('#brandLogoOpen').addClass('is-hidden');
    }

    function closeDroparea() {
        $dropzone.addClass('is-hidden');
        $('#brandLogoOpen').removeClass('is-hidden');
    }

    $(document).on('click','#brandLogoOpen',
        function (event) {
            event.preventDefault();
            openDropzone();
            $('#brandLogoFile').trigger('click');
        }
    );


    $('#brandLogoChoose, #brandLogoChange').on('click', function () {
    openDropzone();

    if ($file.length) {
        $file[0].click();
    }
});

    $(document).on('change','#brandLogoFile',
        function () {
            var file =this.files && this.files.length ? this.files[0] : null;
            if (file) {
                openCropper(file);
            }
            // Cho phép chọn lại đúng file cũ
            this.value = '';
        }
    );

    $dropzone.on('dragenter dragover', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $dropzone.addClass('is-dragging');
    });

    $dropzone.on('dragleave drop', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $dropzone.removeClass('is-dragging');
    });

    $dropzone.on('drop', function (event) {
        var transfer = event.originalEvent.dataTransfer;

        if (transfer && transfer.files && transfer.files.length) {
            openCropper(transfer.files[0]);
        }
    });

    $('#brandLogoClear').on('click', clearLogo);
    $('#logoCropClose, #logoCropCancel').on('click', closeCropModal);

    $cropModal.on('click', function (event) {
        if (event.target === $cropModal[0]) {
            closeCropModal();
        }
    });

    $('[data-crop-ratio]').on('click', function () {
        if (!cropper) return;

        $('[data-crop-ratio]').removeClass('is-active');
        $(this).addClass('is-active');

        var value = $(this).data('crop-ratio');

        cropper.setAspectRatio(
            value === 'free' ? NaN : Number(value)
        );
    });

    $('[data-crop-action]').on('click', function () {
        if (!cropper) return;

        var action = $(this).data('crop-action');

        if (action === 'zoom-in') cropper.zoom(0.1);
        if (action === 'zoom-out') cropper.zoom(-0.1);
        if (action === 'rotate-left') cropper.rotate(-90);
        if (action === 'rotate-right') cropper.rotate(90);
        if (action === 'reset') cropper.reset();
    });

    $uploadButton.on('click', uploadCroppedLogo);

    window.BrandLogoUploader = {
        setExisting: function (url, publicId) {
            setUploadedLogo(url || '', publicId || '');
            $remove.val('0');
            openDropzone();
        },

        clear: clearLogo,

        reset: function () {
            setUploadedLogo('', '');
            $remove.val('0');
            closeDroparea();
        }
    };

})(jQuery);
