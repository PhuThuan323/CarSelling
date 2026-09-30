<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Feedback\FeedbackStory;

/**
 * Trang quan tri: quan ly cau chuyen khach hang (testimonial).
 *
 *   GET /admin/feedback
 *
 * Du lieu luu o storage/feedback-stories.json, khong dung database.
 */
class AdminFeedback
{
    private View $view;

    private FeedbackStory $stories;

    public function __construct()
    {
        $this->view = new View();
        $this->stories = new FeedbackStory();
    }

    public function index(): void
    {
        $user = Auth::requireAdmin(false);

        $stories = $this->stories->all();

        $activeCount = count(
            array_filter(
                $stories,
                static fn (array $story): bool
                    => ($story['status'] ?? 'active') === 'active'
            )
        );

        $this->view->assign('admin_user', $user);
        $this->view->assign('csrf_token', Auth::csrfToken());
        $this->view->assign('inspection_counts', $this->inspectionCounts());
        $this->view->assign(
            'staff_counts',
            (new \App\Models\UserManagement\User())->countByRole()
        );
        $this->view->assign('page_title', 'Câu chuyện khách hàng - CarSelling');
        $this->view->assign('screen_title', 'Câu chuyện khách hàng');
        $this->view->assign(
            'screen_subtitle',
            'Thêm hoặc xóa câu chuyện hiển thị ở trang chủ. Nội dung lưu dạng file JSON, không cần cơ sở dữ liệu.'
        );
        $this->view->assign('active_menu', 'feedback');
        $this->view->assign('stories', $stories);
        $this->view->assign('story_total', count($stories));
        $this->view->assign('story_active', $activeCount);
        $this->view->assign('storage_file', $this->stories->filePath());

        $this->view->display('admin/feedback');
    }

    /**
     * So luong cho badge tren sidebar.
     */
    private function inspectionCounts(): array
    {
        $requests = new \App\Models\Inspection\ValuationRequest();

        $count = fn (array $statuses): int
            => count($requests->findByStatuses($statuses, 500));

        $unassigned = $count(['inspection_requested']);
        $assigned = $count(['inspection_assigned']);
        $inProgress = $count(['inspection_in_progress']);
        $review = $count(['inspection_completed']);

        return [
            'unassigned' => $unassigned,
            'assigned' => $assigned,
            'in_progress' => $inProgress,
            'review' => $review,
            'all' => $unassigned + $assigned + $inProgress + $review,
        ];
    }
}
