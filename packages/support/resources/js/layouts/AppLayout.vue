<script setup lang="ts">
import TeamMenu from '@coleus/support/components/TeamMenu.vue';
import UserMenu from '@coleus/support/components/UserMenu.vue';
import type { NavigationMenuItem } from '@nuxt/ui';
import { computed, ref } from 'vue';

defineProps<{
    title?: string;
}>();

const collapsed = ref(false);
const links = [
    [
        {
            label: 'Home',
            icon: 'i-lucide-house',
            to: '/',
            exact: true,
        },
        {
            label: 'Files',
            icon: 'i-lucide-archive',
            to: '/files',
        },
        {
            label: 'Calendar',
            icon: 'i-lucide-calendar',
            to: '/calendar',
        },
        {
            label: 'Mail',
            icon: 'i-lucide-mail',
            to: '/mail',
            badge: '4',
        },
        {
            label: 'Health',
            icon: 'i-lucide-heart-pulse',
            to: '/health',
            exact: true,
            open: route().current('health.*'),
            children: [
                {
                    label: 'Weights',
                    to: route('health.weights.index', [], false),
                },
                {
                    label: 'Workouts',
                    to: route('health.workouts.index', [], false),
                    open:
                        route().current('health.workouts.*') ||
                        route().current('health.categories.*') ||
                        route().current('health.muscle-groups.*') ||
                        route().current('health.exercises.*'),
                    children: [
                        {
                            label: 'Categories',
                            to: route('health.categories.index', [], false),
                            onSelect: () => {
                                open.value = false;
                            },
                        },
                        {
                            label: 'Muscle Groups',
                            to: route('health.muscle-groups.index', [], false),
                            onSelect: () => {
                                open.value = false;
                            },
                        },
                        {
                            label: 'Exercises',
                            to: route('health.exercises.index', [], false),
                            onSelect: () => {
                                open.value = false;
                            },
                        },
                    ],
                },
                {
                    label: 'Oral Cares',
                    to: route('health.oral-cares.index', [], false),
                    open: route().current('health.oral-cares.*') || route().current('health.toothpastes.*'),
                    children: [
                        {
                            label: 'Toothpastes',
                            to: route('health.toothpastes.index', [], false),
                        },
                    ],
                },
                {
                    label: 'Settings',
                    to: route('health.settings.general', [], false),
                },
            ],
        },
        {
            label: 'Music',
            icon: 'i-lucide-music',
            to: '/music',
        },
        {
            label: 'Finance',
            icon: 'i-lucide-wallet-minimal',
            to: '/music',
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
                },
                {
                    label: 'Members',
                    to: '/settings/members',
                },
                {
                    label: 'Notifications',
                    to: '/settings/notifications',
                },
                {
                    label: 'Security',
                    to: '/settings/security',
                },
                {
                    label: 'Users',
                    to: '/settings/users',
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
        <UiDashboardGroup>
            <UiDashboardSidebar v-model:collapsed="collapsed" mode="drawer" id="default">
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

            <UiDashboardPanel>
                <template #header>
                    <UiDashboardNavbar :title="title">
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" />
                        </template>
                    </UiDashboardNavbar>
                </template>
                <template #body>
                    <slot />
                </template>
            </UiDashboardPanel>
            <UiDashboardSearch :groups="groups" />
        </UiDashboardGroup>
    </UiApp>
</template>
