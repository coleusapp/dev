<script setup lang="ts">
import TeamMenu from '@coleus/support/components/TeamMenu.vue';
import UserMenu from '@coleus/support/components/UserMenu.vue';
import type { NavigationMenuItem } from '@nuxt/ui';
import { computed, ref } from 'vue';

const open = ref(false);

const links = [
    [
        {
            label: 'Home',
            icon: 'i-lucide-house',
            to: '/',
            exact: true,
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Files',
            icon: 'i-lucide-archive',
            to: '/files',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Calendar',
            icon: 'i-lucide-calendar',
            to: '/calendar',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Mail',
            icon: 'i-lucide-mail',
            to: '/mail',
            badge: '4',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Health',
            icon: 'i-lucide-heart-pulse',
            to: '/health',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Music',
            icon: 'i-lucide-music',
            to: '/music',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Finance',
            icon: 'i-lucide-wallet-minimal',
            to: '/music',
            onSelect: () => {
                open.value = false;
            },
        },
        {
            label: 'Settings',
            to: '/settings',
            icon: 'i-lucide-settings',
            defaultOpen: false,
            type: 'trigger',
            children: [
                {
                    label: 'General',
                    to: '/settings',
                    exact: true,
                    onSelect: () => {
                        open.value = false;
                    },
                },
                {
                    label: 'Members',
                    to: '/settings/members',
                    onSelect: () => {
                        open.value = false;
                    },
                },
                {
                    label: 'Notifications',
                    to: '/settings/notifications',
                    onSelect: () => {
                        open.value = false;
                    },
                },
                {
                    label: 'Security',
                    to: '/settings/security',
                    onSelect: () => {
                        open.value = false;
                    },
                },
                {
                    label: 'Users',
                    to: '/settings/users',
                    onSelect: () => {
                        open.value = false;
                    },
                },
            ],
        },
    ],
    [
        {
            label: 'Feedback',
            icon: 'i-lucide-message-circle',
            to: 'https://github.com/coleusapp/coleus',
            target: '_blank',
        },
    ],
] satisfies NavigationMenuItem[][];

const groups = computed(() => [
    {
        id: 'links',
        label: 'Go to',
        items: links.flat(),
    },
    {
        id: 'code',
        label: 'Code',
        items: [
            {
                id: 'source',
                label: 'View page source',
                icon: 'simple-icons:github',
                to: `https://github.com/nuxt-ui-templates/dashboard-vue/blob/main/src/pages${route.path === '/' ? '/index' : route.path}.vue`,
                target: '_blank',
            },
        ],
    },
]);
</script>
<template>
    <UiApp :toaster="{ position: 'top-center', duration: 2500 }" :tooltip="{ delayDuration: 0 }">
        <UiDashboardGroup unit="rem" storage="local">
            <UiDashboardSidebar
                id="default"
                v-model:open="open"
                collapsible
                resizable
                class="bg-elevated/25"
                :ui="{ footer: 'lg:border-t lg:border-default' }"
            >
                <template #header="{ collapsed }">
                    <TeamMenu :collapsed="collapsed" />
                </template>

                <template #default="{ collapsed }">
                    <UiDashboardSearchButton :collapsed="collapsed" class="ring-default bg-transparent" />

                    <UiNavigationMenu :collapsed="collapsed" :items="links[0]" orientation="vertical" tooltip popover />

                    <UiNavigationMenu :collapsed="collapsed" :items="links[1]" orientation="vertical" tooltip class="mt-auto" />
                </template>

                <template #footer="{ collapsed }">
                    <UserMenu :collapsed="collapsed" />
                </template>
            </UiDashboardSidebar>

            <UiDashboardSearch :groups="groups" />

            <slot />

            <NotificationsSlideover />
        </UiDashboardGroup>
    </UiApp>
</template>
