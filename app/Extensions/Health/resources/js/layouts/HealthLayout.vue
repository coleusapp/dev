<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Dumbbell, Laugh, Weight } from 'lucide-vue-next';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Weights',
        href: route('health.weights.index'),
        icon: Weight,
    },
    {
        title: 'Workouts',
        href: route('health.workouts.index'),
        icon: Dumbbell,
    },
    {
        title: 'Oral Cares',
        href: route('health.weights.index'),
        icon: Laugh,
    },
];

const page = usePage<SharedData>();
const currentPath = page.props.ziggy?.location || new URL(page.props.ziggy.location).pathname || '';
</script>

<template>
    <div class="p-4">
        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-x-0 space-y-1">
                    <template v-for="item in sidebarNavItems" :key="item.href">
                        <Button variant="ghost" :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]" as-child>
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                {{ item.title }}
                            </Link>
                        </Button>
                    </template>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
