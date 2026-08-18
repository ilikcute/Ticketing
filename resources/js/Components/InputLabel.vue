<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-800 mb-2 font-heading">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
