<script setup lang="ts">
import AlbumForm from '@/components/albums/Form.vue';
import { AlbumRequest, AlbumResource, resourceKey } from '@/components/albums/album';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';
import { inject } from 'vue';

const resource = inject(resourceKey) as AlbumResource;

const form = useForm<AlbumRequest>({
    title: resource.data.title,
    artist_id: resource.data.artist_id,
    release_date: resource.data.release_date,
});
const submit = () =>
    form.patch(route('music.albums.update', { album: resource.data.id }), {
        ...onSuccessToast(ToastType.UPDATE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>
<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <AlbumForm :value="value as AlbumRequest" />
    </Form>
</template>
