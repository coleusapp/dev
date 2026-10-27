<script setup lang="ts">
import { onMounted, useTemplateRef } from 'vue';

defineProps<{
    count: number;
    current: number;
    error: string | null;
}>();

const emit = defineEmits<{
    next: [];
    previous: [];
    replace: [];
    replaceAll: [];
    close: [];
}>();

const query = defineModel<string>('query', { required: true });
const replacement = defineModel<string>('replacement', { required: true });
const caseSensitive = defineModel<boolean>('caseSensitive', { required: true });
const regex = defineModel<boolean>('regex', { required: true });
const showReplace = defineModel<boolean>('showReplace', { required: true });

const input = useTemplateRef<{ inputRef?: HTMLInputElement }>('input');

const focus = (): void => {
    input.value?.inputRef?.focus();
    input.value?.inputRef?.select();
};

onMounted(focus);

defineExpose({ focus });

const onFindKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter') {
        event.preventDefault();
        emit(event.shiftKey ? 'previous' : 'next');
    } else if (event.key === 'Escape') {
        event.preventDefault();
        emit('close');
    }
};
</script>

<template>
    <div class="bg-elevated flex flex-col gap-1.5 rounded-md p-2" role="search" aria-label="Find in note">
        <div class="flex items-center gap-1">
            <UiButton
                :icon="showReplace ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                :aria-label="showReplace ? 'Hide replace' : 'Show replace'"
                color="neutral"
                variant="ghost"
                size="xs"
                @click="showReplace = !showReplace"
            />
            <UiInput ref="input" v-model="query" placeholder="Find" aria-label="Find" size="sm" class="flex-1" @keydown="onFindKeydown" />
            <UiButton
                label="Aa"
                aria-label="Match case"
                :aria-pressed="caseSensitive"
                :color="caseSensitive ? 'primary' : 'neutral'"
                variant="ghost"
                size="xs"
                @click="caseSensitive = !caseSensitive"
            />
            <UiButton
                label=".*"
                aria-label="Regular expression"
                :aria-pressed="regex"
                :color="regex ? 'primary' : 'neutral'"
                variant="ghost"
                size="xs"
                @click="regex = !regex"
            />
            <span class="text-dimmed w-16 text-center text-xs" role="status">{{
                query ? (count ? `${current + 1} of ${count}` : 'No results') : ''
            }}</span>
            <UiButton
                icon="i-lucide-arrow-up"
                aria-label="Previous match"
                color="neutral"
                variant="ghost"
                size="xs"
                :disabled="!count"
                @click="emit('previous')"
            />
            <UiButton
                icon="i-lucide-arrow-down"
                aria-label="Next match"
                color="neutral"
                variant="ghost"
                size="xs"
                :disabled="!count"
                @click="emit('next')"
            />
            <UiButton icon="i-lucide-x" aria-label="Close find" color="neutral" variant="ghost" size="xs" @click="emit('close')" />
        </div>
        <div v-if="showReplace" class="flex items-center gap-1 pl-7">
            <UiInput
                v-model="replacement"
                placeholder="Replace with"
                aria-label="Replace with"
                size="sm"
                class="flex-1"
                @keydown.esc.prevent="emit('close')"
            />
            <UiButton size="xs" color="neutral" variant="subtle" :disabled="!count" @click="emit('replace')">Replace</UiButton>
            <UiButton size="xs" color="neutral" variant="subtle" :disabled="!count" @click="emit('replaceAll')">Replace all</UiButton>
        </div>
        <p v-if="error" class="text-error pl-7 text-xs" role="alert">{{ error }}</p>
    </div>
</template>
