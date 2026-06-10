<script setup lang="ts">
import TrackForm from '@/components/tracks/Form.vue';
import { TrackRequest } from '@/components/tracks/track';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';
import { ref } from 'vue';

const form = useForm<TrackRequest>({
    title: '',
    artist_id: '',
    album_id: null,
    genre_id: null,
    duration: null,
    track_number: null,
});

const fileRef = ref<File | null>(null);

const submit = () => {
    const handler = form.post(route('music.tracks.store'), {
        ...onSuccessToast(ToastType.STORE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
    return (fields: any, node: any) => handler({ ...fields, file: fileRef.value }, node);
};
</script>

<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <TrackForm :value="value as TrackRequest" v-model:file="fileRef" />
    </Form>
</template>
