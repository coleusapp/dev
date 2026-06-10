<script setup lang="ts">
import GenreForm from '@/components/genres/Form.vue';
import { GenreRequest, GenreResource, resourceKey } from '@/components/genres/genre';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';
import { inject } from 'vue';

const resource = inject(resourceKey) as GenreResource;

const form = useForm<GenreRequest>({
    name: resource.data.name,
});
const submit = () =>
    form.patch(route('music.genres.update', { genre: resource.data.id }), {
        ...onSuccessToast(ToastType.UPDATE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>
<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <GenreForm :value="value as GenreRequest" />
    </Form>
</template>
