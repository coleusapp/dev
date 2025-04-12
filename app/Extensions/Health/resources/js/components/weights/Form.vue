<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const form = defineModel<any>('form')
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-2">
            <Label for="weight">Weight</Label>
            <Input
                id="weight"
                type="number"
                class="mt-1 block w-full"
                v-model="form.weight"
                required
                autocomplete="weight"
                placeholder="Weight in LBS"
                step="0.01"
            />
            <InputError class="mt-2" :message="form.errors.weight" />
        </div>

        <div class="grid gap-2">
            <Label for="date">Date</Label>
            <Input
                id="date"
                type="datetime-local"
                class="mt-1 block w-full"
                v-model="form.date"
                required
                autocomplete="date"
                placeholder="Recorded Date"
            />
            <InputError class="mt-2" :message="form.errors.date" />
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing">Create</Button>
        </div>
    </div>
</template>