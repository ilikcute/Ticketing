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
        class="w-full rounded-2xl border-2 border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm transition-all focus:border-[#0E7BDC] focus:outline-none focus:ring-0 focus:shadow-[0_0_0_4px_rgba(14,123,220,0.25)]"
        v-model="model"
        ref="input"
    />
</template>
