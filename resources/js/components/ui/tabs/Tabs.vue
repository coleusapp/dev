<script setup lang="ts">
import { computed, type HTMLAttributes, useSlots } from 'vue';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import { TabsContent, TabsIndicator, TabsList, TabsRoot, TabsTrigger } from 'radix-vue';

const props = defineProps<{
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
    tabs: string[];
    defaultTab?: string;
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});

// const computedDefaultTab = computed(() => props.defaultTab || Object.keys(useSlots())[0] || props.tabs[0])
</script>

<template>
    {{Object.keys(useSlots())}}
    <TabsRoot class="flex flex-col w-full">
        <TabsList class="relative shrink-0 flex border-b border-sidebar-border/70">
            <TabsIndicator class="absolute px-8 left-0 h-[2px] bottom-0 w-[--radix-tabs-indicator-size] translate-x-[--radix-tabs-indicator-position] rounded-full transition-[width,transform] duration-300">
                <div class="bg-primary w-full h-full" />
            </TabsIndicator>
            <TabsTrigger
                class="bg-white px-5 h-[45px] flex items-center justify-center text-[15px] leading-none text-primary/90 select-none rounded-tl-md hover:text-primary data-[state=active]:text-primary data-[state=active]:font-bold outline-none cursor-pointer focus-visible:relative"
                v-for="(tab, index) in tabs"
                :key="index"
                :value="tab"
            >
                <slot :name="`${tab}-tab`" :tab="tab">{{ tab }}</slot>
            </TabsTrigger>
        </TabsList>
        <TabsContent
            v-for="(tab, index) in tabs"
            :key="index"
            class="grow p-5 bg-white rounded-b-md outline-none"
            :value="tab"
        >
            <slot :name="`${tab}-content`" :tab="tab"></slot>
        </TabsContent>
    </TabsRoot>
</template>
