<?php

declare(strict_types=1);

namespace App\Actions\Automation\Node;

use App\DataTransferObjects\Automation\NodeRunResult;
use App\Models\AutomationRun;
use App\Services\Automation\ExpressionResolver;
use Illuminate\Support\Facades\Http;

class RunWebhookNode
{
    public function __construct(private ExpressionResolver $resolver) {}

    public function __invoke(AutomationRun $run, array $config): NodeRunResult
    {
        $url = $this->resolver->resolve($config['url'] ?? '', $run->context ?? []);
        $method = strtoupper($config['method'] ?? 'POST');
        $headers = [];

        foreach ($config['headers'] ?? [] as $k => $v) {
            $headers[$k] = $this->resolver->resolve((string) $v, $run->context ?? []);
        }

        $payloadJson = $this->resolver->resolve($config['payload_template'] ?? '{}', $run->context ?? []);
        $trimmedPayload = trim($payloadJson);

        if ($trimmedPayload !== '' && $trimmedPayload !== 'null') {
            $decoded = json_decode($payloadJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return NodeRunResult::failed(__('automations.errors.webhook_invalid_payload_json'), [
                    'reason' => 'invalid_payload_json',
                ]);
            }

            $payload = $decoded ?? [];
        } else {
            $payload = [];
        }

        $response = Http::withHeaders($headers)
            ->withUserAgent(config('trypost.user_agent'))
            ->send($method, $url, ['json' => $payload]);

        if ($response->serverError()) {
            return NodeRunResult::failed(__('automations.errors.webhook_server_error'), [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
        }

        return NodeRunResult::completed(output: [
            'webhook' => [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ],
        ]);
    }
}
