<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { IconPlus, IconTrash } from '@tabler/icons-vue';

import CodeEditor from '@/components/CodeEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
type AuthType = 'none' | 'bearer' | 'basic' | 'api_key';

interface HttpRequestConfig {
    url: string;
    method: Method;
    auth_type: AuthType;
    auth_token: string;
    auth_username: string;
    auth_password: string;
    auth_header_name: string;
    headers: Record<string, string>;
    body_template: string;
    items_path: string;
    item_key_path: string;
    item_date_path: string;
}

const props = defineProps<{
    data: Record<string, unknown>;
    errors?: Record<string, string>;
}>();
const emit = defineEmits<{ update: [Record<string, unknown>] }>();

const local = ref<HttpRequestConfig>({
    url: (props.data.url as string) ?? '',
    method: (props.data.method as Method) ?? 'GET',
    auth_type: (props.data.auth_type as AuthType) ?? 'none',
    auth_token: (props.data.auth_token as string) ?? '',
    auth_username: (props.data.auth_username as string) ?? '',
    auth_password: (props.data.auth_password as string) ?? '',
    auth_header_name: (props.data.auth_header_name as string) ?? 'X-API-Key',
    headers: (props.data.headers as Record<string, string>) ?? {},
    body_template: (props.data.body_template as string) ?? '',
    items_path: (props.data.items_path as string) ?? '',
    item_key_path: (props.data.item_key_path as string) ?? '',
    item_date_path: (props.data.item_date_path as string) ?? '',
});

interface HeaderRow {
    name: string;
    value: string;
}

const headerRows = ref<HeaderRow[]>(
    Object.entries((props.data.headers as Record<string, string>) ?? {}).map(([name, value]) => ({
        name,
        value: String(value),
    })),
);

watch(
    headerRows,
    (rows) => {
        const headers: Record<string, string> = {};
        rows.forEach((row) => {
            const name = row.name.trim();
            if (name !== '') {
                headers[name] = row.value;
            }
        });
        local.value.headers = headers;
    },
    { deep: true },
);

const addHeader = (): void => {
    headerRows.value.push({ name: '', value: '' });
};

const removeHeader = (index: number): void => {
    headerRows.value.splice(index, 1);
};

watch(local, (val) => emit('update', val), { deep: true });

const supportsBody = computed(() => ['POST', 'PUT', 'PATCH'].includes(local.value.method));
const isPollingMode = computed(() => local.value.items_path.trim() !== '');

const isBodyJsonInvalid = computed(() => {
    const value = local.value.body_template.trim();
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
    <div class="space-y-4">
        <div class="grid grid-cols-[110px_1fr] gap-2">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.method') }}</label>
                <Select v-model="local.method">
                    <SelectTrigger class="w-full">
                        <SelectValue />
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
                <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.url') }}</label>
                <Input v-model="local.url" placeholder="https://api.example.com/items" />
                <InputError :message="errors?.url" class="mt-1" />
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.auth_type') }}</label>
            <Select v-model="local.auth_type">
                <SelectTrigger class="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">{{ $t('automations.config.http_request.auth.none') }}</SelectItem>
                    <SelectItem value="bearer">{{ $t('automations.config.http_request.auth.bearer') }}</SelectItem>
                    <SelectItem value="basic">{{ $t('automations.config.http_request.auth.basic') }}</SelectItem>
                    <SelectItem value="api_key">{{ $t('automations.config.http_request.auth.api_key') }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="local.auth_type === 'bearer'">
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.bearer_token') }}</label>
            <Input v-model="local.auth_token" type="password" autocomplete="off" placeholder="sk-…" />
            <InputError :message="errors?.auth_token" class="mt-1" />
        </div>

        <template v-if="local.auth_type === 'basic'">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.basic_username') }}</label>
                <Input v-model="local.auth_username" autocomplete="off" />
                <InputError :message="errors?.auth_username" class="mt-1" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.basic_password') }}</label>
                <Input v-model="local.auth_password" type="password" autocomplete="off" />
                <InputError :message="errors?.auth_password" class="mt-1" />
            </div>
        </template>

        <template v-if="local.auth_type === 'api_key'">
            <div>
                <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.api_key_header') }}</label>
                <Input v-model="local.auth_header_name" placeholder="X-API-Key" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.api_key_value') }}</label>
                <Input v-model="local.auth_token" type="password" autocomplete="off" />
                <InputError :message="errors?.auth_token" class="mt-1" />
            </div>
        </template>

        <div v-if="supportsBody">
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.body_template') }}</label>
            <div class="h-36">
                <CodeEditor v-model="local.body_template" language="json" placeholder='{"id": "{{ trigger.post.id }}"}' />
            </div>
            <p v-if="isBodyJsonInvalid" class="mt-1 text-xs text-amber-600 dark:text-amber-500">
                {{ $t('automations.config.invalid_json') }}
            </p>
            <InputError :message="errors?.body_template" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.headers') }}</label>
            <div class="space-y-2">
                <div v-for="(row, index) in headerRows" :key="index" class="flex items-center gap-2">
                    <Input
                        v-model="row.name"
                        class="flex-1"
                        :placeholder="$t('automations.config.http_request.header_name')"
                    />
                    <Input
                        v-model="row.value"
                        class="flex-1"
                        :placeholder="$t('automations.config.http_request.header_value')"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="shrink-0 text-foreground/60 hover:text-destructive"
                        @click="removeHeader(index)"
                    >
                        <IconTrash class="size-4" />
                    </Button>
                </div>
            </div>
            <Button type="button" variant="outline" size="sm" class="mt-2" @click="addHeader">
                <IconPlus class="size-4" />
                {{ $t('automations.config.http_request.add_header') }}
            </Button>
        </div>

        <div class="border-t-2 border-foreground/10 pt-4">
            <p class="mb-2 text-[11px] font-black uppercase tracking-widest text-foreground/60">
                {{ $t('automations.config.http_request.polling_section') }}
            </p>
            <p class="mb-3 text-xs text-foreground/60">
                {{ $t('automations.config.http_request.polling_hint') }}
            </p>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.items_path') }}</label>
                    <Input v-model="local.items_path" placeholder="data.items" />
                    <InputError :message="errors?.items_path" class="mt-1" />
                </div>
                <template v-if="isPollingMode">
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.item_key_path') }}</label>
                        <Input v-model="local.item_key_path" placeholder="id" />
                        <InputError :message="errors?.item_key_path" class="mt-1" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.http_request.item_date_path') }}</label>
                        <Input v-model="local.item_date_path" placeholder="published_at" />
                        <InputError :message="errors?.item_date_path" class="mt-1" />
                        <p class="mt-1 text-xs text-foreground/50">{{ $t('automations.config.http_request.item_date_path_hint') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
