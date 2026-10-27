<script setup lang="ts">
import type { SlashCommand } from '@/editor/slashCommands';

defineProps<{
    commands: SlashCommand[];
    selected: number;
    position: { left: number; top: number };
}>();

defineEmits<{
    choose: [command: SlashCommand];
}>();
</script>

<template>
    <div
        class="bg-default ring-default fixed z-50 max-h-72 w-60 overflow-y-auto rounded-md p-1 shadow-lg ring"
        :style="{ left: `${position.left}px`, top: `${position.top + 4}px` }"
        role="listbox"
        aria-label="Insert"
    >
        <p v-if="!commands.length" class="text-dimmed px-2 py-1.5 text-sm">No matching commands</p>
        <button
            v-for="(command, index) in commands"
            :key="command.id"
            type="button"
            role="option"
            :aria-selected="index === selected"
            class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-sm"
            :class="index === selected ? 'bg-elevated' : 'hover:bg-elevated'"
            @mousedown.prevent="$emit('choose', command)"
        >
            <UiIcon :name="command.icon" class="text-dimmed size-4" />
            {{ command.label }}
        </button>
    </div>
</template>
