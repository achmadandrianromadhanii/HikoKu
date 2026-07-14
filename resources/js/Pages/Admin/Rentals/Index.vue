<script setup>
    import { computed, ref, watch, h, onMounted, onUnmounted } from 'vue';
    import { Link, router } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';
    import AdminPageHeader from '@/Components/admin/AdminPageHeader.vue';
    import AppBadge from '@/Components/ui/AppBadge.vue';
    import AppPagination from '@/Components/ui/AppPagination.vue';
    import ConfirmModal from '@/Components/ui/ConfirmModal.vue';
    import {
        Search,
        Check,
        RotateCcw,
        Eye,
        Calendar,
        ChevronDown,
        X,
        UserCircle2,
        AlertTriangle,
        ScanLine,
    } from 'lucide-vue-next';
    import { useUiStore } from '@/stores/ui';
    import axios from 'axios';

    // --- Persistent Layout ---
    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                {
                    title: 'Rentals',
                    subtitle: 'Pusat operasional rental: payment, jaminan, activate, dan return.',
                },
                () => page
            ),
    });

    const props = defineProps({
        rentals: {
            type: Object,
            default: () => ({ data: [], links: [] }),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        stats: {
            type: Object,
            default: () => ({
                all: 0,
                pending_payment: 0,
                confirmed: 0,
                active: 0,
                overdue: 0,
            }),
        },
    });

    // --- State Management ---
    const search = ref(props.filters.search || '');
    const status = ref(props.filters.status || '');
    const isSearchFocused = ref(false);
    const searchInputRef = ref(null);
    const barcodeInputRef = ref(null); // [UPDATE]: Ref untuk mengunci fokus kursor ke input scanner

    const rows = computed(() => props.rentals?.data || []);
    const paginationLinks = computed(() => props.rentals?.links || []);

    const openConfirmModal = ref(false);
    const openReturnModal = ref(false);
    const openLateFeeModal = ref(false);
    const openDisputedModal = ref(false);
    const lateFeeAmount = ref(0);
    const selectedRental = ref(null);
    const actionLoading = ref(false);

    const isScanning = ref(false);
    const scanInput = ref('');
    const uiStore = useUiStore();

    const handleManualScan = () => {
        const code = scanInput.value.trim();
        if (code.length > 3) {
            processScan(code);
        }
        scanInput.value = '';
    };

    // [UPDATE]: Sistem Deteksi Selesai Ketik (Native Debounce)
    // Kita menggunakan event @input native agar lebih responsif terhadap injeksi teks dari Barcode to PC.
    let typingTimer = null;
    const onScannerInput = (e) => {
        // Memastikan reactivity v-model tetap terupdate secara instan
        scanInput.value = e.target.value;

        const code = scanInput.value.trim();
        clearTimeout(typingTimer);

        if (code.startsWith('GH-') && code.length > 10) {
            typingTimer = setTimeout(() => {
                handleManualScan();
            }, 300); // 300ms adalah jeda ideal untuk scanner emulasi keyboard
        }
    };

    // --- Scanner Logic ---
    // [UPDATE]: Logika pencegat (handleKeydown) dihapus total. Aplikasi Barcode to PC
    // terkadang tidak memicu event standard JS (emulasi input).
    // Solusinya: Kita paksakan kursor (fokus) selalu berada di dalam kolom input Scanner.
    const processScan = async (code) => {
        isScanning.value = true; // Memicu animasi loading kecil (UX) tanpa memberatkan render UI utama
        try {
            const res = await axios.post(route('admin.rentals.scan'), { rental_code: code });
            const data = res.data;

            if (data.action_status === 'activated' || data.action_status === 'returned') {
                uiStore.showToast({ title: 'Berhasil!', message: data.message, type: 'success' });
                // [UPDATE]: Menggunakan preserveState dan preserveScroll agar halaman
                // di-reload secara mulus oleh Inertia tanpa perlu hard refresh.
                // Ini membuat status tabel berubah otomatis secara instan setelah scan.
                router.reload({ preserveState: true, preserveScroll: true });
            } else if (data.action_status === 'late') {
                uiStore.showToast({
                    title: 'Perhatian!',
                    message: 'Pesanan terlambat, harap proses denda tunai.',
                    type: 'warning',
                });
                selectedRental.value = data.rental;
                lateFeeAmount.value = data.late_fee;
                openLateFeeModal.value = true;
            }
        } catch (error) {
            if (error.response?.data?.message) {
                uiStore.showToast({
                    title: 'Tiket Ditolak',
                    message: error.response.data.message,
                    type: 'error',
                });
            } else {
                uiStore.showToast({
                    title: 'Gagal Scan',
                    message: 'Terjadi kesalahan jaringan/server saat memproses tiket.',
                    type: 'error',
                });
            }
        } finally {
            isScanning.value = false;
        }
    };

    // --- Segmented Control (Inline Tab Pills) ---
    const tabs = computed(() => [
        { value: '', label: 'Semua', count: props.stats.all || rows.value.length },
        {
            value: 'pending_payment',
            label: 'Pending',
            count: props.stats.pending_payment,
            color: 'text-amber-600 bg-amber-50',
        },
        {
            value: 'confirmed',
            label: 'Confirmed',
            count: props.stats.confirmed,
            color: 'text-blue-600 bg-blue-50',
        },
        {
            value: 'active',
            label: 'Active',
            count: props.stats.active,
            color: 'text-emerald-600 bg-emerald-50',
        },
        {
            value: 'overdue',
            label: 'Overdue',
            count: props.stats.overdue,
            color: 'text-red-600 bg-red-50',
        },
    ]);

    const statusVariant = (value) => {
        if (value === 'pending_payment') return 'warning';
        if (value === 'confirmed') return 'info';
        if (value === 'active') return 'primary';
        if (value === 'returned') return 'success';
        if (value === 'cancelled' || value === 'overdue') return 'danger';
        return 'default';
    };

    const paymentVariant = (value) => {
        if (value === 'paid') return 'success';
        if (value === 'pending') return 'warning';
        if (value === 'refunded') return 'info';
        return 'danger';
    };

    const statusLabel = (value) => {
        const map = {
            pending_payment: 'Pending Payment',
            confirmed: 'Confirmed',
            active: 'Active',
            returned: 'Returned',
            cancelled: 'Cancelled',
            overdue: 'Overdue',
        };
        return map[value] || value || '-';
    };

    // --- Live Search & Filtering ---
    let searchTimeout = null;
    watch(search, (val) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(
                route('admin.rentals.index'),
                { search: val, status: status.value },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }, 300); // Debounce 300ms
    });

    const selectTab = (val) => {
        status.value = val;
        router.get(
            route('admin.rentals.index'),
            { search: search.value, status: val },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const clearSearch = () => {
        search.value = '';
        isSearchFocused.value = false;
    };

    // Global click listener to close dropdown & refocus scanner
    const closeDropdown = (e) => {
        // 1. Tutup hasil pencarian jika klik di luar box search
        if (searchInputRef.value && !searchInputRef.value.contains(e.target)) {
            isSearchFocused.value = false;
        }

        // 2. [UPDATE KUNCI]: Auto-Focus Lock
        // Pastikan kursor selalu ditarik kembali ke kolom Scanner Barcode jika admin
        // mengklik area kosong (bukan tombol, link, atau form input lainnya).
        // Ini menjamin tembakan Barcode to PC SELALU masuk ke input text!
        const tag = e.target.tagName;
        const isInteractive =
            ['INPUT', 'TEXTAREA', 'BUTTON', 'A'].includes(tag) ||
            e.target.closest('button') ||
            e.target.closest('a');

        if (!isInteractive && barcodeInputRef.value) {
            barcodeInputRef.value.focus();
        }
    };

    onMounted(() => {
        document.addEventListener('click', closeDropdown);
        // Fokus otomatis pada saat halaman pertama kali dibuka
        if (barcodeInputRef.value) {
            barcodeInputRef.value.focus();
        }
    });
    onUnmounted(() => {
        document.removeEventListener('click', closeDropdown);
    });

    // --- Actions ---
    const openConfirm = (rental) => {
        selectedRental.value = rental;
        openConfirmModal.value = true;
    };
    const openReturn = (rental) => {
        selectedRental.value = rental;
        openReturnModal.value = true;
    };
    const openDisputed = (rental) => {
        selectedRental.value = rental;
        openDisputedModal.value = true;
    };

    const executeAction = (routeName, modalRef) => {
        if (!selectedRental.value) return;
        actionLoading.value = true;
        router.post(
            route(routeName, selectedRental.value.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    actionLoading.value = false;
                    modalRef.value = false;
                    selectedRental.value = null;
                },
            }
        );
    };
</script>

<template>
    <!-- [UPDATE]: Menyesuaikan layout menjadi flex-col dengan tinggi penuh (h-full) agar scrollbar hanya muncul di tabel -->
    <div class="h-full flex flex-col space-y-4 sm:space-y-6">
        <div class="flex justify-between items-center shrink-0">
            <AdminPageHeader
                title="Kelola Rentals"
                subtitle="Pusat operasional rental: E-Ticket, Scanner, dan Return."
            />

            <div class="flex flex-col items-end gap-1 w-full max-w-xs">
                <div class="relative w-full">
                    <ScanLine
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-cyan-700"
                    />
                    <!-- [UPDATE]: Mengganti @keyup.enter menjadi @keydown.enter.prevent karena aplikasi emulasi scanner sering gagal mengirimkan keyup.
                         Serta menggunakan @input murni untuk mendeteksi setiap huruf yang masuk. -->
                    <input
                        type="text"
                        ref="barcodeInputRef"
                        :value="scanInput"
                        @input="onScannerInput"
                        @keydown.enter.prevent="handleManualScan"
                        placeholder="Klik disini lalu Scan Barcode..."
                        class="w-full h-9 pl-9 pr-3 text-xs border-cyan-200 rounded-xl focus:border-cyan-500 focus:ring-cyan-500/20 bg-cyan-50/30 placeholder:text-cyan-700/50 text-cyan-900 font-semibold shadow-inner"
                    />
                </div>
                <div
                    v-if="isScanning"
                    class="flex items-center gap-1.5 text-xs font-semibold text-cyan-700 animate-pulse"
                >
                    <ScanLine class="h-3.5 w-3.5 animate-spin" />
                    Memproses...
                </div>
            </div>
        </div>

        <!-- Top Control Bar (Search & Segmented Tabs) -->
        <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-3 rounded-2xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] border border-slate-100 shrink-0"
        >
            <!-- Segmented Control (Tabs) -->
            <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl overflow-x-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    @click="selectTab(tab.value)"
                    class="relative flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 outline-none whitespace-nowrap"
                    :class="[
                        status === tab.value
                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/50'
                            : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100',
                    ]"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        v-if="tab.count !== undefined"
                        class="px-1.5 py-0.5 rounded-md text-[10px] font-bold"
                        :class="tab.color || 'bg-slate-100 text-slate-600'"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- Smart Auto-Suggest Search -->
            <div class="relative w-full sm:w-64" ref="searchInputRef">
                <div class="relative flex items-center">
                    <Search class="absolute left-3 h-4 w-4 text-slate-500" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Ketik 1 huruf untuk mencari..."
                        @focus="isSearchFocused = true"
                        class="w-full h-9 pl-9 pr-8 text-xs bg-slate-50 border-slate-200 rounded-xl focus:border-blue-400 focus:ring-4 focus:ring-blue-400/10 transition-all outline-none"
                    />

                    <button
                        v-if="search"
                        @click="clearSearch"
                        class="absolute right-2 p-1 rounded-full hover:bg-slate-200 text-slate-500 transition"
                    >
                        <X class="h-3 w-3" />
                    </button>
                </div>

                <!-- Floating Pop-up Dropdown (Live Search Results) -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div
                        v-if="isSearchFocused && search.length > 0"
                        class="absolute top-full mt-2 w-full sm:w-80 right-0 bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-100 z-50 overflow-hidden"
                    >
                        <div class="px-3 py-2 border-b border-slate-50 bg-slate-50/50">
                            <span
                                class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider"
                                >Hasil Pencarian Cepat</span
                            >
                        </div>
                        <div class="max-h-64 overflow-y-auto p-1">
                            <div
                                v-if="rows.length === 0"
                                class="p-4 text-center text-xs text-slate-500"
                            >
                                Tidak ada hasil untuk "{{ search }}"
                            </div>
                            <Link prefetch
                                v-for="row in rows.slice(0, 5)"
                                :key="row.id"
                                :href="route('admin.rentals.show', row.id)"
                                class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition-colors group"
                            >
                                <div
                                    class="h-8 w-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0"
                                >
                                    <Search class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-900 truncate">
                                        {{ row.rental_code }}
                                    </p>
                                    <p class="text-[10px] text-slate-500 truncate">
                                        {{ row.user_name || '-' }}
                                    </p>
                                </div>
                                <ChevronDown
                                    class="h-4 w-4 text-slate-300 -rotate-90 group-hover:text-blue-500 transition-colors"
                                />
                            </Link>
                        </div>
                        <div
                            class="p-2 border-t border-slate-50 text-center"
                            v-if="rows.length > 5"
                        >
                            <span class="text-[10px] text-slate-500"
                                >Tekan Enter untuk melihat semua {{ rows.length }} hasil.</span
                            >
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Ultra-Compact Data Table -->
        <!-- [UPDATE]: Container tabel dibuat flex-1 dan min-h-0 agar mengambil sisa ruang layar dan membatasi ukuran -->
        <div
            class="bg-white rounded-2xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] border border-slate-100 flex flex-col min-h-0 flex-1"
        >
            <!-- [UPDATE]: Area scrollbar difokuskan di dalam tabel dengan animasi custom-scrollbar -->
            <div class="flex-1 overflow-auto custom-scrollbar">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead
                        class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-medium"
                    >
                        <tr>
                            <th class="px-4 py-3 pl-5">Penyewa (Customer)</th>
                            <th class="px-4 py-3">Rental Info</th>
                            <th class="px-4 py-3">Durasi</th>
                            <th class="px-4 py-3">Keuangan & Status</th>
                            <!-- 
                                [UPDATE]: Menambahkan nama kolom 'Aksi' sesuai permintaan agar terlihat jelas fungsinya 
                            -->
                            <th class="px-4 py-3 text-right pr-5">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50/80">
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="px-4 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center text-slate-500"
                                >
                                    <Search class="h-8 w-8 mb-3 opacity-20" />
                                    <p class="text-sm font-medium text-slate-600">
                                        Belum ada rental ditemukan
                                    </p>
                                    <p class="text-xs mt-1">
                                        Coba sesuaikan filter atau kata kunci pencarian Anda.
                                    </p>
                                </div>
                            </td>
                        </tr>

                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="group hover:bg-slate-100/50 even:bg-slate-50/80 transition-colors"
                        >
                            <!-- Customer Info -->
                            <td class="px-4 py-2.5 pl-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-8 w-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 flex-shrink-0 overflow-hidden"
                                    >
                                        <UserCircle2 class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900 truncate">
                                            {{ row.user_name || 'No Name' }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 truncate mt-0.5">
                                            {{ row.user_email || '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Rental Info -->
                            <td class="px-4 py-2.5">
                                <Link prefetch
                                    :href="route('admin.rentals.show', row.id)"
                                    class="inline-flex font-bold text-blue-600 hover:text-blue-800 transition hover:underline"
                                >
                                    {{ row.rental_code }}
                                </Link>
                                <p
                                    class="text-[10px] text-slate-500 mt-0.5 max-w-[150px] truncate"
                                    title="Data Produk/Paket bisa ditambahkan disini"
                                >
                                    {{
                                        row.items_count
                                            ? `${row.items_count} Items`
                                            : 'Paket Rental'
                                    }}
                                </p>
                            </td>

                            <!-- Timeline -->
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-1.5 text-slate-600">
                                    <Calendar class="h-3.5 w-3.5 text-slate-500" />
                                    <span>{{ row.period_label || '-' }}</span>
                                </div>
                            </td>

                            <!-- Keuangan & Status -->
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">{{
                                        row.grand_total_label || '-'
                                    }}</span>
                                    <div class="h-3 w-px bg-slate-200 mx-1"></div>
                                    <!-- 
                                        [UPDATE]: Menambahkan kondisi v-if agar badge payment 'pending' tidak dobel 
                                        dengan status rental 'pending_payment'. Hanya tampilkan satu saja yang relevan.
                                    -->
                                    <AppBadge
                                        v-if="
                                            !(
                                                row.status === 'pending_payment' &&
                                                row.payment_status === 'pending'
                                            )
                                        "
                                        :variant="paymentVariant(row.payment_status)"
                                        class="scale-90 origin-left"
                                    >
                                        {{ row.payment_status || '-' }}
                                    </AppBadge>
                                    <AppBadge
                                        :variant="statusVariant(row.status)"
                                        class="scale-90 origin-left"
                                    >
                                        {{ statusLabel(row.status) }}
                                    </AppBadge>
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="px-4 py-2.5 text-right pr-5">
                                <!-- 
                                    [UPDATE]: Menghapus class 'opacity-0 group-hover:opacity-100' agar 
                                    tombol aksi selalu terlihat jelas tanpa harus dihover terlebih dahulu.
                                -->
                                <div
                                    class="flex items-center justify-end gap-1.5 transition-opacity duration-200"
                                >
                                    <Link :href="route('admin.rentals.show', row.id)">
                                        <button
                                            class="h-7 w-7 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 flex items-center justify-center transition"
                                            title="Detail"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                        </button>
                                    </Link>

                                    <!-- [UPDATE]: Tombol Active manual dihapus sesuai permintaan.
                                         Aktivasi rental sekarang HANYA melalui Scan Barcode E-Ticket
                                         untuk keamanan dan konsistensi operasional. -->

                                    <button
                                        v-if="['active', 'overdue'].includes(row.status)"
                                        @click="openReturn(row)"
                                        class="h-7 w-7 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-amber-600 hover:border-amber-200 hover:bg-amber-50 flex items-center justify-center transition"
                                        title="Return Rental"
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" />
                                    </button>

                                    <button
                                        v-if="['active', 'overdue'].includes(row.status)"
                                        @click="openDisputed(row)"
                                        class="h-7 w-7 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-red-600 hover:border-red-200 hover:bg-red-50 flex items-center justify-center transition"
                                        title="Tandai Bermasalah (Rusak/Hilang)"
                                    >
                                        <AlertTriangle class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/50 p-2 shrink-0">
                <AppPagination :links="paginationLinks" />
            </div>
        </div>

        <!-- Modals -->
        <ConfirmModal
            :open="openConfirmModal"
            title="Konfirmasi Rental Manual"
            message="Gunakan ini hanya untuk alur manual / cash."
            confirm-text="Ya, Confirm"
            :loading="actionLoading"
            variant="primary"
            @close="openConfirmModal = false"
            @confirm="executeAction('admin.rentals.confirm', openConfirmModal)"
        />
        <ConfirmModal
            :open="openReturnModal"
            title="Proses Return"
            message="Pastikan semua barang sudah dicek fisiknya sebelum menyelesaikan return."
            confirm-text="Ya, Return"
            :loading="actionLoading"
            variant="success"
            @close="openReturnModal = false"
            @confirm="executeAction('admin.rentals.return', openReturnModal)"
        />

        <ConfirmModal
            :open="openDisputedModal"
            title="Tandai Bermasalah"
            message="Barang rusak atau cacat? Status akan menjadi Disputed dan KTP pelanggan harus Anda tahan sampai ada ganti rugi."
            confirm-text="Tandai Bermasalah"
            :loading="actionLoading"
            variant="danger"
            @close="openDisputedModal = false"
            @confirm="executeAction('admin.rentals.dispute', openDisputedModal)"
        />

        <ConfirmModal
            :open="openLateFeeModal"
            title="⚠️ Keterlambatan Pengembalian"
            :message="`Pelanggan terlambat! Tagih denda sebesar Rp ${new Intl.NumberFormat('id-ID').format(lateFeeAmount)} tunai/transfer. Jika uang denda sudah diterima, klik konfirmasi untuk memproses Return.`"
            confirm-text="Denda Sudah Diterima & Proses Return"
            :loading="actionLoading"
            variant="danger"
            @close="openLateFeeModal = false"
            @confirm="executeAction('admin.rentals.return', openLateFeeModal)"
        />
    </div>
</template>
