<script setup lang="ts">
defineProps<{
    submit: any;
    form: any;
}>();
</script>
<template>
    <FormKit type="form" @submit="(fields, node) => submit()(fields, node)" :plugins="[form.plugin]">
        <template #default="{ value }">
            <slot :value="value" />
        </template>
        <template #submit>
            <UiButton type="submit" :disabled="form.processing.value">Save</UiButton>
        </template>
    </FormKit>
</template>
