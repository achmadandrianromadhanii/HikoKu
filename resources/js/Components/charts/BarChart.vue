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
    height: {
        type: Number,
        default: 240,
    },
    valueSuffix: {
        type: String,
        default: '',
    },
})

const chartItems = computed(() => {
    return props.labels.map((label, index) => ({
        label,
        value: Number(props.values[index] || 0),
    }))
})

const maxValue = computed(() => {
    return Math.max(...chartItems.value.map((item) => item.value), 1)
})

const hasData = computed(() => chartItems.value.length > 0)

const chartHeight = 160

const barWidth = computed(() => {
    if (!chartItems.value.length) return 0
    return `${100 / chartItems.value.length}%`
})

const barInnerHeight = (value) => {
    return `${Math.max((value / maxValue.value) * chartHeight, value > 0 ? 8 : 0)}px`
}

const shortLabel = (label) => {
    if (!label) return '-'
    return String(label).length > 12 ? `${String(label).slice(0, 12)}…` : String(label)
}

const formatValue = (value) => {
    return `${new Intl.NumberFormat('id-ID').format(Number(value || 0))}${props.valueSuffix}`
}
</script>

<template>
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div v-if="hasData" class="space-y-4">
            <div class="flex items-end gap-2 rounded-2xl bg-slate-50 px-3 pt-4" :style="{ height: `${height}px` }">
                <div v-for="item in chartItems" :key="`${item.label}-${item.value}`"
                    class="flex min-w-0 flex-1 flex-col items-center justify-end" :style="{ width: barWidth }">
                    <p class="mb-2 text-[11px] font-semibold text-slate-500">
                        {{ formatValue(item.value) }}
                    </p>

                    <div class="w-full max-w-[40px] rounded-t-2xl bg-gradient-to-t from-emerald-600 to-emerald-400 transition-all duration-300"
                        :style="{ height: barInnerHeight(item.value) }" />

                    <p class="mt-3 w-full truncate pb-3 text-center text-[11px] font-medium text-slate-500">
                        {{ shortLabel(item.label) }}
                    </p>
                </div>
            </div>
        </div>

        <div v-else
            class="flex min-h-[220px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50">
            <p class="text-sm text-slate-500">Belum ada data chart.</p>
        </div>
    </div>
</template>
