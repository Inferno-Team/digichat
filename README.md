# DigiChat WhatsApp API Package for Laravel

## <img src="https://chat.digiworld-dev.com/assets/img/avatars/logo.svg" alt="DigiChat Logo" width="24" height="24" /> DigiChat

DigiChat is a lightweight Laravel package that lets your app send WhatsApp messages and manage the session through DigiChat.

---

## ✅ Requirements

- PHP **8.2+**
- Laravel **8 / 9 / 10 / 11 / 12 / 13**

---

## 📦 Installation

Install via Composer:

```bash
composer require digiworld/digichat
```

Then publish the config:

```bash
php artisan digichat:install
```

---

## ⚙️ Configuration

Add the following to your `.env`:

```dotenv
DIGICHAT_API_TOKEN=your_token_here
DIGICHAT_API_SECRET=your_secret_here
```

You can find these credentials in your DigiChat dashboard:  
**https://chat.digiworld-dev.com/**

If you don’t have access, please contact DigiWorld support.

Full docs are available here:  
**https://digichat.digiworld-dev.com/docs**

---

## 🚀 Quick Start

Using the **Facade**:

```php
use Digiworld\DigiChat\Facades\DigiChat;

$response = DigiChat::sendMessage('963XXXXXXXX', 'Hello from DigiChat');
```

Using the **Manager** directly:

```php
use Digiworld\DigiChat\DigiChatManager;

Route::get('/digichat/test', function (DigiChatManager $digichat) {
    return $digichat->sendMessage('963XXXXXXXX', 'Hello from DigiChat');
});
```

---

## 🔀 Multi Session

The package config acts as the **default session**.

If you need multiple DigiChat sessions in the same Laravel project, use either the facade `session()` helper or create a manager with credentials directly.

Using the **Facade session helper**:

```php
use Digiworld\DigiChat\Facades\DigiChat;

$sessionA = DigiChat::session('token-a', 'secret-a');
$sessionB = DigiChat::session('token-b', 'secret-b');

$sessionA->sendText('123456789@g.us', 'Message from session A');
$sessionB->sendText('987654321@g.us', 'Message from session B');
```

Using the **Manager constructor**:

```php
use Digiworld\DigiChat\DigiChatManager;

$client = new DigiChatManager('token-a', 'secret-a');

$client->sendText('123456789@g.us', 'Message from custom client');
```

If `token` or `secret` is `null`, the package falls back to the values from `config/digichat.php`.

---

## 💬 Chat ID Formats

- Contact: `963XXXXXXXX` or `963XXXXXXXX@c.us`
- Group: `123456789@g.us`
- Newsletter / Channel: `123456789@newsletter`

Notes:

- Contact numbers starting with `+` are normalized automatically.
- `sendMessage()` is still available for plain contact text messages.
- `sendText()` supports contacts, groups, and newsletters.
- `sendMedia()` supports contacts, groups, and newsletters.
- `sendFile()` supports contacts and groups only.
- Newsletter / channel file sends are not supported.

---

## 📚 Available Methods

### 1) `session(?string $token = null, ?string $secret = null): DigiChatManager`

Create an isolated client for another DigiChat session.

```php
$otherSession = DigiChat::session('token-a', 'secret-a');

$otherSession->sendText('123456789@g.us', 'Hello from another session');
```

### 2) `sendMessage(string $phoneNumber, string $message): array`

Send a plain text message to a contact.

```php
DigiChat::sendMessage('963XXXXXXXX', 'Hello there');
```

### 3) `send(array $payload): array`

Send a prepared payload.

```php
DigiChat::send([
    'chatId' => '963XXXXXXXX',
    'type' => 'text',
    'text' => 'Hello from send()',
]);
```

### 4) `sendText(string $chatId, string $text, array $options = []): array`

Send text to a contact, group, or newsletter.

```php
DigiChat::sendText('963XXXXXXXX', 'Hello contact');
DigiChat::sendText('123456789@g.us', 'Hello group');
DigiChat::sendText('123456789@newsletter', 'Latest update');
```

### 5) `sendMedia(string $chatId, array|string $media, ?string $caption = null, array $options = []): array`

Send media with an optional caption.

```php
DigiChat::sendMedia('123456789@g.us', [
    'mimetype' => 'image/png',
    'filename' => 'image.png',
    'base64' => base64_encode(file_get_contents(storage_path('app/image.png'))),
], 'Image caption');

DigiChat::sendMedia('123456789@newsletter', [
    'mimetype' => 'image/jpeg',
    'filename' => 'update.jpg',
    'base64' => base64_encode(file_get_contents(storage_path('app/update.jpg'))),
], 'Channel update');
```

### 6) `sendFile(string $chatId, array|string $media, ?string $caption = null, array $options = []): array`

Send a file to a contact or group.

```php
DigiChat::sendFile('123456789@g.us', [
    'mimetype' => 'application/pdf',
    'filename' => 'report.pdf',
    'base64' => base64_encode(file_get_contents(storage_path('app/report.pdf'))),
], 'Monthly report');
```

### 7) `getQr(): array`

Get the current QR payload for session pairing.

```php
$qr = DigiChat::getQr();
```

### 8) `getStatus(): array`

Get the current session status.

```php
$status = DigiChat::getStatus();
```

### 9) `start(): array`

Start the session.

```php
$start = DigiChat::start();
```

### 10) `refresh(bool $withDeletion = false): array`

Refresh the session.

```php
$refresh = DigiChat::refresh();
$refreshAndDelete = DigiChat::refresh(withDeletion: true);
```

### 11) `logout(bool $withDeletion = false): array`

Logout the current session.

```php
$logout = DigiChat::logout();
$logoutAndDelete = DigiChat::logout(withDeletion: true);
```

### 12) `ping(): array`

Check API availability.

```php
$ping = DigiChat::ping();
```

### 13) `getInviteInfo(string $inviteCode): array`

Get WhatsApp invite information.

```php
$invite = DigiChat::getInviteInfo('YOUR_INVITE_CODE');
```

### 14) `getChannelInfo(array|string $invite): array`

Get newsletter / channel information from an invite code or invite link.

```php
$channelByCode = DigiChat::getChannelInfo('AbCdEfGhIjK');

$channelByLink = DigiChat::getChannelInfo('https://whatsapp.com/channel/AbCdEfGhIjK');
```

---

## 🛠 Example Route

```php
use Illuminate\Support\Facades\Route;
use Digiworld\DigiChat\Facades\DigiChat;

Route::get('/digichat/demo', function () {
    return response()->json([
        'send' => DigiChat::sendMessage('963XXXXXXXX', 'Hello from DigiChat'),
        'status' => DigiChat::getStatus(),
    ]);
});
```

---

## 📝 Notes

- All methods return the API response as an array.
- Config credentials act as the default session.
- Use `session()` or `new DigiChatManager($token, $secret)` for multi-session usage.
- Existing `sendMessage()` integrations remain supported.
- New projects can use `sendText()`, `sendMedia()`, `sendFile()`, or `send()`.
- `getChannelInfo()` can resolve channel details from either a code or a full WhatsApp channel invite link.

---

## 🛑 Disclaimer

> **Important Notice:** DigiChat uses unofficial access to WhatsApp, which may violate WhatsApp’s Terms of Service.

By using this package, you acknowledge that:

- Your phone number may be banned by WhatsApp for using unofficial APIs.
- DigiWorld is not responsible for any bans, suspensions, or loss of access to your WhatsApp account.
- Use this tool at your own risk and only for permitted purposes.

---

## 💬 Support

- Dashboard: **https://chat.digiworld-dev.com/**
- Docs: **https://digichat.digiworld-dev.com/docs**
- Email: **support@digiworld-dev.com**
