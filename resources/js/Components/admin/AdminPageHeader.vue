<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h2 class="truncate text-[28px] font-semibold tracking-tight text-slate-900">
                {{ title }}
            </h2>

            <p v-if="subtitle" class="mt-1 text-sm text-slate-500">
                {{ subtitle }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
