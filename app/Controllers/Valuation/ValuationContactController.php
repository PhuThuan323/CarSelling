<?php

declare(strict_types=1);

namespace App\Controllers\Valuation;

use App\Core\JsonResponse;
use App\Models\Inspection\ValuationRequest;
use App\Models\Inspection\ValuationRequestContact;
use Throwable;

class ValuationContactController
{
    private ValuationRequest $requests;

    private ValuationRequestContact $contacts;


    public function __construct()
    {
        $this->requests =
            new ValuationRequest();

        $this->contacts =
            new ValuationRequestContact();
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


    /*
    |--------------------------------------------------------------------------
    | REQUEST DATA
    |--------------------------------------------------------------------------
    */

    private function requestData(): array
    {
        $contentType =
            $_SERVER['CONTENT_TYPE']
            ?? '';


        /*
         * JSON
         */
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


        /*
         * Form data
         */
        return $_POST;
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE CONTACT
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        try {

            /*
             * User đăng nhập
             */
            $userId =
                $this->requireUser();


            /*
             * Request data
             */
            $data =
                $this->requestData();


            /*
             * valuation_request_id
             */
            $requestId =
                (int) (
                    $data[
                        'valuation_request_id'
                    ]
                    ?? 0
                );


            /*
             * Contact
             */
            $fullName =
                trim(
                    (string) (
                        $data['full_name']
                        ?? ''
                    )
                );


            $phone =
                trim(
                    (string) (
                        $data['phone']
                        ?? ''
                    )
                );


            $email =
                trim(
                    (string) (
                        $data['email']
                        ?? ''
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | VALIDATE REQUEST ID
            |--------------------------------------------------------------------------
            */

            if ($requestId <= 0) {

                JsonResponse::error(
                    'ID hồ sơ không hợp lệ.',
                    422
                );
            }


            /*
            |--------------------------------------------------------------------------
            | KIỂM TRA HỒ SƠ THUỘC USER
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | PHẢI ĐANG Ở CONTACT_PENDING
            |--------------------------------------------------------------------------
            */

            if (
                $request['status']
                !== 'contact_pending'
            ) {

                JsonResponse::error(
                    'Hồ sơ chưa sẵn sàng để nhập thông tin liên hệ.',
                    409,
                    [
                        'current_status'
                            => $request['status']
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDATE NAME
            |--------------------------------------------------------------------------
            */

            if ($fullName === '') {

                JsonResponse::error(
                    'Vui lòng nhập tên chủ xe.',
                    422
                );
            }


            if (
                mb_strlen($fullName)
                > 150
            ) {

                JsonResponse::error(
                    'Tên chủ xe không được vượt quá 150 ký tự.',
                    422
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDATE PHONE
            |--------------------------------------------------------------------------
            */

            if ($phone === '') {

                JsonResponse::error(
                    'Vui lòng nhập số điện thoại.',
                    422
                );
            }


            /*
             * Bỏ khoảng trắng, dấu chấm,
             * dấu gạch ngang...
             */
            $phone =
                preg_replace(
                    '/[^\d+]/',
                    '',
                    $phone
                );


            if (
                $phone === null
                || strlen($phone) < 8
                || strlen($phone) > 20
            ) {

                JsonResponse::error(
                    'Số điện thoại không hợp lệ.',
                    422
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDATE EMAIL
            |--------------------------------------------------------------------------
            */

            if (
                $email !== ''
                && !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                JsonResponse::error(
                    'Email không hợp lệ.',
                    422
                );
            }


            /*
            |--------------------------------------------------------------------------
            | SAVE CONTACT
            |--------------------------------------------------------------------------
            */

            $this->contacts
                ->upsert(
                    $requestId,
                    $fullName,
                    $phone,
                    $email !== ''
                        ? $email
                        : null
                );


            /*
            |--------------------------------------------------------------------------
            | HOÀN TẤT HỒ SƠ
            |--------------------------------------------------------------------------
            */

            $submitted =
                $this->requests
                    ->submitAfterContact(
                        $requestId
                    );


            if (!$submitted) {

                JsonResponse::error(
                    'Không thể hoàn tất hồ sơ. Trạng thái hồ sơ không hợp lệ.',
                    409
                );
            }


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

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
                ],
                'Thông tin liên hệ đã được lưu thành công.'
            );


        } catch (Throwable $e) {

            error_log(
                'Valuation contact error: '
                . $e->getMessage()
            );


            JsonResponse::error(
                'Không thể lưu thông tin liên hệ: '
                . $e->getMessage(),
                500
            );
        }
    }
}