<?php

namespace Digiworld\DigiChat;

use Carbon\Carbon;
use Digiworld\DigiChat\Contracts\DigiChatContract;
use Digiworld\DigiChat\Exceptions\DigiChatException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * What: Main DigiChat API client used by the package facade and container binding.
 * When: Resolved whenever a Laravel app needs to send WhatsApp content or manage the DigiChat session.
 * Why: Centralizing signing, validation, normalization, and response handling keeps the package behavior consistent.
 */
class DigiChatManager implements DigiChatContract
{
    protected string $token;
    protected string $secret;

    private const API_BASE_URL = 'https://digichat.digiworld-dev.com/api';
    private const CONTACT_SUFFIX = '@c.us';
    private const GROUP_SUFFIX = '@g.us';
    private const NEWSLETTER_SUFFIX = '@newsletter';
    private const INVALID_NEWSLETTER_SUFFIX = '@newsletters';
    private const TARGET_CONTACT = 'contact';
    private const TARGET_GROUP = 'group';
    private const TARGET_CHANNEL = 'channel';
    private const TYPE_TEXT = 'text';
    private const TYPE_MEDIA = 'media';
    private const TYPE_FILE = 'file';
    private const SEND_TEXT_ACTION = 'sendMessage';
    private const SEND_MEDIA_ACTION = 'sendMedia';

    /**
     * What: Loads the credentials required to talk to the DigiChat API, with optional runtime overrides.
     * When: Called automatically when the default client is created or manually when building a custom session client.
     * Why: Supporting constructor overrides lets one Laravel project use multiple DigiChat sessions without losing config defaults.
     */
    public function __construct(?string $token = null, ?string $secret = null)
    {
        $this->token = $token ?? (string) config('digichat.api_token');
        $this->secret = $secret ?? (string) config('digichat.api_secret');

        if ($this->token === '') {
            throw new DigiChatException('API token is not configured');
        }

        if ($this->secret === '') {
            throw new DigiChatException('API secret is not configured');
        }
    }

    /**
     * What: Creates a fresh DigiChat client for another session token and secret.
     * When: Use this when the current application needs to send through more than one DigiChat session.
     * Why: Returning a new manager instance avoids mutating the default config-backed client and keeps session credentials isolated.
     */
    public function session(?string $token = null, ?string $secret = null): self
    {
        return new self($token, $secret);
    }

    /**
     * What: Checks whether the DigiChat API is reachable.
     * When: Use this for connectivity checks or lightweight health verification.
     * Why: A ping request is the safest way to confirm credentials and API availability without changing session state.
     */
    public function ping(): array
    {
        return $this->get('ping');
    }

    /**
     * What: Sends a canonical or backward-compatible payload through the unified package send pipeline.
     * When: Use this when the caller already has a payload and wants one entry point for text, media, or file messages.
     * Why: One normalized send path keeps old aliases working while new outbound requests follow the current SaaS contract.
     */
    public function send(array $payload): array
    {
        try {
            $canonicalPayload = $this->normalizeSendPayload($payload);

            return $this->post(
                $this->resolveSendAction($canonicalPayload['type']),
                $canonicalPayload
            );
        } catch (DigiChatException $e) {
            return $this->formatException($e);
        }
    }

    /**
     * What: Sends a plain text message to a contact using the legacy helper signature.
     * When: Use this when existing package consumers still call `sendMessage($phoneNumber, $message)`.
     * Why: Preserving this method avoids a breaking change while the package internally emits canonical send fields.
     */
    public function sendMessage(string $phoneNumber, string $message): array
    {
        return $this->sendText($phoneNumber, $message);
    }

    /**
     * What: Sends a text message to a contact, group, or newsletter target.
     * When: Use this for simple text sends where the destination is known as a chat ID.
     * Why: A dedicated text helper keeps common sends ergonomic while still feeding the shared validation pipeline.
     */
    public function sendText(string $chatId, string $text, array $options = []): array
    {
        return $this->send(array_merge($options, [
            'chatId' => $chatId,
            'type' => self::TYPE_TEXT,
            'text' => $text,
        ]));
    }

    /**
     * What: Sends media content such as images, video, or audio to a supported target.
     * When: Use this when the message should carry renderable media with an optional caption.
     * Why: Separating media sends from file sends keeps intent clear and lets the package enforce channel rules early.
     */
    public function sendMedia(string $chatId, array|string $media, ?string $caption = null, array $options = []): array
    {
        return $this->send(array_merge($options, [
            'chatId' => $chatId,
            'type' => self::TYPE_MEDIA,
            'media' => $media,
            'caption' => $caption,
        ]));
    }

    /**
     * What: Sends a non-renderable file payload to a supported contact or group target.
     * When: Use this when the media should be treated as a downloadable file rather than inline media.
     * Why: File sends have different target rules, especially for newsletters, so they need an explicit helper.
     */
    public function sendFile(string $chatId, array|string $media, ?string $caption = null, array $options = []): array
    {
        return $this->send(array_merge($options, [
            'chatId' => $chatId,
            'type' => self::TYPE_FILE,
            'media' => $media,
            'caption' => $caption,
        ]));
    }

    /**
     * What: Fetches the current QR payload for the WhatsApp session.
     * When: Use this while pairing a new or disconnected session.
     * Why: The QR endpoint lets consumers complete authentication without managing the lower-level API call.
     */
    public function getQr(): array
    {
        return $this->get('qr');
    }

    /**
     * What: Reads the current connection status of the DigiChat WhatsApp session.
     * When: Use this before sends or in dashboards that need to know whether the session is ready.
     * Why: Surfacing status through the client avoids duplicated request logic across consuming applications.
     */
    public function getStatus(): array
    {
        return $this->get('status');
    }

    /**
     * What: Logs out the current WhatsApp session.
     * When: Use this when the session must be disconnected, optionally with remote data cleanup.
     * Why: Exposing logout through the package gives applications a controlled way to reset a linked device.
     */
    public function logout(bool $withDeletion = false): array
    {
        return $this->post('logout', ['withDeletion' => $withDeletion]);
    }

    /**
     * What: Starts or wakes the DigiChat WhatsApp session.
     * When: Use this when the SaaS requires an explicit start request before status or messaging.
     * Why: Packaging the start call keeps session lifecycle operations available alongside messaging.
     */
    public function start(): array
    {
        return $this->post('start', []);
    }

    /**
     * What: Refreshes the current WhatsApp session.
     * When: Use this when the session should be restarted or rebuilt, optionally with deletion.
     * Why: A first-class refresh helper lets consumers recover a session without composing raw API requests.
     */
    public function refresh(bool $withDeletion = false): array
    {
        return $this->post('refresh', ['withDeletion' => $withDeletion]);
    }

    /**
     * What: Resolves invite metadata for a WhatsApp group invite code.
     * When: Use this before joining or showing information about a shared invite.
     * Why: Keeping the lookup here gives applications a typed package entry point instead of manual request code.
     */
    public function getInviteInfo(string $inviteCode): array
    {
        return $this->post('invite-info', ['inviteCode' => $inviteCode]);
    }

    /**
     * What: Resolves WhatsApp newsletter or channel information from an invite code or invite link.
     * When: Use this before displaying channel details or validating whether a channel invite is valid.
     * Why: This keeps channel info lookup as a thin SaaS pass-through without forcing consumers to build raw requests.
     */
    public function getChannelInfo(array|string $invite): array
    {
        try {
            return $this->post('channel-info', $this->normalizeChannelInfoPayload($invite));
        } catch (DigiChatException $e) {
            return $this->formatException($e);
        }
    }

    /**
     * What: Builds the shared HTTP client used for all outgoing requests.
     * When: Called internally before GET or POST calls are sent.
     * Why: A single client factory keeps timeout behavior aligned across the package.
     */
    protected function http(): PendingRequest
    {
        return Http::timeout(20);
    }

    /**
     * What: Builds the full DigiChat API endpoint for a given action.
     * When: Called right before an HTTP request is dispatched.
     * Why: Keeping route construction in one place ensures the package always talks only to the DigiChat SaaS API.
     */
    protected function endpoint(string $action): string
    {
        return self::API_BASE_URL . "/whatsapp/{$this->token}/" . ltrim($action, '/');
    }

    /**
     * What: Signs a JSON payload using the DigiChat token and secret headers expected by the API.
     * When: Called before every signed GET or POST request.
     * Why: Request signing is mandatory for the public API and must stay identical across all package calls.
     */
    protected function sign(array $payload): array
    {
        $timestamp = Carbon::now()->timestamp;
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($jsonPayload === false) {
            throw new DigiChatException('Failed to json_encode payload: ' . json_last_error_msg());
        }

        $signature = hash_hmac('sha256', $timestamp . $this->token . $jsonPayload, $this->secret);

        return [
            'headers' => [
                'X-API-Token' => $this->token,
                'X-API-Timestamp' => $timestamp,
                'X-API-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => $jsonPayload,
        ];
    }

    /**
     * What: Sends a signed POST request to the DigiChat API.
     * When: Used for send actions and session operations that mutate or resolve state.
     * Why: Wrapping POST behavior here guarantees consistent signing, transport settings, and error formatting.
     */
    protected function post(string $action, array $payload): array
    {
        try {
            $signed = $this->sign($payload);

            $response = $this->http()
                ->withHeaders($signed['headers'])
                ->withBody($signed['body'], 'application/json')
                ->post($this->endpoint($action));

            return $this->handle($response);
        } catch (DigiChatException $e) {
            return $this->formatException($e);
        }
    }

    /**
     * What: Sends a signed GET request to the DigiChat API.
     * When: Used for read-only session or health endpoints.
     * Why: Centralizing GET requests keeps authentication and response handling identical to POST requests.
     */
    protected function get(string $action, array $query = []): array
    {
        try {
            $signed = $this->sign($query);

            $response = $this->http()
                ->withHeaders($signed['headers'])
                ->get($this->endpoint($action), $query);

            return $this->handle($response);
        } catch (DigiChatException $e) {
            return $this->formatException($e);
        }
    }

    /**
     * What: Normalizes successful and failed API responses into a consistent array shape.
     * When: Called after each HTTP request returns from the server.
     * Why: Consumers need one predictable response structure without manually decoding Laravel response objects.
     */
    protected function handle(Response $response): array
    {
        $body = $response->json();

        if ($response->successful()) {
            return is_array($body)
                ? $body
                : [
                    'success' => true,
                    'data' => $body,
                ];
        }

        $payload = is_array($body) ? $body : [];
        $payload['success'] = false;
        $payload['status'] = $response->status();

        if (! isset($payload['error']) || ! is_string($payload['error']) || $payload['error'] === '') {
            $payload['error'] = $this->defaultErrorCode($response->status());
        }

        if (! isset($payload['message']) || ! is_string($payload['message']) || $payload['message'] === '') {
            $payload['message'] = $this->defaultErrorMessage($payload['error'], $response->status());
        }

        return $payload;
    }

    /**
     * What: Converts legacy aliases and helper input into one validated canonical send payload.
     * When: Called before every message request leaves the package.
     * Why: Normalizing once prevents drift between helpers and ensures the SaaS receives the preferred field names.
     */
    protected function normalizeSendPayload(array $payload): array
    {
        $payload = $this->normalizeAliases($payload);

        $chatId = $payload['chatId'] ?? null;
        if (! is_string($chatId) || trim($chatId) === '') {
            throw $this->validationException(
                'A valid chatId is required.',
                ['error' => 'INVALID_CHAT_ID']
            );
        }

        $payload['chatId'] = $this->normalizeChatId($chatId);
        $targetType = $this->detectTargetType($payload['chatId']);

        if (array_key_exists('media', $payload) && $payload['media'] !== null) {
            $payload['media'] = $this->normalizeMediaPayload($payload['media']);
        }

        $payload['type'] = $this->normalizeMessageType(
            $payload['type'] ?? null,
            $payload['media'] ?? null
        );

        if ($payload['type'] === self::TYPE_TEXT) {
            if (array_key_exists('media', $payload) && $payload['media'] !== null) {
                throw $this->validationException(
                    'Text messages cannot include a media payload.',
                    [
                        'error' => 'INVALID_MEDIA_PAYLOAD',
                        'contentType' => $payload['type'],
                    ]
                );
            }

            $payload['text'] = $this->normalizeTextPayload($payload['text'] ?? null);
            unset($payload['caption'], $payload['media']);
        } else {
            if (! isset($payload['media']) || ! is_array($payload['media'])) {
                throw $this->validationException(
                    'Media and file messages require a media payload.',
                    [
                        'error' => 'INVALID_MEDIA_PAYLOAD',
                        'contentType' => $payload['type'],
                    ]
                );
            }

            if (! array_key_exists('caption', $payload) && array_key_exists('text', $payload)) {
                $payload['caption'] = $payload['text'];
            }

            if (array_key_exists('caption', $payload) && $payload['caption'] !== null) {
                if (! is_string($payload['caption'])) {
                    throw $this->validationException(
                        'Caption must be a string when provided.',
                        ['error' => 'INVALID_CAPTION']
                    );
                }

                if (trim($payload['caption']) === '') {
                    $payload['caption'] = null;
                }
            }

            unset($payload['text']);
        }

        $this->validateTargetMessageType($targetType, $payload['type'], $payload['chatId']);

        unset($payload['phone'], $payload['message'], $payload['window_ms'], $payload['idempotency_key']);

        return array_filter($payload, static fn ($value) => $value !== null);
    }

    /**
     * What: Maps backward-compatible request aliases to the canonical DigiChat send keys.
     * When: Used at the start of payload normalization.
     * Why: Supporting the old field names keeps existing integrations working while new code uses current names.
     */
    protected function normalizeAliases(array $payload): array
    {
        if (! array_key_exists('chatId', $payload) && array_key_exists('phone', $payload)) {
            $payload['chatId'] = $payload['phone'];
        }

        if (! array_key_exists('text', $payload) && array_key_exists('message', $payload)) {
            $payload['text'] = $payload['message'];
        }

        if (! array_key_exists('windowMs', $payload) && array_key_exists('window_ms', $payload)) {
            $payload['windowMs'] = $payload['window_ms'];
        }

        if (! array_key_exists('idempotencyKey', $payload) && array_key_exists('idempotency_key', $payload)) {
            $payload['idempotencyKey'] = $payload['idempotency_key'];
        }

        return $payload;
    }

    /**
     * What: Converts invite link or invite code input into the channel-info request shape expected by the SaaS API.
     * When: Called before the package sends a `channel-info` request.
     * Why: Keeping this normalization small and local preserves thin pass-through behavior while still preventing empty requests.
     */
    protected function normalizeChannelInfoPayload(array|string $invite): array
    {
        if (is_string($invite)) {
            return $this->channelInfoPayloadFromString($invite);
        }

        $inviteLink = $invite['inviteLink'] ?? null;
        if (is_string($inviteLink) && trim($inviteLink) !== '') {
            return ['inviteLink' => trim($inviteLink)];
        }

        $inviteCode = $invite['inviteCode'] ?? null;
        if (is_string($inviteCode) && trim($inviteCode) !== '') {
            return ['inviteCode' => trim($inviteCode)];
        }

        throw $this->validationException(
            'Channel info requests require a non-empty inviteLink or inviteCode.',
            ['error' => 'INVALID_CHANNEL_INFO_REQUEST']
        );
    }

    /**
     * What: Detects whether a string input is a channel invite link or a raw invite code.
     * When: Used when `getChannelInfo()` receives a plain string instead of an explicit payload array.
     * Why: Auto-detecting the field keeps the public helper ergonomic while still sending the exact SaaS request contract.
     */
    protected function channelInfoPayloadFromString(string $invite): array
    {
        $invite = trim($invite);
        if ($invite === '') {
            throw $this->validationException(
                'Channel info requests require a non-empty inviteLink or inviteCode.',
                ['error' => 'INVALID_CHANNEL_INFO_REQUEST']
            );
        }

        return filter_var($invite, FILTER_VALIDATE_URL) !== false
            ? ['inviteLink' => $invite]
            : ['inviteCode' => $invite];
    }

    /**
     * What: Normalizes a destination into a valid contact, group, or newsletter chat ID.
     * When: Used whenever a send request provides a target.
     * Why: Enforcing one chat ID format early lets the package validate target rules before making API calls.
     */
    protected function normalizeChatId(string $chatId): string
    {
        $chatId = trim($chatId);

        if (str_ends_with($chatId, self::GROUP_SUFFIX)) {
            if (! preg_match('/^\d+@g\.us$/', $chatId)) {
                throw $this->validationException(
                    'Group chatId values must match the format 123456789@g.us.',
                    ['error' => 'INVALID_CHAT_ID']
                );
            }

            return $chatId;
        }

        if (str_ends_with($chatId, self::INVALID_NEWSLETTER_SUFFIX)) {
            throw $this->validationException(
                'Newsletter chatIds must use the @newsletter suffix.',
                ['error' => 'INVALID_CHAT_ID']
            );
        }

        if (str_ends_with($chatId, self::NEWSLETTER_SUFFIX)) {
            if (! preg_match('/^\d+@newsletter$/', $chatId)) {
                throw $this->validationException(
                    'Newsletter chatId values must match the format 123456789@newsletter.',
                    ['error' => 'INVALID_CHAT_ID']
                );
            }

            return $chatId;
        }

        if (str_contains($chatId, '@') && ! str_ends_with($chatId, self::CONTACT_SUFFIX)) {
            throw $this->validationException(
                'Contact chatIds may use raw digits or the @c.us suffix.',
                ['error' => 'INVALID_CHAT_ID']
            );
        }

        $contactValue = str_ends_with($chatId, self::CONTACT_SUFFIX)
            ? substr($chatId, 0, -strlen(self::CONTACT_SUFFIX))
            : $chatId;
        $contactValue = ltrim($contactValue, '+');

        $digits = preg_replace('/\D+/', '', $contactValue) ?? '';
        if ($digits === '') {
            throw $this->validationException(
                'Contact chatIds must contain digits.',
                ['error' => 'INVALID_CHAT_ID']
            );
        }

        return $digits;
    }

    /**
     * What: Detects whether a chat ID points to a contact, group, or newsletter.
     * When: Called after a chat ID has been normalized.
     * Why: Knowing the target type allows the package to enforce content rules before sending.
     */
    protected function detectTargetType(string $chatId): string
    {
        return match (true) {
            preg_match('/^\d+@g\.us$/', $chatId) === 1 => self::TARGET_GROUP,
            preg_match('/^\d+@newsletter$/', $chatId) === 1 => self::TARGET_CHANNEL,
            preg_match('/^\d+$/', $chatId) === 1,
            preg_match('/^\d+@c\.us$/', $chatId) === 1 => self::TARGET_CONTACT,
            default => throw $this->validationException(
                'Unable to determine the target type from the provided chatId.',
                ['error' => 'INVALID_CHAT_ID']
            ),
        };
    }

    /**
     * What: Resolves the effective message type from explicit input or the media mimetype.
     * When: Used while building the canonical send payload.
     * Why: This keeps omitted `type` values backward-compatible without letting unsupported values pass through.
     */
    protected function normalizeMessageType(mixed $type, ?array $media): string
    {
        if (! is_string($type) || trim($type) === '') {
            return $media === null
                ? self::TYPE_TEXT
                : $this->inferMessageTypeFromMedia($media);
        }

        $type = strtolower(trim($type));
        if (! in_array($type, [self::TYPE_TEXT, self::TYPE_MEDIA, self::TYPE_FILE], true)) {
            throw $this->validationException(
                'Unsupported message type. Allowed values are text, media, and file.',
                [
                    'error' => 'UNSUPPORTED_MESSAGE_TYPE',
                    'contentType' => $type,
                ]
            );
        }

        return $type;
    }

    /**
     * What: Validates that a text payload contains non-empty text.
     * When: Called for text messages during send normalization.
     * Why: Rejecting empty text locally avoids unnecessary API round-trips for invalid sends.
     */
    protected function normalizeTextPayload(mixed $text): string
    {
        if (! is_string($text)) {
            throw $this->validationException(
                'Text messages require a text value.',
                ['error' => 'INVALID_TEXT']
            );
        }

        if (trim($text) === '') {
            throw $this->validationException(
                'Text messages cannot be empty.',
                ['error' => 'INVALID_TEXT']
            );
        }

        return $text;
    }

    /**
     * What: Converts media input into the canonical DigiChat media array structure.
     * When: Used for media and file sends before request signing.
     * Why: Supporting arrays and file paths keeps the API ergonomic without sacrificing a single outbound payload shape.
     */
    protected function normalizeMediaPayload(array|string $media): array
    {
        if (is_string($media)) {
            return $this->mediaPayloadFromPath($media);
        }

        if (isset($media['path']) && is_string($media['path'])) {
            return $this->mediaPayloadFromPath($media['path']);
        }

        $normalized = [
            'mimetype' => $media['mimetype'] ?? $media['mimeType'] ?? $media['mime_type'] ?? null,
            'filename' => $media['filename'] ?? $media['name'] ?? null,
            'base64' => $media['base64'] ?? $media['data'] ?? null,
        ];

        foreach (['mimetype', 'filename', 'base64'] as $key) {
            if (! isset($normalized[$key]) || ! is_string($normalized[$key]) || trim($normalized[$key]) === '') {
                throw $this->validationException(
                    'Media payloads must include non-empty mimetype, filename, and base64 values.',
                    ['error' => 'INVALID_MEDIA_PAYLOAD']
                );
            }
        }

        return $normalized;
    }

    /**
     * What: Reads a local file and converts it into the canonical media array.
     * When: Used when a caller passes a file path instead of a prepared media array.
     * Why: This keeps the public API easy to use while ensuring the outbound payload always matches the SaaS contract.
     */
    protected function mediaPayloadFromPath(string $path): array
    {
        $path = trim($path);
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            throw $this->validationException(
                'Media path must point to a readable file.',
                ['error' => 'INVALID_MEDIA_PAYLOAD']
            );
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw $this->validationException(
                'Unable to read the provided media file.',
                ['error' => 'INVALID_MEDIA_PAYLOAD']
            );
        }

        $mimetype = mime_content_type($path) ?: 'application/octet-stream';

        return [
            'mimetype' => $mimetype,
            'filename' => basename($path),
            'base64' => base64_encode($contents),
        ];
    }

    /**
     * What: Infers whether media should be sent as `media` or `file`.
     * When: Used when the caller omitted `type` but supplied a media payload.
     * Why: Matching the content type to the mimetype preserves backward compatibility with smarter defaults.
     */
    protected function inferMessageTypeFromMedia(array $media): string
    {
        $mimetype = strtolower((string) ($media['mimetype'] ?? ''));
        if ($mimetype === '') {
            throw $this->validationException(
                'Unable to infer the message type because the media mimetype is missing.',
                ['error' => 'INVALID_MEDIA_PAYLOAD']
            );
        }

        return match (true) {
            str_starts_with($mimetype, 'image/'),
            str_starts_with($mimetype, 'video/'),
            str_starts_with($mimetype, 'audio/') => self::TYPE_MEDIA,
            default => self::TYPE_FILE,
        };
    }

    /**
     * What: Enforces which message types each target type is allowed to receive.
     * When: Called after the target type and message type are known.
     * Why: Rejecting unsupported combinations locally gives package users faster and clearer feedback.
     */
    protected function validateTargetMessageType(string $targetType, string $messageType, string $chatId): void
    {
        if ($targetType === self::TARGET_CHANNEL && $messageType === self::TYPE_FILE) {
            throw $this->validationException(
                'Newsletters/channels only support text and media messages.',
                [
                    'error' => 'CHANNEL_UNSUPPORTED_MESSAGE_TYPE',
                    'chatId' => $chatId,
                    'targetType' => $targetType,
                    'contentType' => $messageType,
                ]
            );
        }
    }

    /**
     * What: Maps a normalized content type to the correct DigiChat SaaS public route.
     * When: Called immediately before the HTTP POST request is made.
     * Why: The package must keep using the SaaS public routes while hiding route selection from consumers.
     */
    protected function resolveSendAction(string $messageType): string
    {
        return $messageType === self::TYPE_TEXT
            ? self::SEND_TEXT_ACTION
            : self::SEND_MEDIA_ACTION;
    }

    /**
     * What: Creates a DigiChat exception that carries structured validation details.
     * When: Used whenever package-side validation needs to stop a request.
     * Why: A dedicated helper keeps all local validation failures shaped the same way.
     */
    protected function validationException(string $message, array $details = [], int $code = 422): DigiChatException
    {
        return new DigiChatException($message, $code, $details);
    }

    /**
     * What: Converts an internal DigiChat exception into the package's array response style.
     * When: Called when validation or request-preparation fails before a normal API response is returned.
     * Why: Consumers should receive one response format whether the failure happened locally or remotely.
     */
    protected function formatException(DigiChatException $exception): array
    {
        $details = $exception->getDetails();
        $status = $exception->getCode() > 0 ? $exception->getCode() : 422;

        return array_filter([
            'success' => false,
            'status' => $status,
            'error' => $details['error'] ?? $this->defaultErrorCode($status),
            'message' => $exception->getMessage(),
            'chatId' => $details['chatId'] ?? null,
            'targetType' => $details['targetType'] ?? null,
            'contentType' => $details['contentType'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    /**
     * What: Provides a default package error code for HTTP responses that did not return one.
     * When: Used while normalizing failed API responses.
     * Why: Consistent fallback error codes make it easier for consumers to branch on failure types.
     */
    protected function defaultErrorCode(int $status): string
    {
        return match ($status) {
            404 => 'NO_SESSION',
            422 => 'DIGICHAT_VALIDATION_ERROR',
            default => 'DIGICHAT_REQUEST_FAILED',
        };
    }

    /**
     * What: Provides a human-readable fallback message for common DigiChat error states.
     * When: Used when the API response did not include its own message text.
     * Why: Clear default messages help package consumers debug failures without inspecting raw transport details.
     */
    protected function defaultErrorMessage(string $error, int $status): string
    {
        return match ($error) {
            'RECIPIENT_NOT_REGISTERED' => 'The target contact is not registered on WhatsApp.',
            'CHANNEL_UNSUPPORTED_MESSAGE_TYPE' => 'Newsletters/channels only support text and media messages.',
            'NO_SESSION' => 'DigiChat session was not found or is not paired.',
            default => match ($status) {
                404 => 'DigiChat session was not found or is not paired.',
                422 => 'DigiChat rejected the request.',
                default => sprintf('DigiChat API error %d', $status),
            },
        };
    }
}
