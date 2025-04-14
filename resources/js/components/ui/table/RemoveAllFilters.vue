<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
</script>
<template>
    <Link :href="$page.url.split('?')[0]">
        <Button size="sm" variant="link">Remove All Filters</Button>
    </Link>
</template>
