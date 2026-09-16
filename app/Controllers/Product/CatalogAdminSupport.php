<?php
declare(strict_types=1);
namespace App\Controllers\Product;

use App\Core\Auth;
use App\Core\JsonResponse;

/**
 * Shared helpers for the admin catalog APIs (brands / models / versions).
 */
trait CatalogAdminSupport
{
    private function requireAdminApi(): array
    {
        return Auth::requireAdmin(true);
    }

    private function requestData(): array
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($ct, 'application/json')) {
            $d = json_decode(file_get_contents('php://input'), true);
            return is_array($d) ? $d : [];
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') return $_POST;
        $raw = file_get_contents('php://input');
        $d = [];
        parse_str($raw, $d);
        return $d;
    }

    private function slugify(string $v): string
    {
        $v = trim(mb_strtolower($v, 'UTF-8'));
        if (function_exists('iconv')) {
            $x = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $v);
            if ($x !== false) $v = $x;
        }
        $v = preg_replace('/[^a-z0-9]+/', '-', $v) ?? '';
        return trim($v, '-');
    }
}
