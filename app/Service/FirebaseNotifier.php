<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Gui thong bao push qua Firebase Cloud Messaging (FCM HTTP v1).
 *
 * Cau hinh qua .env:
 *   FIREBASE_CREDENTIALS=/duong/dan/service-account.json
 *   FIREBASE_PROJECT_ID=your-project-id
 *
 * Neu chua cau hinh -> ham sendToUser() bo qua (khong nem loi),
 * de logic nghiep vu dau gia van chay binh thuong.
 *
 * Token thiet bi luu o bang `user_devices` (user_id, fcm_token).
 * Neu bang chua ton tai -> bo qua.
 */
class FirebaseNotifier
{
    private ?string $projectId;

    private ?string $credentialsPath;

    private ?string $accessToken = null;

    private int $accessTokenExpiresAt = 0;

    public function __construct()
    {
        $this->projectId = $this->env('FIREBASE_PROJECT_ID');

        $this->credentialsPath = $this->env('FIREBASE_CREDENTIALS');
    }

    /**
     * Gui push toi tat ca thiet bi cua mot user.
     *
     * @param array<string,string> $data
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ): int {
        $tokens = $this->tokensForUser($userId);

        if ($tokens === []) {
            return 0;
        }

        $sent = 0;

        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Lay danh sach FCM token cua user. Tra ve [] neu chua co bang.
     */
    private function tokensForUser(int $userId): array
    {
        try {
            $db = Database::connection();

            $stmt = $db->prepare("
                SELECT fcm_token
                FROM user_devices
                WHERE user_id = :user_id
            ");

            $stmt->execute(['user_id' => $userId]);

            $tokens = [];

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $token = trim((string) ($row['fcm_token'] ?? ''));

                if ($token !== '') {
                    $tokens[] = $token;
                }
            }

            return $tokens;
        } catch (Throwable $e) {
            // Bang chua ton tai hoac loi ket noi -> bo qua.
            return [];
        }
    }

    /**
     * Gui mot message toi mot token qua FCM HTTP v1.
     */
    private function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data
    ): bool {
        if (!$this->isConfigured()) {
            return false;
        }

        $accessToken = $this->accessToken();

        if ($accessToken === null) {
            return false;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/'
            . rawurlencode((string) $this->projectId)
            . '/messages:send';

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->stringifyData($data),
            ],
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json; charset=utf-8',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return true;
        }

        error_log(
            'FCM send failed [' . $status . ']: ' . (string) $response
        );

        return false;
    }

    /**
     * Lay access token (OAuth2) tu service account, co cache theo thoi gian song.
     */
    private function accessToken(): ?string
    {
        if (
            $this->accessToken !== null
            && time() < $this->accessTokenExpiresAt - 60
        ) {
            return $this->accessToken;
        }

        $credentials = $this->loadCredentials();

        if ($credentials === null) {
            return null;
        }

        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwt = $this->signJwt($header, $claims, $credentials['private_key']);

        if ($jwt === null) {
            return null;
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            error_log('FCM token request failed [' . $status . ']: ' . (string) $response);

            return null;
        }

        $decoded = json_decode((string) $response, true);

        $token = $decoded['access_token'] ?? null;

        if (!is_string($token) || $token === '') {
            return null;
        }

        $this->accessToken = $token;

        $this->accessTokenExpiresAt = $now
            + (int) ($decoded['expires_in'] ?? 3600);

        return $token;
    }

    private function signJwt(
        array $header,
        array $claims,
        string $privateKey
    ): ?string {
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];

        $signingInput = implode('.', $segments);

        $signature = '';

        $ok = openssl_sign(
            $signingInput,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$ok) {
            return null;
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @return array{client_email:string,private_key:string}|null
     */
    private function loadCredentials(): ?array
    {
        if ($this->credentialsPath === null) {
            return null;
        }

        $path = $this->credentialsPath;

        if (!is_file($path)) {
            error_log('FCM credentials file not found: ' . $path);

            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (
            !is_array($decoded)
            || empty($decoded['client_email'])
            || empty($decoded['private_key'])
        ) {
            error_log('FCM credentials file is invalid: ' . $path);

            return null;
        }

        return [
            'client_email' => (string) $decoded['client_email'],
            'private_key' => (string) $decoded['private_key'],
        ];
    }

    private function isConfigured(): bool
    {
        return $this->projectId !== null
            && $this->credentialsPath !== null;
    }

    /**
     * FCM data payload chi nhan string.
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    private function stringifyData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[(string) $key] = (string) $value;
        }

        return $result;
    }

    private function env(string $key): ?string
    {
        $value = $_ENV[$key] ?? getenv($key) ?: null;

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
