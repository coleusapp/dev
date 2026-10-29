<script setup lang="ts">
import { jsonHeaders } from '@/lib/http';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

/**
 * Quick Note (A9.1): jot something down without leaving what you're doing;
 * it's saved to the "Quick Notes" folder. Opened from another Coleus page
 * (A9.2), the note links back to it, and so does this dialog afterwards.
 */
const props = defineProps<{
    from?: { path: string; title: string | null } | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const body = ref('');
const saving = ref(false);
const error = ref<string | null>(null);
const saved = ref<{ id: number; title: string } | null>(null);

watch(open, (isOpen) => {
    if (isOpen) {
        body.value = '';
        error.value = null;
        saved.value = null;
    }
});

const save = async (): Promise<void> => {
    if (body.value.trim() === '' || saving.value) {
        return;
    }

    saving.value = true;
    error.value = null;

    try {
        const response = await fetch(route('notes.quick.store'), {
            method: 'POST',
            headers: jsonHeaders(),
            credentials: 'same-origin',
            body: JSON.stringify({ body: body.value, from: props.from?.path ?? null, from_title: props.from?.title ?? null }),
        });
        const payload = await response.json().catch(() => null);

        if (!response.ok) {
            error.value = payload?.errors?.body?.[0] ?? payload?.message ?? "Couldn't save the note.";
            return;
        }

        saved.value = payload.data;
        router.reload({ only: ['notes', 'folders', 'counts'] });
    } finally {
        saving.value = false;
    }
};

const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        void save();
    }
};
</script>

<template>
    <UiModal v-model:open="open" title="Quick note" description="Saved to your Quick Notes folder.">
        <template #body>
            <div v-if="saved" class="flex flex-col gap-3 text-sm">
                <p>Saved “{{ saved.title }}” to Quick Notes.</p>
                <div class="flex flex-wrap gap-2">
                    <UiButton :to="route('notes.show', { note: saved.id })" color="neutral" variant="soft" @click="open = false">Open it</UiButton>
                    <UiButton color="neutral" variant="soft" icon="i-lucide-plus" @click="((saved = null), (body = ''))">Another</UiButton>
                    <a v-if="from" :href="from.path" class="text-primary self-center hover:underline">Back to {{ from.title || from.path }}</a>
                </div>
            </div>
            <form v-else class="flex flex-col gap-3" @submit.prevent="save">
                <p v-if="from" class="text-dimmed flex items-center gap-1 text-xs">
                    <UiIcon name="i-lucide-link" class="size-3" />
                    Will link back to {{ from.title || from.path }}
                </p>
                <UiTextarea
                    v-model="body"
                    :rows="6"
                    autoresize
                    autofocus
                    placeholder="Write something… (⌘/Ctrl+Enter saves)"
                    class="w-full"
                    @keydown="onKeydown"
                />
                <p v-if="error" class="text-error text-xs" role="alert">{{ error }}</p>
                <div class="flex justify-end gap-2">
                    <UiButton color="neutral" variant="ghost" @click="open = false">Cancel</UiButton>
                    <UiButton type="submit" :loading="saving" :disabled="body.trim() === ''">Save</UiButton>
                </div>
            </form>
        </template>
    </UiModal>
</template>
