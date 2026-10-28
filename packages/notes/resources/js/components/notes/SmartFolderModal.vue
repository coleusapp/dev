<script setup lang="ts">
import type { FolderNode, SmartFolder, SmartRules } from '@/components/notes/note';
import { router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    editing: SmartFolder | 'new' | null;
    folders: FolderNode[];
}>();

const emit = defineEmits<{ close: [] }>();

const open = computed({
    get: () => props.editing !== null,
    set: (value: boolean) => {
        if (!value) {
            emit('close');
        }
    },
});

const emptyRules = (): SmartRules => ({
    tags: [],
    tags_match: 'all',
    folder_id: null,
    edited_within_days: null,
    created_within_days: null,
    edited_from: null,
    edited_to: null,
    created_from: null,
    created_to: null,
    pinned: false,
    has_checklist: false,
    has_open_tasks: false,
});

const form = useForm<{ name: string; tags: string; rules: SmartRules }>({ name: '', tags: '', rules: emptyRules() });

watch(
    () => props.editing,
    (editing) => {
        form.clearErrors();
        const smart = editing && editing !== 'new' ? editing : null;
        form.name = smart?.name ?? '';
        form.rules = { ...emptyRules(), ...(smart?.rules ?? {}) };
        form.tags = form.rules.tags.map((tag) => `#${tag}`).join(' ');
    },
);

const flatten = (nodes: FolderNode[], depth = 0): { label: string; value: number }[] =>
    nodes.flatMap((node) => [{ label: `${'   '.repeat(depth)}${node.name}`, value: node.id }, ...flatten(node.children, depth + 1)]);

const folderItems = computed(() => [{ label: 'Any folder', value: 0 }, ...flatten(props.folders)]);

const error = (key: string): string | undefined => (form.errors as Record<string, string>)[key];

const submit = (): void => {
    const editing = props.editing;
    const payload = (data: { name: string; tags: string; rules: SmartRules }) => ({
        name: data.name,
        rules: { ...data.rules, tags: data.tags.split(/[\s,]+/).filter((tag) => tag !== '') },
    });

    if (editing === 'new') {
        form.transform(payload).post(route('notes.smart-folders.store'), { onSuccess: () => emit('close') });
    } else if (editing) {
        form.transform(payload).put(route('notes.smart-folders.update', { smartFolder: editing.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => emit('close'),
        });
    }
};

const remove = (): void => {
    if (props.editing && props.editing !== 'new') {
        router.delete(route('notes.smart-folders.destroy', { smartFolder: props.editing.id }), { onSuccess: () => emit('close') });
    }
};
</script>

<template>
    <UiModal
        v-model:open="open"
        :title="editing === 'new' ? 'New smart folder' : 'Edit smart folder'"
        description="Lists every note that matches all of these rules."
    >
        <template #body>
            <form class="flex flex-col gap-3" @submit.prevent="submit">
                <UiFormField label="Name" :error="form.errors.name">
                    <UiInput v-model="form.name" placeholder="e.g. Open work tasks" class="w-full" autofocus />
                </UiFormField>
                <p v-if="error('rules')" class="text-error text-sm" role="alert">{{ error('rules') }}</p>
                <UiFormField label="Tags" description="Notes tagged with these (sub-tags count too)." :error="error('rules.tags')">
                    <div class="flex gap-2">
                        <UiInput v-model="form.tags" placeholder="#work #urgent" class="flex-1" />
                        <UiSelect
                            v-model="form.rules.tags_match"
                            :items="[
                                { label: 'All of them', value: 'all' },
                                { label: 'Any of them', value: 'any' },
                            ]"
                            class="w-36"
                        />
                    </div>
                </UiFormField>
                <UiFormField label="Folder" description="Includes its sub-folders." :error="error('rules.folder_id')">
                    <UiSelect
                        :model-value="form.rules.folder_id ?? 0"
                        :items="folderItems"
                        class="w-full"
                        @update:model-value="(value: number) => (form.rules.folder_id = value || null)"
                    />
                </UiFormField>
                <div class="grid grid-cols-2 gap-3">
                    <UiFormField label="Edited in the last … days" :error="error('rules.edited_within_days')">
                        <UiInput v-model.number="form.rules.edited_within_days" type="number" min="1" max="3650" class="w-full" />
                    </UiFormField>
                    <UiFormField label="Created in the last … days" :error="error('rules.created_within_days')">
                        <UiInput v-model.number="form.rules.created_within_days" type="number" min="1" max="3650" class="w-full" />
                    </UiFormField>
                    <UiFormField label="Edited from / to" :error="error('rules.edited_from') ?? error('rules.edited_to')">
                        <div class="flex gap-1">
                            <UiInput v-model="form.rules.edited_from" type="date" class="flex-1" />
                            <UiInput v-model="form.rules.edited_to" type="date" class="flex-1" />
                        </div>
                    </UiFormField>
                    <UiFormField label="Created from / to" :error="error('rules.created_from') ?? error('rules.created_to')">
                        <div class="flex gap-1">
                            <UiInput v-model="form.rules.created_from" type="date" class="flex-1" />
                            <UiInput v-model="form.rules.created_to" type="date" class="flex-1" />
                        </div>
                    </UiFormField>
                </div>
                <div class="flex flex-wrap gap-4">
                    <UiCheckbox v-model="form.rules.pinned" label="Pinned" />
                    <UiCheckbox v-model="form.rules.has_checklist" label="Has a checklist" />
                    <UiCheckbox v-model="form.rules.has_open_tasks" label="Has unchecked items" />
                </div>
                <div class="flex items-center justify-between gap-2">
                    <UiButton v-if="editing && editing !== 'new'" color="error" variant="ghost" @click="remove">Delete</UiButton>
                    <span v-else />
                    <div class="flex gap-2">
                        <UiButton color="neutral" variant="ghost" @click="emit('close')">Cancel</UiButton>
                        <UiButton type="submit" :loading="form.processing">Save</UiButton>
                    </div>
                </div>
            </form>
        </template>
    </UiModal>
</template>
