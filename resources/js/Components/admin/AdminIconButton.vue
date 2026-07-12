<script setup>
const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'default',
    },
    title: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const variantClassMap = {
    default: 'border-slate-200 bg-white text-slate-700 hover:border-emerald-200 hover:text-emerald-700',
    primary: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
    blue: 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100',
    red: 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100',
    amber: 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100',
}

const buttonClass = [
    'inline-flex h-8 w-8 items-center justify-center rounded-xl border transition disabled:cursor-not-allowed disabled:opacity-50',
    variantClassMap[props.variant] || variantClassMap.default,
]
</script>

<template>
    <button :type="type" :title="title" :aria-label="title" :disabled="disabled" :class="buttonClass">
        <slot />
    </button>
</template>
