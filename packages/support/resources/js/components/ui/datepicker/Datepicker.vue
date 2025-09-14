<script setup lang="ts">
import { CalendarDate, DateFormatter, getLocalTimeZone } from '@internationalized/date';
import { shallowRef } from 'vue';

const df = new DateFormatter('en-US', {
    dateStyle: 'medium'
});

const modelValue = shallowRef(new CalendarDate(2022, 1, 10));
</script>

<template>
    <UiPopover>
        <UiButton color="neutral" variant="subtle" icon="i-lucide-calendar">
            {{ modelValue ? df.format(modelValue.toDate(getLocalTimeZone())) : 'Select a date' }}
        </UiButton>

        <template #content>
            <UiCalendar v-model="modelValue" class="p-2" />
        </template>
    </UiPopover>
</template>
