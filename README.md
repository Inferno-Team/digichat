# DigiChat WhatsApp API Package for Laravel

## <img src="https://chat.digiworld-dev.com/assets/img/avatars/logo.svg" alt="DigiChat Logo" width="24" height="24" /> DigiChat

DigiChat is a simple Laravel package that allows you to send WhatsApp messages from your application via the DigiWorld API.

## 📦 Installation

Install via Composer:

```bash
composer require digiworld/digichat
```
Then publish the configuration file:
```bash
php artisan digichat:install
```

This will publish the ***config/digichat.php*** file where you can set your API credentials.

## ⚙️ Configuration

After publishing the config file, add the following environment variables to your ***.env*** file:

```bash
DIGICHAT_API_TOKEN=your_token_here
DIGICHAT_API_SECRET=your_secret_here
```

You can find these credentials in your DigiChat dashboard.
go to [DigiChat](https://chat.digiworld-dev.com/)

If you don’t have access, please contact DigiWorld support.

## 📨 Usage Example

Here’s how you can send a WhatsApp message using DigiChat in a route or controller:

```php
use DigiWorld\DigiChat\Facades\DigiChat;

Route::get('/test-digichat', function () {
    try {
        $response = DigiChat::sendMessage(
            "PHONE_NUMBER", // recipient phone number without + or 0 like this 963
            "Test from local package" // message content
        );

        return response()->json($response);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});
```

## 🛑 Disclaimer

##### ⚠️ Important Notice:

DigiChat uses unofficial access to WhatsApp. This may violate WhatsApp's Terms of Service.

By using this package, you acknowledge that:

Your phone number may be banned by WhatsApp for using unofficial APIs.

DigiWorld is not responsible for any bans, suspensions, or loss of access to your WhatsApp account.

Use this tool at your own risk and only for permitted purposes.

### 💬 Need Help?
If you encounter any issues or need support, please open an issue on the GitHub repo or contact us at [Support](support@digiworld.com).


##### ***Happy Messaging 🚀***

