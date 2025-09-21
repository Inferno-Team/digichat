# DigiChat WhatsApp API Package for Laravel

## <img src="https://chat.digiworld-dev.com/assets/img/avatars/logo.svg" alt="DigiChat Logo" width="24" height="24" /> DigiChat

DigiChat is a lightweight Laravel package that lets your app send WhatsApp messages and manage the session (QR / status / logout) via the DigiWorld DigiChat API.

---

## ✅ Requirements
- PHP **8.1+**
- Laravel **10** or **11**
- cURL enabled

---

## 📦 Installation

Install via Composer:

```bash
composer require digiworld/digichat
```

Then publish the config & boilerplate:

```bash
php artisan digichat:install
```

This will publish the `config/digichat.php` file where you can set your API credentials.

---

## ⚙️ Configuration

Add the following to your `.env`:

```dotenv
DIGICHAT_API_TOKEN=your_token_here
DIGICHAT_API_SECRET=your_secret_here

```

> You can find these credentials in your DigiChat dashboard: **https://chat.digiworld-dev.com/**  
> If you don’t have access, please contact DigiWorld support.

---

## 🔐 How requests are signed

Every request is signed the same way your server expects:

```
signature = HMAC_SHA256( timestamp + token + jsonPayload, secret )
```

Headers sent:

```
X-API-Token: {token}
X-API-Timestamp: {unix_timestamp}
X-API-Signature: {hmac_sha256_hex}
Content-Type: application/json
Accept: application/json
```

---

## 🚀 Quick Start

Using the **Facade** (recommended):

```php
use Digiworld\DigiChat\Facades\DigiChat;

Route::get('/test-digichat', function () {
    $response = DigiChat::sendMessage(
        '963XXXXXXXX',                 // recipient without + or 0 prefix
        'Test from local package'      // message
    );

    return response()->json($response);
});
```

Using the **service container**:

```php
use Digiworld\DigiChat\DigiChatManager;

Route::get('/test-digichat-manager', function (DigiChatManager $dc) {
    return $dc->sendMessage('963XXXXXXXX', 'Hello from Manager!');
});
```

---

## 📚 Available Methods (current)

The package currently exposes these methods in `DigiChatManager` (and via the Facade):

### 1) `sendMessage(string $phoneNumber, string $message): array`
Send a plain text WhatsApp message.

```php
DigiChat::sendMessage('963XXXXXXXX', 'Hello there');
```

### 2) `getQr(): array`
Get the current QR code payload (if the session is not yet authenticated).

```php
$qr = DigiChat::getQr();
```

### 3) `getStatus(): array`
Get the session status (e.g., connected / disconnected / waiting-for-qr).

```php
$status = DigiChat::getStatus();
```

### 4) `logout(bool $withDeletion = false): array`
Log out the current session. If `$withDeletion` is `true`, also request server-side data cleanup.

```php
DigiChat::logout();                    // normal logout
DigiChat::logout(withDeletion: true);  // logout + delete persisted data
```

---

## 🧰 Error Handling

- On **success**, methods return the decoded JSON response array from the API.
- On **failure**, the client attempts to surface the server error and returns a consistent array structure like:

```php
[
  'success' => false,
  'message' => 'DigiChat API error 401: {"error":"Invalid token"}'
]
```

You can wrap calls in a try/catch if you prefer throwing exceptions in your own layer.

---

## 🛠 Example Route (copy–paste)

```php
use Illuminate\Support\Facades\Route;
use Digiworld\DigiChat\Facades\DigiChat;

Route::get('/digichat/demo', function () {
    try {
        $client = new DigiChat();
        $send = $client->sendMessage('963XXXXXXXX', 'Hello from DigiChat 👋');
        $status = $client->getStatus();

        return response()->json([
            'send'   => $send,
            'status' => $status,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
});
```

---

## 🛑 Disclaimer

> **Important Notice:** DigiChat uses unofficial access to WhatsApp, which may violate WhatsApp’s Terms of Service.

By using this package, you acknowledge that:

- Your phone number may be banned by WhatsApp for using unofficial APIs.
- DigiWorld is not responsible for any bans, suspensions, or loss of access to your WhatsApp account.
- Use this tool at your own risk and only for permitted purposes.

---

## 💬 Support

- Open an issue on the repository, or
- Email: **support@digiworld.com**

---

##### Happy Messaging 🚀
