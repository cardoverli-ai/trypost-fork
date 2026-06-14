<?php

declare(strict_types=1);

namespace App\Actions\SocialAccount;

use App\Enums\SocialAccount\Platform;
use App\Enums\SocialAccount\Status;
use App\Features\SocialAccountLimit;
use App\Jobs\SyncTelegramAccountAvatar;
use App\Models\SocialAccount;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;

class ConnectTelegramChannel
{
    /**
     * Link a Telegram chat to a workspace for a one-off connect nonce.
     *
     * @param  array<string, mixed>  $chat  The `chat` object from the Bot API update.
     * @return SocialAccount|null The linked account, or null when blocked (account
     *                            limit reached or the code was already consumed).
     */
    public static function execute(Workspace $workspace, array $chat, string $nonce): ?SocialAccount
    {
        $chatId = (string) data_get($chat, 'id');
        $username = data_get($chat, 'username');

        // Block only brand-new accounts against the plan limit, never reconnects.
        $isNewAccount = ! $workspace->socialAccounts()
            ->where('platform', Platform::Telegram->value)
            ->where('platform_user_id', $chatId)
            ->exists();

        if ($isNewAccount && self::workspaceAtAccountLimit($workspace)) {
            return null;
        }

        // Consume the code once so a leaked code can't be replayed to link another chat.
        if (! Cache::add("telegram:connect:{$nonce}", true, now()->addMinutes(15))) {
            return null;
        }

        $account = $workspace->socialAccounts()->updateOrCreate(
            [
                'platform' => Platform::Telegram->value,
                'platform_user_id' => $chatId,
            ],
            [
                'username' => $username,
                'display_name' => data_get($chat, 'title') ?? $username ?? "Telegram {$chatId}",
                'access_token' => '',
                'refresh_token' => '',
                'token_expires_at' => null,
                'scopes' => [],
                'status' => Status::Connected,
                'error_message' => null,
                'disconnected_at' => null,
                'meta' => [
                    'chat_id' => $chatId,
                    'username' => $username,
                    'type' => data_get($chat, 'type'),
                    'connect_nonce' => $nonce,
                ],
            ],
        );

        SyncTelegramAccountAvatar::dispatch($account);

        return $account;
    }

    private static function workspaceAtAccountLimit(Workspace $workspace): bool
    {
        if (config('trypost.self_hosted')) {
            return false;
        }

        $limit = Feature::for($workspace->account)->value(SocialAccountLimit::class);

        return $workspace->socialAccounts()->count() >= $limit;
    }
}
