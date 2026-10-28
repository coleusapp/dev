<script setup lang="ts">
import type { NoteDetailData } from '@/components/notes/note';
import NoteEditor from '@/components/notes/NoteEditor.vue';
import { cachedKey, forgetKeys, keyParams, keysVersion, unlockWithPassword } from '@/composables/useNoteKeys';
import { jsonHeaders } from '@/lib/http';
import { decryptText } from '@/lib/noteCrypto';
import { router } from '@inertiajs/vue3';
import { computed, ref, useTemplateRef, watch } from 'vue';

/**
 * A password-locked note (O12.5): a lock screen until the notes password
 * is entered, then the normal editor on the decrypted text, saving
 * encrypted. Not kept alive between notes, so its text leaves memory when
 * another note opens. Every other editor prop is passed through.
 */
defineOptions({ inheritAttrs: false });

const props = defineProps<{
    note: NoteDetailData;
    readonly?: boolean;
}>();

const plaintext = ref<string | null>(null);
const key = ref<CryptoKey | null>(null);
const password = ref('');
const hint = ref<string | null>(null);
const error = ref<string | null>(null);
const busy = ref(false);
const editor = useTemplateRef<InstanceType<typeof NoteEditor>>('editor');

const keyId = computed(() => props.note.lock?.key_id ?? null);

const open = async (withKey: CryptoKey): Promise<void> => {
    if (!props.note.lock) {
        return;
    }

    try {
        plaintext.value = await decryptText(withKey, props.note.lock);
        key.value = withKey;
        error.value = null;
    } catch {
        error.value = "This note couldn't be decrypted.";
    }
};

/**
 * Opens straight away while the password is remembered; locks again when
 * the remembered keys are forgotten (Lock now, or unused for a while).
 */
watch(
    [keyId, keysVersion],
    async () => {
        const remembered = keyId.value === null ? null : cachedKey(keyId.value);

        if (remembered) {
            await open(remembered);
        } else {
            plaintext.value = null;
            key.value = null;
            hint.value = keyId.value === null ? null : ((await keyParams(keyId.value))?.hint ?? null);
        }
    },
    { immediate: true },
);

const unlock = async (): Promise<void> => {
    if (keyId.value === null || busy.value) {
        return;
    }

    busy.value = true;

    try {
        const params = await keyParams(keyId.value);
        const unlocked = params ? await unlockWithPassword(params, password.value) : null;

        if (!unlocked) {
            error.value = 'Wrong password.';
            return;
        }

        password.value = '';
        await open(unlocked);
    } finally {
        busy.value = false;
    }
};

/**
 * Locks every locked note now (after saving this one).
 */
const lockNow = async (): Promise<void> => {
    await editor.value?.flush();
    forgetKeys();
};

/**
 * Takes the lock off for good, with the text as it is now.
 */
const removeLock = async (): Promise<boolean> => {
    if (!editor.value) {
        return false;
    }

    await editor.value.flush();
    const response = await fetch(route('notes.lock.destroy', { note: props.note.id }), {
        method: 'DELETE',
        headers: jsonHeaders(),
        credentials: 'same-origin',
        body: JSON.stringify({ body: editor.value.currentBody() }),
    });

    if (response.ok) {
        router.reload({ only: ['note', 'notes'] });
    }

    return response.ok;
};

defineExpose({
    paletteCommands: computed(() => editor.value?.paletteCommands ?? []),
    isOpen: computed(() => plaintext.value !== null),
    lockNow,
    removeLock,
});
</script>

<template>
    <div v-if="plaintext !== null && key" class="flex h-full flex-col gap-2">
        <div class="bg-elevated flex items-center gap-2 rounded-md px-3 py-1.5 text-xs">
            <UiIcon name="i-lucide-lock-open" class="text-dimmed size-4 shrink-0" />
            <span class="text-muted flex-1"
                >Locked note, open. It's saved encrypted and locks again when you leave it or after 10 minutes unused.</span
            >
            <UiButton size="xs" color="neutral" variant="soft" icon="i-lucide-lock" @click="lockNow">Lock now</UiButton>
        </div>
        <NoteEditor
            ref="editor"
            v-bind="$attrs"
            :note="{ ...note, body: plaintext }"
            :readonly="readonly"
            :encryption="{ keyId: note.lock!.key_id, key }"
            class="min-h-0 flex-1"
        />
    </div>
    <form v-else class="mx-auto flex h-full max-w-xs flex-col items-center justify-center gap-3 p-4 text-center" @submit.prevent="unlock">
        <UiIcon name="i-lucide-lock" class="text-dimmed size-10" />
        <p class="font-semibold">{{ note.title || 'Locked note' }}</p>
        <p class="text-dimmed text-sm">This note is locked. Enter your notes password to view it.</p>
        <UiInput
            v-model="password"
            type="password"
            autocomplete="off"
            placeholder="Notes password"
            aria-label="Notes password"
            class="w-full"
            autofocus
        />
        <p v-if="hint" class="text-dimmed text-xs">Hint: {{ hint }}</p>
        <p v-if="error" class="text-error text-xs" role="alert">{{ error }}</p>
        <UiButton type="submit" :loading="busy" :disabled="password === ''">View note</UiButton>
    </form>
</template>
