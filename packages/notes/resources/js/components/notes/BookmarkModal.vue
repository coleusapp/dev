<script setup lang="ts">
import type { BookmarkGroup, BookmarkItem } from '@/components/notes/note';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

/**
 * Editing a bookmark's name and group, or choosing a heading of the open
 * note to bookmark.
 */
const props = defineProps<{
    editing: BookmarkItem | null;
    headingsOf: { noteId: number; headings: string[] } | null;
    groups: BookmarkGroup[];
}>();

const emit = defineEmits<{ close: [] }>();

const open = computed({
    get: () => props.editing !== null || props.headingsOf !== null,
    set: (value: boolean) => {
        if (!value) {
            emit('close');
        }
    },
});

const form = useForm<{ label: string; group: string }>({ label: '', group: '' });
const heading = ref<string | null>(null);

const groupNames = computed(() => props.groups.map((group) => group.group).filter((group): group is string => group !== null));

watch(
    () => [props.editing, props.headingsOf] as const,
    ([editing, headingsOf]) => {
        form.clearErrors();
        form.label = editing?.label ?? '';
        form.group = editing?.group ?? '';
        heading.value = headingsOf?.headings[0] ?? null;
    },
);

const options = { preserveScroll: true, preserveState: true, onSuccess: () => emit('close') };

const submit = (): void => {
    if (props.editing) {
        form.put(route('notes.bookmarks.update', { bookmark: props.editing.id }), options);
    } else if (props.headingsOf && heading.value) {
        router.post(route('notes.bookmarks.store', { note: props.headingsOf.noteId }), { heading: heading.value }, options);
    }
};
</script>

<template>
    <UiModal v-model:open="open" :title="editing ? 'Edit bookmark' : 'Bookmark a heading'">
        <template #body>
            <form class="flex flex-col gap-3" @submit.prevent="submit">
                <template v-if="editing">
                    <UiFormField label="Name" :description="`Leave empty to use “${editing.title}”.`" :error="form.errors.label">
                        <UiInput v-model="form.label" :placeholder="editing.title" class="w-full" autofocus />
                    </UiFormField>
                    <UiFormField label="Group" description="Bookmarks with the same group are listed together." :error="form.errors.group">
                        <UiInput v-model="form.group" placeholder="No group" list="bookmark-groups" class="w-full" />
                        <datalist id="bookmark-groups">
                            <option v-for="name in groupNames" :key="name" :value="name" />
                        </datalist>
                    </UiFormField>
                </template>
                <UiFormField v-else label="Heading">
                    <p v-if="!headingsOf?.headings.length" class="text-dimmed text-sm">This note has no headings.</p>
                    <UiSelect v-else v-model="heading" :items="headingsOf.headings" class="w-full" />
                </UiFormField>
                <div class="flex justify-end gap-2">
                    <UiButton color="neutral" variant="ghost" @click="emit('close')">Cancel</UiButton>
                    <UiButton type="submit" :disabled="!editing && !heading">{{ editing ? 'Save' : 'Bookmark' }}</UiButton>
                </div>
            </form>
        </template>
    </UiModal>
</template>
