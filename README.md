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

## 💬 Chat ID Formats

- Contact: `963XXXXXXXX` or `963XXXXXXXX@c.us`
- Group: `123456789@g.us`
- Newsletter / Channel: `123456789@newsletter`

Notes:

- `sendMessage()` is still available for plain contact text messages.
- `sendText()` supports contacts, groups, and newsletters.
- `sendMedia()` supports contacts, groups, and newsletters.
- `sendFile()` supports contacts and groups only.
- Newsletter / channel file sends are not supported.

---

## 📚 Available Methods

### 1) `sendMessage(string $phoneNumber, string $message): array`

Send a plain text message to a contact.

```php
DigiChat::sendMessage('963XXXXXXXX', 'Hello there');
```

### 2) `send(array $payload): array`

Send a prepared payload.

```php
DigiChat::send([
    'chatId' => '963XXXXXXXX',
    'type' => 'text',
    'text' => 'Hello from send()',
]);
```

### 3) `sendText(string $chatId, string $text, array $options = []): array`

Send text to a contact, group, or newsletter.

```php
DigiChat::sendText('963XXXXXXXX', 'Hello contact');
DigiChat::sendText('123456789@g.us', 'Hello group');
DigiChat::sendText('123456789@newsletter', 'Latest update');
```

### 4) `sendMedia(string $chatId, array|string $media, ?string $caption = null, array $options = []): array`

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

### 5) `sendFile(string $chatId, array|string $media, ?string $caption = null, array $options = []): array`

Send a file to a contact or group.

```php
DigiChat::sendFile('123456789@g.us', [
    'mimetype' => 'application/pdf',
    'filename' => 'report.pdf',
    'base64' => base64_encode(file_get_contents(storage_path('app/report.pdf'))),
], 'Monthly report');
```

### 6) `getQr(): array`

Get the current QR payload for session pairing.

```php
$qr = DigiChat::getQr();
```

### 7) `getStatus(): array`

Get the current session status.

```php
$status = DigiChat::getStatus();
```

### 8) `start(): array`

Start the session.

```php
$start = DigiChat::start();
```

### 9) `refresh(bool $withDeletion = false): array`

Refresh the session.

```php
$refresh = DigiChat::refresh();
$refreshAndDelete = DigiChat::refresh(withDeletion: true);
```

### 10) `logout(bool $withDeletion = false): array`

Logout the current session.

```php
$logout = DigiChat::logout();
$logoutAndDelete = DigiChat::logout(withDeletion: true);
```

### 11) `ping(): array`

Check API availability.

```php
$ping = DigiChat::ping();
```

### 12) `getInviteInfo(string $inviteCode): array`

Get WhatsApp invite information.

```php
$invite = DigiChat::getInviteInfo('YOUR_INVITE_CODE');
```

### 13) `getChannelInfo(array|string $invite): array`

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
