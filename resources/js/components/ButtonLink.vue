<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button, type ButtonVariants } from '@/components/ui/button';
import Icon from "@/components/Icon.vue";

interface Props {
    href: string;
    icon?: string;
    tabindex?: number;
    method?: string;
    as?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current dark:decoration-neutral-500"
    >
        <Button :variant="variant" :size="size">
            <Icon v-if="icon" :name="icon" />
            <slot />
        </Button>
    </Link>
</template>
