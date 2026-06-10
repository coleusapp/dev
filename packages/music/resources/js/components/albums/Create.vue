<script setup lang="ts">
import AlbumForm from '@/components/albums/Form.vue';
import { AlbumRequest } from '@/components/albums/album';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';

const form = useForm<AlbumRequest>({
    title: '',
    artist_id: '',
    release_date: null,
});
const submit = () =>
    form.post(route('music.albums.store'), {
        ...onSuccessToast(ToastType.STORE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>
<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <AlbumForm :value="value as AlbumRequest" />
    </Form>
</template>
