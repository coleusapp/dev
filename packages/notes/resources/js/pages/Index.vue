<script setup lang="ts">
import NotesLayout from '@/layouts/NotesLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Notes" />

    <NotesLayout>
        <template #panels>
            <UiDashboardPanel id="notes-1" :default-size="18" :min-size="14" :max-size="24" resizable>
                <template #header>
                    <UiDashboardNavbar title="Notes">
                        <template #leading>
                            <UiDashboardSidebarCollapse variant="ghost" />
                        </template>
                    </UiDashboardNavbar>
                </template>

                <template #body>
                    <div class="flex h-full items-center justify-center p-4 text-center text-sm text-dimmed">No notes yet.</div>
                </template>
            </UiDashboardPanel>

            <div class="hidden flex-1 items-center justify-center lg:flex">
                <UiIcon name="i-lucide-notebook-text" class="size-32 text-dimmed" />
            </div>
        </template>
    </NotesLayout>
</template>
