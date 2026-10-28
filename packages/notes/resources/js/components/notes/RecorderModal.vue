<script setup lang="ts">
import { useRecorder, type RecordingKind } from '@/composables/useRecorder';
import { computed, useTemplateRef, watch } from 'vue';

/**
 * Records a video from the camera or the screen, or a voice memo, right
 * in the browser (O3.1f / O3.6), and hands the file over to be attached.
 */
const props = defineProps<{
    kind: RecordingKind | null;
}>();

const emit = defineEmits<{
    close: [];
    recorded: [file: File];
}>();

const recorder = useRecorder();
const preview = useTemplateRef<HTMLVideoElement>('preview');

const titles: Record<RecordingKind, string> = { camera: 'Record a video', screen: 'Record the screen', audio: 'Record a voice memo' };

const time = computed(() => `${Math.floor(recorder.elapsed.value / 60)}:${String(recorder.elapsed.value % 60).padStart(2, '0')}`);

watch(
    () => props.kind,
    (kind) => {
        if (kind) {
            void recorder.start(kind);
        } else {
            recorder.cancel();
        }
    },
);

watch([recorder.stream, preview], ([stream, video]) => {
    if (video) {
        video.srcObject = stream;
    }
});

const finish = async (): Promise<void> => {
    const file = await recorder.stop();

    if (file) {
        emit('recorded', file);
    }

    emit('close');
};

const cancel = (): void => {
    recorder.cancel();
    emit('close');
};
</script>

<template>
    <UiModal
        :open="kind !== null"
        :title="kind ? titles[kind] : ''"
        description="Recorded in this browser and attached to the note."
        :dismissible="false"
        @update:open="(open) => !open && cancel()"
    >
        <template #body>
            <div class="flex flex-col items-center gap-3">
                <video
                    v-if="kind !== 'audio'"
                    ref="preview"
                    autoplay
                    muted
                    playsinline
                    class="bg-elevated aspect-video w-full rounded-md object-contain"
                />
                <div v-else class="bg-elevated flex h-24 w-full items-center justify-center rounded-md">
                    <UiIcon
                        name="i-lucide-mic"
                        class="size-8"
                        :class="recorder.state.value === 'recording' ? 'text-error animate-pulse' : 'text-dimmed'"
                    />
                </div>
                <p v-if="recorder.error.value" class="text-error text-sm" role="alert">{{ recorder.error.value }}</p>
                <p v-else class="text-sm tabular-nums" role="timer">
                    <span v-if="recorder.state.value === 'recording'" class="text-error">● Recording {{ time }}</span>
                    <span v-else class="text-dimmed">Starting…</span>
                </p>
                <div class="flex gap-2">
                    <UiButton color="neutral" variant="ghost" @click="cancel">Cancel</UiButton>
                    <UiButton icon="i-lucide-square" :disabled="recorder.state.value !== 'recording'" @click="finish">Stop and attach</UiButton>
                </div>
            </div>
        </template>
    </UiModal>
</template>
