<script setup lang="ts">
import SimpleLayout from '@/layouts/SimpleLayout.vue';
import { FormKitNode } from '@formkit/core';
import { useForm } from '@formkit/inertia';

type LoginForm = {
    email: string;
    password: string;
};

const form = useForm({
    email: '',
    password: '',
});

const submit = (field: LoginForm, node: FormKitNode) => {
    form.post(route('login'))(field, node);
};
</script>

<template>
    <SimpleLayout>
        <template #header>Log in</template>
        <div class="mx-auto max-w-md overflow-hidden rounded-lg bg-white shadow p-8">
            <FormKit type="form" @submit="submit" submit-label="Log In" :plugins="[form.plugin]">
                <FormKit type="email" name="email" label="Email" validation="required|email" />
                <FormKit type="password" name="password" label="Password" validation="required" />
            </FormKit>
        </div>
    </SimpleLayout>
</template>
