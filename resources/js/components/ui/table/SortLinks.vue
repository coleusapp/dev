<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { computed } from 'vue';
import ButtonLink from '@/components/ButtonLink.vue';

const props = defineProps<{
    items: {
        label: string;
        value: string;
    }[],
    query: string;
}>();

const { sort, ...params } = route().params;
const noneLink = computed(() => route(route().current(), params));
</script>
<template>
    <div v-if="items.length" class="flex gap-2 mb-1">
        <Link v-for="sort in items" :key="sort.value" :href="route(route().current(), {...route().params, [query]: sort.value})">
            <Button class="p-0 h-auto" size="sm" variant="link">{{ sort.label }}</Button>
        </Link>
        <Link :href="noneLink">
            <Button class="p-0 h-auto" size="sm" variant="link">None</Button>
        </Link>
    </div>
</template>
