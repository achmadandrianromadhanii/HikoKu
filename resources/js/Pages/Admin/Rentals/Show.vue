<script setup>
import { computed, ref, watch, h } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: (h, page) => h(AdminLayout, { title: 'Detail Rental', subtitle: 'Manajemen komprehensif untuk operasional rental, jaminan, dan pembayaran.' }, () => page) })
import AdminPageHeader from '@/Components/admin/AdminPageHeader.vue'
import AppBadge from '@/Components/ui/AppBadge.vue'
import ConfirmModal from '@/Components/ui/ConfirmModal.vue'
import AppModal from '@/Components/ui/AppModal.vue'

import {
    ArrowLeft,
    Check,
    Play,
    RotateCcw,
    User,
    Shield,
    ReceiptText,
    Wallet,
    CreditCard,
    Save,
    X,
    ScanLine
} from 'lucide-vue-next'
import { onMounted, onUnmounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import axios from 'axios'

const props = defineProps({
    rental: {
        type: Object,
        default: () => ({
            id: null,
            rental_code: '-',
            status: 'pending_payment',
            created_at: null,
            rental_start: null,
            rental_end: null,
            subtotal: 0,
            discount_amount: 0,
            late_fee: 0,
            grand_total: 0,
            user: null,
            items: [],
            guarantee: null,
            payment: null,
        }),
    },
})

const actionLoading = ref(false)
const openConfirmModal = ref(false)
const openActivateModal = ref(false)
const openReturnModal = ref(false)
const openLateFeeModal = ref(false)
const lateFeeAmount = ref(0)
const selectedRental = ref(null)

const uiStore = useUiStore()
const isScanning = ref(false)
const scanInput = ref('')

const handleManualScan = () => {
    const code = scanInput.value.trim()
    if (code.length > 3) {
        processScan(code)
    }
    scanInput.value = ''
}

// [UPDATE]: Otomatis memproses scan jika panjang karakter pas 18 dan diawali 'GH-'.
// Ini menyelesaikan masalah aplikasi Barcode to PC yang tidak mengirim tombol Enter otomatis.
watch(scanInput, (newVal) => {
    const code = newVal.trim()
    if (code.length === 18 && code.startsWith('GH-')) {
        handleManualScan()
    }
})

// --- Konfigurasi Scanner Barcode ---
let scanBuffer = ''
let scanTimeout = null

const handleKeydown = (e) => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return
    if (openLateFeeModal.value || openConfirmModal.value || openActivateModal.value || openReturnModal.value) return
    if (e.ctrlKey || e.metaKey || e.altKey) return

    if (e.key === 'Enter') {
        if (scanBuffer.length > 3) {
            processScan(scanBuffer)
        }
        scanBuffer = ''
        return
    }

    if (e.key.length === 1) {
        scanBuffer += e.key
        clearTimeout(scanTimeout)
        scanTimeout = setTimeout(() => { scanBuffer = '' }, 500)
    }
}

const processScan = async (code) => {
    // Validasi apakah kode yang discan cocok dengan rental ini
    if (code !== props.rental.rental_code) {
        uiStore.showToast({ title: 'Tiket Berbeda', message: `Barcode yang discan (${code}) bukan milik rental ini.`, type: 'warning' })
        return
    }

    isScanning.value = true
    try {
        const res = await axios.post(route('admin.rentals.scan'), { rental_code: code })
        const data = res.data
        
        if (data.action_status === 'activated') {
            uiStore.showToast({ title: 'Pesanan Masuk', message: 'Sukses! Status otomatis menjadi Aktif.', type: 'success' })
            router.reload({ only: ['rental'], preserveScroll: true })
        } else if (data.action_status === 'returned') {
            uiStore.showToast({ title: 'Berhasil Return', message: 'Stok barang telah dikembalikan otomatis.', type: 'success' })
            router.reload({ only: ['rental'], preserveScroll: true })
        } else if (data.action_status === 'late') {
            uiStore.showToast({ title: 'Perhatian!', message: 'Pesanan terlambat, harap proses denda tunai.', type: 'warning' })
            lateFeeAmount.value = data.late_fee
            openLateFeeModal.value = true
        }
    } catch (error) {
        if (error.response?.data?.message) {
            uiStore.showToast({ title: 'Gagal', message: error.response.data.message, type: 'error' })
        } else {
            uiStore.showToast({ title: 'Gagal', message: 'Terjadi kesalahan sistem.', type: 'error' })
        }
    } finally {
        isScanning.value = false
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
})
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
})

// [UPDATE]: State openGuaranteeModal dan guaranteeForm dihapus total 
// sesuai request untuk membuang kotak jaminan secara bersih.

const statusVariant = computed(() => {
    const value = props.rental.status

    if (value === 'pending_payment') return 'warning'
    if (value === 'confirmed') return 'info'
    if (value === 'active') return 'primary'
    if (value === 'returned') return 'success'
    if (value === 'cancelled' || value === 'overdue') return 'danger'
    return 'default'
})

const paymentVariant = computed(() => {
    const value = props.rental.payment?.status

    if (value === 'paid') return 'success'
    if (value === 'pending') return 'warning'
    if (value === 'refunded') return 'info'
    return 'danger'
})

const statusLabel = computed(() => {
    const map = {
        pending_payment: 'Pending Payment',
        confirmed: 'Confirmed',
        active: 'Active',
        returned: 'Returned',
        cancelled: 'Cancelled',
        overdue: 'Overdue',
    }

    return map[props.rental.status] || props.rental.status || '-'
})

// [UPDATE]: canManageGuarantee dihapus karena fitur jaminan ditiadakan.

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
        month: 'long',
        year: 'numeric',
    })
}

const formatDateTime = (value) => {
    if (!value) return '-'
    return new Date(value).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

// [UPDATE]: Fungsi openGuaranteeForm dan saveGuarantee dihapus secara permanen.

const confirmRental = () => {
    actionLoading.value = true

    router.post(
        route('admin.rentals.confirm', props.rental.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                actionLoading.value = false
                openConfirmModal.value = false
            },
        }
    )
}

const activateRental = () => {
    actionLoading.value = true

    router.post(
        route('admin.rentals.activate', props.rental.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                actionLoading.value = false
                openActivateModal.value = false
            },
        }
    )
}

const returnForm = useForm({
    items: props.rental.items.reduce((acc, item) => {
        acc[item.id] = {
            condition: 'good',
            missing_qty: 0,
            damaged_qty: 0,
            penalty: 0,
            notes: ''
        }
        return acc
    }, {})
})

const processReturn = () => {
    actionLoading.value = true

    returnForm.post(
        route('admin.rentals.return', props.rental.id),
        {
            preserveScroll: true,
            onFinish: () => {
                actionLoading.value = false
                if (!returnForm.hasErrors) {
                    openReturnModal.value = false
                }
            },
        }
    )
}
</script>

<template>
    <!-- Komentar: Menambahkan wrapper h-full overflow-y-auto agar halaman detail rental memiliki scrollbar internal sendiri. 
         Ini mencegah elemen di bagian bawah (seperti info transaksi) tertutup dan menjaga layout utama tetap fixed. -->
    <div class="h-full w-full overflow-y-auto custom-scrollbar pr-2 pb-6">
        <div class="space-y-6">
            <!-- Section Utama: Identitas & Badges -->
        <div class="animate-fade-up opacity-0 [animation-fill-mode:forwards]">
            <!-- [PENJELASAN FUNGSI]:
                 - Card ini diberi border sangat tipis dengan shadow-sm agar ringan.
                 - bg-gradient-to-r: Membuat garis tipis di atas card untuk aksen warna premium.
            -->
            <section class="relative overflow-hidden rounded-2xl bg-white border border-slate-200 p-5 shadow-sm">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-500 to-blue-600"></div>
                
                <!-- [UPDATE]: Tombol "Kembali" dihapus dari header dan diletakkan di sini
                     berupa tombol kotak ber-icon X merah di pojok kanan atas -->
                <Link :href="route('admin.rentals.index')"
                    class="absolute top-4 right-4 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition-all hover:bg-red-100 hover:scale-105"
                    title="Kembali">
                    <X class="h-4 w-4" />
                </Link>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between pr-10">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">{{ rental.rental_code }}</h2>
                            <!-- [UPDATE]: Memadatkan ukuran lencana (badge) agar lebih selaras dan elegan -->
                            <div class="flex gap-1.5">
                                <AppBadge :variant="statusVariant" class="scale-90 origin-left shadow-sm">{{ statusLabel }}</AppBadge>
                                <!-- [UPDATE]: Menyembunyikan badge payment jika status utamanya sudah Pending Payment 
                                     demi menghindari redudansi / dobel tulisan pending -->
                                <AppBadge v-if="rental.payment && !(rental.status === 'pending_payment' && rental.payment.status === 'pending')" 
                                    :variant="paymentVariant" class="scale-90 origin-left shadow-sm">
                                    Payment: {{ rental.payment.status || '-' }}
                                </AppBadge>
                            </div>
                        </div>
                        <p class="mt-1 text-[10px] font-medium text-slate-500">
                            Dibuat pada {{ formatDateTime(rental.created_at) }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- [PENJELASAN FUNGSI]:
             - grid-cols-[7fr_3fr]: Proporsi layar diubah menjadi 70% kiri dan 30% kanan di layar besar (lg).
             - Ini mencegah card menjadi terlalu lebar (melar) dan membuat layout rapi seperti dashboard modern.
        -->
        <div class="grid gap-6 lg:grid-cols-[7fr_3fr] items-start">
            
            <!-- Kolom Kiri (Data Rental & Timeline) -->
            <div class="space-y-6">
                
                <!-- [UPDATE]: "Informasi Penyewa" & "Item Rental" digabung menjadi satu kotak (Unified Block).
                     Jaminan dan Timeline ditiadakan 100% untuk UI/UX yang jauh lebih ringkas (versi 999++++++). 
                     Padding dikecilkan menjadi p-4, font size dikompres menjadi lebih tajam. -->
                <div class="animate-fade-up opacity-0 [animation-fill-mode:forwards] [animation-delay:200ms]">
                    <section class="rounded-xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-md hover:border-cyan-100 overflow-hidden">
                        
                        <div class="flex items-center gap-2 bg-slate-50/80 px-4 py-3 border-b border-slate-100">
                            <User class="h-3.5 w-3.5 text-cyan-600" />
                            <h3 class="text-[10px] font-extrabold uppercase tracking-widest text-slate-800">Detail Transaksi & Penyewa</h3>
                        </div>

                        <div class="p-4 grid gap-5 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
                            <!-- Data Penyewa -->
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="space-y-0.5">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Nama Lengkap</p>
                                    <p class="text-xs font-semibold text-slate-900">{{ rental.user?.name || '-' }}</p>
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Telepon</p>
                                    <p class="text-xs font-semibold text-slate-900">{{ rental.user?.phone || '-' }}</p>
                                </div>
                                <div class="space-y-0.5 sm:col-span-2">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Email</p>
                                    <p class="text-xs font-semibold text-slate-900">{{ rental.user?.email || '-' }}</p>
                                </div>
                                <div class="space-y-0.5 sm:col-span-2">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Periode Sewa</p>
                                    <p class="text-xs font-semibold text-slate-900">
                                        {{ formatDate(rental.rental_start) }} &mdash; {{ formatDate(rental.rental_end) }}
                                    </p>
                                </div>
                            </div>

                            <!-- List Item Rental -->
                            <div class="lg:pl-5 pt-5 lg:pt-0">
                                <div class="flex items-center gap-2 mb-3">
                                    <ReceiptText class="h-3.5 w-3.5 text-cyan-600" />
                                    <h4 class="text-[9px] font-bold uppercase tracking-wider text-slate-800">Item Disewa ({{ rental.items?.length || 0 }})</h4>
                                </div>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    <div v-for="item in rental.items || []" :key="item.id" class="flex justify-between items-center bg-slate-50 rounded-lg p-2.5 transition-colors hover:bg-slate-100">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[11px] font-semibold text-slate-900">{{ item.product_name || '-' }}</p>
                                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">
                                                QTY {{ item.quantity }} &bull; {{ formatCurrency(item.subtotal) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="!rental.items || rental.items.length === 0" class="py-2 text-center">
                                        <p class="text-[10px] text-slate-500">Belum ada item rental.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

            </div>

            <!-- Kolom Kanan (Sticky Panel) -->
            <!-- [PENJELASAN FUNGSI]:
                 - sticky top-6: Membuat panel kanan tetap terlihat (sticky) mengikuti arah scroll kebawah.
            -->
            <div class="space-y-6 sticky top-6">
                <!-- Action Buttons (Delay 300ms) -->
                <div class="animate-fade-up opacity-0 [animation-fill-mode:forwards] [animation-delay:300ms]">
                    <!-- [UPDATE]: Mengurangi padding (p-4) dan tinggi tombol (h-8) 
                         serta font-size untuk menghasilkan panel "Pusat Kendali" ultra-compact -->
                    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col gap-2 transition-all duration-300 hover:shadow-md hover:border-cyan-100 relative overflow-hidden">
                        <div v-if="isScanning" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
                            <ScanLine class="h-6 w-6 text-cyan-500 animate-spin mb-1" />
                            <span class="text-[10px] font-bold text-cyan-600">Memproses...</span>
                        </div>
                        <h3 class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Pusat Kendali</h3>
                        
                        <!-- [UPDATE]: Menjadikan Scanner UI selalu tampil di Pusat Kendali untuk semua status pesanan.
                             Jika status belum valid (misal: pending), sistem backend (ScannerController) yang akan menolaknya dan memberi notifikasi. -->
                        <div class="rounded-lg border-2 border-dashed border-cyan-300 bg-cyan-50/50 p-3 text-center transition hover:border-cyan-400 hover:bg-cyan-50 flex flex-col items-center gap-2">
                            <div class="h-8 w-8 rounded-full bg-cyan-100 flex items-center justify-center text-cyan-600 shadow-sm">
                                <ScanLine class="h-4 w-4" />
                            </div>
                            <div class="w-full">
                                <p class="text-[10px] font-bold text-cyan-800 mb-1">Scan Barcode Disini</p>
                                <input type="text" v-model="scanInput" @keyup.enter="handleManualScan" placeholder="Klik disini lalu Scan..." class="w-full h-8 text-[11px] font-medium text-center border-cyan-200 rounded-lg focus:border-cyan-500 focus:ring-cyan-500/20 placeholder:text-cyan-600/40 bg-white" autofocus />
                                <p class="text-[8px] font-medium text-cyan-600/80 mt-1">Jika scan tidak otomatis, tekan Enter</p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Ringkasan Pembayaran & Detail Payment (Delay 400ms) -->
                <div class="animate-fade-up opacity-0 [animation-fill-mode:forwards] [animation-delay:400ms]">
                    <!-- [UPDATE]: Memperkecil padding (p-4), mengecilkan font subtotal menjadi text-[11px] 
                         dan merapikan UI payment detail agar sangat efisien ruang. -->
                    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-md hover:border-cyan-100">
                        <div class="mb-3 flex items-center gap-1.5 border-b border-slate-100 pb-2.5">
                            <Wallet class="h-3.5 w-3.5 text-cyan-600" />
                            <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-800">Ringkasan Biaya</h3>
                        </div>

                        <div class="space-y-2 border-b border-slate-100 pb-3">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-500">Subtotal</span>
                                <span class="font-bold text-slate-900">{{ formatCurrency(rental.subtotal) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-500">Diskon</span>
                                <span class="font-bold text-slate-900">{{ formatCurrency(rental.discount_amount) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-500">Late Fee</span>
                                <span class="font-bold text-slate-900">{{ formatCurrency(rental.late_fee) }}</span>
                            </div>
                        </div>
                        
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-800">Grand Total</span>
                            <span class="text-lg font-extrabold text-cyan-700 drop-shadow-sm">{{ formatCurrency(rental.grand_total) }}</span>
                        </div>

                        <!-- Payment Detail if exists -->
                        <div v-if="rental.payment" class="mt-4 rounded-lg border border-slate-100 bg-slate-50/50 p-3 transition-all hover:bg-slate-50">
                            <div class="mb-2 flex items-center gap-1.5">
                                <CreditCard class="h-3 w-3 text-slate-400" />
                                <p class="text-[9px] font-bold uppercase tracking-wider text-slate-500">Info Transaksi</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="space-y-0.5">
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-400">Kode</p>
                                    <p class="text-[10px] font-bold text-slate-900 truncate">{{ rental.payment.payment_code || '-' }}</p>
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-400">Waktu</p>
                                    <!-- [UPDATE]: Menghilangkan kata 'Belum dibayar' dan 'Menunggu', cukup tampilkan '-' jika belum lunas agar rapi -->
                                    <p class="text-[10px] font-bold text-slate-900 truncate">
                                        {{ rental.payment.status === 'paid' ? formatDateTime(rental.payment.paid_at) : '-' }}
                                    </p>
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-400">Metode</p>
                                    <p class="text-[10px] font-bold text-slate-900 uppercase">
                                        {{ rental.payment.status === 'paid' ? rental.payment.payment_method : '-' }}
                                    </p>
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[8px] font-bold uppercase tracking-wider text-slate-400">Status</p>
                                    <p class="text-[10px] font-bold uppercase" :class="rental.payment.status === 'paid' ? 'text-emerald-600' : 'text-slate-900'">
                                        {{ rental.payment.status || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Modals -->


        <!-- Reusable Modal Confirmations -->
        <ConfirmModal :open="openLateFeeModal" title="⚠️ Keterlambatan Pengembalian" :message="`Pelanggan terlambat! Tagih denda sebesar Rp ${new Intl.NumberFormat('id-ID').format(lateFeeAmount)} tunai/transfer. Jika uang denda sudah diterima, klik konfirmasi untuk memproses Return.`" confirm-text="Denda Sudah Diterima & Proses Return" :loading="actionLoading" variant="danger" @close="openLateFeeModal = false" @confirm="processReturn" />

        <AppModal :open="openReturnModal" @close="openReturnModal = false" max-width="max-w-3xl">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-2">Proses Return Barang</h2>
                <p class="text-xs font-medium text-slate-500 mb-6">
                    Isi kondisi barang saat dikembalikan. Denda keterlambatan dihitung otomatis jika lewat batas waktu.
                </p>

                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                    <div v-for="item in rental.items" :key="item.id" class="p-4 border border-slate-200 rounded-2xl bg-white shadow-sm">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-sm font-bold text-slate-800">{{ item.product_name }}</h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600 px-2 py-1 rounded-md">QTY: {{ item.quantity }}</span>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Kondisi Barang</label>
                                <select v-model="returnForm.items[item.id].condition"
                                    class="h-9 w-full text-sm font-medium border-slate-200 rounded-xl focus:border-cyan-500 focus:ring-cyan-500/20">
                                    <option value="good">Semua Baik</option>
                                    <option value="damaged">Ada Kerusakan</option>
                                    <option value="missing">Ada Kehilangan</option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Denda (Rp)</label>
                                <input type="number" v-model="returnForm.items[item.id].penalty" min="0"
                                    class="h-9 w-full text-sm font-medium border-slate-200 rounded-xl focus:border-cyan-500 focus:ring-cyan-500/20" />
                            </div>

                            <div v-if="['missing'].includes(returnForm.items[item.id].condition)" class="space-y-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Jml Hilang</label>
                                <input type="number" v-model="returnForm.items[item.id].missing_qty" min="0" :max="item.quantity"
                                    class="h-9 w-full text-sm font-medium border-slate-200 rounded-xl focus:border-cyan-500 focus:ring-cyan-500/20" />
                            </div>

                            <div v-if="['damaged'].includes(returnForm.items[item.id].condition)" class="space-y-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Jml Rusak</label>
                                <input type="number" v-model="returnForm.items[item.id].damaged_qty" min="0" :max="item.quantity"
                                    class="h-9 w-full text-sm font-medium border-slate-200 rounded-xl focus:border-cyan-500 focus:ring-cyan-500/20" />
                            </div>

                            <div class="sm:col-span-2 space-y-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Catatan Detail</label>
                                <textarea v-model="returnForm.items[item.id].notes" rows="2"
                                    class="w-full text-sm font-medium border-slate-200 rounded-xl focus:border-cyan-500 focus:ring-cyan-500/20" placeholder="Keterangan opsional..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <button type="button" @click="openReturnModal = false"
                        class="inline-flex h-10 items-center rounded-xl bg-white border border-slate-200 px-4 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                        Batal
                    </button>
                    <button type="button" @click="processReturn" :disabled="returnForm.processing"
                        class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-5 text-xs font-bold text-white shadow-md shadow-emerald-500/20 transition hover:scale-[1.02] disabled:opacity-50 disabled:hover:scale-100">
                        <Check class="w-4 h-4" />
                        {{ returnForm.processing ? 'Memproses...' : 'Proses Return & Hitung Denda' }}
                    </button>
                </div>
            </div>
        </AppModal>    
        </div>
    </div>
</template>

<style scoped>
/* Animasi Khusus untuk fade-up staggered */
@keyframes fade-up {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-up {
    animation: fade-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>