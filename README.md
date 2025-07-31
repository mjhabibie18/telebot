
# Laravel Telegram Bot API

Telegram Bot menampilkan menu restoran dari Yelp API.

---

## Fitur

- ✅ Webhook listener via `/api/telegram/webhook`
- ✅ Komunikasi dua arah dengan Telegram Bot
- ✅ Ambil data menu restoran dari [Yelp Business API](https://rapidapi.com/apidojo/api/yelp-business/)
- ✅ Format pesan Telegram dengan Markdown
- ✅ Tes langsung dari Telegram
- ✅ Logging pesan masuk ke webhook

---

## Langkah Instalasi

### 1. Clone Repo & Install Dependency

```bash
git clone https://github.com/nama/repo-telebot.git
cd repo-telebot
composer install
```

### 2. Siapkan `.env`

```bash
cp .env.example .env
```

Isi variabel penting:

```
APP_NAME=TeleBot
APP_URL=http://localhost

TELEGRAM_BOT_TOKEN=isi_token_telegram_kamu
RAPIDAPI_KEY=isi_api_key_dari_rapidapi

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=telebot
DB_USERNAME=postgres
DB_PASSWORD=password_kamu
```

### 3. Generate Key & Migrate Database

```bash
php artisan key:generate
php artisan migrate
```

### 4. Jalankan Laravel

```bash
php artisan serve
```

---

## Ngrok (Expose ke Publik)

Jika testing secara lokal, kamu butuh [ngrok](https://ngrok.com):

```bash
ngrok http 8000
```

Ambil URL dari ngrok, misal:

```
https://abcd1234.ngrok-free.app
```

---

## Set Webhook Telegram

Ganti `<TOKEN>` dan URL kamu:

```bash
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://abcd1234.ngrok-free.app/api/telegram/webhook"
```

Contoh:

```
https://api.telegram.org/bot123456:ABC/setWebhook?url=https://abcd1234.ngrok-free.app/api/telegram/webhook
```
![Screenshot Telebot](https://raw.githubusercontent.com/mjhabibie18/telebot/main/docs/img/sswebhook.png)
---

## Struktur Route

### `routes/api.php`

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramBotController;

Route::post('/telegram/webhook', [TelegramBotController::class, 'handle']);
```

Pastikan juga di `bootstrap/app.php`:

```php
->withRouting(
    api: __DIR__.'/../routes/api.php',
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

---

## Contoh TelegramBotController

`app/Http/Controllers/TelegramBotController.php`

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    public function handle(Request $request)
    {
        \Log::info('Webhook Telegram Masuk:', $request->all());

        $message = $request->input('message');
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';

        if (!$chatId || !$text) {
            return response()->json(['status' => 'No valid message received.']);
        }

        if ($text === '/start') {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "👋 Selamat datang!\nGunakan perintah: `/menu <business_id>`\nContoh: `/menu 5uUs2b4bQdS3WS8z16LJKw`",
                'parse_mode' => 'Markdown'
            ]);
            return response()->json(['status' => 'OK']);
        }

        if (Str::startsWith($text, '/menu')) {
            $parts = explode(' ', $text);
            $id = $parts[1] ?? null;

            if ($id) {
                $menuMessage = $this->getMenusByBusinessId($id);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $menuMessage,
                    'parse_mode' => 'Markdown'
                ]);
            } else {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => " Format salah.\nGunakan: `/menu <business_id>`",
                    'parse_mode' => 'Markdown'
                ]);
            }
        }

        return response()->json(['status' => 'OK']);
    }

    private function getMenusByBusinessId($businessId)
    {
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => env('RAPIDAPI_KEY'),
            'X-RapidAPI-Host' => 'yelp-business-api.p.rapidapi.com'
        ])->get('https://yelp-business-api.p.rapidapi.com/get_menus', [
            'business_id' => $businessId
        ]);

        if ($response->failed()) {
            return " Gagal mengambil data dari API Yelp.";
        }

        $data = $response->json();

        if (!isset($data['menus']) || count($data['menus']) === 0) {
            return " Tidak ada menu ditemukan.";
        }

        $menus = $data['menus'];
        $message = "* Daftar Menu:*\n\n";

        foreach ($menus as $menu) {
            $message .= " *{$menu['Food Name']}*\n";
            $message .= "_Kategori_: {$menu['Category']}\n";
            $message .= "_Detail_: {$menu['Details']}\n";
            $message .= "_Harga_: {$menu['Price']}\n\n";
        }

        return $message;
    }
}
```

---

## Testing Webhook

### 1. Dari Telegram
- Kirim `/start` atau `/menu 5uUs2b4bQdS3WS8z16LJKw` ke bot kamu
- Cek respons langsung

![Screenshot Via Browser](https://raw.githubusercontent.com/mjhabibie18/telebot/main/docs/img/sstelebot.png)

## Debugging

- Cek log di:
  ```
  storage/logs/laravel.log
  ```
- Gunakan `\Log::info()` di controller

---

## Tools

- Laravel 11/12
- PostgreSQL
- Telegram Bot SDK: [irazasyed/telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk)
- RapidAPI (Yelp)
- Ngrok

---

## Author

Habibie — TeleBot with Laravel
