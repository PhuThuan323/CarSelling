<?php

declare(strict_types=1);

namespace App\Controllers\Valuation;

use App\Core\JsonResponse;
use App\Models\Inspection\ValuationRequest;
use App\Models\Inspection\ValuationRequestImage;
use App\Service\CloudinaryService;
use Throwable;

class ValuationController
{
    private ValuationRequest $requests;

    private ValuationRequestImage $images;

    private CloudinaryService $cloudinary;



    private const REQUIRED_SLOTS = [

        // Ngoại thất - 7

        'front_left_45' => [
            'category' => 'exterior',
            'label' => 'Góc trước trái 45°',
            'sort_order' => 10,
        ],

        'front_right_45' => [
            'category' => 'exterior',
            'label' => 'Góc trước phải 45°',
            'sort_order' => 20,
        ],

        'rear_left_45' => [
            'category' => 'exterior',
            'label' => 'Góc sau trái 45°',
            'sort_order' => 30,
        ],

        'rear_right_45' => [
            'category' => 'exterior',
            'label' => 'Góc sau phải 45°',
            'sort_order' => 40,
        ],

        'front' => [
            'category' => 'exterior',
            'label' => 'Đầu xe',
            'sort_order' => 50,
        ],

        'rear' => [
            'category' => 'exterior',
            'label' => 'Đuôi xe',
            'sort_order' => 60,
        ],

        'roof' => [
            'category' => 'exterior',
            'label' => 'Nóc xe',
            'sort_order' => 70,
        ],


        // Nội thất - 5

        'odometer' => [
            'category' => 'interior',
            'label' => 'Bảng đồng hồ ODO',
            'sort_order' => 110,
        ],

        'cockpit' => [
            'category' => 'interior',
            'label' => 'Toàn cảnh khoang lái',
            'sort_order' => 120,
        ],

        'driver_seat' => [
            'category' => 'interior',
            'label' => 'Ghế lái',
            'sort_order' => 130,
        ],

        'passenger_seat' => [
            'category' => 'interior',
            'label' => 'Ghế hành khách',
            'sort_order' => 140,
        ],

        'rear_seat_headliner' => [
            'category' => 'interior',
            'label' => 'Hàng ghế sau và trần',
            'sort_order' => 150,
        ],


        // Máy + bánh xe - 5

        'engine_bay' => [
            'category' => 'mechanical',
            'label' => 'Khoang động cơ',
            'sort_order' => 210,
        ],

        'wheel_front_left' => [
            'category' => 'mechanical',
            'label' => 'Bánh trước trái',
            'sort_order' => 220,
        ],

        'wheel_front_right' => [
            'category' => 'mechanical',
            'label' => 'Bánh trước phải',
            'sort_order' => 230,
        ],

        'wheel_rear_left' => [
            'category' => 'mechanical',
            'label' => 'Bánh sau trái',
            'sort_order' => 240,
        ],

        'wheel_rear_right' => [
            'category' => 'mechanical',
            'label' => 'Bánh sau phải',
            'sort_order' => 250,
        ],


    ];


    public function __construct()
    {
        $this->requests =
            new ValuationRequest();

        $this->images =
            new ValuationRequestImage();

        $this->cloudinary =
            new CloudinaryService();
    }


    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */

    private function requireUser(): int
    {
        $userId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        if ($userId <= 0) {
            JsonResponse::error(
                'Vui lòng đăng nhập.',
                401
            );
        }

        return $userId;
    }


    private function requestData(): array
    {
        $contentType =
            $_SERVER['CONTENT_TYPE']
            ?? '';

        if (
            str_contains(
                $contentType,
                'application/json'
            )
        ) {
            $raw =
                file_get_contents(
                    'php://input'
                );

            $data =
                json_decode(
                    $raw ?: '',
                    true
                );

            return is_array($data)
                ? $data
                : [];
        }

        return $_POST;
    }


    private function editable(
        array $request
    ): bool {
        return in_array(
            $request['status'],
            [
                'draft',
                'photos_pending',
            ],
            true
        );
    }

    public function createDraft(): void
    {
        $userId =
            $this->requireUser();

        $data =
            $this->requestData();


        $versionId =
            (int) (
                $data['vehicle_version_id']
                ?? 0
            );

        $year =
            (int) (
                $data['manufacture_year']
                ?? 0
            );

        $odometer =
            isset($data['odometer_km'])
                && $data['odometer_km'] !== ''
                    ? (int) $data['odometer_km']
                    : null;


        if ($versionId <= 0) {
            JsonResponse::error(
                'Vui lòng chọn phiên bản xe.',
                422
            );
        }


        $currentYear =
            (int) date('Y');


        if (
            $year < 1950
            || $year > $currentYear + 1
        ) {
            JsonResponse::error(
                'Đời xe không hợp lệ.',
                422
            );
        }


        if (
            $odometer !== null
            && $odometer < 0
        ) {
            JsonResponse::error(
                'Số km đã đi không hợp lệ.',
                422
            );
        }


        $version =
            $this->requests
                ->findVersion(
                    $versionId
                );


        if (!$version) {
            JsonResponse::error(
                'Phiên bản xe không tồn tại hoặc đã ngừng hoạt động.',
                422
            );
        }


        $from =
            $version[
                'production_year_from'
            ] !== null
                ? (int)
                    $version[
                        'production_year_from'
                    ]
                : null;


        $to =
            $version[
                'production_year_to'
            ] !== null
                ? (int)
                    $version[
                        'production_year_to'
                    ]
                : null;


        if (
            $from !== null
            && $year < $from
        ) {
            JsonResponse::error(
                "Phiên bản này bắt đầu từ đời {$from}.",
                422
            );
        }


        if (
            $to !== null
            && $year > $to
        ) {
            JsonResponse::error(
                "Phiên bản này chỉ hỗ trợ đến đời {$to}.",
                422
            );
        }


        $referenceCode =
            'VAL-'
            . date('Ymd')
            . '-'
            . strtoupper(
                bin2hex(
                    random_bytes(4)
                )
            );


        $snapshot = [
            'brand_id' =>
                (int) $version['brand_id'],

            'brand_name' =>
                $version['brand_name'],

            'model_id' =>
                (int) $version['model_id'],

            'model_name' =>
                $version['model_name'],

            'version_id' =>
                (int) $version['id'],

            'version_name' =>
                $version['name'],

            'manufacture_year' =>
                $year,
        ];


        $id =
            $this->requests
                ->createDraft(
                    $userId,
                    [
                        'reference_code' =>
                            $referenceCode,

                        'vehicle_version_id' =>
                            $versionId,

                        'manufacture_year' =>
                            $year,

                        'odometer_km' =>
                            $odometer,

                        'exterior_color' =>
                            $this->nullableText(
                                $data[
                                    'exterior_color'
                                ] ?? null
                            ),

                        'interior_color' =>
                            $this->nullableText(
                                $data[
                                    'interior_color'
                                ] ?? null
                            ),

                        'license_plate' =>
                            $this->nullableText(
                                $data[
                                    'license_plate'
                                ] ?? null
                            ),

                        'registration_province' =>
                            $this->nullableText(
                                $data[
                                    'registration_province'
                                ] ?? null
                            ),

                        'owners_count' =>
                            isset(
                                $data[
                                    'owners_count'
                                ]
                            )
                            && $data[
                                'owners_count'
                            ] !== ''
                                ? (int)
                                    $data[
                                        'owners_count'
                                    ]
                                : null,

                        'seller_note' =>
                            $this->nullableText(
                                $data[
                                    'seller_note'
                                ] ?? null
                            ),
                    ],
                    $snapshot
                );


        JsonResponse::success(
            [
                'valuation_request_id' =>
                    $id,

                'reference_code' =>
                    $referenceCode,

                'status' =>
                    'draft',

                'required_photos' =>
                    count(
                        self::REQUIRED_SLOTS
                    ),
            ],
            'Đã tạo hồ sơ định giá.',
            201
        );
    }


    public function detail(): void
    {
        $userId =
            $this->requireUser();

        $id =
            (int) (
                $_GET['id']
                ?? 0
            );

        if ($id <= 0) {
            JsonResponse::error(
                'ID hồ sơ không hợp lệ.',
                422
            );
        }


        $request =
            $this->requests
                ->findOwned(
                    $id,
                    $userId
                );


        if (!$request) {
            JsonResponse::error(
                'Không tìm thấy hồ sơ.',
                404
            );
        }


        $images =
            $this->images
                ->activeImages($id);


        $progress =
            $this->photoProgress($id);


        JsonResponse::success([
            'valuation' => $request,
            'images' => $images,
            'photo_progress' => $progress,
        ]);
    }

    public function uploadImage(): void
    {
        $userId =
            $this->requireUser();

        $requestId =
            (int) (
                $_POST[
                    'valuation_request_id'
                ]
                ?? 0
            );

        $slotKey =
            trim(
                (string) (
                    $_POST['slot_key']
                    ?? ''
                )
            );


        $request =
            $this->ownedEditableRequest(
                $requestId,
                $userId
            );


        $slot =
            $this->validateSlot(
                $slotKey
            );


        if (
            $this->images
                ->findActiveBySlot(
                    $requestId,
                    $slotKey
                )
        ) {
            JsonResponse::error(
                'Vị trí ảnh này đã có ảnh. Hãy dùng chức năng Thay ảnh.',
                409
            );
        }


        $file =
            $this->validatedUpload();


        $oldRecord =
            $this->images
                ->findAnyBySlot(
                    $requestId,
                    $slotKey
                );


        $upload = null;

        try {

            $upload =
                $this->cloudinary
                    ->uploadValuationImage(
                        $file['tmp_name'],
                        $request[
                            'reference_code'
                        ],
                        $slot['category'],
                        $slotKey
                    );


            $imageData = [
                'category' =>
                    $slot['category'],

                'image_url' =>
                    $upload[
                        'secure_url'
                    ],

                'public_id' =>
                    $upload[
                        'public_id'
                    ],

                'asset_id' =>
                    $upload[
                        'asset_id'
                    ],

                'mime_type' =>
                    $file['mime'],

                'file_size_bytes' =>
                    $upload['bytes']
                    ?? $file['size'],

                'width' =>
                    $upload['width']
                    ?? null,

                'height' =>
                    $upload['height']
                    ?? null,

                'is_required' =>
                    1,

                'sort_order' =>
                    $slot['sort_order'],

                'uploaded_by' =>
                    $userId,
            ];


            if ($oldRecord) {

                $this->images->replace(
                    (int) $oldRecord['id'],
                    $imageData
                );

                $imageId =
                    (int) $oldRecord['id'];

            } else {

                $imageId =
                    $this->images->create(
                        [
                            'valuation_request_id'
                                => $requestId,

                            'category'
                                => $slot[
                                    'category'
                                ],

                            'slot_key'
                                => $slotKey,

                            'image_url'
                                => $upload[
                                    'secure_url'
                                ],

                            'public_id'
                                => $upload[
                                    'public_id'
                                ],

                            'asset_id'
                                => $upload[
                                    'asset_id'
                                ],

                            'mime_type'
                                => $file[
                                    'mime'
                                ],

                            'file_size_bytes'
                                => $upload[
                                    'bytes'
                                ]
                                    ?? $file[
                                        'size'
                                    ],

                            'width'
                                => $upload[
                                    'width'
                                ]
                                    ?? null,

                            'height'
                                => $upload[
                                    'height'
                                ]
                                    ?? null,

                            'is_required'
                                => 1,

                            'sort_order'
                                => $slot[
                                    'sort_order'
                                ],

                            'uploaded_by'
                                => $userId,
                        ]
                    );
            }


            $this->requests
                ->markPhotosPending(
                    $requestId
                );


            JsonResponse::success(
                [
                    'image' => [
                        'id' =>
                            $imageId,

                        'slot_key' =>
                            $slotKey,

                        'label' =>
                            $slot['label'],

                        'category' =>
                            $slot[
                                'category'
                            ],

                        'image_url' =>
                            $upload[
                                'secure_url'
                            ],
                    ],

                    'photo_progress' =>
                        $this->photoProgress(
                            $requestId
                        ),
                ],
                'Tải ảnh thành công.',
                201
            );

        } catch (Throwable $e) {

            if (
                is_array($upload)
                && !empty(
                    $upload['public_id']
                )
            ) {
                try {
                    $this->cloudinary
                        ->destroyImage(
                            $upload[
                                'public_id'
                            ]
                        );
                } catch (Throwable) {
                }
            }

            error_log(
                'Valuation upload error: '
                . $e->getMessage()
            );

            JsonResponse::error(
                'Không thể tải ảnh: '
                . $e->getMessage(),
                500
            );
        }
    }

    public function replaceImage(): void
    {
        $userId =
            $this->requireUser();

        $requestId =
            (int) (
                $_POST[
                    'valuation_request_id'
                ]
                ?? 0
            );

        $slotKey =
            trim(
                (string) (
                    $_POST['slot_key']
                    ?? ''
                )
            );


        $request =
            $this->ownedEditableRequest(
                $requestId,
                $userId
            );


        $slot =
            $this->validateSlot(
                $slotKey
            );


        $old =
            $this->images
                ->findActiveBySlot(
                    $requestId,
                    $slotKey
                );


        if (!$old) {
            JsonResponse::error(
                'Chưa có ảnh ở vị trí này để thay.',
                404
            );
        }


        $file =
            $this->validatedUpload();


        $newUpload = null;

        try {

            $newUpload =
                $this->cloudinary
                    ->uploadValuationImage(
                        $file['tmp_name'],
                        $request[
                            'reference_code'
                        ],
                        $slot[
                            'category'
                        ],
                        $slotKey
                    );


            $this->images->replace(
                (int) $old['id'],
                [
                    'category' =>
                        $slot[
                            'category'
                        ],

                    'image_url' =>
                        $newUpload[
                            'secure_url'
                        ],

                    'public_id' =>
                        $newUpload[
                            'public_id'
                        ],

                    'asset_id' =>
                        $newUpload[
                            'asset_id'
                        ],

                    'mime_type' =>
                        $file['mime'],

                    'file_size_bytes' =>
                        $newUpload[
                            'bytes'
                        ]
                        ?? $file[
                            'size'
                        ],

                    'width' =>
                        $newUpload[
                            'width'
                        ]
                        ?? null,

                    'height' =>
                        $newUpload[
                            'height'
                        ]
                        ?? null,

                    'is_required' =>
                        1,

                    'sort_order' =>
                        $slot[
                            'sort_order'
                        ],

                    'uploaded_by' =>
                        $userId,
                ]
            );


            /*
             * DB đã cập nhật thành công
             * mới xóa asset cũ.
             */

            if (!empty($old['public_id'])) {
                try {
                    $this->cloudinary
                        ->destroyImage(
                            $old[
                                'public_id'
                            ]
                        );
                } catch (Throwable $destroyError) {
                    error_log(
                        'Không xóa được ảnh cũ: '
                        . $destroyError
                            ->getMessage()
                    );
                }
            }


            JsonResponse::success(
                [
                    'image' => [
                        'id' =>
                            (int) $old['id'],

                        'slot_key' =>
                            $slotKey,

                        'image_url' =>
                            $newUpload[
                                'secure_url'
                            ],
                    ],

                    'photo_progress' =>
                        $this->photoProgress(
                            $requestId
                        ),
                ],
                'Đã thay ảnh thành công.'
            );


        } catch (Throwable $e) {

            if (
                is_array($newUpload)
                && !empty(
                    $newUpload[
                        'public_id'
                    ]
                )
            ) {
                try {
                    $this->cloudinary
                        ->destroyImage(
                            $newUpload[
                                'public_id'
                            ]
                        );
                } catch (Throwable) {
                }
            }


            JsonResponse::error(
                'Không thể thay ảnh: '
                . $e->getMessage(),
                500
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function completePhotos(): void
{
    $userId =
        $this->requireUser();

    $data =
        $this->requestData();


    $requestId =
        (int) (
            $data['valuation_request_id']
            ?? 0
        );


    $privacyAccepted =
        filter_var(
            $data['privacy_accepted']
            ?? false,
            FILTER_VALIDATE_BOOLEAN
        );


    $termsAccepted =
        filter_var(
            $data['terms_accepted']
            ?? false,
            FILTER_VALIDATE_BOOLEAN
        );


    $request =
        $this->ownedEditableRequest(
            $requestId,
            $userId
        );


    if (
        !$privacyAccepted ||
        !$termsAccepted
    ) {

        JsonResponse::error(
            'Bạn cần đồng ý Chính sách bảo mật và Quy chế hoạt động.',
            422
        );
    }


    $progress =
        $this->photoProgress(
            $requestId
        );


    if (!$progress['complete']) {

        JsonResponse::error(
            'Bạn chưa tải đầy đủ ảnh bắt buộc.',
            422,
            [
                'photo_progress'
                    => $progress
            ]
        );
    }


    $this->requests
        ->setConsents(
            $requestId,
            true,
            true
        );


    $this->requests
        ->markContactPending(
            $requestId
        );


    JsonResponse::success(
        [
            'valuation_request_id'
                => $requestId,

            'reference_code'
                => $request[
                    'reference_code'
                ],

            'status'
                => 'contact_pending',

            'photo_progress'
                => $progress,
        ],
        'Hình ảnh đã được lưu. Vui lòng nhập thông tin liên hệ.'
    );
}
    public function deleteImage(): void
    {
        $userId =
            $this->requireUser();

        $data =
            $this->requestData();


        $requestId =
            (int) (
                $data[
                    'valuation_request_id'
                ]
                ?? 0
            );


        $slotKey =
            trim(
                (string) (
                    $data['slot_key']
                    ?? ''
                )
            );


        $this->ownedEditableRequest(
            $requestId,
            $userId
        );


        $this->validateSlot(
            $slotKey
        );


        $image =
            $this->images
                ->findActiveBySlot(
                    $requestId,
                    $slotKey
                );


        if (!$image) {
            JsonResponse::error(
                'Không tìm thấy ảnh.',
                404
            );
        }


        /*
         * Xóa logic DB trước.
         *
         * Nếu Cloudinary lỗi thì chỉ còn
         * orphan asset, không làm hỏng dữ liệu
         * người dùng.
         */

        $this->images
            ->softDelete(
                (int) $image['id']
            );


        if (
            !empty(
                $image['public_id']
            )
        ) {
            try {

                $this->cloudinary
                    ->destroyImage(
                        $image[
                            'public_id'
                        ]
                    );

            } catch (Throwable $e) {

                error_log(
                    'Cloudinary delete failed: '
                    . $e->getMessage()
                );
            }
        }


        JsonResponse::success(
            [
                'slot_key' =>
                    $slotKey,

                'photo_progress' =>
                    $this->photoProgress(
                        $requestId
                    ),
            ],
            'Đã xóa ảnh.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SUBMIT
    |--------------------------------------------------------------------------
    */

    public function submit(): void
    {
        $userId =
            $this->requireUser();

        $data =
            $this->requestData();


        $requestId =
            (int) (
                $data[
                    'valuation_request_id'
                ]
                ?? 0
            );


        $privacyAccepted =
            filter_var(
                $data[
                    'privacy_accepted'
                ]
                ?? false,
                FILTER_VALIDATE_BOOLEAN
            );


        $termsAccepted =
            filter_var(
                $data[
                    'terms_accepted'
                ]
                ?? false,
                FILTER_VALIDATE_BOOLEAN
            );


        $request =
            $this->ownedEditableRequest(
                $requestId,
                $userId
            );


        if (
            !$privacyAccepted
            || !$termsAccepted
        ) {
            JsonResponse::error(
                'Bạn cần đồng ý Chính sách bảo mật và Quy chế hoạt động.',
                422
            );
        }


        $progress =
            $this->photoProgress(
                $requestId
            );


        if (
            !$progress['complete']
        ) {
            JsonResponse::error(
                'Bạn chưa tải đầy đủ 21 ảnh bắt buộc.',
                422,
                [
                    'photo_progress' =>
                        $progress
                ]
            );
        }


        /*
         * Kiểm tra lại thông tin cơ bản.
         */

        if (
            empty(
                $request[
                    'vehicle_version_id'
                ]
            )
            || empty(
                $request[
                    'manufacture_year'
                ]
            )
        ) {
            JsonResponse::error(
                'Thông tin xe chưa đầy đủ.',
                422
            );
        }


        $this->requests
            ->setConsents(
                $requestId,
                true,
                true
            );


        $this->requests
            ->submit(
                $requestId
            );


        JsonResponse::success(
            [
                'valuation_request_id'
                    => $requestId,

                'reference_code'
                    => $request[
                        'reference_code'
                    ],

                'status'
                    => 'ready_for_estimate',

                'photo_progress'
                    => $progress,
            ],
            'Hồ sơ đã được gửi để định giá.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function ownedEditableRequest(
        int $requestId,
        int $userId
    ): array {
        if ($requestId <= 0) {
            JsonResponse::error(
                'ID hồ sơ không hợp lệ.',
                422
            );
        }


        $request =
            $this->requests
                ->findOwned(
                    $requestId,
                    $userId
                );


        if (!$request) {
            JsonResponse::error(
                'Không tìm thấy hồ sơ định giá.',
                404
            );
        }


        if (
            !$this->editable(
                $request
            )
        ) {
            JsonResponse::error(
                'Hồ sơ này đã được gửi và không còn cho phép chỉnh sửa ảnh.',
                409
            );
        }


        return $request;
    }


    private function validateSlot(
        string $slotKey
    ): array {
        if (
            $slotKey === ''
            || !isset(
                self::REQUIRED_SLOTS[
                    $slotKey
                ]
            )
        ) {
            JsonResponse::error(
                'Vị trí ảnh không hợp lệ.',
                422
            );
        }


        return self::REQUIRED_SLOTS[
            $slotKey
        ];
    }


    private function validatedUpload(): array
    {
        if (
            empty($_FILES['image'])
            || !is_array(
                $_FILES['image']
            )
        ) {
            JsonResponse::error(
                'Vui lòng chọn ảnh.',
                422
            );
        }


        $file =
            $_FILES['image'];


        $error =
            (int) (
                $file['error']
                ?? UPLOAD_ERR_NO_FILE
            );


        if (
            $error !== UPLOAD_ERR_OK
        ) {
            JsonResponse::error(
                'Upload ảnh thất bại. Mã lỗi: '
                . $error,
                422
            );
        }


        $tmpName =
            (string) (
                $file['tmp_name']
                ?? ''
            );


        if (
            $tmpName === ''
            || !is_uploaded_file(
                $tmpName
            )
        ) {
            JsonResponse::error(
                'File upload không hợp lệ.',
                422
            );
        }


        $size =
            (int) (
                $file['size']
                ?? 0
            );


        /*
         * Tối đa 10 MB / ảnh.
         */

        if (
            $size <= 0
            || $size >
                10 * 1024 * 1024
        ) {
            JsonResponse::error(
                'Ảnh phải có dung lượng nhỏ hơn hoặc bằng 10 MB.',
                422
            );
        }


        $finfo =
            new \finfo(
                FILEINFO_MIME_TYPE
            );


        $mime =
            $finfo->file(
                $tmpName
            )
            ?: '';


        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];


        if (
            !in_array(
                $mime,
                $allowed,
                true
            )
        ) {
            JsonResponse::error(
                'Chỉ hỗ trợ ảnh JPG, PNG hoặc WebP.',
                422
            );
        }


        /*
         * Kiểm tra thật sự là ảnh.
         */

        $imageInfo =
            @getimagesize(
                $tmpName
            );


        if (!$imageInfo) {
            JsonResponse::error(
                'File không phải hình ảnh hợp lệ.',
                422
            );
        }


        return [
            'tmp_name' =>
                $tmpName,

            'size' =>
                $size,

            'mime' =>
                $mime,

            'width' =>
                (int) (
                    $imageInfo[0]
                    ?? 0
                ),

            'height' =>
                (int) (
                    $imageInfo[1]
                    ?? 0
                ),
        ];
    }


    private function photoProgress(
        int $requestId
    ): array {
        $uploaded =
            $this->images
                ->activeSlotKeys(
                    $requestId
                );


        $required =
            array_keys(
                self::REQUIRED_SLOTS
            );


        $missing =
            array_values(
                array_diff(
                    $required,
                    $uploaded
                )
            );


        $completed =
            count($required)
            - count($missing);


        $groups = [
            'exterior' => [
                'completed' => 0,
                'required' => 0,
            ],

            'interior' => [
                'completed' => 0,
                'required' => 0,
            ],

            'mechanical' => [
                'completed' => 0,
                'required' => 0,
            ],

            'legal' => [
                'completed' => 0,
                'required' => 0,
            ],
        ];


        foreach (
            self::REQUIRED_SLOTS
            as $key => $slot
        ) {
            $category =
                $slot['category'];

            $groups[
                $category
            ]['required']++;


            if (
                in_array(
                    $key,
                    $uploaded,
                    true
                )
            ) {
                $groups[
                    $category
                ]['completed']++;
            }
        }


        $missingDetails = [];


        foreach (
            $missing
            as $key
        ) {
            $missingDetails[] = [
                'slot_key' =>
                    $key,

                'label' =>
                    self::REQUIRED_SLOTS[
                        $key
                    ]['label'],

                'category' =>
                    self::REQUIRED_SLOTS[
                        $key
                    ]['category'],
            ];
        }


        return [
            'completed' =>
                $completed,

            'required' =>
                count($required),

            'percent' =>
                (int) round(
                    (
                        $completed
                        / count(
                            $required
                        )
                    ) * 100
                ),

            'complete' =>
                count(
                    $missing
                ) === 0,

            'groups' =>
                $groups,

            'missing' =>
                $missingDetails,
        ];
    }


    private function nullableText(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) (
                    $value
                    ?? ''
                )
            );

        return $value === ''
            ? null
            : $value;
    }
}