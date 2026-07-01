<script setup>
import { computed, h } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Dashboard', subtitle: 'Ringkasan aktivitas dan metrik utama.' }, () => page) })
import AdminStatCard from '@/Components/admin/AdminStatCard.vue'
import LineChart from '@/Components/charts/LineChart.vue'
import DonutChart from '@/Components/charts/DonutChart.vue'
import ApexAreaChart from '@/Components/charts/ApexAreaChart.vue' // Komentar: Menggunakan ApexAreaChart untuk fitur realtime dan tampilan premium
import TopProductsList from '@/Components/charts/TopProductsList.vue' // Komentar: Mengganti BarChart dengan List yang lebih elegan
import FleetStatusChart from '@/Components/charts/FleetStatusChart.vue' // Komentar: Komponen Stacked Bar Chart baru untuk sirkulasi armada
import {
    Boxes,
    CheckCircle2,
    ClipboardList,
    AlertTriangle,
} from 'lucide-vue-next'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            rentals_today: 0,
            revenue_month: 0,
            active_rentals: 0,
            low_stock_count: 0,
        }),
    },
    revenueChart: {
        type: Object,
        default: () => ({
            labels: [],
            values: [],
        }),
    },
    statusChart: {
        type: Object,
        default: () => ({
            labels: [],
            values: [],
        }),
    },
    rentalsVolumeChart: {
        type: Object,
        default: () => ({
            labels: [],
            values: [],
        }),
    },
    topProductsChart: {
        type: Object,
        default: () => ({
            labels: [],
            values: [],
        }),
    },
    fleetStatusChart: {
        type: Object,
        default: () => ({
            labels: [],
            series: [],
        }),
    },
    recentTransactions: {
        type: Array,
        default: () => [],
    }
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

// Komentar: State Reaktif untuk Volume Chart agar bisa di-update secara realtime tanpa reload
import { ref, onMounted } from 'vue'
const volumeChartLabels = ref([...props.rentalsVolumeChart.labels])
const volumeChartValues = ref([...props.rentalsVolumeChart.values])

// Komentar: Mendengarkan event Websocket (Laravel Echo) untuk pembaruan realtime
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('admin-notifications')
            .listen('.NewRentalCreated', (e) => {
                // Animasi update chart yang smooth: Tambahkan 1 transaksi pada hari terakhir
                if (volumeChartValues.value.length > 0) {
                    const lastIndex = volumeChartValues.value.length - 1;
                    volumeChartValues.value[lastIndex] += 1;
                }
            })
    }
})

const statCards = computed(() => [
    {
        label: 'Rental Hari Ini',
        value: props.stats?.rentals_today ?? 0,
        icon: ClipboardList,
        variant: 'blue',
    },
    {
        label: 'Revenue Bulan Ini',
        value: formatCurrency(props.stats?.revenue_month ?? 0),
        icon: CheckCircle2,
        variant: 'emerald',
    },
    {
        label: 'Rental Aktif',
        value: props.stats?.active_rentals ?? 0,
        icon: Boxes,
        variant: 'amber',
    },
    {
        label: 'Stok Kritis',
        value: props.stats?.low_stock_count ?? 0,
        icon: AlertTriangle,
        variant: 'red',
    },
])
</script>

<template>
    <!-- Top Bar Dashboard tetap dipertahankan bawaan Layout, namun konten langsung ke inti data -->
    <!-- Persistent AdminLayout -->
        
        <div class="h-full overflow-y-auto custom-scrollbar space-y-6 pr-1 pb-4">
            <!-- Stats Grid: Ultra-minimalist cards -->
            <section class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                <AdminStatCard v-for="item in statCards" :key="item.label" :label="item.label" :value="item.value"
                    :icon="item.icon" :variant="item.variant" />
            </section>

            <!-- Charts Section (12-column Grid: 8 for Line Chart, 4 for Donut Chart) -->
            <section class="grid gap-6 xl:grid-cols-12">
                
                <!-- Main Trend Chart (8 cols) -->
                <div class="xl:col-span-8 flex flex-col rounded-3xl border border-slate-100 bg-white p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold tracking-tight text-slate-900">Trend Revenue</h3>
                            <p class="text-xs font-medium text-slate-500 mt-1">Pendapatan beberapa periode terakhir.</p>
                        </div>
                    </div>
                    <!-- Chart Component wrapper to take remaining height -->
                    <div class="flex-1 min-h-[320px]">
                        <LineChart :labels="revenueChart.labels" :values="revenueChart.values" title="Revenue"
                            value-prefix="Rp " />
                    </div>
                </div>

                <!-- Status Donut Chart (4 cols) -->
                <div class="xl:col-span-4 flex flex-col rounded-3xl border border-slate-100 bg-white p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                    <div class="mb-6">
                        <h3 class="text-base font-bold tracking-tight text-slate-900">Status Rental</h3>
                        <p class="text-xs font-medium text-slate-500 mt-1">Distribusi status transaksi saat ini.</p>
                    </div>
                    <div class="flex-1 flex items-center justify-center min-h-[320px]">
                        <DonutChart :labels="statusChart.labels" :values="statusChart.values" />
                    </div>
                </div>
                
            </section>

            <!-- Secondary Charts Section (12-column Grid: 8 for Volume, 4 for Top Products) -->
            <!-- Komentar: Mengubah rasio grid menjadi 8:4 agar chart volume transaksi terlihat luas dan elegan -->
            <section class="grid gap-6 xl:grid-cols-12">
                
                <!-- Volume Rental Chart (8 cols) -->
                <div class="xl:col-span-8 flex flex-col rounded-3xl border border-slate-100 bg-white p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold tracking-tight text-slate-900">Volume Transaksi</h3>
                            <p class="text-xs font-medium text-slate-500 mt-1">Jumlah penyewaan dalam 7 hari terakhir.</p>
                        </div>
                    </div>
                    <div class="flex-1 min-h-[320px]">
                        <!-- Komentar: Menggunakan ApexAreaChart dengan data reaktif (ref) -->
                        <ApexAreaChart :labels="volumeChartLabels" :values="volumeChartValues" title="Sewa" />
                    </div>
                </div>

                <!-- Top Products Chart (4 cols) -->
                <div class="xl:col-span-4 flex flex-col rounded-3xl border border-slate-100 bg-white p-7 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                    <div class="mb-6">
                        <h3 class="text-base font-bold tracking-tight text-slate-900">Produk Terlaris</h3>
                        <p class="text-xs font-medium text-slate-500 mt-1">5 produk paling banyak disewa sepanjang waktu.</p>
                    </div>
                    <div class="flex-1 min-h-[320px]">
                        <!-- Komentar: Menggunakan tampilan Elegant List -->
                        <TopProductsList :labels="topProductsChart.labels" :values="topProductsChart.values" title="Unit Disewa" />
                    </div>
                </div>
                
            </section>

            <!-- Komentar: Menambahkan Seksi Khusus untuk Live Fleet / Inventory Circulation Chart -->
            <section class="rounded-3xl border border-slate-100 bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div class="border-b border-slate-100/60 p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold tracking-tight text-slate-900">Sirkulasi Aset Fisik & Armada</h3>
                        <p class="text-xs font-medium text-slate-500 mt-1">Status real-time ketersediaan dan penggunaan inventaris berdasarkan kategori.</p>
                    </div>
                </div>
                <div class="p-6 h-[400px]">
                    <FleetStatusChart :labels="fleetStatusChart.labels" :series="fleetStatusChart.series" />
                </div>
            </section>

            <!-- Recent Transactions Table (Stripe/Linear Sleek Style) -->
            <section class="rounded-3xl border border-slate-100 bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div class="border-b border-slate-100/60 p-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold tracking-tight text-slate-900">Transaksi Terbaru</h3>
                        <p class="text-xs font-medium text-slate-500 mt-1">5 aktivitas penyewaan terakhir yang masuk ke sistem.</p>
                    </div>
                    <Link :href="route('admin.rentals.index')" class="text-[13px] font-semibold text-cyan-600 hover:text-cyan-500 transition-colors flex items-center gap-1">
                        Lihat Semua <span aria-hidden="true">&rarr;</span>
                    </Link>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-white text-[10px] font-bold uppercase tracking-widest text-slate-400 border-b border-slate-100/60">
                            <tr>
                                <th class="px-6 py-4">Kode Transaksi</th>
                                <th class="px-6 py-4">Nama Pelanggan</th>
                                <th class="px-6 py-4">Total Biaya</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-if="!recentTransactions || recentTransactions.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center text-slate-400 font-medium">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center">
                                            <ClipboardList class="h-6 w-6 text-slate-300" />
                                        </div>
                                        <span class="text-xs">Belum ada transaksi terbaru.</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else v-for="trx in recentTransactions" :key="trx.id" class="transition-colors hover:bg-slate-50/50 group">
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ trx.code }}</td>
                                <td class="px-6 py-4 text-[13px] font-medium">{{ trx.customer_name }}</td>
                                <td class="px-6 py-4 text-[13px] font-semibold text-slate-700">{{ formatCurrency(trx.total_price) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wide"
                                        :class="{
                                            'bg-emerald-50 text-emerald-600': trx.status === 'completed',
                                            'bg-amber-50 text-amber-600': trx.status === 'pending',
                                            'bg-cyan-50 text-cyan-600': trx.status === 'active',
                                            'bg-slate-50 text-slate-600': !['completed', 'pending', 'active'].includes(trx.status)
                                        }">
                                        {{ trx.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('admin.rentals.show', trx.id)" class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-[11px] font-bold text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-900 group-hover:text-cyan-600 group-hover:bg-cyan-50">
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    
</template>