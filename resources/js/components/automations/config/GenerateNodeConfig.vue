<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { IconCheck } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import FacebookSettings from '@/components/posts/editor/FacebookSettings.vue';
import InstagramSettings from '@/components/posts/editor/InstagramSettings.vue';
import LinkedInSettings from '@/components/posts/editor/LinkedInSettings.vue';
import PinterestSettings from '@/components/posts/editor/PinterestSettings.vue';
import TikTokSettings from '@/components/posts/editor/TikTokSettings.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ContentType } from '@/types/content-type';
import { Platform } from '@/types/platform';
import type { PinterestBoard } from '@/types';

interface SocialAccount {
    id: string;
    platform: string;
    display_name: string;
    username: string;
    avatar_url: string | null;
}

interface TikTokCreatorInfo {
    creator_nickname: string | null;
    creator_username: string | null;
    creator_avatar_url: string | null;
    privacy_level_options: string[];
    comment_disabled: boolean;
    duet_disabled: boolean;
    stitch_disabled: boolean;
    max_video_post_duration_sec: number | null;
}

interface GenerateAccount {
    social_account_id: string;
    content_type: string;
    meta: Record<string, any>;
}

interface GenerateConfig {
    accounts: GenerateAccount[];
    target_slide_count?: number;
    prompt_template: string;
    image_source: 'ai' | 'unsplash' | 'none';
}

const props = defineProps<{
    data: Record<string, unknown>;
    errors?: Record<string, string>;
}>();
const emit = defineEmits<{ update: [Record<string, unknown>] }>();

const page = usePage();

const socialAccounts = computed<SocialAccount[]>(() => {
    const raw = page.props.socialAccounts as { data?: SocialAccount[] } | SocialAccount[] | undefined;
    if (!raw) return [];
    return Array.isArray(raw) ? (raw as SocialAccount[]) : ((raw as { data: SocialAccount[] }).data ?? []);
});

const platformConfigs = computed<Record<string, any>>(() => {
    const raw = page.props.platformConfigs as Record<string, any> | undefined;
    return raw ?? {};
});

const pinterestBoards = computed<Record<string, PinterestBoard[]>>(() => {
    const raw = page.props.pinterestBoards as Record<string, PinterestBoard[]> | undefined;
    return raw ?? {};
});

const tiktokCreatorInfos = computed<Record<string, TikTokCreatorInfo>>(() => {
    const raw = page.props.tiktokCreatorInfos as Record<string, TikTokCreatorInfo> | null | undefined;
    return raw ?? {};
});

const defaultContentTypeFor = (platform: string): string => {
    switch (platform) {
        case Platform.Instagram:
        case Platform.InstagramFacebook:
            return ContentType.InstagramFeed;
        case Platform.Facebook:
            return ContentType.FacebookPost;
        case Platform.LinkedIn:
            return ContentType.LinkedInPost;
        case Platform.LinkedInPage:
            return ContentType.LinkedInPagePost;
        case Platform.TikTok:
            return ContentType.TikTokVideo;
        case Platform.Pinterest:
            return ContentType.PinterestPin;
        case Platform.YouTube:
            return ContentType.YouTubeShort;
        case Platform.X:
            return ContentType.XPost;
        case Platform.Threads:
            return ContentType.ThreadsPost;
        case Platform.Bluesky:
            return ContentType.BlueskyPost;
        case Platform.Mastodon:
            return ContentType.MastodonPost;
        default:
            return '';
    }
};

const accountById = (id: string): SocialAccount | undefined =>
    socialAccounts.value.find((a) => a.id === id);

const normalizeAccountsFromData = (): GenerateAccount[] => {
    const incoming = props.data.accounts;
    if (Array.isArray(incoming)) {
        return (incoming as any[]).map((a) => ({
            social_account_id: String(a.social_account_id ?? ''),
            content_type: typeof a.content_type === 'string' && a.content_type
                ? a.content_type
                : defaultContentTypeFor(accountById(String(a.social_account_id ?? ''))?.platform ?? ''),
            meta: (a.meta as Record<string, any>) ?? {},
        })).filter((a) => a.social_account_id);
    }
    // Backward-compat with the old shape (social_account_ids: string[]).
    const legacyIds = props.data.social_account_ids;
    if (Array.isArray(legacyIds)) {
        return (legacyIds as string[]).map((id) => ({
            social_account_id: id,
            content_type: defaultContentTypeFor(accountById(id)?.platform ?? ''),
            meta: {},
        }));
    }
    return [];
};

const local = ref<GenerateConfig>({
    accounts: normalizeAccountsFromData(),
    target_slide_count: props.data.target_slide_count as number | undefined,
    prompt_template: (props.data.prompt_template as string) ?? '',
    image_source: (props.data.image_source as GenerateConfig['image_source']) ?? 'ai',
});

watch(local, (val) => emit('update', val), { deep: true });

const isSelected = (accountId: string): boolean =>
    local.value.accounts.some((a) => a.social_account_id === accountId);

const toggleAccount = (account: SocialAccount) => {
    if (isSelected(account.id)) {
        local.value.accounts = local.value.accounts.filter((a) => a.social_account_id !== account.id);
        return;
    }
    local.value.accounts = [
        ...local.value.accounts,
        {
            social_account_id: account.id,
            content_type: defaultContentTypeFor(account.platform),
            meta: {},
        },
    ];
};

const selectedAccounts = computed(() =>
    local.value.accounts
        .map((entry) => {
            const account = accountById(entry.social_account_id);
            return account ? { entry, account } : null;
        })
        .filter((pair): pair is { entry: GenerateAccount; account: SocialAccount } => pair !== null),
);

const updateContentType = (accountId: string, value: string) => {
    const idx = local.value.accounts.findIndex((a) => a.social_account_id === accountId);
    if (idx === -1) return;
    local.value.accounts[idx] = { ...local.value.accounts[idx], content_type: value };
};

const updateMeta = (accountId: string, value: Record<string, any>) => {
    const idx = local.value.accounts.findIndex((a) => a.social_account_id === accountId);
    if (idx === -1) return;
    local.value.accounts[idx] = { ...local.value.accounts[idx], meta: value };
};

const getPublishConfig = (account: SocialAccount): Record<string, any> | null =>
    platformConfigs.value[account.id]?.publishConfig ?? null;

const getCreatorInfo = (account: SocialAccount): TikTokCreatorInfo | null =>
    tiktokCreatorInfos.value[account.id] ?? null;

const getBoards = (account: SocialAccount): PinterestBoard[] =>
    pinterestBoards.value[account.id] ?? [];

// Carousel-capable content types: instagram_feed, linkedin_carousel,
// linkedin_page_carousel, pinterest_carousel, tiktok_photo.
const carouselCapableContentTypes = new Set([
    ContentType.InstagramFeed,
    ContentType.LinkedInCarousel,
    ContentType.LinkedInPageCarousel,
    ContentType.PinterestCarousel,
    ContentType.TikTokPhoto,
]);

const hasCarouselCapableAccount = computed(() =>
    local.value.accounts.some((a) => carouselCapableContentTypes.has(a.content_type as ContentType)),
);
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <Label class="text-sm font-bold">{{ $t('automations.config.generate.social_accounts') }}</Label>
            <InputError :message="errors?.accounts" />
            <p v-if="socialAccounts.length === 0" class="text-xs text-foreground/60">
                {{ $t('automations.config.generate.social_accounts_empty') }}
            </p>
            <div v-else class="space-y-2 px-1 pb-1">
                <button
                    v-for="account in socialAccounts"
                    :key="account.id"
                    type="button"
                    class="relative flex w-full cursor-pointer items-center gap-2 rounded-xl border-2 border-foreground bg-card p-2.5 text-left text-sm shadow-2xs transition-all hover:bg-foreground/5"
                    :class="{ '!bg-violet-100 shadow-md': isSelected(account.id) }"
                    @click="toggleAccount(account)"
                >
                    <span class="inline-flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-foreground bg-card shadow-2xs">
                        <img
                            v-if="account.avatar_url"
                            :src="account.avatar_url"
                            :alt="account.display_name"
                            class="size-full object-cover"
                        />
                        <span v-else class="text-xs font-bold text-foreground">{{ account.display_name.charAt(0).toUpperCase() }}</span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold leading-tight text-foreground">{{ account.display_name }}</p>
                        <p class="truncate text-xs font-medium capitalize text-foreground/60">
                            {{ account.platform }}<span v-if="account.username"> · @{{ account.username }}</span>
                        </p>
                    </div>
                    <IconCheck
                        v-if="isSelected(account.id)"
                        class="absolute right-2 top-2 size-3.5 text-foreground"
                        stroke-width="3"
                    />
                </button>
            </div>
        </div>

        <div v-if="selectedAccounts.length > 0" class="space-y-3">
            <template v-for="{ entry, account } in selectedAccounts" :key="account.id">
                <InstagramSettings
                    v-if="account.platform === Platform.Instagram || account.platform === Platform.InstagramFacebook"
                    :social-account="account"
                    :content-type="entry.content_type"
                    :media="[]"
                    :meta="entry.meta"
                    :preview-only="true"
                    @update:content-type="updateContentType(account.id, $event)"
                    @update:meta="updateMeta(account.id, $event)"
                />
                <FacebookSettings
                    v-else-if="account.platform === Platform.Facebook"
                    :social-account="account"
                    :content-type="entry.content_type"
                    :media="[]"
                    :preview-only="true"
                    @update:content-type="updateContentType(account.id, $event)"
                />
                <TikTokSettings
                    v-else-if="account.platform === Platform.TikTok"
                    :social-account="account"
                    :publish-config="getPublishConfig(account)"
                    :creator-info="getCreatorInfo(account)"
                    :video-duration-sec="null"
                    :content-type="entry.content_type"
                    :meta="entry.meta"
                    :preview-only="true"
                    @update:content-type="updateContentType(account.id, $event)"
                    @update:meta="updateMeta(account.id, $event)"
                />
                <PinterestSettings
                    v-else-if="account.platform === Platform.Pinterest"
                    :social-account="account"
                    :content-type="entry.content_type"
                    :media="[]"
                    :boards="getBoards(account)"
                    :meta="entry.meta"
                    :preview-only="true"
                    @update:content-type="updateContentType(account.id, $event)"
                    @update:meta="updateMeta(account.id, $event)"
                />
                <LinkedInSettings
                    v-else-if="account.platform === Platform.LinkedIn || account.platform === Platform.LinkedInPage"
                    :social-account="account"
                    :platform="account.platform"
                    :content-type="entry.content_type"
                    :media="[]"
                    :preview-only="true"
                    @update:content-type="updateContentType(account.id, $event)"
                />
            </template>
        </div>

        <div v-if="hasCarouselCapableAccount">
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.generate.target_slide_count') }}</label>
            <Input type="number" v-model.number="local.target_slide_count" placeholder="5" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.generate.prompt_template') }}</label>
            <Textarea v-model="local.prompt_template" :rows="6" placeholder="Write a social media post about {{ trigger.title }}…" />
            <InputError :message="errors?.prompt_template" class="mt-1" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">{{ $t('automations.config.generate.image_source') }}</label>
            <Select v-model="local.image_source">
                <SelectTrigger class="w-full">
                    <SelectValue :placeholder="$t('automations.config.select_placeholder')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="ai">{{ $t('automations.config.generate.image_sources.ai') }}</SelectItem>
                    <SelectItem value="unsplash">{{ $t('automations.config.generate.image_sources.unsplash') }}</SelectItem>
                    <SelectItem value="none">{{ $t('automations.config.generate.image_sources.none') }}</SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors?.image_source" class="mt-1" />
        </div>
    </div>
</template>
