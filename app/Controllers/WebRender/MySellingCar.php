<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Inspection\ValuationCustomerFeedback;
use App\Models\Inspection\ValuationInspectionResult;
use App\Models\Inspection\ValuationRequest;
use App\Models\Inspection\ValuationRequestContact;
use App\Models\Inspection\ValuationRequestImage;

/**
 * Trang khach hang: danh sach xe da dang ban + chi tiet ket qua dinh gia.
 *
 *   GET /my-selling-cars
 *   GET /my-selling-cars/{id}
 *
 * Khach chi duoc xem ho so cua chinh minh.
 */
class MySellingCar
{
    private View $view;

    private ValuationRequest $requests;

    private ValuationRequestImage $images;

    private ValuationRequestContact $contacts;

    private ValuationInspectionResult $results;

    private ValuationCustomerFeedback $feedbacks;

    public function __construct()
    {
        $this->view = new View();
        $this->requests = new ValuationRequest();
        $this->images = new ValuationRequestImage();
        $this->contacts = new ValuationRequestContact();
        $this->results = new ValuationInspectionResult();
        $this->feedbacks = new ValuationCustomerFeedback();
    }

    public function index(): void
    {
        $user = Auth::requireLogin(false);

        $cars = $this->requests->findAllByUser((int) $user['id']);

        foreach ($cars as &$car) {
            $car['vehicle_label'] = $this->vehicleLabel($car);
            $car['status_label'] = ValuationRequest::statusLabel(
                (string) $car['status']
            );
        }

        unset($car);

        $this->view->assign('page_title', 'Xe đã đăng bán - FastCar');
        $this->view->assign('current_user', $user);
        $this->view->assign('cars', $cars);
        $this->view->assign('csrf_token', Auth::csrfToken());

        $this->view->display('banxe/my-selling-cars');
    }

    public function detail(string $id): void
    {
        $user = Auth::requireLogin(false);

        $requestId = (int) $id;

        // Chi chu ho so moi xem duoc.
        $owned = $this->requests->findOwned($requestId, (int) $user['id']);

        if (!$owned) {
            http_response_code(404);
            $this->view->display('errors/404');

            return;
        }

        $request = $this->requests->findDetailed($requestId);

        $status = (string) $request['status'];

        $result = $this->results->findByRequest($requestId);

        $feedback = $this->feedbacks->findLatestByRequest($requestId);

        $this->view->assign(
            'page_title',
            ($request['reference_code'] ?? 'Hồ sơ') . ' - FastCar'
        );
        $this->view->assign('current_user', $user);
        $this->view->assign('request', $request);
        $this->view->assign('vehicle_label', $this->vehicleLabel($request));
        $this->view->assign('status', $status);
        $this->view->assign('status_label', ValuationRequest::statusLabel($status));
        $this->view->assign('images', $this->images->activeImages($requestId));
        $this->view->assign('contact', $this->contacts->findByRequestId($requestId));
        $this->view->assign('result', $result);
        $this->view->assign('feedback', $feedback);
        $this->view->assign('rating_rows', $this->ratingRows($result));
        $this->view->assign(
            'timeline',
            $this->requests->findHistory($requestId)
        );
        $this->view->assign('can_respond', $status === 'estimated');
        $this->view->assign('csrf_token', Auth::csrfToken());

        $this->view->display('banxe/my-selling-car-detail');
    }

    /**
     * Chi hien cac hang muc da duoc staff danh gia.
     */
    private function ratingRows(?array $result): array
    {
        if ($result === null) {
            return [];
        }

        $rows = [];

        foreach (ValuationInspectionResult::RATING_FIELDS as $field => $meta) {
            $value = $result[$field] ?? null;

            if ($value === null) {
                continue;
            }

            $rows[] = [
                'label' => $meta['label'],
                'text' => $this->ratingLabel(
                    (string) $value,
                    (string) $meta['type']
                ),
            ];
        }

        return $rows;
    }

    private function ratingLabel(string $rating, string $type): string
    {
        if ($type === 'good_issue') {
            return $rating === 'good' ? 'Tốt' : 'Có vấn đề';
        }

        return [
            'good' => 'Tốt',
            'average' => 'Trung bình',
            'poor' => 'Kém',
        ][$rating] ?? $rating;
    }

    private function vehicleLabel(array $request): string
    {
        $snapshot = $request['vehicle_snapshot'] ?? [];

        if (is_string($snapshot)) {
            $decoded = json_decode($snapshot, true);

            $snapshot = is_array($decoded) ? $decoded : [];
        }

        $name = trim(
            (string) ($snapshot['brand_name'] ?? '')
            . ' '
            . (string) ($snapshot['model_name'] ?? '')
        );

        if ($name === '') {
            $name = 'Xe #' . ($request['reference_code'] ?? '');
        }

        $year = $request['manufacture_year'] ?? null;

        if ($year !== null && $year !== '') {
            $name .= ' ' . $year;
        }

        return $name;
    }
}
    
