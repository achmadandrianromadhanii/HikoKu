<script setup>
    import { computed, ref, watch, h, onMounted, onUnmounted } from 'vue';
    import { Link, router } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';
    import AdminPageHeader from '@/Components/admin/AdminPageHeader.vue';
    import AppBadge from '@/Components/ui/AppBadge.vue';
    import AppPagination from '@/Components/ui/AppPagination.vue';
    import {
        Search,
        Eye,
        ChevronDown,
        X,
        UserCircle2,
        Copy,
        Check,
        CreditCard,
        Wallet,
    } from 'lucide-vue-next';

    // --- Persistent Layout ---
    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                {
                    title: 'Payments',
                    subtitle: 'Monitoring pembayaran transaksi secara real-time.',
                },
                () => page
            ),
    });

    const props = defineProps({
        payments: {
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
                pending: 0,
                paid: 0,
                failed: 0,
                refunded: 0,
            }),
        },
    });

    // --- State Management ---
    const search = ref(props.filters.search || '');
    const status = ref(props.filters.status || '');
    const method = ref(props.filters.method || '');
    const isSearchFocused = ref(false);
    const searchInputRef = ref(null);
    const copiedCode = ref(null);

    const rows = computed(() => props.payments?.data || []);
    const paginationLinks = computed(() => props.payments?.links || []);

    // --- Segmented Control (Inline Tab Pills) ---
    const tabs = computed(() => [
        { value: '', label: 'Semua', count: props.stats.all || rows.value.length },
        {
            value: 'pending',
            label: 'Pending',
            count: props.stats.pending,
            color: 'text-amber-600 bg-amber-50',
        },
        {
            value: 'paid',
            label: 'Paid',
            count: props.stats.paid,
            color: 'text-emerald-600 bg-emerald-50',
        },
        {
            value: 'failed',
            label: 'Failed',
            count: props.stats.failed,
            color: 'text-red-600 bg-red-50',
        },
        {
            value: 'refunded',
            label: 'Refunded',
            count: props.stats.refunded,
            color: 'text-blue-600 bg-blue-50',
        },
    ]);

    const statusVariant = (value) => {
        if (value === 'paid') return 'success';
        if (value === 'pending') return 'warning';
        if (value === 'refunded') return 'info';
        return 'danger'; // failed, expired, cancelled
    };

    const getMethodIcon = (value) => {
        if (!value) return Wallet;
        const val = value.toLowerCase();
        if (
            val.includes('transfer') ||
            val.includes('bank') ||
            val.includes('bca') ||
            val.includes('mandiri')
        )
            return CreditCard;
        return Wallet;
    };

    // --- Live Search & Filtering ---
    let searchTimeout = null;
    watch(search, (val) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(
                route('admin.payments.index'),
                { search: val, status: status.value, method: method.value },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }, 300); // Debounce 300ms
    });

    const selectTab = (val) => {
        status.value = val;
        applyFilters();
    };

    const applyFilters = () => {
        router.get(
            route('admin.payments.index'),
            { search: search.value, status: status.value, method: method.value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const clearSearch = () => {
        search.value = '';
        isSearchFocused.value = false;
    };

    // Global click listener to close dropdown
    const closeDropdown = (e) => {
        if (searchInputRef.value && !searchInputRef.value.contains(e.target)) {
            isSearchFocused.value = false;
        }
    };
    onMounted(() => document.addEventListener('click', closeDropdown));
    onUnmounted(() => document.removeEventListener('click', closeDropdown));

    // --- Utilities ---
    const copyToClipboard = async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            copiedCode.value = text;
            setTimeout(() => {
                copiedCode.value = null;
            }, 2000);
        } catch (err) {
            console.error('Failed to copy', err);
        }
    };
</script>

<template>
    <div class="h-full flex flex-col space-y-4 sm:space-y-6">
        <AdminPageHeader
            title="Kelola Payments"
            subtitle="Monitoring arus kas dan detail pembayaran dari seluruh pengguna."
        />

        <!-- Top Control Bar (Search & Segmented Tabs) -->
        <div
            class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white p-3 rounded-2xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] border border-slate-100 shrink-0"
        >
            <!-- Segmented Control (Tabs) -->
            <div class="flex flex-wrap items-center gap-1 bg-slate-50 p-1 rounded-xl">
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

            <!-- Smart Auto-Suggest Search & Method Filter -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto">
                <!-- Method Dropdown -->
                <div class="relative w-full sm:w-auto">
                    <select
                        v-model="method"
                        @change="applyFilters"
                        class="w-full sm:w-40 h-9 pl-3 pr-8 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-400 focus:ring-4 focus:ring-blue-400/10 transition-all outline-none text-slate-700 cursor-pointer appearance-none"
                    >
                        <option value="">Semua Metode</option>
                        <option value="midtrans">Midtrans</option>
                        <option value="cash">Cash</option>
                        <option value="transfer_manual">Transfer Manual</option>
                    </select>
                    <ChevronDown
                        class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 pointer-events-none"
                    />
                </div>

                <!-- Live Search -->
                <div class="relative w-full sm:w-64" ref="searchInputRef">
                    <div class="relative flex items-center">
                        <Search class="absolute left-3 h-4 w-4 text-slate-500" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Ketik 1 huruf mencari..."
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
                                <div
                                    v-for="row in rows.slice(0, 5)"
                                    :key="row.id"
                                    class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition-colors group"
                                >
                                    <div
                                        class="h-8 w-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0"
                                    >
                                        <Wallet class="h-3.5 w-3.5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-slate-900 truncate">
                                            {{ row.payment_code }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 truncate">
                                            {{ row.user_name || '-' }}
                                        </p>
                                    </div>
                                </div>
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
        </div>

        <!-- Ultra-Compact Data Table -->
        <div
            class="bg-white rounded-2xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] border border-slate-100 flex flex-col min-h-0 flex-1"
        >
            <div class="flex-1 overflow-auto custom-scrollbar">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead
                        class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-medium"
                    >
                        <tr>
                            <th class="px-4 py-3 pl-5">Transaksi & Referensi</th>
                            <th class="px-4 py-3">Penyewa (Customer)</th>
                            <th class="px-4 py-3">Finansial & Metode</th>
                            <!-- 
                                [UPDATE]: Menghapus tag <th> untuk kolom aksi di bagian header, 
                                agar tabel lebih ringkas dan pengguna diarahkan ke halaman Admin Rentals 
                                jika ingin melihat detail lengkap.
                            -->
                            <th class="px-4 py-3 text-right pr-5">Waktu & Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50/80">
                        <tr v-if="rows.length === 0">
                            <td colspan="4" class="px-4 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center text-slate-500"
                                >
                                    <Search class="h-8 w-8 mb-3 opacity-20" />
                                    <p class="text-sm font-medium text-slate-600">
                                        Belum ada pembayaran ditemukan
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
                            <!-- Transaksi & Referensi -->
                            <td class="px-4 py-2.5 pl-5">
                                <div class="flex items-center gap-2 group/copy">
                                    <span class="font-bold text-slate-900 font-mono">{{
                                        row.payment_code || '-'
                                    }}</span>
                                    <button
                                        v-if="row.payment_code"
                                        @click="copyToClipboard(row.payment_code)"
                                        class="text-slate-300 hover:text-blue-500 transition opacity-0 group-hover/copy:opacity-100 focus:opacity-100"
                                        title="Copy Payment Code"
                                    >
                                        <Check
                                            v-if="copiedCode === row.payment_code"
                                            class="h-3 w-3 text-emerald-500"
                                        />
                                        <Copy v-else class="h-3 w-3" />
                                    </button>
                                </div>
                                <div class="mt-0.5">
                                    <!-- 
                                        [UPDATE]: Menghapus tag <Link> yang mengarah ke halaman detail rental, 
                                        menggantinya dengan teks biasa <span> agar detail rental 
                                        hanya bisa diakses melalui halaman Admin Rentals.
                                    -->
                                    <span class="text-[10px] text-slate-500">
                                        {{ row.rental_code || 'Rental' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Pembayar (Customer) -->
                            <td class="px-4 py-2.5">
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

                            <!-- Finansial & Metode -->
                            <td class="px-4 py-2.5">
                                <div class="flex flex-col justify-center">
                                    <div class="flex items-center gap-2 group/copy">
                                        <span
                                            class="font-bold text-slate-900 font-mono text-sm tracking-tight"
                                            >{{ row.amount_label || '-' }}</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1 text-slate-500">
                                        <component
                                            :is="getMethodIcon(row.method)"
                                            class="h-3.5 w-3.5 opacity-70"
                                        />
                                        <span
                                            class="text-[10px] font-medium uppercase tracking-wider"
                                            >{{ row.method || '-' }}</span
                                        >
                                    </div>
                                </div>
                            </td>

                            <!-- Waktu & Status -->
                            <td class="px-4 py-2.5 text-right pr-5">
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-[10px] text-slate-500">{{
                                        row.created_at_label || '-'
                                    }}</span>
                                    <AppBadge
                                        :variant="statusVariant(row.status)"
                                        class="scale-90 origin-right"
                                    >
                                        {{ row.status || '-' }}
                                    </AppBadge>
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
    </div>
</template>
