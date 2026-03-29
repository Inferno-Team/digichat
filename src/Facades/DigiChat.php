<?php

namespace Digiworld\DigiChat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * What: Static facade entry point for the DigiChat package client.
 * When: Use this in Laravel applications that prefer facade-style access over constructor injection.
 * Why: The facade keeps package usage concise while still resolving the shared manager from the container.
 *
 * @method static \Digiworld\DigiChat\DigiChatManager session(?string $token = null, ?string $secret = null)
 * @method static array send(array $payload)
 * @method static array sendMessage(string $phoneNumber, string $message)
 * @method static array sendText(string $chatId, string $text, array $options = [])
 * @method static array sendMedia(string $chatId, array|string $media, ?string $caption = null, array $options = [])
 * @method static array sendFile(string $chatId, array|string $media, ?string $caption = null, array $options = [])
 * @method static array ping()
 * @method static array getQr()
 * @method static array getStatus()
 * @method static array logout(bool $withDeletion = false)
 * @method static array start()
 * @method static array refresh(bool $withDeletion = false)
 * @method static array getInviteInfo(string $inviteCode)
 * @method static array getChannelInfo(array|string $invite)
 */
class DigiChat extends Facade
{
    /**
     * What: Returns the container key behind the DigiChat facade.
     * When: Called internally by Laravel whenever a static facade method is invoked.
     * Why: This is how the facade resolves the package manager from the service container.
     */
    protected static function getFacadeAccessor()
    {
        return 'digichat';
    }
}
