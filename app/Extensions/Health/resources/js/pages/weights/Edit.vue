<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import WeightForm from '@health/components/weights/Form.vue';
import HealthLayout from '@health/Layouts/HealthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { getBreadcrumbs, weights } from '@health/composables/useBreadcrumb';

const breadcrumbs: BreadcrumbItem[] = getBreadcrumbs([weights, { title: 'Edit Weight' }]);

interface Weight {
    data: {
        weight: number;
        date: string;
    };
}

const props = defineProps<{
    weight: Weight;
}>();

const form = useForm({
    weight: props.weight.data.weight,
    date: props.weight.data.date,
});

const submit = () => {
    form.post(route('health.weights.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Update Weight" />
        <HealthLayout>
            <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <HeadingSmall title="Update Weight" />
                <div class="flex max-w-xl flex-col">
                    <form @submit.prevent="submit" class="space-y-6">
                        <WeightForm :form="form" />
                    </form>
                </div>
            </div>
        </HealthLayout>
    </AppLayout>
</template>
