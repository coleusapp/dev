<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import HealthLayout from '@health/Layouts/HealthLayout.vue';
import { Head } from '@inertiajs/vue3';
import CreateWeight from "@health/components/weights/Craete.vue";


defineProps<{
    weight?: number;
}>();
</script>

<template>
    <AppLayout>
        <Head title="New Weight" />
        <HealthLayout>
            <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div class="flex max-w-xl flex-col">
                    <CreateWeight :weight="weight" />
                </div>
            </div>
        </HealthLayout>
    </AppLayout>
</template>
