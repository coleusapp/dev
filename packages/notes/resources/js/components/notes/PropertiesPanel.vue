<script setup lang="ts">
import {
    isValidPropertyName,
    parseProperties,
    propertyType,
    setProperties,
    type Properties,
    type PropertyType,
    type PropertyValue,
} from '@/lib/properties';
import { computed, ref } from 'vue';

const props = defineProps<{
    markdown: string;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    update: [markdown: string];
}>();

const properties = computed(() => parseProperties(props.markdown));
const entries = computed(() => Object.entries(properties.value ?? {}));
const open = ref(true);
const adding = ref(false);
const newName = ref('');
const newType = ref<PropertyType>('text');
const nameError = ref<string | null>(null);

const types: { label: string; value: PropertyType }[] = [
    { label: 'Text', value: 'text' },
    { label: 'List', value: 'list' },
    { label: 'Number', value: 'number' },
    { label: 'Checkbox', value: 'checkbox' },
    { label: 'Date', value: 'date' },
    { label: 'Date & time', value: 'datetime' },
];

const icons: Record<PropertyType, string> = {
    text: 'i-lucide-text',
    list: 'i-lucide-list',
    number: 'i-lucide-hash',
    checkbox: 'i-lucide-square-check',
    date: 'i-lucide-calendar',
    datetime: 'i-lucide-calendar-clock',
};

const save = (next: Properties): void => {
    emit('update', setProperties(props.markdown, next));
};

const setValue = (key: string, value: PropertyValue): void => {
    save({ ...(properties.value ?? {}), [key]: value });
};

const remove = (key: string): void => {
    const next = { ...(properties.value ?? {}) };
    delete next[key];
    save(next);
};

const initialValue = (type: PropertyType): PropertyValue =>
    ({
        text: null,
        list: [],
        number: 0,
        checkbox: false,
        date: new Date().toISOString().slice(0, 10),
        datetime: new Date().toISOString().slice(0, 16),
    })[type];

const add = (): void => {
    const name = newName.value.trim();

    if (!isValidPropertyName(name)) {
        nameError.value = 'Use letters, numbers, spaces, "_", "-" or "."';
        return;
    }
    if (properties.value && name in properties.value) {
        nameError.value = 'This note already has that property.';
        return;
    }

    setValue(name, initialValue(newType.value));
    newName.value = '';
    nameError.value = null;
    adding.value = false;
};

const startAdding = (): void => {
    open.value = true;
    adding.value = true;
};

defineExpose({ startAdding });

const listText = (value: PropertyValue): string => (Array.isArray(value) ? value.join(', ') : '');

const parseList = (text: string): string[] =>
    text
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item !== '');
</script>

<template>
    <section
        v-if="properties === null || entries.length || adding"
        class="border-default rounded-md border px-3 py-2 text-sm"
        aria-label="Properties"
    >
        <button type="button" class="text-dimmed flex w-full items-center gap-1 text-xs font-medium" :aria-expanded="open" @click="open = !open">
            <UiIcon :name="open ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" class="size-3" />
            Properties
        </button>
        <div v-if="open" class="mt-2 flex flex-col gap-1.5">
            <p v-if="properties === null" class="text-dimmed text-xs">
                This note's front matter uses YAML the properties panel can't edit (like nested values). Edit it in Markdown source.
            </p>
            <div v-for="[key, value] in entries" :key="key" class="group flex items-center gap-2">
                <span class="text-muted flex w-36 shrink-0 items-center gap-1.5 truncate">
                    <UiIcon :name="icons[propertyType(value)]" class="text-dimmed size-3.5 shrink-0" />
                    {{ key }}
                </span>
                <div class="min-w-0 flex-1">
                    <UiCheckbox
                        v-if="propertyType(value) === 'checkbox'"
                        :model-value="value === true"
                        :disabled="readonly"
                        @update:model-value="(checked: boolean | 'indeterminate') => setValue(key, checked === true)"
                    />
                    <UiInput
                        v-else-if="propertyType(value) === 'list'"
                        :model-value="listText(value)"
                        placeholder="a, b, c"
                        size="xs"
                        class="w-full"
                        :disabled="readonly"
                        @change="(event: Event) => setValue(key, parseList((event.target as HTMLInputElement).value))"
                    />
                    <UiInput
                        v-else-if="propertyType(value) === 'number'"
                        :model-value="value as number"
                        type="number"
                        size="xs"
                        class="w-full"
                        :disabled="readonly"
                        @change="(event: Event) => setValue(key, Number((event.target as HTMLInputElement).value) || 0)"
                    />
                    <UiInput
                        v-else-if="propertyType(value) === 'date' || propertyType(value) === 'datetime'"
                        :model-value="value as string"
                        :type="propertyType(value) === 'date' ? 'date' : 'datetime-local'"
                        size="xs"
                        class="w-full"
                        :disabled="readonly"
                        @change="(event: Event) => setValue(key, (event.target as HTMLInputElement).value || null)"
                    />
                    <UiInput
                        v-else
                        :model-value="(value as string | null) ?? ''"
                        size="xs"
                        class="w-full"
                        placeholder="Empty"
                        :disabled="readonly"
                        @change="(event: Event) => setValue(key, (event.target as HTMLInputElement).value || null)"
                    />
                </div>
                <UiButton
                    v-if="!readonly"
                    icon="i-lucide-x"
                    :aria-label="`Remove ${key}`"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    class="opacity-0 group-hover:opacity-100 focus-visible:opacity-100"
                    @click="remove(key)"
                />
            </div>
            <form v-if="adding && properties !== null" class="flex items-center gap-2" @submit.prevent="add">
                <UiInput v-model="newName" placeholder="Property name" size="xs" class="w-36" autofocus />
                <UiSelect v-model="newType" :items="types" size="xs" class="w-32" />
                <UiButton type="submit" size="xs">Add</UiButton>
                <UiButton size="xs" color="neutral" variant="ghost" @click="adding = false">Cancel</UiButton>
            </form>
            <p v-if="nameError" class="text-error text-xs" role="alert">{{ nameError }}</p>
            <UiButton
                v-if="!adding && !readonly && properties !== null"
                icon="i-lucide-plus"
                size="xs"
                color="neutral"
                variant="link"
                class="self-start"
                @click="adding = true"
            >
                Add property
            </UiButton>
        </div>
    </section>
</template>
