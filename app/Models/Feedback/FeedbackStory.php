<?php

declare(strict_types=1);

namespace App\Models\Feedback;

/**
 * Cau chuyen khach hang (testimonial) hien thi o trang chu.
 *
 * Du lieu duoc luu trong MOT file JSON duy nhat, KHONG dung database:
 *
 *     storage/feedback-stories.json
 *
 * Admin co the them moi / xoa cau chuyen truc tiep tren giao dien quan tri.
 */
class FeedbackStory
{
    private const MAX_ITEMS = 60;

    private const MAX_TITLE = 160;

    private const MAX_AUTHOR = 120;

    private const MAX_LOCATION = 120;

    private const MAX_EXCERPT = 320;

    private const MAX_STORY = 6000;

    private const MAX_IMAGE = 500;

    private const MAX_SUMMARY = 500;

    private const MAX_PROBLEM = 200;

    private const MAX_SHORT = 40;

    private const MAX_PROBLEMS = 8;

    private string $filePath;

    public function __construct()
    {
        $base = defined('BASE_PATH')
            ? BASE_PATH
            : dirname(__DIR__, 3);

        $this->filePath =
            $base
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'feedback-stories.json';
    }

    /**
     * Toan bo cau chuyen, moi nhat truoc.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $items = $this->read();

        usort(
            $items,
            static fn (array $a, array $b): int
                => ((int) ($b['created_at'] ?? 0)) <=> ((int) ($a['created_at'] ?? 0))
        );

        return $items;
    }

    /**
     * Cau chuyen dang bat, dung cho trang khach hang.
     *
     * @return array<int, array<string, mixed>>
     */
    public function published(): array
    {
        return array_values(
            array_filter(
                $this->all(),
                static fn (array $item): bool
                    => ($item['status'] ?? 'active') === 'active'
            )
        );
    }

    /**
     * Cau chuyen dang bat theo slug (dung cho trang chi tiet).
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedBySlug(string $slug): ?array
    {
        foreach ($this->published() as $item) {
            if (hash_equals((string) $item['slug'], $slug)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Vai cau chuyen dau tien cho khoi review o trang chu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function featured(int $limit = 4): array
    {
        return array_slice($this->published(), 0, max(0, $limit));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        $items = $this->read();

        if (count($items) >= self::MAX_ITEMS) {
            throw new \RuntimeException(
                'Chỉ lưu được tối đa ' . self::MAX_ITEMS . ' câu chuyện. Vui lòng xóa bớt câu chuyện cũ.'
            );
        }

        $story = $this->normalize($payload);

        $story['id'] = $this->nextId($items);
        $story['slug'] = $this->uniqueSlug($story['slug'], $items, $story['id']);
        $story['created_at'] = time();

        $items[] = $story;

        $this->write($items);

        return $story;
    }

    public function delete(int $id): bool
    {
        $items = $this->read();

        $remaining = array_values(
            array_filter(
                $items,
                static fn (array $item): bool => (int) $item['id'] !== $id
            )
        );

        if (count($remaining) === count($items)) {
            return false;
        }

        $this->write($remaining);

        return true;
    }

    /**
     * Duong dan file JSON (dung de bao loi / debug).
     */
    public function filePath(): string
    {
        return $this->filePath;
    }

    /* =======================================================
       DOC / GHI FILE
       ======================================================= */

    /**
     * @return array<int, array<string, mixed>>
     */
    private function read(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $raw = file_get_contents($this->filePath);

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        $stories = [];

        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $stories[] = [
                'id' => (int) ($item['id'] ?? 0),
                'slug' => (string) ($item['slug'] ?? ''),
                'author_name' => (string) ($item['author_name'] ?? ''),
                'location' => (string) ($item['location'] ?? ''),
                'title' => (string) ($item['title'] ?? ''),
                'excerpt' => (string) ($item['excerpt'] ?? ''),
                'story' => (string) ($item['story'] ?? ''),
                'image' => (string) ($item['image'] ?? ''),
                'story_image' => (string) ($item['story_image'] ?? ''),
                'summary' => (string) ($item['summary'] ?? ''),
                'highlight_price' => (string) ($item['highlight_price'] ?? ''),
                'highlight_time' => (string) ($item['highlight_time'] ?? ''),
                'highlight_fee' => (string) ($item['highlight_fee'] ?? ''),
                'problems' => is_array($item['problems'] ?? null)
                    ? array_values(array_map('strval', $item['problems']))
                    : [],
                'status' => ($item['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active',
                'created_at' => (int) ($item['created_at'] ?? 0),
            ];
        }

        return $stories;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function write(array $items): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true)) {
            throw new \RuntimeException('Không tạo được thư mục lưu câu chuyện khách hàng.');
        }

        $json = json_encode(
            array_values($items),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new \RuntimeException('Không chuyển được dữ liệu câu chuyện sang JSON.');
        }

        // Ghi qua file tam roi doi ten de tranh hong du lieu khi ghi loi.
        $temporary = $this->filePath . '.tmp';

        if (@file_put_contents($temporary, $json, LOCK_EX) === false) {
            throw new \RuntimeException(
                'Không ghi được file dữ liệu (' . $this->filePath . '). Kiểm tra quyền ghi thư mục storage.'
            );
        }

        if (!@rename($temporary, $this->filePath)) {
            @unlink($temporary);

            throw new \RuntimeException('Không cập nhật được file dữ liệu câu chuyện khách hàng.');
        }

        @chmod($this->filePath, 0664);
    }

    /* =======================================================
       CHUAN HOA DU LIEU
       ======================================================= */

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        $author = $this->text($payload['author_name'] ?? '', self::MAX_AUTHOR);
        $title = $this->text($payload['title'] ?? '', self::MAX_TITLE);
        $story = $this->textarea($payload['story'] ?? '', self::MAX_STORY);
        $excerpt = $this->textarea($payload['excerpt'] ?? '', self::MAX_EXCERPT);

        if ($author === '') {
            throw new \InvalidArgumentException('Vui lòng nhập tên khách hàng.');
        }

        if ($title === '') {
            throw new \InvalidArgumentException('Vui lòng nhập tiêu đề câu chuyện.');
        }

        if ($story === '') {
            throw new \InvalidArgumentException('Vui lòng nhập nội dung câu chuyện.');
        }

        if ($excerpt === '') {
            $excerpt = $this->excerptFrom($story, 200);
        }

        // Doan mo ta ngan gon o dau trang chi tiet (neu admin de trong thi lay excerpt).
        $summary = $this->textarea($payload['summary'] ?? '', self::MAX_SUMMARY);

        // Danh sach "nhung van de khach gap phai" - gui len dang mang hoac chuoi cach dong.
        $problems = $this->problems($payload['problems'] ?? []);

        $slugSource = trim((string) ($payload['slug'] ?? ''));

        if ($slugSource === '') {
            $slugSource = $title;
        }

        $status = ($payload['status'] ?? 'active') === 'hidden'
            ? 'hidden'
            : 'active';

        return [
            'slug' => $this->slugify($slugSource),
            'author_name' => $author,
            'location' => $this->text($payload['location'] ?? '', self::MAX_LOCATION),
            'title' => $title,
            'excerpt' => $excerpt,
            'story' => $story,
            'image' => $this->image($payload['image'] ?? ''),
            'story_image' => $this->image($payload['story_image'] ?? ''),
            'summary' => $summary === '' ? $excerpt : $summary,
            'highlight_price' => $this->text($payload['highlight_price'] ?? '', self::MAX_SHORT),
            'highlight_time' => $this->text($payload['highlight_time'] ?? '', self::MAX_SHORT),
            'highlight_fee' => $this->text($payload['highlight_fee'] ?? '', self::MAX_SHORT),
            'problems' => $problems,
            'status' => $status,
        ];
    }

    /**
     * Cho phep anh upload noi bo (/assets/...) hoac anh https ben ngoai.
     */
    private function image(mixed $value): string
    {
        $image = trim((string) $value);

        if ($image === '') {
            return '';
        }

        if (mb_strlen($image) > self::MAX_IMAGE) {
            return '';
        }

        if (
            str_starts_with($image, '/')
            && !str_starts_with($image, '//')
            && !str_contains($image, '..')
        ) {
            return $image;
        }

        if (!preg_match('#^https?://#i', $image)) {
            return '';
        }

        if (filter_var($image, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        // Chan javascript:/data: va cac scheme khac.
        $scheme = strtolower((string) parse_url($image, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $image : '';
    }

    private function excerptFrom(string $story, int $limit): string
    {
        $oneLine = trim((string) preg_replace('/\s+/u', ' ', $story));

        if (mb_strlen($oneLine) <= $limit) {
            return $oneLine;
        }

        return rtrim(mb_substr($oneLine, 0, $limit)) . '…';
    }

    /**
     * Danh sach "nhung van de khach hang gap phai" hien o dau trang chi tiet.
     * Chap nhan ca mang lan chuoi nhieu dong.
     *
     * @return array<int, string>
     */
    private function problems(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $problems = [];

        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $text = $this->text($item, self::MAX_PROBLEM);

            if ($text !== '') {
                $problems[] = $text;
            }

            if (count($problems) >= self::MAX_PROBLEMS) {
                break;
            }
        }

        return $problems;
    }

    private function text(mixed $value, int $limit): string
    {
        $text = trim((string) $value);

        // Bo the HTML, chi giu van ban thuan.
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));

        return mb_substr($text, 0, $limit);
    }

    private function textarea(mixed $value, int $limit): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $text = strip_tags($text);
        $text = trim((string) preg_replace("/\n{3,}/", "\n\n", $text));

        return mb_substr($text, 0, $limit);
    }

    private function slugify(string $value): string
    {
        $slug = mb_strtolower(trim($value), 'UTF-8');

        $map = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'e' => 'éèẻẽẹêếềểễệ',
            'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'u' => 'úùủũụưứừửữự',
            'y' => 'ýỳỷỹỵ',
            'd' => 'đ',
        ];

        foreach ($map as $replacement => $characters) {
            $slug = preg_replace(
                '/[' . $characters . ']/u',
                $replacement,
                $slug
            ) ?? $slug;
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;

        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function nextId(array $items): int
    {
        $max = 0;

        foreach ($items as $item) {
            $max = max($max, (int) $item['id']);
        }

        return $max + 1;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function uniqueSlug(string $slug, array $items, int $id): string
    {
        if ($slug === '') {
            $slug = 'cau-chuyen-' . $id;
        }

        $used = [];

        foreach ($items as $item) {
            $used[(string) $item['slug']] = true;
        }

        if (!isset($used[$slug])) {
            return $slug;
        }

        $suffix = 2;

        while (isset($used[$slug . '-' . $suffix])) {
            $suffix++;
        }

        return $slug . '-' . $suffix;
    }
}
