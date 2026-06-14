<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SocialAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SyncTelegramAccountAvatar implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public SocialAccount $account) {}

    public function handle(): void
    {
        $token = (string) config('trypost.platforms.telegram.bot_token');
        $api = rtrim((string) config('trypost.platforms.telegram.api'), '/');
        $chatId = data_get($this->account->meta, 'chat_id');

        if ($token === '' || $chatId === null) {
            return;
        }

        $chat = Http::get("{$api}/bot{$token}/getChat", ['chat_id' => $chatId]);
        $fileId = data_get($chat->json(), 'result.photo.big_file_id');

        if (! is_string($fileId)) {
            return;
        }

        $file = Http::get("{$api}/bot{$token}/getFile", ['file_id' => $fileId]);
        $filePath = data_get($file->json(), 'result.file_path');

        if (! is_string($filePath)) {
            return;
        }

        $avatar = uploadFromUrl("{$api}/file/bot{$token}/{$filePath}");

        if ($avatar !== null) {
            $this->account->update(['avatar_url' => $avatar]);
        }
    }
}
