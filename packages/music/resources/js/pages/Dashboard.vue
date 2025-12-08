<script setup lang="ts">
import MusicLayout from '@/layouts/MusicLayout.vue';
import { Head } from '@inertiajs/vue3';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    files: Array<string>;
}>();

const player = ref(null);
const playing = ref(null);
const index = ref(-1);
const src = ref(null);
onMounted(() => {
    navigator.mediaSession.setActionHandler('nexttrack', () => next());
    navigator.mediaSession.setActionHandler('previoustrack', () => previous());
    player.value = new Plyr('#player', {
        // enabled: false,
        autoplay: true,
        controls: ['play', 'progress', 'current-time', 'mute'],
    });
    player.value.on('ended', next);
});
const play = (file: string, i: number) => {
    playing.value = file;
    index.value = i;
    src.value = `/music/stream/${file}`;
    player.value.source = {
        type: 'audio',
        sources: [
            {
                src: src.value,
                type: 'audio/mp3',
            },
        ],
    };
};
const next = () => {
    if (!props.files.length) return;

    index.value = (index.value + 1) % props.files.length;

    play(props.files[index.value], index.value);
};
const previous = () => {
    if (!props.files.length || index.value === 0) return;

    index.value = (index.value - 1) % props.files.length;

    play(props.files[index.value], index.value);
};
</script>

<template>
    <Head title="Dashboard" />

    <MusicLayout>
        <div v-show="src" class="absolute right-0 bottom-0 left-0 z-20 w-full bg-white dark:bg-gray-900">
            <audio id="player" class="w-full" controls></audio>
        </div>
        <div class="grid grid-cols-2 gap-4 pb-12 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <button v-for="(file, index) in files" :key="index" type="button" class="cursor-pointer" @click="() => play(file, index)">
                <div
                    class="relative flex aspect-square h-full w-full flex-col items-center justify-end rounded-lg bg-linear-to-t shadow transition duration-300"
                    :class="[playing === file ? 'from-green-900 to-green-300' : 'from-indigo-900 to-indigo-300']"
                >
                    <UiIcon
                        name="i-lucide-music"
                        class="absolute top-1/2 left-1/2 size-40 -translate-x-1/2 -translate-y-1/2 transform text-white/5"
                    />
                    <p class="z-10 p-2 font-semibold text-white">{{ file }}</p>
                    <!-- <audio class="w-full bg-indigo-600" controls src="/shared-assets/audio/t-rex-roar.mp3"></audio> -->
                </div>
            </button>
        </div>
    </MusicLayout>
</template>

<style>
:root {
    --plyr-color-main: var(--color-purple-600);
    --plyr-audio-controls-background: var(--color-white);
    --plyr-audio-control-color: var(--color-black);
}
@media (prefers-color-scheme: dark) {
    :root {
        --plyr-audio-controls-background: var(--color-gray-900);
        --plyr-audio-control-color: var(--color-white);
    }
}
</style>
