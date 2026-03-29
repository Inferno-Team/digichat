<?php

namespace Digiworld\DigiChat\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class DigiChatManagerTest extends TestCase
{
    public static function textTargetProvider(): array
    {
        return [
            'contact digits' => ['963123456789', '963123456789', 'contact'],
            'contact @c.us' => ['963123456789@c.us', '963123456789', 'contact'],
            'group' => ['123456789@g.us', '123456789@g.us', 'group'],
            'newsletter' => ['123456789@newsletter', '123456789@newsletter', 'channel'],
        ];
    }

    public static function mediaTargetProvider(): array
    {
        return [
            'group' => ['123456789@g.us', '123456789@g.us', 'group'],
            'newsletter' => ['123456789@newsletter', '123456789@newsletter', 'channel'],
        ];
    }

    public function test_send_message_keeps_existing_signature_and_sends_canonical_contact_payload(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame(
                'https://digichat.digiworld-dev.com/api/whatsapp/test-token/sendMessage',
                $request->url()
            );

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame('963123456789', $payload['chatId']);
            $this->assertSame('text', $payload['type']);
            $this->assertSame('Hello there', $payload['text']);
            $this->assertArrayNotHasKey('phone', $payload);
            $this->assertArrayNotHasKey('message', $payload);

            return Http::response($this->successPayload(
                chatId: '963123456789',
                targetType: 'contact',
                contentType: 'text',
            ), 200);
        });

        $response = $this->manager()->sendMessage('963123456789', 'Hello there');

        $this->assertTrue($response['success']);
        $this->assertSame('immediate', $response['mode']);
        $this->assertSame('contact', $response['targetType']);
        $this->assertSame('text', $response['contentType']);
    }

    /**
     * @dataProvider textTargetProvider
     */
    public function test_send_text_supports_contact_group_and_newsletter_targets(
        string $inputChatId,
        string $expectedChatId,
        string $expectedTargetType
    ): void {
        Http::fake(function (Request $request) use ($expectedChatId, $expectedTargetType) {
            $this->assertSame(
                'https://digichat.digiworld-dev.com/api/whatsapp/test-token/sendMessage',
                $request->url()
            );

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($expectedChatId, $payload['chatId']);
            $this->assertSame('text', $payload['type']);
            $this->assertSame('Broadcast copy', $payload['text']);

            return Http::response($this->successPayload(
                chatId: $expectedChatId,
                targetType: $expectedTargetType,
                contentType: 'text',
            ), 200);
        });

        $response = $this->manager()->sendText($inputChatId, 'Broadcast copy');

        $this->assertTrue($response['success']);
        $this->assertSame($expectedTargetType, $response['targetType']);
        $this->assertSame($expectedChatId, $response['chatId']);
    }

    /**
     * @dataProvider mediaTargetProvider
     */
    public function test_send_media_supports_group_and_newsletter_targets(
        string $inputChatId,
        string $expectedChatId,
        string $expectedTargetType
    ): void {
        Http::fake(function (Request $request) use ($expectedChatId, $expectedTargetType) {
            $this->assertSame(
                'https://digichat.digiworld-dev.com/api/whatsapp/test-token/sendMedia',
                $request->url()
            );

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($expectedChatId, $payload['chatId']);
            $this->assertSame('media', $payload['type']);
            $this->assertSame('Launch asset', $payload['caption']);
            $this->assertSame('image/png', $payload['media']['mimetype']);

            return Http::response($this->successPayload(
                chatId: $expectedChatId,
                targetType: $expectedTargetType,
                contentType: 'media',
            ), 200);
        });

        $response = $this->manager()->sendMedia($inputChatId, $this->imageMediaPayload(), 'Launch asset');

        $this->assertTrue($response['success']);
        $this->assertSame($expectedTargetType, $response['targetType']);
        $this->assertSame('media', $response['contentType']);
    }

    public function test_send_file_supports_groups(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame(
                'https://digichat.digiworld-dev.com/api/whatsapp/test-token/sendMedia',
                $request->url()
            );

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame('123456789@g.us', $payload['chatId']);
            $this->assertSame('file', $payload['type']);
            $this->assertSame('Monthly report', $payload['caption']);
            $this->assertSame('application/pdf', $payload['media']['mimetype']);

            return Http::response($this->successPayload(
                chatId: '123456789@g.us',
                targetType: 'group',
                contentType: 'file',
            ), 200);
        });

        $response = $this->manager()->sendFile('123456789@g.us', $this->fileMediaPayload(), 'Monthly report');

        $this->assertTrue($response['success']);
        $this->assertSame('group', $response['targetType']);
        $this->assertSame('file', $response['contentType']);
    }

    public function test_send_rejects_newsletter_files_before_http_request(): void
    {
        Http::fake();

        $response = $this->manager()->sendFile(
            '123456789@newsletter',
            $this->fileMediaPayload(),
            'Not allowed'
        );

        $this->assertFalse($response['success']);
        $this->assertSame(422, $response['status']);
        $this->assertSame('CHANNEL_UNSUPPORTED_MESSAGE_TYPE', $response['error']);
        $this->assertSame('channel', $response['targetType']);
        $this->assertSame('file', $response['contentType']);

        Http::assertNothingSent();
    }

    public function test_send_handles_immediate_mode_responses(): void
    {
        Http::fake(fn (Request $request) => Http::response($this->successPayload(
            chatId: '963123456789',
            targetType: 'contact',
            contentType: 'text',
            mode: 'immediate',
            queued: false,
        ), 200));

        $response = $this->manager()->sendText('963123456789', 'Immediate hello');

        $this->assertTrue($response['success']);
        $this->assertSame('immediate', $response['mode']);
        $this->assertFalse($response['queued']);
    }

    public function test_send_handles_scheduled_mode_responses(): void
    {
        Http::fake(fn (Request $request) => Http::response($this->successPayload(
            chatId: '963123456789',
            targetType: 'contact',
            contentType: 'text',
            mode: 'scheduled',
            queued: true,
            messageId: null,
        ), 200));

        $response = $this->manager()->sendText('963123456789', 'Queued hello');

        $this->assertTrue($response['success']);
        $this->assertSame('scheduled', $response['mode']);
        $this->assertTrue($response['queued']);
        $this->assertNull($response['messageId']);
    }

    public function test_send_surfaces_recipient_not_registered_errors(): void
    {
        Http::fake(fn (Request $request) => Http::response([
            'success' => false,
            'mode' => 'immediate',
            'online' => true,
            'chatId' => '963123456789',
            'targetType' => 'contact',
            'contentType' => 'text',
            'queued' => false,
            'duplicate' => false,
            'error' => 'RECIPIENT_NOT_REGISTERED',
        ], 422));

        $response = $this->manager()->sendText('963123456789', 'Hello');

        $this->assertFalse($response['success']);
        $this->assertSame(422, $response['status']);
        $this->assertSame('RECIPIENT_NOT_REGISTERED', $response['error']);
        $this->assertSame('The target contact is not registered on WhatsApp.', $response['message']);
    }

    public function test_send_surfaces_channel_unsupported_message_type_errors(): void
    {
        Http::fake(fn (Request $request) => Http::response([
            'success' => false,
            'mode' => 'immediate',
            'online' => true,
            'chatId' => '123456789@newsletter',
            'targetType' => 'channel',
            'contentType' => 'file',
            'queued' => false,
            'duplicate' => false,
            'error' => 'CHANNEL_UNSUPPORTED_MESSAGE_TYPE',
        ], 422));

        $response = $this->manager()->sendMedia(
            '123456789@newsletter',
            $this->imageMediaPayload(),
            'Asset'
        );

        $this->assertFalse($response['success']);
        $this->assertSame(422, $response['status']);
        $this->assertSame('CHANNEL_UNSUPPORTED_MESSAGE_TYPE', $response['error']);
        $this->assertSame('Newsletters/channels only support text and media messages.', $response['message']);
    }

    public function test_send_surfaces_missing_session_errors(): void
    {
        Http::fake(fn (Request $request) => Http::response([], 404));

        $response = $this->manager()->sendText('963123456789', 'Hello');

        $this->assertFalse($response['success']);
        $this->assertSame(404, $response['status']);
        $this->assertSame('NO_SESSION', $response['error']);
        $this->assertSame('DigiChat session was not found or is not paired.', $response['message']);
    }

    public function test_get_channel_info_sends_invite_code_payload(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame(
                'https://digichat.digiworld-dev.com/api/whatsapp/test-token/channel-info',
                $request->url()
            );

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(['inviteCode' => 'AbCdEfGhIjK'], $payload);

            return Http::response($this->channelInfoResponse(), 200);
        });

        $response = $this->manager()->getChannelInfo('AbCdEfGhIjK');

        $this->assertTrue($response['success']);
        $this->assertSame('1234567890@newsletter', $response['channelInfo']['channelId']);
        $this->assertSame('AbCdEfGhIjK', $response['channelInfo']['inviteCode']);
    }

    public function test_get_channel_info_sends_invite_link_payload(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame(
                'https://digichat.digiworld-dev.com/api/whatsapp/test-token/channel-info',
                $request->url()
            );

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([
                'inviteLink' => 'https://whatsapp.com/channel/AbCdEfGhIjK',
            ], $payload);

            return Http::response($this->channelInfoResponse(), 200);
        });

        $response = $this->manager()->getChannelInfo('https://whatsapp.com/channel/AbCdEfGhIjK');

        $this->assertTrue($response['success']);
        $this->assertSame('Channel Name', $response['channelInfo']['name']);
        $this->assertSame(
            'https://whatsapp.com/channel/AbCdEfGhIjK',
            $response['channelInfo']['inviteLink']
        );
    }

    public function test_get_channel_info_accepts_explicit_payload_array(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame(
                'https://digichat.digiworld-dev.com/api/whatsapp/test-token/channel-info',
                $request->url()
            );

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([
                'inviteCode' => 'AbCdEfGhIjK',
            ], $payload);

            return Http::response($this->channelInfoResponse(), 200);
        });

        $response = $this->manager()->getChannelInfo([
            'inviteCode' => 'AbCdEfGhIjK',
        ]);

        $this->assertTrue($response['success']);
        $this->assertSame('Channel description', $response['channelInfo']['description']);
    }

    private function successPayload(
        string $chatId,
        string $targetType,
        string $contentType,
        string $mode = 'immediate',
        bool $queued = false,
        ?string $messageId = 'wamid.test',
        bool $online = true,
        bool $duplicate = false,
        ?string $error = null
    ): array {
        return [
            'success' => true,
            'mode' => $mode,
            'online' => $online,
            'idempotencyKey' => 'idem-123',
            'chatId' => $chatId,
            'targetType' => $targetType,
            'contentType' => $contentType,
            'messageId' => $messageId,
            'queued' => $queued,
            'duplicate' => $duplicate,
            'error' => $error,
        ];
    }

    private function channelInfoResponse(): array
    {
        return [
            'success' => true,
            'channelInfo' => [
                'channelId' => '1234567890@newsletter',
                'name' => 'Channel Name',
                'description' => 'Channel description',
                'inviteCode' => 'AbCdEfGhIjK',
                'inviteLink' => 'https://whatsapp.com/channel/AbCdEfGhIjK',
                'channelMetadata' => [],
            ],
        ];
    }

    private function imageMediaPayload(): array
    {
        return [
            'mimetype' => 'image/png',
            'filename' => 'launch.png',
            'base64' => base64_encode('png-binary'),
        ];
    }

    private function fileMediaPayload(): array
    {
        return [
            'mimetype' => 'application/pdf',
            'filename' => 'report.pdf',
            'base64' => base64_encode('pdf-binary'),
        ];
    }
}
