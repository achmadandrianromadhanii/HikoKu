<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

// Mendefinisikan properti yang diterima oleh komponen ini dari parent (Dashboard)
const props = defineProps({
    labels: {
        type: Array,
        default: () => [], // Array untuk sumbu X (contoh: tanggal)
    },
    values: {
        type: Array,
        default: () => [], // Array untuk sumbu Y (contoh: total revenue)
    },
    title: {
        type: String,
        default: 'Revenue',
    },
    valuePrefix: {
        type: String,
        default: 'Rp ', // Prefix mata uang
    },
    valueSuffix: {
        type: String,
        default: '',
    },
})

// Mengecek apakah ada data yang tersedia untuk dirender
const hasData = computed(() => props.values && props.values.length > 0)

// Mengatur konfigurasi premium untuk ApexCharts
const chartOptions = computed(() => ({
    chart: {
        type: 'area', // Menggunakan tipe area agar ada efek gradasi di bawah garis
        fontFamily: 'inherit',
        height: '100%',
        parentHeightOffset: 0,
        toolbar: {
            show: false, // Menyembunyikan menu hamburger (download/pan) agar UI terlihat bersih
        },
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800, // Kecepatan animasi awal saat diload
            dynamicAnimation: {
                enabled: true,
                speed: 350
            }
        }
    },
    colors: ['#06b6d4'], // Warna utama garis (Cyan-500)
    stroke: {
        curve: 'smooth', // Membuat garis melengkung halus, tidak kaku (patah-patah)
        width: 3, // Ketebalan garis
        colors: ['#06b6d4'],
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4, // Gradasi awal yang cukup terlihat
            opacityTo: 0.01, // Pudar menjadi transparan di bagian bawah
            stops: [0, 90, 100]
        }
    },
    dataLabels: {
        enabled: false, // Menyembunyikan angka di setiap titik agar tidak berantakan
    },
    grid: {
        show: true,
        borderColor: '#f8fafc', // Warna grid line sangat transparan/lembut (slate-50)
        strokeDashArray: 4, // Membuat grid horizontal menjadi garis putus-putus
        xaxis: {
            lines: {
                show: false // Menyembunyikan grid vertikal agar tampilan lapang
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
        categories: props.labels, // Data label sumbu X
        axisBorder: {
            show: false, // Menghilangkan garis tebal sumbu X
        },
        axisTicks: {
            show: false,
        },
        labels: {
            style: {
                colors: '#94a3b8', // Warna teks label X abu-abu (slate-400)
                fontSize: '12px',
                fontWeight: 500,
            },
        },
        tooltip: {
            enabled: false // Mematikan tooltip sumbu X bawaan yang kaku
        }
    },
    yaxis: {
        labels: {
            formatter: (value) => {
                // Menyederhanakan nominal besar menjadi K (Ribuan) atau M (Jutaan) agar hemat tempat
                if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M'
                if (value >= 1000) return (value / 1000).toFixed(0) + 'K'
                return value
            },
            style: {
                colors: '#94a3b8',
                fontSize: '11px',
                fontWeight: 500,
            },
            offsetX: -10,
        },
    },
    tooltip: {
        theme: 'dark', // Menggunakan tema dark untuk efek premium glassmorphism
        y: {
            formatter: function (val) {
                // Memformat nilai pada tooltip ke bentuk Rupiah
                return props.valuePrefix + new Intl.NumberFormat('id-ID').format(val) + props.valueSuffix
            }
        },
        style: {
            fontSize: '13px',
            fontFamily: 'inherit'
        },
        marker: {
            show: false, // Menghilangkan bulatan warna di sebelah teks tooltip
        },
    },
}))

// Membungkus data untuk disuplai ke ApexCharts
const chartSeries = computed(() => [
    {
        name: props.title,
        data: props.values,
    }
])
</script>

<template>
    <div class="h-full w-full">
        <!-- Render grafik Apex jika data tersedia -->
        <template v-if="hasData">
            <VueApexCharts
                type="area"
                height="100%"
                :options="chartOptions"
                :series="chartSeries"
            />
        </template>
        
        <!-- Tampilan kosong jika data tidak tersedia -->
        <div v-else class="flex h-full min-h-[300px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
            <p class="text-sm font-medium text-slate-500">Belum ada data revenue.</p>
        </div>
    </div>
</template>

<style>
/* Kustomisasi gaya Tooltip ApexCharts untuk menciptakan efek Glassmorphism premium (Blur) */
.apexcharts-tooltip {
    background: rgba(15, 23, 42, 0.8) !important; /* Warna dasar slate-900 dengan transparansi */
    border: 1px solid rgba(255,255,255,0.1) !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    backdrop-filter: blur(12px) !important; /* Efek kaca buram ala iOS/macOS */
    border-radius: 12px !important;
}
.apexcharts-tooltip-title {
    background: transparent !important;
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
    font-weight: 600 !important;
    color: #cbd5e1 !important; /* slate-300 */
    padding: 8px 12px !important;
    font-size: 11px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}
.apexcharts-tooltip-text-y-value {
    color: #22d3ee !important; /* cyan-400 */
    font-weight: 800 !important;
    font-size: 14px !important;
}
/* Menghapus outline saat grafik diklik */
.apexcharts-svg {
    outline: none !important;
}
</style>
