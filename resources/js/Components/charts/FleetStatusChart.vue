<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    labels: {
        type: Array,
        default: () => [],
    },
    series: {
        type: Array,
        default: () => [],
    },
})

// Komentar: Mengecek apakah ada data di dalam series
const hasData = computed(() => {
    return props.series && props.series.length > 0 && props.labels.length > 0
})

// Komentar: Konfigurasi ApexCharts untuk Stacked Bar Chart yang elegan
const chartOptions = computed(() => ({
    chart: {
        type: 'bar',
        stacked: true,
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
        }
    },
    // Komentar: Warna kustom sesuai desain sistem
    // 0: Tersedia (Emerald-500)
    // 1: Sedang Disewa (Blue-500)
    // 2: Di-booking (Yellow-500)
    // 3: Maintenance (Red-500)
    colors: ['#10B981', '#3B82F6', '#EAB308', '#EF4444'],
    plotOptions: {
        bar: {
            horizontal: false,
            borderRadius: 4,
            columnWidth: '45%',
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        width: 2,
        colors: ['transparent']
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
    grid: {
        show: true,
        borderColor: '#f1f5f9',
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
    fill: {
        opacity: 1
    },
    // Komentar: Shared Tooltip agar ketika kursor di hover ke 1 batang, semua nilai tampil sekaligus
    tooltip: {
        theme: 'light',
        shared: true,
        intersect: false,
        y: {
            formatter: function (val) {
                return val + " Unit"
            }
        },
        style: {
            fontSize: '12px',
            fontFamily: 'inherit'
        },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
        offsetY: -20,
        markers: {
            radius: 12,
        },
        itemMargin: {
            horizontal: 10,
            vertical: 0
        },
        fontSize: '12px',
        fontWeight: 500,
        labels: {
            colors: '#64748b'
        }
    }
}))
</script>

<template>
    <div class="h-full w-full">
        <div v-if="hasData" class="h-full w-full -ml-3 mt-2">
            <!-- Render apex chart dengan konfigurasi stacked bar -->
            <VueApexCharts type="bar" height="100%" width="100%" :options="chartOptions" :series="props.series" />
        </div>
        <div v-else class="flex h-full items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
            <p class="text-sm font-medium text-slate-400">Belum ada data sirkulasi barang.</p>
        </div>
    </div>
</template>
