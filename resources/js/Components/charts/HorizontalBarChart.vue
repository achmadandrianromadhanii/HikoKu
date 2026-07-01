<script setup>
import { computed } from 'vue'

const props = defineProps({
    labels: {
        type: Array,
        default: () => [],
    },
    values: {
        type: Array,
        default: () => [],
    },
    valueSuffix: {
        type: String,
        default: '',
    },
})

const items = computed(() => {
    return props.labels.map((label, index) => ({
        label,
        value: Number(props.values[index] || 0),
    }))
})

const hasData = computed(() => items.value.length > 0)

const maxValue = computed(() => {
    return Math.max(...items.value.map((item) => item.value), 1)
})

const getWidth = (value) => {
    return `${Math.max((Number(value || 0) / maxValue.value) * 100, value > 0 ? 8 : 0)}%`
}

const formatValue = (value) => {
    return `${new Intl.NumberFormat('id-ID').format(Number(value || 0))}${props.valueSuffix}`
}
</script>

<template>
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div v-if="hasData" class="space-y-4">
            <div v-for="item in items" :key="`${item.label}-${item.value}`" class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <p class="truncate text-sm font-medium text-slate-800">
                        {{ item.label }}
                    </p>

                    <p class="shrink-0 text-sm font-semibold text-slate-900">
                        {{ formatValue(item.value) }}
                    </p>
                </div>

                <div class="h-3 rounded-full bg-slate-100">
                    <div class="h-3 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-300"
                        :style="{ width: getWidth(item.value) }" />
                </div>
            </div>
        </div>

        <div v-else
            class="flex min-h-[220px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50">
            <p class="text-sm text-slate-500">Belum ada data chart.</p>
        </div>
    </div>
</template>