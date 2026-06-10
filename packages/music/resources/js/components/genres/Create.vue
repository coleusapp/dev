<script setup lang="ts">
import GenreForm from '@/components/genres/Form.vue';
import { GenreRequest } from '@/components/genres/genre';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';

const form = useForm<GenreRequest>({
    name: '',
});
const submit = () =>
    form.post(route('music.genres.store'), {
        ...onSuccessToast(ToastType.STORE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>
<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <GenreForm :value="value as GenreRequest" />
    </Form>
</template>
