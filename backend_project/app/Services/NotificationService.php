<?php

namespace App\Services;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification:
     * 1. Save it in the database
     * 2. Broadcast it live via Reverb (WebSocket)
     * 3. Push it to all devices of the user via FCM (if any)
     */
    public static function send(
        User $user,
        string $title,
        string $body,
        string $type,
        ?Model $referenceable = null
    ): Notification {
        // 1. Save notification in database
        $notification = Notification::create([
            'user_id'        => $user->id,
            'type'           => $type,
            'title'          => $title,
            'body'           => $body,
            'is_read'        => false,
            'reference_type' => $referenceable ? get_class($referenceable) : null,
            'reference_id'   => $referenceable?->id,
        ]);

        // 2. Live WebSocket broadcast (Reverb)
        event(new NewNotification($notification));

        // 3. Get all FCM tokens of this user
        $tokens = UserDevice::where('user_id', $user->id)
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values();

        // 4. Send push to every device
        foreach ($tokens as $token) {
            try {
                self::sendFcmPush($token, $title, $body, [
                    'notification_id' => (string) $notification->id,
                    'type'            => $type,
                    'reference_type'  => $notification->reference_type ?? '',
                    'reference_id'    => (string) ($notification->reference_id ?? ''),
                ]);
            } catch (\Throwable $e) {
                Log::error('FCM push failed', [
                    'user_id' => $user->id,
                    'token'   => substr($token, 0, 25) . '...',
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }

    /**
     * Send one push notification using FCM HTTP v1
     */
    protected static function sendFcmPush(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): void {
        $accessToken = self::getAccessToken();
        $projectId   = config('services.firebase.project_id');

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('FCM HTTP error', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            throw new \RuntimeException('FCM send failed: ' . $response->body());
        }
    }

    /**
     * Get a valid OAuth2 access token from the service account
     * Cached for 50 minutes (token is valid for 60 minutes)
     */
    protected static function getAccessToken(): string
    {
        return Cache::remember('firebase_access_token', 50 * 60, function () {
            $credentialsPath = base_path(config('services.firebase.credentials'));

            if (!file_exists($credentialsPath)) {
                throw new \RuntimeException(
                    "Firebase credentials file not found at: {$credentialsPath}"
                );
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
                throw new \RuntimeException('Invalid Firebase service account JSON');
            }

            $now = time();

            $header = self::base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $claim = self::base64UrlEncode(json_encode([
                'iss'   => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $unsignedJwt = $header . '.' . $claim;

            $privateKey = openssl_pkey_get_private($credentials['private_key']);
            if (!$privateKey) {
                throw new \RuntimeException('Invalid private key in Firebase credentials');
            }

            openssl_sign($unsignedJwt, $signature, $privateKey, OPENSSL_ALGO_SHA256);

            $jwt = $unsignedJwt . '.' . self::base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->failed()) {
                throw new \RuntimeException(
                    'Failed to obtain Firebase access token: ' . $response->body()
                );
            }

            $accessToken = $response->json('access_token');

            if (empty($accessToken)) {
                throw new \RuntimeException('No access_token returned from Google');
            }

            return $accessToken;
        });
    }

    /**
     * Base64 URL-safe encoding (required for JWT)
     */
    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}