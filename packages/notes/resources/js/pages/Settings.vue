<script setup lang="ts">
import type { NotePreferences } from '@/components/notes/note';
import NotesPasswordSettings from '@/components/notes/NotesPasswordSettings.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { formatDate } from '@/lib/templates';
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
            ({ edit: 'Visual editor', source: 'Markdown source', split: 'Source and preview', preview: 'Reading view' })[value as string] ??
            String(value),
    },
    {
        key: 'editor.autosave_delay',
        label: 'Autosave after',
        help: 'How long to wait after you stop typing before saving.',
        optionLabel: (value) => `${Number(value) / 1000} s`,
    },
    {
        key: 'editor.readable_width',
        label: 'Readable line length',
        help: 'Keep lines to a comfortable width on wide screens instead of running edge to edge.',
    },
    { key: 'editor.spellcheck', label: 'Check spelling', help: "Underline misspelled words while you type (uses your browser's spellchecker)." },
    {
        key: 'editor.text_direction',
        label: 'Text direction',
        help: 'Automatic picks left-to-right or right-to-left for each paragraph from its first letters (for Arabic, Hebrew, Persian…).',
        optionLabel: (value) => ({ auto: 'Automatic', ltr: 'Left to right', rtl: 'Right to left' })[value as string] ?? String(value),
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
    {
        key: 'list.sort',
        label: 'Sort notes by',
        help: 'The default order of the note list. Folders can have their own (sort menu in the list while in a folder).',
        optionLabel: (value) => ({ modified: 'Date edited', created: 'Date created', title: 'Title' })[value as string] ?? String(value),
    },
    {
        key: 'list.sort_direction',
        label: 'Sort direction',
        help: 'Descending is newest first (or Z to A for titles); ascending is oldest first (or A to Z).',
        optionLabel: (value) => ({ desc: 'Descending', asc: 'Ascending' })[value as string] ?? String(value),
    },
    {
        key: 'list.group_by_date',
        label: 'Group notes by date',
        help: 'Show the list in sections: Pinned, Today, Yesterday, Previous 7 Days… (when sorted by date).',
    },
    { key: 'list.relative_dates', label: 'Relative dates in the list', help: 'Show "5 minutes ago" instead of the full date and time.' },
    { key: 'list.show_snippets', label: 'Show snippets in the list', help: 'Show a couple of lines of each note under its title.' },
    {
        key: 'list.layout',
        label: 'Show notes as',
        help: 'A list, or a gallery of cards with each note’s first picture. Folders can have their own (the grid button in the list).',
        optionLabel: (value) => ({ list: 'List', gallery: 'Gallery' })[value as string] ?? String(value),
    },
    {
        key: 'reading.line_breaks',
        label: 'Line breaks in the reading view',
        help: 'Markdown joins lines that are only one line break apart into one paragraph; keep them to show each line as you typed it.',
        optionLabel: (value) => ({ soft: 'Join lines (Markdown)', hard: 'Keep every line break' })[value as string] ?? String(value),
    },
    {
        key: 'daily.title_format',
        label: 'Daily note titles',
        help: 'How a daily note is titled. They go in a "Daily notes" folder; a template titled "Daily note" in your Templates folder is used for new ones.',
        optionLabel: (value) => formatDate(new Date(), String(value)),
    },
    {
        key: 'search.sort',
        label: 'Order search results',
        help: 'Best match puts notes that mention your search most (in the title above all) first. Otherwise results keep the list order.',
        optionLabel: (value) => ({ relevance: 'Best match first', list: 'Same as the list' })[value as string] ?? String(value),
    },
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
                    <div class="mx-auto w-full max-w-xl pt-6">
                        <NotesPasswordSettings />
                    </div>
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
