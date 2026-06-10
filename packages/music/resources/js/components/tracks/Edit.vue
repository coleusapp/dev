<script setup lang="ts">
import TrackForm from '@/components/tracks/Form.vue';
import { TrackRequest, TrackResource, resourceKey } from '@/components/tracks/track';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';
import { inject, ref } from 'vue';

const resource = inject(resourceKey) as TrackResource;

const form = useForm<TrackRequest>({
    title: resource.data.title,
    artist_id: resource.data.artist_id,
    album_id: resource.data.album_id,
    genre_id: resource.data.genre_id,
    duration: resource.data.duration,
    track_number: resource.data.track_number,
});

const fileRef = ref<File | null>(null);

const submit = () => {
    const handler = form.patch(route('music.tracks.update', { track: resource.data.id }), {
        ...onSuccessToast(ToastType.UPDATE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
    return (fields: any, node: any) => handler({ ...fields, file: fileRef.value }, node);
};
</script>

<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <TrackForm :value="value as TrackRequest" v-model:file="fileRef" :current-path="resource.data.path" />
    </Form>
</template>
