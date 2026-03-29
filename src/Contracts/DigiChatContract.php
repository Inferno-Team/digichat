<?php

namespace Digiworld\DigiChat\Contracts;

/**
 * What: Public contract for the DigiChat client exposed by the package.
 * When: Used by the service container and consumers that depend on an interface instead of the concrete manager.
 * Why: Keeping the public API in one contract makes the package easier to understand and extend safely.
 */
interface DigiChatContract
{
    /**
     * What: Sends a canonical or backward-compatible payload through the unified send pipeline.
     * When: Use this when the caller already has a prepared payload.
     * Why: This is the lowest-level public send entry point for advanced integrations.
     */
    public function send(array $payload): array;

    /**
     * What: Sends a text message to a contact, group, or newsletter.
     * When: Use this for normal text sends.
     * Why: It keeps the most common message type concise and explicit.
     */
    public function sendText(string $chatId, string $text, array $options = []): array;

    /**
     * What: Sends media content with an optional caption.
     * When: Use this for image, video, or audio style sends.
     * Why: It clearly communicates that the content should be treated as inline media.
     */
    public function sendMedia(string $chatId, array|string $media, ?string $caption = null, array $options = []): array;

    /**
     * What: Sends a file payload with an optional caption.
     * When: Use this for documents or other non-inline media.
     * Why: File sends have different rules than media sends, especially for newsletters.
     */
    public function sendFile(string $chatId, array|string $media, ?string $caption = null, array $options = []): array;

    /**
     * What: Starts the DigiChat session.
     * When: Use this when the remote session must be explicitly started.
     * Why: It exposes session lifecycle control through the package API.
     */
    public function start(): array;

    /**
     * What: Refreshes the DigiChat session.
     * When: Use this when the remote session should be restarted or rebuilt.
     * Why: It gives consumers a clean recovery path without crafting raw requests.
     */
    public function refresh(bool $withDeletion = false): array;

    /**
     * What: Sends a plain text message to a contact using the legacy helper signature.
     * When: Use this for existing integrations that already rely on `phoneNumber` and `message`.
     * Why: Keeping it preserves backward compatibility for current package users.
     */
    public function sendMessage(string $phoneNumber, string $message): array;

    /**
     * What: Gets the current QR payload for pairing the session.
     * When: Use this while connecting a WhatsApp device.
     * Why: It lets consumers complete authentication through the package.
     */
    public function getQr(): array;

    /**
     * What: Gets the current session status.
     * When: Use this to check if the session is connected and ready.
     * Why: Status checks are a common prerequisite for support flows and dashboards.
     */
    public function getStatus(): array;

    /**
     * What: Logs out the current session.
     * When: Use this when the linked device should be disconnected.
     * Why: It exposes controlled session teardown directly from the package.
     */
    public function logout(bool $withDeletion = false): array;

    /**
     * What: Calls the DigiChat ping endpoint.
     * When: Use this for connectivity or credential checks.
     * Why: A lightweight health method is useful before deeper session operations.
     */
    public function ping(): array;

    /**
     * What: Resolves metadata for a WhatsApp invite code.
     * When: Use this before joining or displaying a group invitation.
     * Why: It avoids duplicating invite lookup logic in consumer applications.
     */
    public function getInviteInfo(string $inviteCode): array;

    /**
     * What: Resolves WhatsApp newsletter or channel information from an invite code or invite link.
     * When: Use this before showing channel details or validating a channel invitation.
     * Why: It exposes the SaaS channel-info handler through the same package API style as the other helpers.
     */
    public function getChannelInfo(array|string $invite): array;
}
