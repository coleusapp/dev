<script setup lang="ts">
import { SHORTCUTS } from '@/lib/shortcuts';

const open = defineModel<boolean>('open', { required: true });

const groups = (['Notes', 'Editor'] as const).map((group) => ({ group, shortcuts: SHORTCUTS.filter((shortcut) => shortcut.group === group) }));
</script>

<template>
    <UiModal v-model:open="open" title="Keyboard shortcuts" description="Work anywhere in Notes, including while you write.">
        <template #body>
            <div class="grid gap-6 sm:grid-cols-2">
                <section v-for="{ group, shortcuts } in groups" :key="group" :aria-label="group">
                    <h3 class="text-dimmed mb-2 text-xs font-semibold">{{ group }}</h3>
                    <dl class="flex flex-col gap-1.5 text-sm">
                        <div v-for="shortcut in shortcuts" :key="shortcut.id" class="flex items-center justify-between gap-3">
                            <dt>{{ shortcut.label }}</dt>
                            <dd class="flex shrink-0 gap-0.5">
                                <UiKbd v-for="key in shortcut.keys" :key="key" :value="key" />
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>
        </template>
    </UiModal>
</template>
