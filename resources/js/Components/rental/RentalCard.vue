<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    rental: {
        type: Object,
        required: true,
    },
})

const statusMap = {
    pending_payment: {
        label: 'Menunggu Pembayaran',
        class: 'bg-amber-100 text-amber-700',
    },
    confirmed: {
        label: 'Dikonfirmasi',
        class: 'bg-sky-100 text-sky-700',
    },
    active: {
        label: 'Aktif',
        class: 'bg-primary-100 text-primary-700',
    },
    returned: {
        label: 'Selesai',
        class: 'bg-emerald-100 text-emerald-700',
    },
    cancelled: {
        label: 'Dibatalkan',
        class: 'bg-red-100 text-red-600',
    },
    overdue: {
        label: 'Terlambat',
        class: 'bg-red-100 text-red-700',
    },
}

const status = computed(() => {
    return statusMap[props.rental.status] || {
        label: props.rental.status,
        class: 'bg-surface-100 text-surface-700',
    }
})

const itemPreview = computed(() => {
    const items = props.rental.items || []
    return items.slice(0, 3)
})

const moreCount = computed(() => {
    const items = props.rental.items || []
    return Math.max(0, items.length - 3)
})

const canReorder = computed(() => {
    return ['returned', 'cancelled'].includes(props.rental.status)
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

const formatDate = (value) => {
    if (!value) return '-'
    return new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <!-- [UPDATE]: Mengubah keseluruhan struktur menjadi seperti Boarding Pass / Tiket Konser Fisik -->
    <!-- [UPDATE]: Membuang tombol detail manual dan menjadikan seluruh kartu sebagai area yang bisa di-klik dengan animasi hover mulus -->
    <Link :href="route('my-rentals.show', rental.rental_code)" class="group block relative overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-surface-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary-900/5 hover:ring-primary-200">
        <div class="flex flex-col sm:flex-row h-full">
            
            <!-- Sisi Kiri: Informasi Tiket -->
            <div class="flex-1 p-5 sm:p-6 flex flex-col justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest shadow-sm" :class="status.class">
                            {{ status.label }}
                        </span>
                        <span v-if="canReorder" class="text-[10px] font-bold text-surface-400 bg-surface-100 px-2 py-0.5 rounded-full border border-surface-200">Sewa Ulang Tersedia</span>
                    </div>
                    
                    <!-- [UPDATE]: Font Monospace untuk kode tiket agar terasa seperti kode digital profesional -->
                    <h3 class="text-xl font-mono font-bold text-surface-900 tracking-tight group-hover:text-primary-700 transition-colors">
                        {{ rental.rental_code }}
                    </h3>
                    <p class="mt-1 text-xs font-medium text-surface-500 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-surface-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        {{ formatDate(rental.rental_start) }} <span class="text-surface-300 mx-1">→</span> {{ formatDate(rental.rental_end) }}
                    </p>
                </div>

                <div class="mt-6">
                    <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-1.5">Item Disewa</p>
                    <p class="text-sm font-semibold text-surface-700 leading-snug">
                        {{ itemPreview.map(i => i.product_name).join(', ') }}
                        <span v-if="moreCount > 0" class="text-surface-400 font-medium italic"> +{{ moreCount }} lainnya</span>
                    </p>
                </div>
            </div>

            <!-- Garis Putus-putus (Perforasi) layaknya tiket fisik -->
            <div class="relative flex items-center justify-center sm:w-8 py-4 sm:py-0">
                <!-- Pembatas Mobile -->
                <div class="w-full h-px border-t-2 border-dashed border-surface-200 sm:hidden px-4 relative">
                    <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50 shadow-inner"></div>
                    <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50 shadow-inner"></div>
                </div>
                <!-- Pembatas Desktop -->
                <div class="hidden sm:block h-full w-px border-l-2 border-dashed border-surface-200 relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-6 h-6 rounded-full bg-slate-50 ring-1 ring-inset ring-surface-200/50 shadow-inner z-10"></div>
                    <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-6 h-6 rounded-full bg-slate-50 ring-1 ring-inset ring-surface-200/50 shadow-inner z-10"></div>
                </div>
            </div>

            <!-- Sisi Kanan: Harga & Aksi -->
            <div class="w-full sm:w-48 bg-surface-50/50 p-5 sm:p-6 flex flex-col justify-center items-center text-center sm:items-end sm:text-right border-t border-surface-100 sm:border-t-0">
                <div class="w-full flex justify-between sm:flex-col items-center sm:items-end gap-1">
                    <span class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Total Biaya</span>
                    <span class="text-lg font-extrabold text-primary-700">{{ formatCurrency(rental.grand_total) }}</span>
                </div>
                
                <!-- [UPDATE]: Tanda panah interaktif yang hanya muncul saat di hover, meniadakan tombol kaku -->
                <div class="mt-4 sm:mt-6 hidden sm:flex items-center justify-end text-primary-600 gap-1.5 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                    <span class="text-[10px] font-bold uppercase tracking-widest">Lihat Tiket</span>
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </div>
                
                <!-- Tombol mobile statis agar jelas bisa diklik -->
                <div class="mt-3 sm:hidden text-primary-600 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-1 w-full border border-primary-200 bg-primary-50 rounded-lg py-2">
                    Buka E-Ticket <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </div>
            </div>
            
        </div>
    </Link>
</template>
