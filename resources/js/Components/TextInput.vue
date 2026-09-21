<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-brand-sky focus:ring-brand-sky dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:focus:border-brand-sky dark:focus:ring-brand-sky bg-white text-slate-900"
        v-model="model"
        ref="input"
    />
</template>
