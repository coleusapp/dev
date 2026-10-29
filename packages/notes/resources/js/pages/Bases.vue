<script setup lang="ts">
import BaseEditor from '@/components/notes/BaseEditor.vue';
import BaseView from '@/components/notes/BaseView.vue';
import NotesLayout from '@/layouts/NotesLayout.vue';
import { columnChoices, nextSort, type BaseDefinition, type BaseOptions, type BaseTable, type BaseViewKind } from '@/lib/bases';
import { Head, router, useForm } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { computed, ref } from 'vue';

const props = defineProps<{
    bases: { id: number; name: string }[];
    base: { id: number; name: string; definition: BaseDefinition } | null;
    table: BaseTable | null;
    options: BaseOptions;
}>();

/**
 * Bases (O11.1): a table over notes, one row per note, the title first
 * and then the base's columns. Clicking a header re-sorts and saves it;
 * Table / Cards (O11.2) switches the view and saves that too.
 */
const editing = ref(false);
const creating = ref(false);
const saving = ref(false);
const createForm = useForm({ name: '' });
const copied = ref(false);

/**
 * The line that embeds this base in a note (O11.3).
 */
const embedLine = computed(() => (props.base ? `![[${props.base.name}.base]]` : ''));

const copyEmbed = async (): Promise<void> => {
    try {
        await navigator.clipboard.writeText(embedLine.value);
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 1500);
    } catch {
        copied.value = false;
    }
};

const baseItems = computed<DropdownMenuItem[][]>(() => [
    props.bases.map((each) => ({
        label: each.name,
        icon: 'i-lucide-table-2',
        active: each.id === props.base?.id,
        onSelect: () => router.visit(route('notes.bases.show', { base: each.id })),
    })),
    [{ label: 'New base', icon: 'i-lucide-plus', onSelect: () => openCreate() }],
]);

const openCreate = (): void => {
    createForm.reset();
    createForm.clearErrors();
    creating.value = true;
};

const create = (): void => {
    createForm.post(route('notes.bases.store'), { onSuccess: () => (creating.value = false) });
};

/**
 * Saves a change to the definition straight away (sort, view).
 */
const saveDefinition = (change: Partial<BaseDefinition>): void => {
    const base = props.base;

    if (!base || saving.value) {
        return;
    }

    saving.value = true;
    router.put(
        route('notes.bases.update', { base: base.id }),
        { name: base.name, definition: { ...base.definition, ...change } },
        { preserveScroll: true, preserveState: true, onFinish: () => (saving.value = false) },
    );
};

const sortBy = (column: string): void => {
    if (props.base) {
        saveDefinition({ sort: nextSort(props.base.definition.sort, column) });
    }
};

const setView = (view: BaseViewKind): void => {
    if (props.base && props.base.definition.view !== view) {
        saveDefinition({ view });
    }
};

const groupLabel = computed(() => {
    const groupBy = props.base?.definition.group_by ?? '';

    return groupBy === '' || !props.base
        ? ''
        : (columnChoices(props.options, props.base.definition, true).find((choice) => choice.value === groupBy)?.label ?? groupBy);
});

const tableErrors = computed(() =>
    Object.entries(props.table?.errors ?? {}).map(
        ([where, message]) => `${where === 'where' ? 'Only where' : where.replace(/^formula\./, 'ƒ ')}: ${message}`,
    ),
);
</script>

<template>
    <Head :title="base?.name ?? 'Bases'" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-bases">
                <template #header>
                    <UiDashboardNavbar :title="base?.name ?? 'Bases'">
                        <template #leading>
                            <UiButton
                                icon="i-lucide-chevron-left"
                                color="neutral"
                                variant="ghost"
                                aria-label="Back to notes"
                                :to="route('notes.index')"
                            />
                        </template>
                        <template #right>
                            <UiDropdownMenu v-if="bases.length" :items="baseItems" :content="{ align: 'end' }">
                                <UiButton icon="i-lucide-table-2" trailing-icon="i-lucide-chevron-down" color="neutral" variant="ghost" size="sm"
                                    >Bases</UiButton
                                >
                            </UiDropdownMenu>
                            <div v-if="base" class="flex gap-0.5" role="group" aria-label="View">
                                <UiButton
                                    v-for="view in ['table', 'cards'] as const"
                                    :key="view"
                                    :icon="view === 'table' ? 'i-lucide-table' : 'i-lucide-layout-grid'"
                                    :aria-label="view === 'table' ? 'Table' : 'Cards'"
                                    :aria-pressed="base.definition.view === view"
                                    :variant="base.definition.view === view ? 'soft' : 'ghost'"
                                    :disabled="saving"
                                    color="neutral"
                                    size="sm"
                                    @click="setView(view)"
                                />
                            </div>
                            <UiTooltip v-if="base" :text="`Copy ${embedLine} to put this base in a note`">
                                <UiButton
                                    :icon="copied ? 'i-lucide-check' : 'i-lucide-copy'"
                                    color="neutral"
                                    variant="ghost"
                                    size="sm"
                                    :aria-label="`Copy embed ${embedLine}`"
                                    @click="copyEmbed"
                                    >{{ copied ? 'Copied' : 'Embed' }}</UiButton
                                >
                            </UiTooltip>
                            <UiButton v-if="base" icon="i-lucide-settings-2" color="neutral" variant="soft" size="sm" @click="editing = true"
                                >Edit</UiButton
                            >
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <div v-if="!base || !table" class="mx-auto flex max-w-md flex-col items-center gap-3 py-16 text-center">
                        <UiIcon name="i-lucide-table-2" class="text-dimmed size-10" />
                        <p class="font-medium">No bases yet</p>
                        <p class="text-dimmed text-sm">
                            A base is a table over your notes: their properties as columns, with filters, sorting, formulas, groups and a cards view.
                        </p>
                        <UiButton icon="i-lucide-plus" @click="openCreate">New base</UiButton>
                    </div>

                    <div v-else class="flex min-w-0 flex-col gap-3">
                        <div v-if="tableErrors.length" class="bg-error/10 text-error rounded-md px-3 py-2 text-sm" role="alert">
                            <p v-for="message in tableErrors" :key="message">{{ message }}</p>
                        </div>
                        <p class="text-dimmed text-xs">
                            {{ table.total }} note{{ table.total === 1 ? '' : 's' }}
                            <template v-if="table.total > table.rows.length"> · showing the first {{ table.rows.length }}</template>
                        </p>

                        <p v-if="!table.rows.length" class="text-dimmed text-sm">No notes match. Change the base's notes or filters with Edit.</p>

                        <BaseView
                            v-else
                            :table="table"
                            :view="base.definition.view"
                            :sort="base.definition.sort"
                            :group-label="groupLabel"
                            sortable
                            :busy="saving"
                            @sort="sortBy"
                        />
                    </div>
                </template>
            </UiDashboardPanel>
        </template>

        <BaseEditor v-if="base" v-model:open="editing" :base="base" :options="options" />

        <UiModal v-model:open="creating" title="New base" description="A table over your notes. Choose its columns and filters next.">
            <template #body>
                <form class="flex flex-col gap-3" @submit.prevent="create">
                    <UiFormField label="Name" :error="createForm.errors.name">
                        <UiInput v-model="createForm.name" placeholder="e.g. Reading list" class="w-full" autofocus />
                    </UiFormField>
                    <div class="flex justify-end gap-2">
                        <UiButton color="neutral" variant="ghost" @click="creating = false">Cancel</UiButton>
                        <UiButton type="submit" :loading="createForm.processing">Create</UiButton>
                    </div>
                </form>
            </template>
        </UiModal>
    </NotesLayout>
</template>
