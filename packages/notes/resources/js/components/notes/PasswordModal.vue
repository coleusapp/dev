<script setup lang="ts">
import { ref, watch } from 'vue';

/**
 * Asks for the notes password: "set" a new one (twice, with an optional
 * hint) or "enter" it (showing the hint). The password is only handed to
 * the parent, which derives the key in the browser.
 */
const props = defineProps<{
    mode: 'set' | 'enter';
    title: string;
    description: string;
    hint?: string | null;
    error?: string | null;
    busy?: boolean;
}>();

const emit = defineEmits<{
    submit: [value: { password: string; hint: string | null }];
}>();

const open = defineModel<boolean>('open', { required: true });

const password = ref('');
const confirmation = ref('');
const newHint = ref('');
const mismatch = ref<string | null>(null);

watch(open, (isOpen) => {
    if (isOpen) {
        password.value = '';
        confirmation.value = '';
        newHint.value = '';
        mismatch.value = null;
    }
});

const submit = (): void => {
    if (props.mode === 'set') {
        if (password.value.length < 6) {
            mismatch.value = 'Use at least 6 characters.';
            return;
        }

        if (password.value !== confirmation.value) {
            mismatch.value = "The passwords don't match.";
            return;
        }

        if (newHint.value.trim() !== '' && newHint.value.trim() === password.value) {
            mismatch.value = "The hint can't be the password.";
            return;
        }
    }

    mismatch.value = null;
    emit('submit', { password: password.value, hint: props.mode === 'set' ? newHint.value.trim() || null : null });
};
</script>

<template>
    <UiModal v-model:open="open" :title="title" :description="description">
        <template #body>
            <form class="flex flex-col gap-3" @submit.prevent="submit">
                <UiFormField label="Password" :error="mode === 'enter' ? (error ?? undefined) : undefined">
                    <UiInput v-model="password" type="password" autocomplete="off" class="w-full" autofocus />
                </UiFormField>
                <template v-if="mode === 'set'">
                    <UiFormField label="Confirm password">
                        <UiInput v-model="confirmation" type="password" autocomplete="off" class="w-full" />
                    </UiFormField>
                    <UiFormField label="Hint (optional)" help="Shown when you're asked for the password.">
                        <UiInput v-model="newHint" class="w-full" maxlength="255" />
                    </UiFormField>
                    <p class="text-dimmed text-xs">
                        If you forget this password, notes locked with it can't be opened. Coleus can't recover it: it never leaves this browser.
                    </p>
                </template>
                <p v-else-if="hint" class="text-dimmed text-xs">Hint: {{ hint }}</p>
                <p v-if="mismatch || (mode === 'set' && error)" class="text-error text-xs" role="alert">{{ mismatch ?? error }}</p>
                <div class="flex justify-end gap-2">
                    <UiButton color="neutral" variant="ghost" @click="open = false">Cancel</UiButton>
                    <UiButton type="submit" :loading="busy">{{ mode === 'set' ? 'Set password' : 'OK' }}</UiButton>
                </div>
            </form>
        </template>
    </UiModal>
</template>
