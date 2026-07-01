<script setup>
const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: 'md',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
})

const variantClassMap = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700',
    secondary: 'border border-primary-600 bg-white text-primary-600 hover:bg-primary-50',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    ghost: 'bg-transparent text-gray-700 hover:bg-gray-100',
}

const sizeClassMap = {
    sm: 'h-9 px-4 text-sm',
    md: 'h-10 px-5 text-sm',
    lg: 'h-12 px-6 text-base',
}

const buttonClass = [
    'inline-flex items-center justify-center rounded-xl font-semibold transition focus:outline-none focus:ring-2 focus:ring-primary-200 disabled:cursor-not-allowed disabled:opacity-60',
    variantClassMap[props.variant] || variantClassMap.primary,
    sizeClassMap[props.size] || sizeClassMap.md,
    props.block ? 'w-full' : '',
]
</script>

<template>
    <button :type="type" :disabled="disabled || loading" :class="buttonClass">
        <svg v-if="loading" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25" />
            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" />
        </svg>

        <slot />
    </button>
</template>