<script setup lang="ts">
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type PropertySummary = { key: string; types: string[]; count: number; examples: string[] };

defineProps<{
    properties: PropertySummary[];
}>();

const renaming = ref<string | null>(null);
const form = useForm({ from: '', to: '' });

const startRename = (key: string): void => {
    renaming.value = key;
    form.clearErrors();
    form.from = key;
    form.to = key;
};

const rename = (): void => {
    form.put(route('notes.properties.rename'), { preserveScroll: true, onSuccess: () => (renaming.value = null) });
};
</script>

<template>
    <Head title="Properties" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-properties">
                <template #header>
                    <UiDashboardNavbar title="Properties">
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
                    <div class="mx-auto w-full max-w-3xl">
                        <p class="text-dimmed mb-4 text-sm">Every property used in your notes' front matter (trash not included).</p>
                        <p v-if="!properties.length" class="text-dimmed text-sm">
                            No note has properties yet. Add one with the list-plus button next to a note's title.
                        </p>
                        <table v-else class="w-full text-sm">
                            <thead class="text-dimmed text-start text-xs">
                                <tr>
                                    <th class="py-2 text-start font-medium">Property</th>
                                    <th class="py-2 text-start font-medium">Type</th>
                                    <th class="py-2 text-end font-medium">Notes</th>
                                    <th class="py-2 ps-4 text-start font-medium">Values</th>
                                    <th class="py-2" />
                                </tr>
                            </thead>
                            <tbody class="divide-default divide-y">
                                <tr v-for="property in properties" :key="property.key">
                                    <td class="py-2 font-medium">
                                        <form v-if="renaming === property.key" class="flex items-center gap-1" @submit.prevent="rename">
                                            <UiInput v-model="form.to" size="xs" autofocus aria-label="New name" />
                                            <UiButton type="submit" size="xs" :loading="form.processing">Rename</UiButton>
                                            <UiButton size="xs" color="neutral" variant="ghost" @click="renaming = null">Cancel</UiButton>
                                        </form>
                                        <UiLink v-else :to="route('notes.index', { prop: property.key })" class="hover:underline">{{
                                            property.key
                                        }}</UiLink>
                                        <p v-if="renaming === property.key && form.errors.to" class="text-error text-xs" role="alert">
                                            {{ form.errors.to }}
                                        </p>
                                    </td>
                                    <td class="text-muted py-2">{{ property.types.join(', ') }}</td>
                                    <td class="py-2 text-end tabular-nums">{{ property.count }}</td>
                                    <td class="text-muted max-w-60 truncate py-2 ps-4">
                                        <template v-for="(example, index) in property.examples" :key="example"
                                            >{{ index ? ', ' : ''
                                            }}<UiLink :to="route('notes.index', { prop: property.key, value: example })" class="hover:underline">{{
                                                example
                                            }}</UiLink></template
                                        >
                                    </td>
                                    <td class="py-2 text-end">
                                        <UiButton
                                            v-if="renaming !== property.key"
                                            icon="i-lucide-pencil"
                                            size="xs"
                                            color="neutral"
                                            variant="ghost"
                                            :aria-label="`Rename ${property.key} in every note`"
                                            @click="startRename(property.key)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </UiDashboardPanel>
        </template>
    </NotesLayout>
</template>
