<script setup lang="ts">
import Icon from "@/components/Icon.vue";

const messages = [
    'Empty and forgotten',
    'Left with nothing',
    'Gone. Just emptiness',
    'Lost in the void',
    'Nothing remains',
    'Only silence lingers',
    'Fading into nothing',
    'A hollow space',
    'Forgotten and empty',
];
const message = () => messages[Math.floor(Math.random() * messages.length)];
const icons = [
    'Origami', 'Rabbit', 'Squirrel', 'Bird', 'Fish', 'Rat', 'Worm'
];
const icon = () => icons[Math.floor(Math.random() * icons.length)];
</script>

<template>
    <div class="flex w-full flex-col items-center justify-center gap-5 py-10">
        <Icon :name="icon()" class="h-10 w-10 text-gray-500 dark:text-gray-300" />
        <span class="italic font-thin text-sm leading-tight text-gray-500 dark:text-gray-300">
            <slot>&ldquo; {{ message() }} &rdquo;</slot>
        </span>
    </div>
</template>
