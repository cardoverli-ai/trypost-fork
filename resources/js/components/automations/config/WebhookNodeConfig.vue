<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import CodeEditor from '@/components/CodeEditor.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface WebhookConfig {
    url: string;
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    headers?: Record<string, string>;
    payload_template: string;
}

const props = defineProps<{
    data: Record<string, unknown>;
    errors?: Record<string, string>;
}>();
const emit = defineEmits<{ update: [Record<string, unknown>] }>();

const local = ref<WebhookConfig>({
    url: (props.data.url as string) ?? '',
    method: (props.data.method as WebhookConfig['method']) ?? 'POST',
    headers: (props.data.headers as Record<string, string>) ?? {},
    payload_template: (props.data.payload_template as string) ?? '{}',
});

watch(local, (val) => emit('update', val), { deep: true });

const isPayloadJsonInvalid = computed(() => {
    const value = local.value.payload_template.trim();
    if (value === '') {
        return false;
    }
    try {
        JSON.parse(value);
        return false;
    } catch {
        return true;
    }
});
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.webhook.url') }}</label>
            <Input v-model="local.url" placeholder="https://hooks.example.com/…" />
            <InputError :message="errors?.url" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.webhook.method') }}</label>
            <Select v-model="local.method">
                <SelectTrigger class="w-full">
                    <SelectValue :placeholder="$t('automations.config.select_placeholder')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="GET">GET</SelectItem>
                    <SelectItem value="POST">POST</SelectItem>
                    <SelectItem value="PUT">PUT</SelectItem>
                    <SelectItem value="PATCH">PATCH</SelectItem>
                    <SelectItem value="DELETE">DELETE</SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors?.method" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.webhook.payload_template') }}</label>
            <div class="h-40">
                <CodeEditor v-model="local.payload_template" language="json" placeholder='{"content": "{{ post.content }}"}' />
            </div>
            <p v-if="isPayloadJsonInvalid" class="mt-1 text-xs text-amber-600 dark:text-amber-500">
                {{ $t('automations.config.invalid_json') }}
            </p>
            <InputError :message="errors?.payload_template" class="mt-1" />
        </div>
    </div>
</template>
