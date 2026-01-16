<?php

namespace Digiworld\DigiChat;

use Digiworld\DigiChat\Exceptions\DigiChatException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DigiChatManager
{
    protected string $token;
    protected string $secret;
    protected string $baseUrl;

    public function __construct()
    {
        $this->token  = (string) config('digichat.api_token');
        $this->secret = (string) config('digichat.api_secret');
        $this->baseUrl = rtrim((string) (config('digichat.base_url') ?? 'https://digichat.digiworld-dev.com/api'), '/');

        if ($this->token === '') {
            throw new DigiChatException('API token is not configured');
        }
        if ($this->secret === '') {
            throw new DigiChatException('API secret is not configured');
        }
    }

    public function ping(): array
    {
        return $this->get('ping');
    }

    /** Send a plain text message. */
    public function sendMessage(string $phoneNumber, string $message): array
    {
        $payload = array_filter([
            'phone'     => $phoneNumber,
            'message'   => $message,
        ], fn($v) => $v !== null);

        return $this->post('sendMessage', $payload);
    }


    /* -----------------------------------------------------------------
     |  Public: Session / QR / Profile
     | -----------------------------------------------------------------
     */

    public function getQr(): array
    {
        return $this->get('qr');
    }

    public function getStatus(): array
    {
        return $this->get('status');
    }

    public function logout(bool $withDeletion = false): array
    {
        return $this->post('logout', ['withDeletion' => $withDeletion]);
    }
    public function start(): array
    {
        return $this->post('start', []);
    }
    public function refresh($withDeletion = false): array
    {
        return $this->post('refresh', ['withDeletion' => $withDeletion]);
    }

    /* -----------------------------------------------------------------
     |  Internals
     | -----------------------------------------------------------------
     */

    protected function http(): PendingRequest
    {
        return Http::timeout(20);
    }

    protected function endpoint(string $action): string
    {
        // All routes are rooted at /api/whatsapp/{token}/...
        return "{$this->baseUrl}/whatsapp/{$this->token}/" . ltrim($action, '/');
    }

    protected function sign(array $payload): array
    {
        $timestamp   = Carbon::now()->timestamp;
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($jsonPayload === false) {
            throw new DigiChatException('Failed to json_encode payload: ' . json_last_error_msg());
        }

        $signature = hash_hmac('sha256', $timestamp . $this->token . $jsonPayload, $this->secret);

        return [
            'headers' => [
                'X-API-Token'     => $this->token,
                'X-API-Timestamp' => $timestamp,
                'X-API-Signature' => $signature,
                'Content-Type'    => 'application/json',
                'Accept'          => 'application/json',
            ],
            'body' => $jsonPayload,
        ];
    }


    /** Generic POST with signing + robust error handling. */
    protected function post(string $action, array $payload): array
    {
        $signed = $this->sign($payload);

        $res = Http::timeout(20)
            ->withHeaders($signed['headers'])
            ->withBody($signed['body'], 'application/json')
            ->post($this->endpoint($action));

        return $this->handle($res);
    }

    /** Generic GET with signing (sign over the effective query). */
    protected function get(string $action, array $query = []): array
    {
        $signed = $this->sign($query);

        $headers = $signed['headers'];
        $res = $this->http()->withHeaders($headers)->get($this->endpoint($action), $signed['body']);
        return $this->handle($res);
    }

    protected function handle(Response $response): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        // Try to surface server JSON error if present
        $body = null;
        try {
            $body = $response->json();
            // You can either throw or return a consistent error structure. Throwing is usually cleaner.
            throw new DigiChatException(sprintf(
                'DigiChat API error %d: %s',
                $response->status(),
                is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $response->body()
            ));
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
