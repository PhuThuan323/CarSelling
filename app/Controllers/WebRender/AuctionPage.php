<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Auction\Auction;
use App\Models\Auction\AuctionBid;
use App\Service\AuctionWorkflow;

/**
 * Trang khach hang: danh sach phien dau gia + chi tiet + xe da thang.
 *
 *   GET /cars                - danh sach phien dang dau gia (muc "Mua Xe")
 *   GET /cars/{id}           - chi tiet phien dau gia
 *   GET /my-auctions         - cac phien user da thang (cho thanh toan)
 */
class AuctionPage
{
    private View $view;

    private Auction $auctions;

    private AuctionBid $bids;

    private AuctionWorkflow $workflow;

    public function __construct()
    {
        $this->view = new View();
        $this->auctions = new Auction();
        $this->bids = new AuctionBid();
        $this->workflow = new AuctionWorkflow();
    }

    public function index(): void
    {
        // Dong phien het gio + bat dau phien den lich (lazy).
        $this->workflow->closeExpiredAuctions();

        $this->auctions->activateDue();

        $items = $this->auctions->findActive(100);

        foreach ($items as &$item) {
            $item['status_label'] = Auction::statusLabel(
                (string) $item['status']
            );
        }

        unset($item);

        $this->view->assign('page_title', 'Mua xe đấu giá - FastCar');
        $this->view->assign('current_user', Auth::user());
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign('auctions', $items);

        $this->view->display('muaxe/cars');
    }

    public function detail(string $id): void
    {
        $auctionId = (int) $id;

        $this->workflow->closeExpiredAuctions();

        $this->auctions->activateDue();

        $auction = $this->auctions->findById($auctionId);

        if (!$auction) {
            http_response_code(404);
            $this->view->display('errors/404');

            return;
        }

        $currentUser = Auth::user();

        $myBestBid = null;

        if ($currentUser !== null) {
            $myBestBid = $this->bids->findBestBid(
                $auctionId,
                (int) $currentUser['id']
            );
        }

        $minNextBid = (float) $auction['current_price']
            + (float) $auction['min_increment'];

        $this->view->assign(
            'page_title',
            $auction['title'] . ' - FastCar'
        );
        $this->view->assign('current_user', $currentUser);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign('auction', $auction);
        $this->view->assign('status_label', Auction::statusLabel(
            (string) $auction['status']
        ));
        $this->view->assign('bids', $this->bids->findByAuction($auctionId, 100));
        $this->view->assign('my_best_bid', $myBestBid);
        $this->view->assign('min_next_bid', $minNextBid);
        $this->view->assign(
            'is_active',
            (string) $auction['status'] === 'active'
        );
        $this->view->assign(
            'is_winner',
            $currentUser !== null
            && (int) $auction['winner_user_id'] === (int) $currentUser['id']
        );

        $this->view->display('muaxe/auction-detail');
    }

    public function myAuctions(): void
    {
        $user = Auth::requireLogin(false);

        $userId = (int) $user['id'];

        $this->workflow->closeExpiredAuctions();

        $won = $this->auctions->findByWinner(
            $userId,
            ['awaiting_payment', 'paid', 'cancelled']
        );

        foreach ($won as &$row) {
            $row['status_label'] = Auction::statusLabel(
                (string) $row['status']
            );
        }

        unset($row);

        $this->view->assign('page_title', 'Xe đã đấu giá thành công - FastCar');
        $this->view->assign('current_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign('won', $won);

        $this->view->display('muaxe/my-auctions');
    }
}
