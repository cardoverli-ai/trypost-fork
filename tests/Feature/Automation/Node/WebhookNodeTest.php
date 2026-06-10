<?php

declare(strict_types=1);

use App\Actions\Automation\Node\RunWebhookNode;
use App\Enums\Automation\NodeRun\Status;
use App\Models\AutomationRun;
use Illuminate\Support\Facades\Http;

it('posts interpolated payload to the configured url', function () {
    Http::fake([
        'hooks.example.com/*' => Http::response(['ok' => true], 200),
    ]);

    $run = AutomationRun::factory()->create([
        'context' => ['trigger' => ['title' => 'Hello'], 'generated' => ['post_url' => 'https://t.it/p/1']],
    ]);

    $result = app(RunWebhookNode::class)($run, [
        'url' => 'https://hooks.example.com/test',
        'method' => 'POST',
        'headers' => ['X-Source' => 'TryPost'],
        'payload_template' => '{"title":"{{ trigger.title }}","post_url":"{{ generated.post_url }}"}',
    ]);

    expect($result->status)->toBe(Status::Completed);
    Http::assertSent(fn ($request) => $request['title'] === 'Hello' && $request['post_url'] === 'https://t.it/p/1');
});

it('sends the branded user-agent header', function () {
    Http::fake([
        'hooks.example.com/*' => Http::response(['ok' => true], 200),
    ]);

    $run = AutomationRun::factory()->create();

    app(RunWebhookNode::class)($run, [
        'url' => 'https://hooks.example.com/test',
        'method' => 'POST',
        'headers' => ['User-Agent' => 'user-supplied-agent'],
        'payload_template' => '{}',
    ]);

    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', config('trypost.user_agent')));
});

it('fails on 5xx response', function () {
    Http::fake(['hooks.example.com/*' => Http::response('err', 500)]);

    $run = AutomationRun::factory()->create();

    $result = app(RunWebhookNode::class)($run, [
        'url' => 'https://hooks.example.com/test',
        'method' => 'POST',
        'payload_template' => '{}',
    ]);

    expect($result->status)->toBe(Status::Failed);
});

it('fails on malformed payload json instead of silently sending an empty body', function () {
    Http::fake(['hooks.example.com/*' => Http::response(['ok' => true], 200)]);

    $run = AutomationRun::factory()->create();

    $result = app(RunWebhookNode::class)($run, [
        'url' => 'https://hooks.example.com/test',
        'method' => 'POST',
        'payload_template' => '{ "a": }',
    ]);

    expect($result->status)->toBe(Status::Failed);
    expect($result->error['reason'])->toBe('invalid_payload_json');
    Http::assertNothingSent();
});

it('treats 4xx responses as completed (only 5xx fails)', function () {
    Http::fake(['hooks.example.com/*' => Http::response('not found', 404)]);

    $run = AutomationRun::factory()->create();

    $result = app(RunWebhookNode::class)($run, [
        'url' => 'https://hooks.example.com/test',
        'method' => 'POST',
        'payload_template' => '{}',
    ]);

    expect($result->status->value)->toBe('completed');
    expect($result->output['webhook']['status'])->toBe(404);
});
