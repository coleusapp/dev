<script setup lang="ts">
import SimpleLayout from '@/layouts/SimpleLayout.vue';
import {useForm} from "@formkit/inertia";
import { FormKitNode } from '@formkit/core';

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
        <FormKit type="form" @submit="submit" submit-label="Log In" :plugins="[form.plugin]">
            <FormKit
                type="email"
                name="email"
                label="Email"
                validation="required|email"
            />
            <FormKit
                type="password"
                name="password"
                label="Password"
                validation="required"
            />
        </FormKit>
    </SimpleLayout>
</template>
