<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    labels: {
        type: Array,
        default: () => [],
    },
    values: {
        type: Array,
        default: () => [],
    },
})

const hasData = computed(() => props.values && props.values.length > 0)
const total = computed(() => props.values.reduce((sum, val) => sum + Number(val), 0))

const chartOptions = computed(() => ({
    chart: {
        type: 'donut',
        fontFamily: 'inherit',
        animations: {
            enabled: true,
            speed: 800,
            dynamicAnimation: {
                enabled: true,
                speed: 350
            }
        }
    },
    labels: props.labels,
    colors: ['#06b6d4', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444', '#3b82f6'],
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '11px',
                        fontFamily: 'inherit',
                        color: '#94a3b8', // slate-400
                        offsetY: -10,
                    },
                    value: {
                        show: true,
                        fontSize: '28px',
                        fontFamily: 'inherit',
                        fontWeight: 800,
                        color: '#0f172a', // slate-900
                        offsetY: 10,
                        formatter: function (val) {
                            return new Intl.NumberFormat('id-ID').format(val)
                        }
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: 'Total Data',
                        fontSize: '11px',
                        fontFamily: 'inherit',
                        color: '#94a3b8',
                        formatter: function (w) {
                            return new Intl.NumberFormat('id-ID').format(total.value)
                        }
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        show: true,
        width: 4,
        colors: ['#ffffff']
    },
    legend: {
        show: true,
        position: 'bottom',
        fontFamily: 'inherit',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
            colors: '#475569',
        },
        markers: {
            radius: 12,
        },
        itemMargin: {
            horizontal: 10,
            vertical: 5
        }
    },
    tooltip: {
        theme: 'dark',
        y: {
            formatter: function (val) {
                return new Intl.NumberFormat('id-ID').format(val)
            }
        },
        style: {
            fontSize: '13px',
            fontFamily: 'inherit'
        },
    },
}))
</script>

<template>
    <div class="h-full w-full">
        <template v-if="hasData">
            <VueApexCharts
                type="donut"
                height="320"
                :options="chartOptions"
                :series="values.map(Number)"
            />
        </template>
        <div v-else class="flex h-full min-h-[300px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
            <p class="text-sm font-medium text-slate-500">Belum ada data chart.</p>
        </div>
    </div>
</template>
