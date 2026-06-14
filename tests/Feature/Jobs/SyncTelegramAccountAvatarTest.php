<?php

declare(strict_types=1);

use App\Jobs\SyncTelegramAccountAvatar;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['trypost.platforms.telegram.bot_token' => 'TESTTOKEN']);
    Storage::fake();
});

it('stores the telegram channel photo as the account avatar', function () {
    $account = SocialAccount::factory()->telegram()->create(['avatar_url' => null]);

    Http::fake([
        '*/botTESTTOKEN/getChat*' => Http::response(['ok' => true, 'result' => ['photo' => ['big_file_id' => 'BIGFILE']]], 200),
        '*/botTESTTOKEN/getFile*' => Http::response(['ok' => true, 'result' => ['file_path' => 'photos/file_1.jpg']], 200),
        '*/file/botTESTTOKEN/photos/file_1.jpg' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    (new SyncTelegramAccountAvatar($account))->handle();

    expect($account->fresh()->getRawOriginal('avatar_url'))->not->toBeNull();
});

it('does nothing when the channel has no photo', function () {
    $account = SocialAccount::factory()->telegram()->create(['avatar_url' => null]);

    Http::fake([
        '*/botTESTTOKEN/getChat*' => Http::response(['ok' => true, 'result' => []], 200),
    ]);

    (new SyncTelegramAccountAvatar($account))->handle();

    expect($account->fresh()->getRawOriginal('avatar_url'))->toBeNull();
});
