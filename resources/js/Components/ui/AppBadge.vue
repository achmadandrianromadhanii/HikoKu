<script setup>
const props = defineProps({
    variant: {
        type: String,
        default: 'default',
    },
})

const variantClassMap = {
    default: 'bg-gray-100 text-gray-700',
    success: 'bg-emerald-100 text-emerald-700',
    warning: 'bg-amber-100 text-amber-700',
    danger: 'bg-red-100 text-red-600',
    info: 'bg-blue-100 text-blue-700',
    primary: 'bg-primary-100 text-primary-700',
}
</script>

<template>
    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
        :class="variantClassMap[variant] || variantClassMap.default">
        <slot />
    </span>
</template>
