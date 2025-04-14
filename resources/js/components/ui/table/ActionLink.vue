<script setup lang="ts">
import { Button, type ButtonVariants } from '@/components/ui/button';
import ButtonLink from '@/components/ButtonLink.vue';
import { InertiaLinkProps } from '@inertiajs/vue3';

interface Props {
    href: string|InertiaLinkProps['href'];
    icon?: string;
    tabindex?: number;
    method?: string;
    as?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

withDefaults(defineProps<Props>(), {
    variant: 'link',
    size: 'sm',
    method: 'get',
});
</script>
<template>
    <ButtonLink v-bind="$props">
        <slot />
    </ButtonLink>
</template>
