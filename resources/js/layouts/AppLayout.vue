<script setup lang="ts">
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
    <UiApp :toaster="{ position: 'top-center', duration: 2500 }" :tooltip="{ delayDuration: 0 }">
        <div class="min-h-screen flex flex-col">
            <!-- Header -->
            <!--<header class="bg-gray-950 text-white py-2 px-4 flex justify-between items-center">-->
            <!--    <div class="flex items-center gap-2">-->
            <!--        <UiButton class="rounded-full text-white hover:text-white hover:bg-white/30" icon="i-lucide-menu" variant="link" color="neutral" size="lg"></UiButton>-->
            <!--    </div>-->
            <!--    <div class="flex items-center gap-2">-->
            <!--        <UiTooltip :content="{ side: 'bottom' }" text="Health">-->
            <!--            <UiButton class="rounded-full text-white hover:text-white hover:bg-white/30" icon="i-lucide-heart-pulse" variant="link" color="neutral" size="lg"></UiButton>-->
            <!--        </UiTooltip>-->
            <!--        <UiTooltip :content="{ side: 'bottom' }" text="Settings">-->
            <!--            <UiButton class="rounded-full text-white hover:text-white hover:bg-white/30" icon="i-lucide-settings" variant="link" color="neutral" size="lg"></UiButton>-->
            <!--        </UiTooltip>-->
            <!--        <UiTooltip :content="{ side: 'bottom' }" text="Apps">-->
            <!--            <UiButton class="rounded-full text-white hover:text-white hover:bg-white/30" icon="i-lucide-grip" variant="link" color="neutral" size="lg"></UiButton>-->
            <!--        </UiTooltip>-->
            <!--    </div>-->
            <!--</header>-->

            <main class="flex h-screen overflow-scroll bg-gray-100">
                <slot />
            </main>
        </div>


        <!--<div class="flex h-screen w-screen flex-col bg-zinc-50">-->
        <!--    &lt;!&ndash;<div class="flex h-8 w-screen items-center bg-white/30 px-4 py-2 backdrop-blur-sm">&ndash;&gt;-->
        <!--    &lt;!&ndash;    <div class="flex w-full items-center justify-between">&ndash;&gt;-->
        <!--    &lt;!&ndash;        <div class="flex items-center justify-start">&ndash;&gt;-->
        <!--    &lt;!&ndash;            <span class="font-bold"><slot name="name"></slot></span>&ndash;&gt;-->
        <!--    &lt;!&ndash;            <slot name="left"></slot>&ndash;&gt;-->
        <!--    &lt;!&ndash;        </div>&ndash;&gt;-->
        <!--    &lt;!&ndash;        <div class="flex items-center justify-end gap-2">&ndash;&gt;-->
        <!--    &lt;!&ndash;            <slot name="right">&ndash;&gt;-->
        <!--    &lt;!&ndash;                <UiButton variant="ghost" icon="lucide-settings-2" size="xs" color="neutral" />&ndash;&gt;-->
        <!--    &lt;!&ndash;                <UiSlideover :overlay="false" :close="false">&ndash;&gt;-->
        <!--    &lt;!&ndash;                    <UiButton variant="ghost" size="xs" color="neutral">&ndash;&gt;-->
        <!--    &lt;!&ndash;                        <Time />&ndash;&gt;-->
        <!--    &lt;!&ndash;                    </UiButton>&ndash;&gt;-->
        <!--    &lt;!&ndash;                    <template #body>&ndash;&gt;-->
        <!--    &lt;!&ndash;                        <div class="flex items-center justify-between gap-2">&ndash;&gt;-->
        <!--    &lt;!&ndash;                            <Weather class="max-w-1/2" />&ndash;&gt;-->
        <!--    &lt;!&ndash;                            <Calendar class="max-w-1/2" />&ndash;&gt;-->
        <!--    &lt;!&ndash;                        </div>&ndash;&gt;-->
        <!--    &lt;!&ndash;                    </template>&ndash;&gt;-->
        <!--    &lt;!&ndash;                    <template #footer>&ndash;&gt;-->
        <!--    &lt;!&ndash;                        <div class="flex w-full items-center justify-center gap-2">&ndash;&gt;-->
        <!--    &lt;!&ndash;                            <UiButton color="neutral" variant="outline" class="rounded-full" size="xs">Edit Widgets </UiButton>&ndash;&gt;-->
        <!--    &lt;!&ndash;                        </div>&ndash;&gt;-->
        <!--    &lt;!&ndash;                    </template>&ndash;&gt;-->
        <!--    &lt;!&ndash;                </UiSlideover>&ndash;&gt;-->
        <!--    &lt;!&ndash;            </slot>&ndash;&gt;-->
        <!--    &lt;!&ndash;        </div>&ndash;&gt;-->
        <!--    &lt;!&ndash;    </div>&ndash;&gt;-->
        <!--    &lt;!&ndash;</div>&ndash;&gt;-->
        <!--    <div class="flex h-full max-h-screen w-full">-->
        <!--        <aside class="flex w-12 flex-col justify-between py-2">-->
        <!--            <div class="flex flex-col items-center gap-2 overflow-scroll">-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Apps">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-layout-grid" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Storage">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-archive" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Health">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-heart-pulse" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Calculator">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-calculator" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Mail">-->
        <!--                    <UiChip color="error" inset>-->
        <!--                        <UiButton class="rounded-full" icon="i-lucide-mail" variant="ghost" color="neutral"></UiButton>-->
        <!--                    </UiChip>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Store">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-store" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Browser">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-earth" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Contacts">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-contact" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Calendar">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-calendar-range" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Reminders">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-list-todo" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Notes">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-notebook-pen" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Maps">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-map" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Photos">-->
        <!--                    <UiChip color="error" inset>-->
        <!--                        <UiButton class="rounded-full" icon="i-lucide-images" variant="ghost" color="neutral"></UiButton>-->
        <!--                    </UiChip>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Music">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-music" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Books">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-library-big" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Weather">-->
        <!--                    <UiChip color="primary" inset>-->
        <!--                        <UiButton class="rounded-full" icon="i-lucide-cloud-sun" variant="subtle" color="primary"></UiButton>-->
        <!--                    </UiChip>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Player">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-square-play" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Clock">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-alarm-clock" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--            </div>-->
        <!--            <div class="flex flex-col gap-2 overflow-scroll" :class="[isDockOpen ? 'items-start' : 'items-center']">-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Terminal">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-terminal" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Monitoring">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-cpu" variant="subtle" color="primary"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiTooltip :content="{ side: 'right' }" text="Settings">-->
        <!--                    <UiButton class="rounded-full" icon="i-lucide-settings" variant="ghost" color="neutral"></UiButton>-->
        <!--                </UiTooltip>-->
        <!--                <UiDropdownMenu-->
        <!--                    :items="items"-->
        <!--                    :ui="{-->
        <!--                        content: 'w-48',-->
        <!--                    }"-->
        <!--                >-->
        <!--                    <UiButton-->
        <!--                        :avatar="{-->
        <!--                            src: 'https://images.unsplash.com/photo-1554151228-14d9def656e4?q=80&w=64&h=64&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',-->
        <!--                        }"-->
        <!--                        size="md"-->
        <!--                        color="neutral"-->
        <!--                        variant="ghost"-->
        <!--                        class="rounded-full"-->
        <!--                    />-->
        <!--                </UiDropdownMenu>-->
        <!--            </div>-->
        <!--        </aside>-->
        <!--        <main class="flex max-h-full w-full flex-col bg-white">-->
        <!--            <slot />-->
        <!--        </main>-->
        <!--    </div>-->
        <!--</div>-->
    </UiApp>
</template>
