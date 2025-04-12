<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import Select from '@/components/ui/select/Select.vue';

interface Props {
    timezones: string[];
}

const page = usePage<SharedData>();

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'General settings',
        href: '/settings/general',
    },
];

const form = useForm({
    timezone: page.props.config.timezone || 'UTC',
});

const submit = () => {
    form.patch(route('general.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="General settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="General information" description="Update your general settings" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="timezone">Timezone</Label>
                        <Select
                            id="timezone"
                            required
                            class="mwt-1 block w-full"
                            :options="timezones"
                            v-model="form.timezone"
                            placeholder="Timezone"
                        />
                        <InputError class="mt-2" :message="form.errors.timezone" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
