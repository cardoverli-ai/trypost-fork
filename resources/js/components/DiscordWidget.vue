<script setup lang="ts">
import { IconBrandDiscord } from '@tabler/icons-vue';

import { captureEvent } from '@/posthog';

const DISCORD_URL = 'https://trypost.it/discord';

// Distinct from the marketing site's site_discord_clicked, following the
// app's noun.verb event convention.
const trackClick = () => {
    captureEvent('discord.clicked', { placement: 'floating_widget' });
};
</script>

<template>
    <a
        :href="DISCORD_URL"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="$t('common.discord.join')"
        class="group fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 rounded-full border-2 border-foreground bg-[#5865F2] py-2.5 pl-3 pr-4 text-sm font-bold text-white shadow-md transition-all hover:-translate-y-0.5 hover:shadow-lg"
        @click="trackClick"
    >
        <IconBrandDiscord class="size-5 shrink-0" stroke-width="2" />
        <span>{{ $t('common.discord.label') }}</span>
    </a>
</template>
