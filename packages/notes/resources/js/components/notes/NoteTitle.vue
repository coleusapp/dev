<script setup lang="ts">
import { nextTick, ref, useTemplateRef } from 'vue';

const props = defineProps<{
    title: string;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    rename: [title: string];
}>();

const editing = ref(false);
const draft = ref('');
const input = useTemplateRef<HTMLInputElement>('input');

const start = async (): Promise<void> => {
    if (props.readonly) {
        return;
    }

    draft.value = props.title;
    editing.value = true;
    await nextTick();
    input.value?.focus();
    input.value?.select();
};

const commit = (): void => {
    if (!editing.value) {
        return;
    }

    editing.value = false;
    const title = draft.value.trim();

    if (title !== '' && title !== props.title) {
        emit('rename', title);
    }
};
</script>

<template>
    <input
        v-if="editing"
        ref="input"
        v-model="draft"
        class="w-full min-w-0 bg-transparent text-lg font-semibold focus:outline-none"
        aria-label="Note title"
        maxlength="250"
        @keydown.enter.prevent="commit"
        @keydown.esc.prevent="editing = false"
        @blur="commit"
    />
    <button
        v-else
        type="button"
        class="hover:bg-elevated -mx-1 min-w-0 truncate rounded px-1 text-start text-lg font-semibold"
        :class="{ 'text-dimmed': !title, 'cursor-default': readonly }"
        :title="readonly ? undefined : 'Rename (changes the first line of the note)'"
        @click="start"
    >
        {{ title || 'Untitled' }}
    </button>
</template>
