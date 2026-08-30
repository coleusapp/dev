<template>
    <div class="font-sans text-sm">{{ time }}</div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const time = ref('');

const updateTime = () => {
    const now = new Date();
    time.value = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
};

let interval: NodeJS.Timeout;

onMounted(() => {
    updateTime();
    interval = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>
