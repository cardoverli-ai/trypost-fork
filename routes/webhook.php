<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
