<script setup lang="ts">
import Calendar from '@/components/Calendar.vue';
import Time from '@/components/Time.vue';
import Weather from '@/components/Weather.vue';
import type { DropdownMenuItem } from '@nuxt/ui';
import { ref } from 'vue';

const items = ref<DropdownMenuItem[]>([
    {
        label: 'Account',
        icon: 'i-lucide-user',
    },
    {
        label: 'Lock',
        icon: 'i-lucide-lock',
    },
    {
        label: 'Log out',
        icon: 'i-lucide-log-out',
    },
]);
</script>
<template>
    <UiApp :toaster="{ position: 'top-right' }" :tooltip="{ delayDuration: 0 }">
        <div class="flex h-screen w-screen flex-col bg-zinc-50">
            <div class="flex h-8 w-screen items-center bg-white/30 px-4 py-2 backdrop-blur-sm">
                <div class="flex w-full items-center justify-between">
                    <div class="flex items-center justify-start">
                        <span class="font-bold"><slot name="name"></slot></span>
                        <slot name="left"></slot>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <slot name="right">
                            <UiButton variant="ghost" icon="lucide-settings-2" size="xs" color="neutral" />
                            <UiSlideover :overlay="false" :close="false">
                                <UiButton variant="ghost" size="xs" color="neutral">
                                    <Time />
                                </UiButton>
                                <template #body>
                                    <div class="flex items-center justify-between gap-2">
                                        <Weather class="max-w-1/2" />
                                        <Calendar class="max-w-1/2" />
                                    </div>
                                </template>
                                <template #footer>
                                    <div class="flex w-full items-center justify-center gap-2">
                                        <UiButton color="neutral" variant="outline" class="rounded-full" size="xs">Edit Widgets </UiButton>
                                    </div>
                                </template>
                            </UiSlideover>
                        </slot>
                    </div>
                </div>
            </div>
            <div class="flex h-full max-h-[calc(100vh-calc(var(--spacing)*8))] w-full">
                <aside class="my-2 flex w-12 flex-col justify-between">
                    <div class="flex flex-col items-center gap-2 overflow-scroll">
                        <UiTooltip :content="{ side: 'right' }" text="Apps">
                            <UiButton class="rounded-full" icon="i-lucide-layout-grid" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Storage">
                            <UiButton class="rounded-full" icon="i-lucide-archive" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Health">
                            <UiButton class="rounded-full" icon="i-lucide-heart-pulse" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Calculator">
                            <UiButton class="rounded-full" icon="i-lucide-calculator" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Mail">
                            <UiChip color="error" inset>
                                <UiButton class="rounded-full" icon="i-lucide-mail" variant="ghost" color="neutral"></UiButton>
                            </UiChip>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Store">
                            <UiButton class="rounded-full" icon="i-lucide-store" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Browser">
                            <UiButton class="rounded-full" icon="i-lucide-earth" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Contacts">
                            <UiButton class="rounded-full" icon="i-lucide-contact" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Calendar">
                            <UiButton class="rounded-full" icon="i-lucide-calendar-range" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Reminders">
                            <UiButton class="rounded-full" icon="i-lucide-list-todo" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Notes">
                            <UiButton class="rounded-full" icon="i-lucide-notebook-pen" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Maps">
                            <UiButton class="rounded-full" icon="i-lucide-map" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Photos">
                            <UiChip color="error" inset>
                                <UiButton class="rounded-full" icon="i-lucide-images" variant="ghost" color="neutral"></UiButton>
                            </UiChip>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Music">
                            <UiButton class="rounded-full" icon="i-lucide-music" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Books">
                            <UiButton class="rounded-full" icon="i-lucide-library-big" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Weather">
                            <UiChip color="primary" inset>
                                <UiButton class="rounded-full" icon="i-lucide-cloud-sun" variant="subtle" color="primary"></UiButton>
                            </UiChip>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Player">
                            <UiButton class="rounded-full" icon="i-lucide-square-play" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Clock">
                            <UiButton class="rounded-full" icon="i-lucide-alarm-clock" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                    </div>
                    <div class="flex flex-col gap-2 overflow-scroll" :class="[isDockOpen ? 'items-start' : 'items-center']">
                        <UiTooltip :content="{ side: 'right' }" text="Terminal">
                            <UiButton class="rounded-full" icon="i-lucide-terminal" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Monitoring">
                            <UiButton class="rounded-full" icon="i-lucide-cpu" variant="subtle" color="primary"></UiButton>
                        </UiTooltip>
                        <UiTooltip :content="{ side: 'right' }" text="Settings">
                            <UiButton class="rounded-full" icon="i-lucide-settings" variant="ghost" color="neutral"></UiButton>
                        </UiTooltip>
                        <UiDropdownMenu
                            :items="items"
                            :ui="{
                                content: 'w-48',
                            }"
                        >
                            <UiButton
                                :avatar="{
                                    src: 'https://images.unsplash.com/photo-1554151228-14d9def656e4?q=80&w=64&h=64&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
                                }"
                                size="md"
                                color="neutral"
                                variant="ghost"
                                class="rounded-full"
                            />
                        </UiDropdownMenu>
                    </div>
                </aside>
                <main class="mr-2 mb-2 w-full rounded-lg border border-zinc-200 bg-white">
                    <slot />
                    <!--<div class="flex items-center justify-between gap-5 border-b border-b-zinc-200 p-2 text-sm">-->
                    <!--    <div class="w-full max-w-[48px]">-->
                    <!--        <UiButton icon="i-lucide-x" size="xs" variant="ghost" color="neutral" class="rounded-full"></UiButton>-->
                    <!--        <UiButton icon="i-lucide-minus" size="xs" variant="ghost" color="neutral" class="rounded-full"></UiButton>-->
                    <!--    </div>-->
                    <!--    <div class="w-full">-->
                    <!--        <slot name="header" />-->
                    <!--    </div>-->
                    <!--</div>-->
                    <!--<div class="p-2 overflow-scroll h-[calc(100vh-5.25rem)]">-->
                    <!--    <slot />-->
                    <!--</div>-->
                </main>
            </div>
        </div>
    </UiApp>
</template>
