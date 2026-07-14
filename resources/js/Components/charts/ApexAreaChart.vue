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
    title: {
        type: String,
        default: 'Volume Transaksi',
    },
})

const hasData = computed(() => props.values && props.values.length > 0)

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        fontFamily: 'inherit',
        height: '100%',
        parentHeightOffset: 0,
        toolbar: {
            show: false,
        },
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800,
            dynamicAnimation: {
                enabled: true,
                speed: 350
            }
        }
    },
    colors: ['#0891b2'], // Cyan-600 for a bit darker, more premium feel than 500
    stroke: {
        curve: 'smooth',
        width: 3,
        colors: ['#0891b2'],
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.5,
            opacityTo: 0.0,
            stops: [0, 90, 100]
        }
    },
    dataLabels: {
        enabled: false,
    },
    grid: {
        show: true,
        borderColor: '#f1f5f9', // Sangat tipis slate-100
        strokeDashArray: 3,
        xaxis: {
            lines: {
                show: false
            }
        },
        yaxis: {
            lines: {
                show: true
            }
        },
        padding: {
            top: 0,
            right: 0,
            bottom: 0,
            left: 10
        },
    },
    xaxis: {
        categories: props.labels,
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
        labels: {
            style: {
                colors: '#94a3b8',
                fontSize: '11px',
                fontWeight: 600,
            },
            offsetY: 5,
        },
        tooltip: {
            enabled: false, // disable default x-axis tooltip
        }
    },
    yaxis: {
        labels: {
            formatter: (value) => {
                return Math.round(value)
            },
            style: {
                colors: '#94a3b8',
                fontSize: '11px',
                fontWeight: 600,
            },
            offsetX: -10,
        },
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: function (val) {
                return val + " Penyewaan"
            }
        },
        style: {
            fontSize: '12px',
            fontFamily: 'inherit'
        },
        marker: {
            show: true,
        },
    }
}))

const chartSeries = computed(() => [
    {
        name: props.title,
        data: props.values,
    },
])
</script>

<template>
    <div class="h-full w-full">
        <div v-if="hasData" class="h-full w-full -ml-3">
            <!-- Render apex chart dengan transisi/animasi penuh -->
            <VueApexCharts type="area" height="100%" width="100%" :options="chartOptions" :series="chartSeries" />
        </div>
        <div v-else class="flex h-full items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
            <p class="text-sm font-medium text-slate-500">Belum ada data transaksi.</p>
        </div>
    </div>
</template>
