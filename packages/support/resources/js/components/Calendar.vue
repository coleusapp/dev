<script setup lang="ts">
import { DateTime } from 'luxon';

const dayOfWeek = DateTime.now().toFormat('ccc');
const month = DateTime.now().toFormat('LLL');
const day = DateTime.now().toFormat('dd')
</script>

<template>
    <div class="w-full rounded-3xl shadow-md p-4 bg-white h-[180px] flex flex-col -space-y-2">
        <div class="font-bold text-3xl flex gap-1 items-center justify-center">
            <span class="text-red-500">{{dayOfWeek}}</span>
            <span class="text-gray-500">{{month}}</span>
        </div>
        <div class="font-extrabold text-9xl text-black">
            {{day}}
        </div>
    </div>
</template>
