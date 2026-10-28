<script setup lang="ts">
import PasswordModal from '@/components/notes/PasswordModal.vue';
import { changeHint, changePassword, currentKeyParams, setPassword, type KeyParams } from '@/composables/useNoteKeys';
import { onMounted, ref } from 'vue';

/**
 * The notes password on the Settings page (A7.1): set it, change it
 * (every locked note is re-encrypted in this browser), reset it (a new
 * password for future locks; notes locked before keep the old one), and
 * edit the hint.
 */
const current = ref<KeyParams | null>(null);
const loaded = ref(false);
const dialog = ref<'set' | 'reset' | 'change' | 'hint' | null>(null);
const busy = ref(false);
const error = ref<string | null>(null);
const done = ref<string | null>(null);

const oldPassword = ref('');
const newPassword = ref('');
const confirmation = ref('');
const hint = ref('');

const load = async (): Promise<void> => {
    current.value = await currentKeyParams();
    loaded.value = true;
};

onMounted(load);

const openDialog = (which: 'set' | 'reset' | 'change' | 'hint'): void => {
    oldPassword.value = '';
    newPassword.value = '';
    confirmation.value = '';
    hint.value = which === 'hint' ? (current.value?.hint ?? '') : '';
    error.value = null;
    done.value = null;
    dialog.value = which;
};

const finish = async (message: string): Promise<void> => {
    dialog.value = null;
    done.value = message;
    await load();
};

const onNewPassword = async ({ password, hint: newHint }: { password: string; hint: string | null }): Promise<void> => {
    busy.value = true;
    const result = await setPassword(password, newHint);
    busy.value = false;

    if ('error' in result) {
        error.value = result.error;
    } else {
        await finish(dialog.value === 'reset' ? 'New password set. Notes locked before still open with the old one.' : 'Password set.');
    }
};

const submitChange = async (): Promise<void> => {
    if (newPassword.value.length < 6 || newPassword.value !== confirmation.value) {
        error.value = newPassword.value.length < 6 ? 'Use at least 6 characters.' : "The new passwords don't match.";
        return;
    }

    busy.value = true;
    const result = await changePassword(oldPassword.value, newPassword.value, hint.value.trim() || null);
    busy.value = false;

    if (result.error) {
        error.value = result.error;
    } else {
        await finish('Password changed. Your locked notes now open with the new one.');
    }
};

const submitHint = async (): Promise<void> => {
    if (!current.value) {
        return;
    }

    busy.value = true;
    const result = await changeHint(current.value.id, hint.value);
    busy.value = false;

    if (result.error) {
        error.value = result.error;
    } else {
        await finish('Hint saved.');
    }
};
</script>

<template>
    <section class="border-default flex flex-col gap-3 border-t pt-6" aria-labelledby="notes-password-heading">
        <div>
            <h2 id="notes-password-heading" class="font-semibold">Locked notes password</h2>
            <p class="text-dimmed text-sm">
                Locked notes are encrypted in your browser with this password. Coleus can't recover it if you forget it.
            </p>
        </div>
        <p v-if="!loaded" class="text-dimmed text-sm">Loading…</p>
        <template v-else-if="current">
            <p class="text-sm">
                A password is set.<span v-if="current.hint" class="text-dimmed"> Hint: {{ current.hint }}</span>
            </p>
            <div class="flex flex-wrap gap-2">
                <UiButton size="sm" color="neutral" variant="soft" @click="openDialog('change')">Change password</UiButton>
                <UiButton size="sm" color="neutral" variant="soft" @click="openDialog('hint')">Edit hint</UiButton>
                <UiButton size="sm" color="neutral" variant="ghost" @click="openDialog('reset')">Reset password…</UiButton>
            </div>
        </template>
        <div v-else class="flex items-center gap-3">
            <p class="text-sm">No password yet. It's also asked for the first time you lock a note.</p>
            <UiButton size="sm" color="neutral" variant="soft" @click="openDialog('set')">Set password</UiButton>
        </div>
        <p v-if="done" class="text-dimmed text-sm" role="status">{{ done }}</p>

        <PasswordModal
            :open="dialog === 'set' || dialog === 'reset'"
            mode="set"
            :title="dialog === 'reset' ? 'Reset notes password' : 'Set a notes password'"
            :description="
                dialog === 'reset'
                    ? 'For notes you lock from now on. Notes locked before keep the old password: you can still open them with it.'
                    : 'Used to lock notes. Locked notes are encrypted in this browser before they are saved.'
            "
            :error="error"
            :busy="busy"
            @update:open="(open) => !open && (dialog = null)"
            @submit="onNewPassword"
        />

        <UiModal
            :open="dialog === 'change'"
            title="Change notes password"
            description="Every note locked with the current password is re-encrypted here with the new one."
            @update:open="(open) => !open && (dialog = null)"
        >
            <template #body>
                <form class="flex flex-col gap-3" @submit.prevent="submitChange">
                    <UiFormField label="Current password">
                        <UiInput v-model="oldPassword" type="password" autocomplete="off" class="w-full" autofocus />
                    </UiFormField>
                    <UiFormField label="New password">
                        <UiInput v-model="newPassword" type="password" autocomplete="off" class="w-full" />
                    </UiFormField>
                    <UiFormField label="Confirm new password">
                        <UiInput v-model="confirmation" type="password" autocomplete="off" class="w-full" />
                    </UiFormField>
                    <UiFormField label="Hint (optional)">
                        <UiInput v-model="hint" class="w-full" maxlength="255" />
                    </UiFormField>
                    <p v-if="error" class="text-error text-xs" role="alert">{{ error }}</p>
                    <div class="flex justify-end gap-2">
                        <UiButton color="neutral" variant="ghost" @click="dialog = null">Cancel</UiButton>
                        <UiButton type="submit" :loading="busy">Change password</UiButton>
                    </div>
                </form>
            </template>
        </UiModal>

        <UiModal
            :open="dialog === 'hint'"
            title="Password hint"
            description="Shown when you're asked for the notes password."
            @update:open="(open) => !open && (dialog = null)"
        >
            <template #body>
                <form class="flex flex-col gap-3" @submit.prevent="submitHint">
                    <UiInput v-model="hint" class="w-full" maxlength="255" autofocus aria-label="Hint" />
                    <p v-if="error" class="text-error text-xs" role="alert">{{ error }}</p>
                    <div class="flex justify-end gap-2">
                        <UiButton color="neutral" variant="ghost" @click="dialog = null">Cancel</UiButton>
                        <UiButton type="submit" :loading="busy">Save hint</UiButton>
                    </div>
                </form>
            </template>
        </UiModal>
    </section>
</template>
