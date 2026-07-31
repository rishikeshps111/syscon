<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseMessaging
{
    public function send(
        string $deviceToken,
        string $title,
        string $body,
        array $data = [],
        string $appType = 'operations'
    ): Response
    {
        $appConfig = config("services.firebase.apps.{$appType}", []);
        $credentials = $this->credentials($appType, $appConfig['credentials'] ?? null);
        $projectId = ($appConfig['project_id'] ?? null)
            ?: config('services.firebase.project_id')
            ?: ($credentials['project_id'] ?? null);

        if (! $projectId) {
            throw new RuntimeException("Firebase project ID is not configured for the {$appType} app.");
        }

        return Http::withToken($this->accessToken($credentials, $appType, $projectId))
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => collect($data)->map(fn($value) => (string) $value)->all(),
                    'android' => ['priority' => 'high'],
                    'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                ],
            ]);
    }

    private function accessToken(array $credentials, string $appType, string $projectId): string
    {
        $cacheKey = 'firebase_access_token_' . sha1(
            $appType . '|' . $projectId . '|' . (string) ($credentials['client_email'] ?? '')
        );

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): string {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));

            if (! openssl_sign("{$header}.{$claims}", $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Could not sign the Firebase service-account JWT.');
            }

            $assertion = "{$header}.{$claims}." . $this->base64Url($signature);
            $response = Http::asForm()->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ])->throw();

            return (string) $response->json('access_token');
        });
    }

    private function credentials(string $appType, ?string $configuredPath): array
    {
        $path = $configuredPath ?: config('services.firebase.credentials');

        if (! $path || ! is_file($path)) {
            throw new RuntimeException(
                "Firebase credentials for the {$appType} app must point to a readable service-account JSON file."
            );
        }

        $credentials = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('The Firebase service-account file is invalid.');
        }

        return $credentials;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
