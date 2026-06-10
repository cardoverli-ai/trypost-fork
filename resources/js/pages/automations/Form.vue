<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import {
    IconBolt,
    IconCircleX,
    IconClock,
    IconGitBranch,
    IconRss,
    IconSend,
    IconSparkles,
    IconTrash,
    IconWebhook,
    IconWorld,
    IconX,
} from '@tabler/icons-vue';
import { computed, markRaw, ref, watch } from 'vue';
import {
    ConnectionMode,
    MarkerType,
    VueFlow,
    useVueFlow,
    type Connection,
    type Edge,
    type Node,
    type XYPosition,
} from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';

import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    show as showAutomation,
    update as updateAutomation,
} from '@/routes/app/automations';

import AutomationConnectionLine from '@/components/automations/AutomationConnectionLine.vue';
import TestRunPanel from '@/components/automations/TestRunPanel.vue';
import { useHistory } from '@/composables/history/useHistory';
import { AddEdgeCommand } from '@/composables/history/commands/AddEdgeCommand';
import { AddNodeCommand } from '@/composables/history/commands/AddNodeCommand';
import { MoveNodeCommand } from '@/composables/history/commands/MoveNodeCommand';
import { RemoveEdgeCommand } from '@/composables/history/commands/RemoveEdgeCommand';
import { RemoveNodeCommand } from '@/composables/history/commands/RemoveNodeCommand';
import { UpdateNodeDataCommand } from '@/composables/history/commands/UpdateNodeDataCommand';
import { useShortcut } from '@/composables/useShortcut';
import { usePageErrors } from '@/composables/usePageErrors';
import type { Automation } from '@/types/automation/automation';
import { NodeType } from '@/types/automation/node-type';
import type { RawConnection } from '@/types/automation/raw-connection';
import { ScheduleField } from '@/types/automation/schedule-field';
import { TriggerType } from '@/types/automation/trigger-type';
import ConditionNode from '@/components/automations/nodes/ConditionNode.vue';
import DelayNode from '@/components/automations/nodes/DelayNode.vue';
import EndNode from '@/components/automations/nodes/EndNode.vue';
import HttpRequestNode from '@/components/automations/nodes/HttpRequestNode.vue';
import FetchRssNode from '@/components/automations/nodes/FetchRssNode.vue';
import GenerateNode from '@/components/automations/nodes/GenerateNode.vue';
import PublishNode from '@/components/automations/nodes/PublishNode.vue';
import TriggerNode from '@/components/automations/nodes/TriggerNode.vue';
import WebhookNode from '@/components/automations/nodes/WebhookNode.vue';

import ConditionNodeConfig from '@/components/automations/config/ConditionNodeConfig.vue';
import DelayNodeConfig from '@/components/automations/config/DelayNodeConfig.vue';
import EndNodeConfig from '@/components/automations/config/EndNodeConfig.vue';
import HttpRequestNodeConfig from '@/components/automations/config/HttpRequestNodeConfig.vue';
import FetchRssNodeConfig from '@/components/automations/config/FetchRssNodeConfig.vue';
import GenerateNodeConfig from '@/components/automations/config/GenerateNodeConfig.vue';
import PublishNodeConfig from '@/components/automations/config/PublishNodeConfig.vue';
import TriggerNodeConfig from '@/components/automations/config/TriggerNodeConfig.vue';
import WebhookNodeConfig from '@/components/automations/config/WebhookNodeConfig.vue';

const props = defineProps<{ automation: Automation }>();

const nodeTypes = {
    [NodeType.Trigger]: markRaw(TriggerNode),
    [NodeType.Generate]: markRaw(GenerateNode),
    [NodeType.Delay]: markRaw(DelayNode),
    [NodeType.Condition]: markRaw(ConditionNode),
    [NodeType.Publish]: markRaw(PublishNode),
    [NodeType.Webhook]: markRaw(WebhookNode),
    [NodeType.End]: markRaw(EndNode),
    [NodeType.FetchRss]: markRaw(FetchRssNode),
    [NodeType.HttpRequest]: markRaw(HttpRequestNode),
};

const configByType: Record<string, unknown> = {
    [NodeType.Trigger]: TriggerNodeConfig,
    [NodeType.Generate]: GenerateNodeConfig,
    [NodeType.Delay]: DelayNodeConfig,
    [NodeType.Condition]: ConditionNodeConfig,
    [NodeType.Publish]: PublishNodeConfig,
    [NodeType.Webhook]: WebhookNodeConfig,
    [NodeType.End]: EndNodeConfig,
    [NodeType.FetchRss]: FetchRssNodeConfig,
    [NodeType.HttpRequest]: HttpRequestNodeConfig,
};

const nodeTypeOptions = computed(() => [
    { type: NodeType.Trigger, label: trans('automations.nodes.trigger'), icon: IconBolt, accent: 'violet' },
    { type: NodeType.FetchRss, label: trans('automations.nodes.fetch_rss'), icon: IconRss, accent: 'amber' },
    { type: NodeType.HttpRequest, label: trans('automations.nodes.http_request'), icon: IconWorld, accent: 'slate' },
    { type: NodeType.Generate, label: trans('automations.nodes.generate'), icon: IconSparkles, accent: 'blue' },
    { type: NodeType.Delay, label: trans('automations.nodes.delay'), icon: IconClock, accent: 'amber' },
    { type: NodeType.Condition, label: trans('automations.nodes.condition'), icon: IconGitBranch, accent: 'rose' },
    { type: NodeType.Publish, label: trans('automations.nodes.publish'), icon: IconSend, accent: 'emerald' },
    { type: NodeType.Webhook, label: trans('automations.nodes.webhook'), icon: IconWebhook, accent: 'slate' },
    { type: NodeType.End, label: trans('automations.nodes.end'), icon: IconCircleX, accent: 'zinc' },
]);

const accentClasses: Record<string, { dot: string; tint: string; text: string }> = {
    violet: { dot: 'bg-violet-500', tint: 'bg-violet-200', text: 'text-violet-900' },
    blue: { dot: 'bg-blue-500', tint: 'bg-blue-200', text: 'text-blue-900' },
    amber: { dot: 'bg-amber-500', tint: 'bg-amber-200', text: 'text-amber-900' },
    rose: { dot: 'bg-rose-500', tint: 'bg-rose-200', text: 'text-rose-900' },
    emerald: { dot: 'bg-emerald-500', tint: 'bg-emerald-200', text: 'text-emerald-900' },
    slate: { dot: 'bg-slate-500', tint: 'bg-slate-200', text: 'text-slate-900' },
    zinc: { dot: 'bg-zinc-500', tint: 'bg-zinc-200', text: 'text-zinc-900' },
};

const hydrateEdges = (list: RawConnection[]): Edge[] =>
    list.map((e) => ({
        id: e.id,
        source: e.source,
        target: e.target,
        sourceHandle: e.source_handle ?? e.sourceHandle ?? undefined,
        targetHandle: e.target_handle ?? e.targetHandle ?? undefined,
    }));

const nodes = ref<Node[]>(props.automation.nodes ?? []);
const edges = ref<Edge[]>(hydrateEdges(props.automation.connections ?? []));
const selectedNodeId = ref<string | null>(null);
const name = ref(props.automation.name);

watch(
    () => [props.automation.nodes, props.automation.connections] as const,
    ([newNodes, newEdges]) => {
        nodes.value = newNodes ?? [];
        edges.value = hydrateEdges(newEdges ?? []);
        // Server-canonical state arrived — any prior undo history points at
        // stale references, so reset it together with the in-flight config diff.
        configSnapshot = null;
        history.clear();
    },
);

watch(() => props.automation.name, (newName) => {
    name.value = newName;
});

const selectedNode = computed(() => nodes.value.find((n) => n.id === selectedNodeId.value));
const selectedConfigComponent = computed(() =>
    selectedNode.value ? configByType[selectedNode.value.type as string] : null,
);

// Backend returns validation errors with paths like `nodes.{index}.data.{field}`.
// We flatten them to `{ field: message }` for the currently-selected node so each
// config component can show the InputError inline without knowing its own index.
const pageErrors = usePageErrors();
const selectedNodeErrors = computed<Record<string, string>>(() => {
    if (!selectedNode.value) return {};
    const idx = nodes.value.findIndex((n) => n.id === selectedNode.value!.id);
    if (idx === -1) return {};
    const prefix = `nodes.${idx}.data.`;
    const out: Record<string, string> = {};
    for (const [key, message] of Object.entries(pageErrors.value)) {
        if (key.startsWith(prefix)) {
            out[key.slice(prefix.length)] = message;
        }
    }
    return out;
});

const history = useHistory();

const {
    onNodeClick,
    onConnect,
    onNodeDragStart,
    onNodeDragStop,
    addEdges,
    screenToFlowCoordinate,
} = useVueFlow();

onNodeClick(({ node }) => {
    isTestPanelOpen.value = false;
    selectedNodeId.value = node.id;
});

onConnect((connection: Connection) => {
    const edge: Edge = {
        ...connection,
        id: `edge_${Date.now()}_${Math.floor(Math.random() * 1000)}`,
    } as Edge;
    addEdges([edge]);
    history.push(new AddEdgeCommand(edge, edges));
});

let dragStartPosition: XYPosition | null = null;
onNodeDragStart(({ node }) => {
    dragStartPosition = { ...node.position };
});
onNodeDragStop(({ node }) => {
    if (!dragStartPosition) return;
    const oldPos = dragStartPosition;
    dragStartPosition = null;
    const newPos = { ...node.position };
    if (oldPos.x === newPos.x && oldPos.y === newPos.y) return;
    history.push(new MoveNodeCommand(node.id, oldPos, newPos, nodes));
});

// Snapshot of the selected node's data at the moment the config panel opens.
// When the user switches nodes (or closes the panel), we diff against the live
// data and push a single UpdateNodeDataCommand for the whole editing session —
// avoids one undo step per keystroke.
let configSnapshot: { nodeId: string; data: Record<string, unknown> } | null = null;

// JSON-based clone because Vue Flow wraps node.data in reactive proxies that
// `structuredClone` rejects. Node data is always JSON-serializable (it's what
// gets persisted), so the conversion is lossless for our shape.
const cloneNodeData = (data: unknown): Record<string, unknown> =>
    JSON.parse(JSON.stringify(data ?? {})) as Record<string, unknown>;

const commitConfigSnapshot = (): void => {
    if (!configSnapshot) return;
    const node = nodes.value.find((n) => n.id === configSnapshot!.nodeId);
    if (node) {
        const liveData = cloneNodeData(node.data);
        if (JSON.stringify(liveData) !== JSON.stringify(configSnapshot.data)) {
            history.push(
                new UpdateNodeDataCommand(configSnapshot.nodeId, configSnapshot.data, liveData, nodes),
            );
        }
    }
    configSnapshot = null;
};

watch(selectedNodeId, (newId, oldId) => {
    if (oldId) commitConfigSnapshot();
    if (newId) {
        const node = nodes.value.find((n) => n.id === newId);
        if (node) {
            configSnapshot = { nodeId: newId, data: cloneNodeData(node.data) };
        }
    }
});

const updateSelectedConfig = (newData: Record<string, unknown>) => {
    if (!selectedNode.value) return;
    const idx = nodes.value.findIndex((n) => n.id === selectedNode.value!.id);
    if (idx === -1) return;
    nodes.value[idx] = { ...nodes.value[idx], data: { ...nodes.value[idx].data, ...newData } };
};

const defaultConfigFor = (type: string): Record<string, unknown> => {
    switch (type) {
        case NodeType.Trigger: return {
            trigger_type: TriggerType.Schedule,
            cron: '0 9 * * *',
            schedule_field: ScheduleField.Days,
            schedule_days_interval: 1,
            schedule_hour: 9,
            schedule_minute: 0,
            schedule_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        };
        case NodeType.Generate: return { accounts: [], prompt_template: '', include_image: true, target_slide_count: 5 };
        case NodeType.Delay: return { duration: 1, unit: 'hours' };
        case NodeType.Condition: return { field: '', operator: 'contains', value: '' };
        case NodeType.Publish: return { mode: 'now', scheduled_offset: 60 };
        case NodeType.Webhook: return { url: '', method: 'POST', headers: {}, payload_template: '{}' };
        case NodeType.End: return { reason: '' };
        case NodeType.FetchRss: return { feed_url: '' };
        case NodeType.HttpRequest: return {
            url: '',
            method: 'GET',
            auth_type: 'none',
            headers: {},
            body_template: '',
            items_path: '',
            item_key_path: '',
            item_date_path: '',
        };
        default: return {};
    }
};

const createNodeAt = (type: string, position: { x: number; y: number }) => {
    const node: Node = {
        id: `node_${Date.now()}_${Math.floor(Math.random() * 1000)}`,
        type,
        position,
        data: defaultConfigFor(type),
    };
    nodes.value = [...nodes.value, node];
    history.push(new AddNodeCommand(node, nodes));
};

const addNodeOfType = (type: string) => {
    createNodeAt(type, { x: 200 + Math.random() * 100, y: 100 + Math.random() * 200 });
};

const onDragStart = (event: DragEvent, nodeType: string) => {
    if (!event.dataTransfer) return;
    event.dataTransfer.setData('application/automation-node-type', nodeType);
    event.dataTransfer.effectAllowed = 'move';
};

const onDragOver = (event: DragEvent) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
};

const onDrop = (event: DragEvent) => {
    event.preventDefault();
    const nodeType = event.dataTransfer?.getData('application/automation-node-type');
    if (!nodeType) return;
    const position = screenToFlowCoordinate({ x: event.clientX, y: event.clientY });
    createNodeAt(nodeType, position);
};

const deleteSelectedNode = () => {
    if (!selectedNode.value) return;
    const node = selectedNode.value;
    const cascadedEdges = edges.value.filter((e) => e.source === node.id || e.target === node.id);

    history.startBulk();
    cascadedEdges.forEach((e) => history.push(new RemoveEdgeCommand(e, edges)));
    history.push(new RemoveNodeCommand(node, nodes));
    history.endBulk();

    selectedNodeId.value = null;
    edges.value = edges.value.filter((e) => e.source !== node.id && e.target !== node.id);
    nodes.value = nodes.value.filter((n) => n.id !== node.id);
};

const isSaving = ref(false);
const isTestPanelOpen = ref(false);

const handleTestClick = () => {
    isTestPanelOpen.value = true;
};

const sanitizeNodes = (list: Node[]) =>
    list.map((n) => ({
        id: n.id,
        type: n.type,
        position: { x: n.position.x, y: n.position.y },
        data: n.data ?? {},
    }));

const sanitizeEdges = (list: Edge[]) =>
    list.map((e) => {
        const edge: Record<string, unknown> = {
            id: e.id,
            source: e.source,
            target: e.target,
        };
        if (e.sourceHandle) edge.source_handle = e.sourceHandle;
        if (e.targetHandle) edge.target_handle = e.targetHandle;
        return edge;
    });

const save = (): Promise<boolean> =>
    new Promise((resolve) => {
        if (isSaving.value) return resolve(false);
        isSaving.value = true;
        router.put(
            updateAutomation.url(props.automation.id),
            {
                name: name.value.trim() || props.automation.name,
                nodes: sanitizeNodes(nodes.value),
                connections: sanitizeEdges(edges.value),
            },
            {
                preserveScroll: true,
                onFinish: () => { isSaving.value = false; },
                onSuccess: () => {
                    toast.success(trans('automations.form.save_success'));
                    resolve(true);
                },
                onError: (errors: Record<string, string>) => {
                    const msg = (errors as any).message ?? trans('automations.form.save_error_fallback');
                    toast.error(msg);
                    resolve(false);
                },
            },
        );
    });

const closePanel = () => {
    selectedNodeId.value = null;
};

useShortcut('mod+s', save);
useShortcut('mod+z', () => {
    commitConfigSnapshot();
    history.undo();
});
useShortcut('mod+shift+z', () => {
    commitConfigSnapshot();
    history.redo();
});
useShortcut('backspace', () => {
    if (selectedNode.value) deleteSelectedNode();
}, { ignoreOnInput: true });
useShortcut('delete', () => {
    if (selectedNode.value) deleteSelectedNode();
}, { ignoreOnInput: true });

const defaultEdgeOptions = {
    type: 'smoothstep',
    animated: false,
    style: { strokeWidth: 3 },
    pathOptions: {
        borderRadius: 32,
        offset: 24,
    },
    markerEnd: {
        type: MarkerType.ArrowClosed,
        width: 18,
        height: 18,
        color: '#0a0a0a',
    },
};
</script>

<template>
    <Head :title="`Edit ${automation.name}`" />

    <AppLayout>
        <div class="fixed inset-0 z-50 flex flex-col bg-background">
            <header class="grid flex-shrink-0 grid-cols-[1fr_auto_1fr] items-center gap-3 border-b-2 border-foreground/10 bg-card px-4 py-2">
                <div class="flex items-center">
                    <Link :href="showAutomation.url(automation.id)">
                        <Button variant="outline" size="sm">← {{ $t('common.back') }}</Button>
                    </Link>
                </div>
                <input
                    v-model="name"
                    type="text"
                    :placeholder="$t('automations.form.name_placeholder')"
                    class="w-72 rounded-md border-2 border-transparent bg-transparent px-3 py-1 text-center text-sm font-semibold text-foreground transition-colors hover:border-foreground/15 focus:border-foreground focus:bg-background focus:outline-none"
                />
                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" size="sm" @click="handleTestClick">{{ $t('automations.actions.test') }}</Button>
                    <Button size="sm" @click="save" :disabled="isSaving">{{ $t('automations.actions.save') }}</Button>
                </div>
            </header>

            <div class="flex flex-1 overflow-hidden">
                <aside class="flex w-56 flex-shrink-0 flex-col gap-2 border-r-2 border-foreground/10 bg-card/30 p-4">
                    <p class="mb-1 text-[11px] font-black uppercase tracking-widest text-foreground/60">
                        {{ $t('automations.actions.add_node') }}
                    </p>
                    <button
                        v-for="option in nodeTypeOptions"
                        :key="option.type"
                        draggable="true"
                        class="group flex cursor-grab items-center gap-2.5 rounded-xl border-2 border-foreground bg-card p-2.5 text-left text-sm font-bold text-foreground shadow-[2px_2px_0_var(--foreground)] transition-all hover:-translate-x-px hover:-translate-y-px hover:shadow-[3px_3px_0_var(--foreground)] active:translate-x-0 active:translate-y-0 active:rotate-[-1deg] active:cursor-grabbing active:shadow-[1px_1px_0_var(--foreground)]"
                        @dragstart="onDragStart($event, option.type)"
                        @click="addNodeOfType(option.type)"
                    >
                        <div :class="['flex size-7 -rotate-3 items-center justify-center rounded-md border-2 border-foreground', accentClasses[option.accent].tint]">
                            <component :is="option.icon" :size="14" :class="accentClasses[option.accent].text" />
                        </div>
                        <span class="flex-1">{{ option.label }}</span>
                    </button>
                </aside>

                <main
                    class="automations-canvas-host relative flex-1"
                    @drop="onDrop"
                    @dragover="onDragOver"
                >
                    <VueFlow
                        v-model:nodes="nodes"
                        v-model:edges="edges"
                        :node-types="nodeTypes"
                        :default-edge-options="defaultEdgeOptions"
                        :connection-mode="ConnectionMode.Loose"
                        :delete-key-code="null"
                        :snap-to-grid="true"
                        :snap-grid="[16, 16]"
                        fit-view-on-init
                        class="automations-canvas"
                    >
                        <Background
                            pattern-color="#9ca3af"
                            :gap="16"
                            :size="1"
                        />
                        <Controls position="bottom-left" />
                        <template #connection-line="props">
                            <AutomationConnectionLine v-bind="props" />
                        </template>
                    </VueFlow>

                    <div
                        v-if="nodes.length === 0"
                        class="pointer-events-none absolute inset-0 flex items-center justify-center"
                    >
                        <div class="text-center text-muted-foreground">
                            <IconBolt :size="48" class="mx-auto mb-3 opacity-40" />
                            <p class="font-medium">{{ $t('automations.form.empty_canvas_title') }}</p>
                            <p class="mt-1 text-sm">{{ $t('automations.form.empty_canvas_description') }}</p>
                        </div>
                    </div>
                </main>

                <TestRunPanel
                    v-if="isTestPanelOpen"
                    v-model:open="isTestPanelOpen"
                    :automation-id="automation.id"
                    :before-run="save"
                />

                <aside
                    v-else-if="selectedNode"
                    class="flex w-[36rem] flex-shrink-0 flex-col gap-4 overflow-y-auto border-l-2 border-foreground/10 px-4 pt-4 pb-12"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold capitalize">{{ $t('automations.form.config_title', { type: $t(`automations.nodes.${selectedNode.type}`) }) }}</h2>
                        <div class="flex items-center gap-1">
                            <Button variant="ghost" size="icon-sm" @click="deleteSelectedNode">
                                <IconTrash class="size-4" />
                            </Button>
                            <Button variant="ghost" size="icon-sm" @click="closePanel">
                                <IconX class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <component
                        :is="selectedConfigComponent"
                        :data="selectedNode.data"
                        :errors="selectedNodeErrors"
                        @update="updateSelectedConfig"
                    />
                </aside>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Canvas surface — n8n uses a near-white gray (#f5f5f5) as the canvas background.
   TryPost uses a warm cream (#faf8f5) for brand consistency. Dots use n8n's
   neutral-500 gray, drawn at 1px with 16px gap (n8n's GRID_SIZE) so they are
   crisp and clearly visible against either tint. */
.automations-canvas-host {
    background-color: var(--background);
}

.automations-canvas .vue-flow__background {
    background-color: transparent;
}

/* Controls — keep TryPost's ink-border, hard-shadow identity but adopt n8n's
   compact, square-icon layout (icons sit vertically on the bottom-left). */
.automations-canvas .vue-flow__controls {
    box-shadow: var(--shadow-sm);
    border: 2px solid var(--foreground);
    border-radius: 0.625rem;
    overflow: hidden;
    background: var(--card);
}

.automations-canvas .vue-flow__controls-button {
    background: var(--card);
    border-bottom: 1px solid color-mix(in srgb, var(--foreground) 10%, transparent);
    color: var(--foreground);
    width: 28px;
    height: 28px;
    transition: background-color 120ms ease;
}

.automations-canvas .vue-flow__controls-button:last-child {
    border-bottom: none;
}

.automations-canvas .vue-flow__controls-button:hover {
    background: var(--muted);
}

.automations-canvas .vue-flow__controls-button svg {
    fill: currentColor;
    max-width: 14px;
    max-height: 14px;
}

/* Edges — TryPost ink-on-cream. Default uses the foreground color at 55% so
   it reads as a confident line without being heavy. Hover/selected pop to full
   ink. The arrowhead marker (configured per edge in JS) inherits stroke color
   via `context-stroke`. */
.automations-canvas .vue-flow__edge-path {
    stroke: color-mix(in srgb, var(--foreground) 60%, transparent);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
    transition: stroke 120ms ease;
}

.automations-canvas .vue-flow__edge:hover .vue-flow__edge-path,
.automations-canvas .vue-flow__edge.selected .vue-flow__edge-path,
.automations-canvas .vue-flow__edge:focus .vue-flow__edge-path {
    stroke: var(--foreground);
}

.automations-canvas .vue-flow__connection-path,
.automations-canvas .vue-flow__connectionline .vue-flow__edge-path {
    stroke: var(--foreground);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}


/* Handles — solid ink-bordered dots in the TryPost brutalist style. Offset by
   -2px on the active side compensates for the node's 2px border so the dot
   straddles the border (50% inside / 50% outside). */
.automations-canvas .vue-flow__handle {
    width: 14px;
    height: 14px;
    border-radius: 9999px;
    border: 2px solid var(--foreground);
    transition: transform 120ms ease;
}

/* Invisible halo expands the clickable / hoverable hit area without changing
   the visual dot size. Matches the cross cursor users see when starting a
   connection. */
.automations-canvas .vue-flow__handle::before {
    content: '';
    position: absolute;
    inset: -10px;
    border-radius: 9999px;
}

.automations-canvas .vue-flow__handle.vue-flow__handle-right {
    right: -2px;
}

.automations-canvas .vue-flow__handle.vue-flow__handle-left {
    left: -2px;
}

.automations-canvas .vue-flow__handle.vue-flow__handle-right:hover {
    transform: translate(50%, -50%) scale(1.5);
}

.automations-canvas .vue-flow__handle.vue-flow__handle-left:hover {
    transform: translate(-50%, -50%) scale(1.5);
}

.automations-canvas .vue-flow__handle.vue-flow__handle-top:hover {
    transform: translate(-50%, -50%) scale(1.5);
}

.automations-canvas .vue-flow__handle.vue-flow__handle-bottom:hover {
    transform: translate(-50%, 50%) scale(1.5);
}

/* Node selection ring — n8n uses a wide soft halo; we keep the ring crisp but
   tighter so it pairs with the ink-border identity. */
.automations-canvas .vue-flow__node.selected {
    z-index: 10;
}
</style>
