<script setup lang="ts">
import ArtistForm from '@/components/artists/Form.vue';
import { ArtistRequest } from '@/components/artists/artist';
import Form from '@coleus/support/components/form/Form.vue';
import { onErrorToast, onSuccessToast, ToastType } from '@coleus/support/lib/inertia';
import { useForm } from '@formkit/inertia';

const form = useForm<ArtistRequest>({
    name: '',
    bio: null,
});
const submit = () =>
    form.post(route('music.artists.store'), {
        ...onSuccessToast(ToastType.STORE_SUCCESS),
        ...onErrorToast(ToastType.ERROR),
    });
</script>
<template>
    <Form :form="form" :submit="submit" #default="{ value }">
        <ArtistForm :value="value as ArtistRequest" />
    </Form>
</template>
