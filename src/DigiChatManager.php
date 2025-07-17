<?php

namespace Digiworld\DigiChat;

use Digiworld\DigiChat\Exceptions\DigiChatException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DigiChatManager
{
    protected string $token;
    protected string $secret;
    protected string $baseUrl;

    public function __construct()
    {
        $this->token = config('digichat.api_token');
        $this->secret = config('digichat.api_secret');

        if (empty($this->token)) {
            throw new DigiChatException('API token is not configured');
        }
        if (empty($this->secret)) {
            throw new DigiChatException('API secret is not configured');
        }
        $this->baseUrl = "https://chat.digiworld-dev.com/api";
    }

    public function sendMessage(string $phoneNumber, string $message): array
    {
        $token =  $this->token;
        $timestamp = Carbon::now()->timestamp;
        $payload = [
            "phone" => $phoneNumber,
            "message" => $message,
        ];
        $jsonPayload = json_encode($payload);

        $signature = hash_hmac(
            'sha256',
            $timestamp . $token . $jsonPayload,
            $this->secret
        );

        $response = Http::withHeaders([
            'X-API-Token' => $token,
            'X-API-Timestamp' => $timestamp,
            'X-API-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->baseUrl}/whatsapp/{$token}/sendMessage", $payload);

        if ($response->failed()) {
            throw new DigiChatException(
                'API request failed: ' . $response->body(),
                $response->status(),
                $response->json() ?? []
            );
        }

        return $response->json();
    }
}
