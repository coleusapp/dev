<script setup lang="ts">
import MusicLayout from '@/layouts/MusicLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    files: Array<string>;
}>();

const playing = ref(null);
const src = ref(null);
const play = (file: string) => {
    playing.value = file;
    src.value = `/music/stream/${file}`;

}
</script>

<template>
    <Head title="Dashboard" />

    <MusicLayout>
        <audio v-if="src" class="w-full mb-5 rounded-xl bg-indigo-400" controls :src="src" autoplay loop></audio>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <button v-for="file in files" :key="file" type="button" class="cursor-pointer" @click="() => play(file)">
                <div class="relative transition duration-300 h-full w-full aspect-square bg-linear-to-t flex flex-col justify-end items-center rounded-lg shadow"
                    :class="[
                        playing === file ? 'from-green-900 to-green-300' : 'from-indigo-900 to-indigo-300',
                    ]"
                >
                    <UiIcon name="i-lucide-music" class="size-40 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-white/5" />
                    <p class="p-2 text-white font-semibold z-10">{{ file }}</p>
                    <!-- <audio class="w-full bg-indigo-600" controls src="/shared-assets/audio/t-rex-roar.mp3"></audio> -->
                </div>
            </button>
        </div>
    </MusicLayout>
</template>
