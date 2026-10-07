<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Auction\Auction;
use App\Models\Inspection\ValuationRequest;
use App\Models\UserManagement\User;
use App\Service\AuctionWorkflow;

/**
 * Trang quan tri: quan ly phien dau gia.
 *
 *   GET /admin/auctions              - danh sach phien
 *   GET /admin/auctions?status=active
 */
class AdminAuction
{
    private View $view;

    private Auction $auctions;

    private ValuationRequest $requests;

    private AuctionWorkflow $workflow;

    public function __construct()
    {
        $this->view = new View();
        $this->auctions = new Auction();
        $this->requests = new ValuationRequest();
        $this->workflow = new AuctionWorkflow();
    }

    public function index(): void
    {
        $user = Auth::requireAdmin(false);

        // Dong phien het gio de trang thai hien thi dung.
        $this->workflow->closeExpiredAuctions();

        $status = trim((string) ($_GET['status'] ?? ''));

        if (
            $status !== ''
            && !in_array($status, array_keys(Auction::STATUS_LABELS), true)
        ) {
            $status = '';
        }

        $items = $this->auctions->findAllForAdmin(
            $status !== '' ? $status : null,
            300
        );

        foreach ($items as &$item) {
            $item['status_label'] = Auction::statusLabel(
                (string) $item['status']
            );
        }

        unset($item);

        // Ho so da 'accepted' co the mo phien dau gia.
        $eligible = $this->requests->findByStatuses(['accepted'], 200);

        foreach ($eligible as &$row) {
            $row['status_label'] = ValuationRequest::statusLabel(
                (string) $row['status']
            );
            $row['already_auctioned'] = $this->auctions
                ->existsActiveForRequest((int) $row['id']);
        }

        unset($row);

        $this->view->assign('admin_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign(
            'staff_counts',
            (new User())->countByRole()
        );
        $this->view->assign('page_title', 'Quản lý đấu giá - CarSelling');
        $this->view->assign('screen_title', 'Quản lý phiên đấu giá');
        $this->view->assign(
            'screen_subtitle',
            'Mở phiên đấu giá từ hồ sơ khách đã đồng ý bán, theo dõi người thắng và xác nhận thanh toán.'
        );
        $this->view->assign('active_menu', 'auctions');
        $this->view->assign('status_filter', $status);
        $this->view->assign('status_labels', Auction::STATUS_LABELS);
        $this->view->assign('counts', $this->counts());
        $this->view->assign('items', $items);
        $this->view->assign('eligible_requests', $eligible);
        $this->view->assign(
            'min_duration_days',
            Auction::MIN_DURATION_DAYS
        );
        $this->view->assign(
            'max_duration_days',
            Auction::MAX_DURATION_DAYS
        );

        $this->view->display('admin/auctions');
    }

    /**
     * @return array<string,int>
     */
    private function counts(): array
    {
        $counts = [];

        foreach (array_keys(Auction::STATUS_LABELS) as $key) {
            $counts[$key] = count(
                $this->auctions->findAllForAdmin($key, 500)
            );
        }

        $counts['all'] = array_sum($counts);

        return $counts;
    }
}
