<script setup lang="ts">
import PlaylistForm from '@/components/playlists/Form.vue';
import { PlaylistRequest, PlaylistResource, resourceKey } from '@/components/playlists/playlist';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';
import { inject } from 'vue';

const resource = inject(resourceKey) as PlaylistResource;

const form = useForm<PlaylistRequest>({
    name: resource.data.name,
    description: resource.data.description,
    tracks: resource.data.tracks || [],
});
const submit = () =>
    form.patch(route('music.playlists.update', { playlist: resource.data.id }), {
        ...onSuccessToast(ToastType.UPDATE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>

<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <PlaylistForm :value="value as PlaylistRequest" />
    </Form>
</template>
