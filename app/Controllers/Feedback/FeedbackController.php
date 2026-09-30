<?php

declare(strict_types=1);

namespace App\Controllers\Feedback;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Models\Feedback\FeedbackStory;

/**
 * API quan tri cau chuyen khach hang (luu file JSON, khong dung database).
 *
 *   GET    /api/v1/admin/feedback-stories
 *   POST   /api/v1/admin/feedback-stories        (them moi)
 *   DELETE /api/v1/admin/feedback-stories/{id}   (xoa)
 */
class FeedbackController
{
    private FeedbackStory $stories;

    public function __construct()
    {
        $this->stories = new FeedbackStory();
    }

    /**
     * GET /api/v1/admin/feedback-stories
     */
    public function index(): void
    {
        Auth::requireAdmin();

        JsonResponse::success([
            'stories' => $this->stories->all(),
        ]);
    }

    /**
     * POST /api/v1/admin/feedback-stories
     *
     * Body JSON (hoac form):
     *   author_name, title, excerpt, story, location, image, status
     */
    public function store(): void
    {
        Auth::requireAdmin();

        try {
            $story = $this->stories->create($this->payload());
        } catch (\InvalidArgumentException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }

        JsonResponse::success(
            ['story' => $story],
            'Đã thêm câu chuyện khách hàng.',
            201
        );
    }

    /**
     * DELETE /api/v1/admin/feedback-stories/{id}
     */
    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        $storyId = (int) $id;

        if ($storyId <= 0) {
            JsonResponse::error('Câu chuyện không hợp lệ.', 422);
        }

        if (!$this->stories->delete($storyId)) {
            JsonResponse::error('Không tìm thấy câu chuyện cần xóa.', 404);
        }

        JsonResponse::success(
            ['id' => $storyId],
            'Đã xóa câu chuyện khách hàng.'
        );
    }

    /**
     * Doc du lieu tu JSON body hoac form post.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');

            if ($raw !== false && trim($raw) !== '') {
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            return [];
        }

        return $_POST;
    }
}
