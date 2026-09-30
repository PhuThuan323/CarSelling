<?php

declare(strict_types=1);

namespace App\Controllers\WebRender;

use App\Core\Auth;
use App\Core\View;
use App\Models\Feedback\FeedbackStory;

/**
 * Trang khach hang: cau chuyen khach hang.
 *
 *   GET /customer-stories            - xem tat ca
 *   GET /customer-stories/{slug}     - xem chi tiet mot cau chuyen
 */
class CustomerStory
{
    private const PER_PAGE = 9;

    private View $view;

    private FeedbackStory $stories;

    public function __construct()
    {
        $this->view = new View();
        $this->stories = new FeedbackStory();
    }

    /**
     * Danh sach tat ca cau chuyen (co tim kiem + phan trang don gian).
     */
    public function index(): void
    {
        $all = $this->stories->published();

        $keyword = trim((string) ($_GET['q'] ?? ''));

        if ($keyword !== '') {
            $needle = mb_strtolower($keyword, 'UTF-8');

            $all = array_values(
                array_filter(
                    $all,
                    static function (array $story) use ($needle): bool {
                        $haystack = mb_strtolower(
                            implode(
                                ' ',
                                [
                                    (string) ($story['author_name'] ?? ''),
                                    (string) ($story['title'] ?? ''),
                                    (string) ($story['excerpt'] ?? ''),
                                    (string) ($story['story'] ?? ''),
                                    (string) ($story['location'] ?? ''),
                                ]
                            ),
                            'UTF-8'
                        );

                        return str_contains($haystack, $needle);
                    }
                )
            );
        }

        $total = count($all);
        $pageCount = max(1, (int) ceil($total / self::PER_PAGE));
        $page = (int) ($_GET['page'] ?? 1);
        $page = max(1, min($page, $pageCount));

        $stories = array_slice(
            $all,
            ($page - 1) * self::PER_PAGE,
            self::PER_PAGE
        );

        $this->view->assign('page_title', 'Câu chuyện khách hàng - FastCar');
        $this->view->assign('current_user', Auth::user());
        $this->view->assign('stories', $stories);
        $this->view->assign('story_total', $total);
        $this->view->assign('keyword', $keyword);
        $this->view->assign('current_page', $page);
        $this->view->assign('page_count', $pageCount);

        $this->view->display('home/customer-stories');
    }

    /**
     * Chi tiet mot cau chuyen.
     */
    public function detail(string $slug): void
    {
        $story = $this->stories->findPublishedBySlug($slug);

        if ($story === null) {
            http_response_code(404);

            (new View())->display('errors/404');

            return;
        }

        $this->view->assign('page_title', $story['title'] . ' - FastCar');
        $this->view->assign('current_user', Auth::user());
        $this->view->assign('story', $story);
        $this->view->assign('other_stories', $this->otherStories($story));

        $this->view->display('home/customer-story-detail');
    }

    /**
     * Vai cau chuyen khac de goi y cuoi trang chi tiet.
     *
     * @param array<string, mixed> $current
     * @return array<int, array<string, mixed>>
     */
    private function otherStories(array $current): array
    {
        $others = array_values(
            array_filter(
                $this->stories->published(),
                static fn (array $story): bool
                    => (string) $story['slug'] !== (string) $current['slug']
            )
        );

        return array_slice($others, 0, 3);
    }
}
