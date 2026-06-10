<script setup lang="ts">
import PlaylistForm from '@/components/playlists/Form.vue';
import { PlaylistRequest } from '@/components/playlists/playlist';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';

const form = useForm<PlaylistRequest>({
    name: '',
    description: null,
    tracks: [],
});
const submit = () =>
    form.post(route('music.playlists.store'), {
        ...onSuccessToast(ToastType.STORE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>

<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <PlaylistForm :value="value as PlaylistRequest" />
    </Form>
</template>
