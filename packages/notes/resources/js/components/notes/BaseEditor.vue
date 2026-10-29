<script setup lang="ts">
import ConfirmModal from '@/components/notes/ConfirmModal.vue';
import { columnChoices, move, operatorTakesValue, renameFormula, type BaseDefinition, type BaseOptions } from '@/lib/bases';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    base: { id: number; name: string; definition: BaseDefinition };
    options: BaseOptions;
}>();

const open = defineModel<boolean>('open', { required: true });

/**
 * Edits a whole base (O11.1): which notes, columns, formulas, filters and
 * sort. Saving reloads the page, so the table is rebuilt on the server.
 */
const form = useForm<{ name: string; definition: BaseDefinition }>({ name: '', definition: structuredClone(props.base.definition) });
const confirmingDelete = ref(false);

watch(
    open,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();
            form.name = props.base.name;
            form.definition = structuredClone(props.base.definition);
        }
    },
    { immediate: true },
);

const columnItems = computed(() => columnChoices(props.options, form.definition, false));
const anyItems = computed(() => columnChoices(props.options, form.definition, true));
const unusedColumns = computed(() => columnItems.value.filter((choice) => !form.definition.columns.includes(choice.value)));
const operatorItems = computed(() => Object.entries(props.options.operators).map(([value, label]) => ({ label, value })));
const labelOf = (column: string): string => anyItems.value.find((choice) => choice.value === column)?.label ?? column;

const error = (key: string): string | undefined => (form.errors as Record<string, string>)[key];

const addColumn = (column: string): void => {
    if (column && !form.definition.columns.some((each) => each.toLowerCase() === column.toLowerCase())) {
        form.definition.columns = [...form.definition.columns, column];
    }
};

/**
 * A formula's name is applied on blur, so columns using it follow once.
 */
const renamingFrom = ref<string | null>(null);

const finishRename = (index: number): void => {
    const to = form.definition.formulas[index].name.trim();

    if (renamingFrom.value !== null && renamingFrom.value !== to) {
        form.definition = renameFormula(form.definition, renamingFrom.value, to);
    }

    renamingFrom.value = null;
};

const addFormula = (): void => {
    const taken = new Set(form.definition.formulas.map((formula) => formula.name.toLowerCase()));
    let name = 'formula1';

    for (let n = 2; taken.has(name); n++) {
        name = `formula${n}`;
    }

    form.definition.formulas = [...form.definition.formulas, { name, expression: '' }];
};

const removeFormula = (index: number): void => {
    const column = `formula.${form.definition.formulas[index].name}`.toLowerCase();
    const keep = (id: string): boolean => id.toLowerCase() !== column;

    form.definition = {
        ...form.definition,
        formulas: form.definition.formulas.filter((_, each) => each !== index),
        columns: form.definition.columns.filter(keep),
        filters: form.definition.filters.filter((filter) => keep(filter.column)),
        sort: form.definition.sort.filter((sort) => keep(sort.column)),
        group_by: keep(form.definition.group_by) ? form.definition.group_by : '',
    };
};

const save = (): void => {
    form.put(route('notes.bases.update', { base: props.base.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (open.value = false),
    });
};

const remove = (): void => {
    router.delete(route('notes.bases.destroy', { base: props.base.id }));
};
</script>

<template>
    <UiSlideover v-model:open="open" title="Edit base" description="Which notes this table shows, and how." :ui="{ content: 'max-w-xl' }">
        <template #body>
            <form id="base-editor" class="flex flex-col gap-5" @submit.prevent="save">
                <UiFormField label="Name" :error="form.errors.name">
                    <UiInput v-model="form.name" class="w-full" />
                </UiFormField>

                <UiFormField
                    label="Notes"
                    description="A search, e.g. tag:book folder:Reading has:properties. Empty: every note (not archived)."
                    :error="error('definition.query')"
                >
                    <UiInput v-model="form.definition.query" placeholder="All notes" class="w-full font-mono" />
                </UiFormField>

                <section class="flex flex-col gap-2" aria-labelledby="base-columns">
                    <h3 id="base-columns" class="text-sm font-medium">Columns</h3>
                    <p class="text-dimmed text-xs">The title always comes first.</p>
                    <ul class="flex flex-col gap-1">
                        <li v-for="(column, index) in form.definition.columns" :key="column" class="flex items-center gap-1 text-sm">
                            <span class="flex-1 truncate">{{ labelOf(column) }}</span>
                            <UiButton
                                icon="i-lucide-arrow-up"
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                :disabled="index === 0"
                                :aria-label="`Move ${labelOf(column)} left`"
                                @click="form.definition.columns = move(form.definition.columns, index, -1)"
                            />
                            <UiButton
                                icon="i-lucide-arrow-down"
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                :disabled="index === form.definition.columns.length - 1"
                                :aria-label="`Move ${labelOf(column)} right`"
                                @click="form.definition.columns = move(form.definition.columns, index, 1)"
                            />
                            <UiButton
                                icon="i-lucide-x"
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                :aria-label="`Remove ${labelOf(column)}`"
                                @click="form.definition.columns = form.definition.columns.filter((_, each) => each !== index)"
                            />
                        </li>
                    </ul>
                    <UiSelectMenu
                        :items="unusedColumns"
                        value-key="value"
                        placeholder="Add a column…"
                        size="sm"
                        class="w-full"
                        :model-value="undefined"
                        create-item
                        @update:model-value="(value: string) => addColumn(value)"
                        @create="(value: string) => addColumn(value.trim())"
                    />
                    <p v-if="error('definition.columns')" class="text-error text-xs" role="alert">{{ error('definition.columns') }}</p>
                </section>

                <section class="flex flex-col gap-2" aria-labelledby="base-formulas">
                    <h3 id="base-formulas" class="text-sm font-medium">Formulas</h3>
                    <p class="text-dimmed text-xs">
                        e.g. <code>price * quantity</code>, <code>if(done, "✓", "")</code>, <code>days_between(today(), due)</code>. Bare words are
                        properties; <code>note.title</code>, <code>note.folder</code>, <code>note.tags</code>, <code>note.created</code>,
                        <code>note.updated</code> are the note's own; <code>formula.name</code> is another formula.
                    </p>
                    <div v-for="(formula, index) in form.definition.formulas" :key="index" class="flex flex-col gap-1">
                        <div class="flex items-start gap-1">
                            <UiInput
                                v-model="formula.name"
                                size="sm"
                                class="w-32"
                                aria-label="Formula name"
                                @focus="renamingFrom = formula.name"
                                @blur="finishRename(index)"
                            />
                            <UiInput
                                v-model="formula.expression"
                                size="sm"
                                class="flex-1 font-mono"
                                aria-label="Formula"
                                placeholder="price * quantity"
                            />
                            <UiButton
                                icon="i-lucide-x"
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                :aria-label="`Remove formula ${formula.name}`"
                                @click="removeFormula(index)"
                            />
                        </div>
                        <p
                            v-if="error(`definition.formulas.${index}.name`) || error(`definition.formulas.${index}.expression`)"
                            class="text-error text-xs"
                            role="alert"
                        >
                            {{ error(`definition.formulas.${index}.name`) ?? error(`definition.formulas.${index}.expression`) }}
                        </p>
                    </div>
                    <UiButton icon="i-lucide-plus" size="xs" color="neutral" variant="ghost" class="self-start" @click="addFormula"
                        >Add formula</UiButton
                    >
                    <p v-if="error('definition.formulas')" class="text-error text-xs" role="alert">{{ error('definition.formulas') }}</p>
                </section>

                <section class="flex flex-col gap-2" aria-labelledby="base-filters">
                    <h3 id="base-filters" class="text-sm font-medium">Filters</h3>
                    <p class="text-dimmed text-xs">A note must pass every filter.</p>
                    <div v-for="(filter, index) in form.definition.filters" :key="index" class="flex flex-col gap-1">
                        <div class="flex items-center gap-1">
                            <UiSelectMenu
                                v-model="filter.column"
                                :items="anyItems"
                                value-key="value"
                                size="sm"
                                class="w-40"
                                aria-label="Filter on"
                                create-item
                                @create="(value: string) => (filter.column = value.trim())"
                            />
                            <UiSelect v-model="filter.operator" :items="operatorItems" size="sm" class="w-36" aria-label="Operator" />
                            <UiInput v-if="operatorTakesValue(filter.operator)" v-model="filter.value" size="sm" class="flex-1" aria-label="Value" />
                            <span v-else class="flex-1" />
                            <UiButton
                                icon="i-lucide-x"
                                size="xs"
                                color="neutral"
                                variant="ghost"
                                aria-label="Remove filter"
                                @click="form.definition.filters = form.definition.filters.filter((_, each) => each !== index)"
                            />
                        </div>
                        <p
                            v-if="error(`definition.filters.${index}.column`) || error(`definition.filters.${index}.operator`)"
                            class="text-error text-xs"
                            role="alert"
                        >
                            {{ error(`definition.filters.${index}.column`) ?? error(`definition.filters.${index}.operator`) }}
                        </p>
                    </div>
                    <UiButton
                        icon="i-lucide-plus"
                        size="xs"
                        color="neutral"
                        variant="ghost"
                        class="self-start"
                        @click="form.definition.filters = [...form.definition.filters, { column: 'note.title', operator: 'contains', value: '' }]"
                        >Add filter</UiButton
                    >
                    <UiFormField
                        label="Only where"
                        description='A formula each note must make true, e.g. status != "done" && due < today().'
                        :error="error('definition.where')"
                    >
                        <UiInput v-model="form.definition.where" size="sm" class="w-full font-mono" placeholder="Optional" />
                    </UiFormField>
                </section>

                <section class="flex flex-col gap-2" aria-labelledby="base-view">
                    <h3 id="base-view" class="text-sm font-medium">View</h3>
                    <div class="flex gap-2">
                        <UiFormField label="Show as" class="w-32" :error="error('definition.view')">
                            <UiSelect
                                v-model="form.definition.view"
                                :items="[
                                    { label: 'Table', value: 'table' },
                                    { label: 'Cards', value: 'cards' },
                                ]"
                                size="sm"
                                class="w-full"
                            />
                        </UiFormField>
                        <UiFormField label="Group by" class="flex-1" :error="error('definition.group_by')">
                            <UiSelectMenu
                                :model-value="form.definition.group_by || undefined"
                                :items="anyItems"
                                value-key="value"
                                placeholder="No groups"
                                size="sm"
                                class="w-full"
                                @update:model-value="(value: string) => (form.definition.group_by = value ?? '')"
                            />
                        </UiFormField>
                        <UiButton
                            v-if="form.definition.group_by"
                            icon="i-lucide-x"
                            size="xs"
                            color="neutral"
                            variant="ghost"
                            class="self-end"
                            aria-label="No groups"
                            @click="form.definition.group_by = ''"
                        />
                    </div>
                </section>

                <section class="flex flex-col gap-2" aria-labelledby="base-sort">
                    <h3 id="base-sort" class="text-sm font-medium">Sort</h3>
                    <p class="text-dimmed text-xs">Empty values go last. No sort: last edited first.</p>
                    <div v-for="(sort, index) in form.definition.sort" :key="index" class="flex items-center gap-1">
                        <UiSelectMenu v-model="sort.column" :items="anyItems" value-key="value" size="sm" class="flex-1" aria-label="Sort by" />
                        <UiSelect
                            v-model="sort.direction"
                            :items="[
                                { label: 'Ascending', value: 'asc' },
                                { label: 'Descending', value: 'desc' },
                            ]"
                            size="sm"
                            class="w-32"
                            aria-label="Direction"
                        />
                        <UiButton
                            icon="i-lucide-x"
                            size="xs"
                            color="neutral"
                            variant="ghost"
                            aria-label="Remove sort"
                            @click="form.definition.sort = form.definition.sort.filter((_, each) => each !== index)"
                        />
                    </div>
                    <UiButton
                        v-if="form.definition.sort.length < 3"
                        icon="i-lucide-plus"
                        size="xs"
                        color="neutral"
                        variant="ghost"
                        class="self-start"
                        @click="form.definition.sort = [...form.definition.sort, { column: 'note.title', direction: 'asc' }]"
                        >Add sort</UiButton
                    >
                    <p v-if="error('definition.sort')" class="text-error text-xs" role="alert">{{ error('definition.sort') }}</p>
                </section>
            </form>
        </template>

        <template #footer>
            <div class="flex w-full items-center justify-between gap-2">
                <UiButton color="error" variant="ghost" @click="confirmingDelete = true">Delete base</UiButton>
                <div class="flex gap-2">
                    <UiButton color="neutral" variant="ghost" @click="open = false">Cancel</UiButton>
                    <UiButton type="submit" form="base-editor" :loading="form.processing">Save</UiButton>
                </div>
            </div>
            <ConfirmModal
                v-model:open="confirmingDelete"
                :title="`Delete “${base.name}”?`"
                description="The base goes; its notes stay as they are."
                confirm-label="Delete base"
                @confirm="remove"
            />
        </template>
    </UiSlideover>
</template>
