<script setup lang="ts">
import WeightForm from '@health/components/weights/Form.vue';
import { useForm } from '@inertiajs/vue3';
import { DateTime } from 'luxon';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    weight?: number;
}>();

const form = useForm({
    weight: props.weight || 0,
    date: DateTime.now().toFormat("yyyy-LL-dd'T'HH:mm"),
});

const submit = () => {
    form.post(route('health.weights.store'), {
        preserveScroll: true,
    });
};
</script>
<template>
    <form @submit.prevent="submit" class="space-y-6">
        <WeightForm :form="form" />
    </form>
</template>