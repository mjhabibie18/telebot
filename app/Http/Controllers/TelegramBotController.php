<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotController extends Controller
{
    public function handle(Request $request)
    {
        $message = $request->input('message');
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';

        if (!$chatId || !$text) {
            return response()->json(['status' => 'No valid message received.']);
        }

        if ($text === '/start') {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => " Selamat datang!\nGunakan perintah: `/menu <business_id>`\nContoh: `/menu 5uUs2b4bQdS3WS8z16LJKw`",
                'parse_mode' => 'Markdown'
            ]);
            return response()->json(['status' => 'OK']);
        }

        // Tangani command /menu
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
                    'text' => " Format salah.\nGunakan: `/menu <business_id>`\nContoh: `/menu 5uUs2b4bQdS3WS8z16LJKw`",
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
            return " Gagal mengambil data dari API Yelp!";
        }

        $data = $response->json();

        if (!isset($data['menus']) || !is_array($data['menus']) || count($data['menus']) === 0) {
            return "Tidak ada menu yang ditemukan untuk ID: `$businessId`.";
        }

        $menus = $data['menus'];
        $message = "*Daftar Menu:*\n\n";

        foreach ($menus as $menu) {
            $foodName = str_replace(['_', '*'], ['\_', '\*'], $menu['Food Name'] ?? 'Tanpa Nama');
            $category = $menu['Category'] ?? '-';
            $details = $menu['Details'] ?? '-';
            $price = $menu['Price'] ?? '-';

            $message .= "*{$foodName}*\n";
            $message .= "_Kategori_: {$category}\n";
            $message .= "_Detail_: {$details}\n";
            $message .= "_Harga_: {$price}\n\n";
        }

        return $message;
    }
}
