<?php

declare(strict_types=1);

namespace App\Actions\Automation\Run;

use App\Enums\Automation\Run\Status;
use App\Jobs\Automation\ProcessAutomationNode;
use App\Models\AutomationRun;

class AdvanceAutomationRun
{
    public function __invoke(AutomationRun $run, string $fromNodeId, string $handle = 'default'): void
    {
        $automation = $run->automation;

        $connection = collect($automation->connections ?? [])
            ->first(fn ($c) => $c['source'] === $fromNodeId && ($c['source_handle'] ?? 'default') === $handle);

        if ($connection === null) {
            $run->update([
                'status' => Status::Completed,
                'finished_at' => now(),
                'current_node_id' => null,
                'error' => [
                    'reason' => 'no_matching_edge',
                    'handle' => $handle,
                    'node_id' => $fromNodeId,
                ],
            ]);

            return;
        }

        ProcessAutomationNode::dispatch($run, $connection['target']);
    }
}
