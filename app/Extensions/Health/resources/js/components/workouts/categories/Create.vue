<script setup lang="ts">
import CategoryForm from '@health/components/categories/Form.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({ name: '' });

const submit = () => {
    form.post(route('health.categories.store'), {
        preserveScroll: true,
    });
};
</script>
<template>
    <form @submit.prevent="submit">
        <CategoryForm v-model="form" />
    </form>
</template>
