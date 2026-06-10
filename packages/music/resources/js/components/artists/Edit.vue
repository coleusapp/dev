<script setup lang="ts">
import ArtistForm from '@/components/artists/Form.vue';
import { ArtistRequest, ArtistResource, resourceKey } from '@/components/artists/artist';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';
import { inject } from 'vue';

const resource = inject(resourceKey) as ArtistResource;

const form = useForm<ArtistRequest>({
    name: resource.data.name,
    bio: resource.data.bio,
});
const submit = () =>
    form.patch(route('music.artists.update', { artist: resource.data.id }), {
        ...onSuccessToast(ToastType.UPDATE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>
<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <ArtistForm :value="value as ArtistRequest" />
    </Form>
</template>
