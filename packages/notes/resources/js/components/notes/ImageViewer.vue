<script setup lang="ts">
/**
 * An image from a note, full screen (O3.2). Click anywhere or press Escape to close.
 */
defineProps<{
    image: { src: string; alt: string } | null;
}>();

defineEmits<{
    close: [];
}>();
</script>

<template>
    <UiModal
        :open="image !== null"
        fullscreen
        :title="image?.alt || 'Image'"
        description="Full-screen image"
        :ui="{ header: 'sr-only', body: 'p-0 sm:p-0 flex items-center justify-center bg-black/90' }"
        @update:open="(open) => !open && $emit('close')"
    >
        <template #body>
            <button
                v-if="image"
                type="button"
                class="flex h-full w-full cursor-zoom-out items-center justify-center p-4"
                aria-label="Close"
                @click="$emit('close')"
            >
                <img :src="image.src" :alt="image.alt" class="max-h-full max-w-full object-contain" />
            </button>
            <a
                v-if="image"
                :href="image.src"
                target="_blank"
                rel="noopener"
                class="absolute end-4 bottom-4 rounded bg-black/60 px-2 py-1 text-xs text-white hover:bg-black/80"
                >Open original</a
            >
        </template>
    </UiModal>
</template>
