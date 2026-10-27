<script setup lang="ts">
import type { NotePreferences } from '@/components/notes/note';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type Definition = {
    default: string | number | boolean;
    options?: (string | number)[];
    type?: 'boolean';
};

const props = defineProps<{
    definitions: Record<keyof NotePreferences, Definition>;
    preferences: NotePreferences;
}>();

/**
 * Labels and help text for each preference; keys and allowed values come from the server.
 */
const fields: { key: keyof NotePreferences; label: string; help: string; optionLabel?: (value: string | number) => string }[] = [
    {
        key: 'editor.default_mode',
        label: 'Open notes in',
        help: 'How a note opens. Source and preview side by side needs a wide screen.',
        optionLabel: (value) =>
            ({ edit: 'Visual editor', source: 'Markdown source', split: 'Source and preview', preview: 'Preview' })[value as string] ?? String(value),
    },
    {
        key: 'editor.autosave_delay',
        label: 'Autosave after',
        help: 'How long to wait after you stop typing before saving.',
        optionLabel: (value) => `${Number(value) / 1000} s`,
    },
    {
        key: 'editor.first_line_style',
        label: 'New notes start with',
        help: 'The style of the first line of an empty note in the visual editor, where you type its title.',
        optionLabel: (value) => ({ title: 'Title', heading: 'Heading', subheading: 'Subheading', body: 'Body' })[value as string] ?? String(value),
    },
    {
        key: 'checklists.checked_to_bottom',
        label: 'Move checked items to the bottom',
        help: 'When you tick a checklist item in the editor, it moves below the unticked ones.',
    },
    { key: 'list.relative_dates', label: 'Relative dates in the list', help: 'Show "5 minutes ago" instead of the full date and time.' },
    { key: 'list.show_snippets', label: 'Show snippets in the list', help: 'Show a couple of lines of each note under its title.' },
];

const inputName = (key: string): string => key.replace(/\./g, '_');

const form = useForm(Object.fromEntries(fields.map(({ key }) => [inputName(key), props.preferences[key]])));

const optionValue = (key: keyof NotePreferences): string | number => form[inputName(key)] as string | number;

const save = (): void => {
    form.put(route('notes.settings.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Notes settings" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-settings">
                <template #header>
                    <UiDashboardNavbar title="Notes settings">
                        <template #leading>
                            <UiButton
                                icon="i-lucide-chevron-left"
                                color="neutral"
                                variant="ghost"
                                aria-label="Back to notes"
                                :to="route('notes.index')"
                            />
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <form class="mx-auto flex w-full max-w-xl flex-col gap-6" @submit.prevent="save">
                        <UiFormField
                            v-for="field in fields"
                            :key="field.key"
                            :label="field.label"
                            :description="field.help"
                            :error="form.errors[inputName(field.key)]"
                        >
                            <UiSwitch
                                v-if="definitions[field.key].type === 'boolean'"
                                :model-value="Boolean(form[inputName(field.key)])"
                                @update:model-value="(value: boolean) => (form[inputName(field.key)] = value)"
                            />
                            <UiSelect
                                v-else
                                :model-value="optionValue(field.key)"
                                @update:model-value="(value: string | number) => (form[inputName(field.key)] = value)"
                                :items="
                                    (definitions[field.key].options ?? []).map((value) => ({
                                        label: field.optionLabel?.(value) ?? String(value),
                                        value,
                                    }))
                                "
                                class="w-60"
                            />
                        </UiFormField>

                        <div class="flex items-center gap-3">
                            <UiButton type="submit" :loading="form.processing" :disabled="!form.isDirty">Save</UiButton>
                            <span v-if="form.recentlySuccessful" class="text-dimmed text-sm" role="status">Saved</span>
                            <Link :href="route('notes.index')" class="text-dimmed ml-auto text-sm hover:underline">Back to notes</Link>
                        </div>
                    </form>
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
