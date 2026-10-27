<script setup lang="ts">
import { EMOJI_GROUPS, searchEmoji } from '@/lib/emoji';
import { computed, ref } from 'vue';

const emit = defineEmits<{
    pick: [emoji: string];
}>();

const open = ref(false);
const query = ref('');

const groups = computed(() => (query.value.trim() ? [{ name: 'Results', emoji: searchEmoji(query.value) }] : EMOJI_GROUPS));

const pick = (emoji: string): void => {
    emit('pick', emoji);
    open.value = false;
    query.value = '';
};
</script>

<template>
    <UiPopover v-model:open="open" :content="{ align: 'start' }">
        <UiTooltip text="Emoji">
            <UiButton icon="i-lucide-smile-plus" aria-label="Emoji" color="neutral" variant="ghost" size="xs" @mousedown.prevent />
        </UiTooltip>
        <template #content>
            <div class="flex w-72 flex-col gap-2 p-2">
                <UiInput v-model="query" icon="i-lucide-search" placeholder="Search emoji" aria-label="Search emoji" size="sm" autofocus />
                <div class="max-h-64 overflow-y-auto">
                    <section v-for="group in groups" :key="group.name" class="mb-2">
                        <h3 class="text-dimmed mb-1 text-xs font-medium">{{ group.name }}</h3>
                        <p v-if="!group.emoji.length" class="text-dimmed text-sm">No emoji found.</p>
                        <div class="grid grid-cols-8 gap-0.5">
                            <button
                                v-for="emoji in group.emoji"
                                :key="emoji.char"
                                type="button"
                                class="hover:bg-elevated rounded p-1 text-lg leading-none"
                                :title="emoji.name"
                                :aria-label="emoji.name"
                                @mousedown.prevent
                                @click="pick(emoji.char)"
                            >
                                {{ emoji.char }}
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </template>
    </UiPopover>
</template>
