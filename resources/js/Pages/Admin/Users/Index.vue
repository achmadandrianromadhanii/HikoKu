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
        X,
        Copy,
        Check,
        MoreVertical,
        ShieldCheck,
        UserCircle,
        Phone,
        Mail,
        Lock,
        Unlock,
    } from 'lucide-vue-next';

    // --- Persistent Layout ---
    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                { title: 'Users', subtitle: 'Manajemen akun pengguna dan hak akses sistem.' },
                () => page
            ),
    });

    const props = defineProps({
        users: {
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
                total: 0,
                active: 0,
                inactive: 0,
                admins: 0,
                users: 0,
            }),
        },
    });

    // --- State Management ---
    const search = ref(props.filters.search || '');
    const role = ref(props.filters.role || '');
    const activeOnly = ref(props.filters.active_only || '');
    const isSearchFocused = ref(false);
    const searchInputRef = ref(null);
    const copiedText = ref(null);

    const selectedUser = ref(null);
    const openToggleModal = ref(false);
    const actionLoading = ref(false);

    const rows = computed(() => props.users?.data || []);
    const paginationLinks = computed(() => props.users?.links || []);

    // --- Segmented Control (Inline Tab Pills) ---
    const tabs = computed(() => [
        {
            value: '',
            label: 'Semua',
            count: props.stats.total,
            color: 'text-slate-600 bg-slate-100',
        },
        {
            value: 'user',
            label: 'User',
            count: props.stats.total - props.stats.admins,
            color: 'text-blue-600 bg-blue-50',
        },
        {
            value: 'admin',
            label: 'Admin',
            count: props.stats.admins,
            color: 'text-purple-600 bg-purple-50',
        },
    ]);

    // --- Utilities ---
    const getInitials = (name) => {
        if (!name) return 'U';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    const getAvatarColor = (name) => {
        if (!name) return 'bg-slate-100 text-slate-500';
        const colors = [
            'bg-rose-100 text-rose-600 border-rose-200',
            'bg-blue-100 text-blue-600 border-blue-200',
            'bg-emerald-100 text-emerald-600 border-emerald-200',
            'bg-amber-100 text-amber-600 border-amber-200',
            'bg-purple-100 text-purple-600 border-purple-200',
            'bg-cyan-100 text-cyan-600 border-cyan-200',
        ];
        const index = name.charCodeAt(0) % colors.length;
        return colors[index];
    };

    const copyToClipboard = async (text) => {
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            copiedText.value = text;
            setTimeout(() => {
                copiedText.value = null;
            }, 2000);
        } catch (err) {
            console.error('Failed to copy', err);
        }
    };

    // --- Live Search & Filtering ---
    let searchTimeout = null;
    watch(search, (val) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 300); // Debounce 300ms
    });

    const selectTab = (val) => {
        role.value = val;
        applyFilters();
    };

    const applyFilters = () => {
        router.get(
            route('admin.users.index'),
            { search: search.value, role: role.value, active_only: activeOnly.value },
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

    // --- Actions ---
    const openToggle = (user) => {
        selectedUser.value = user;
        openToggleModal.value = true;
    };

    const toggleActive = () => {
        if (!selectedUser.value) return;
        actionLoading.value = true;
        router.post(
            route('admin.users.toggle-active', selectedUser.value.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    actionLoading.value = false;
                    openToggleModal.value = false;
                    selectedUser.value = null;
                },
            }
        );
    };
</script>

<template>
    <div class="h-full flex flex-col space-y-4 sm:space-y-6">
        <AdminPageHeader
            title="Kelola Users"
            subtitle="Cari, filter, dan atur status akses akun pengguna dengan presisi."
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
                        role === tab.value
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

            <!-- Smart Auto-Suggest Search & Status Filter -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto">
                <!-- Status Switch Filter -->
                <div
                    class="flex items-center bg-slate-50 rounded-xl p-1 border border-slate-100 w-full sm:w-auto"
                >
                    <button
                        @click="
                            activeOnly = '';
                            applyFilters();
                        "
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all w-full sm:w-auto"
                        :class="
                            activeOnly === ''
                                ? 'bg-white shadow-sm text-slate-800'
                                : 'text-slate-500 hover:text-slate-700'
                        "
                    >
                        Semua
                    </button>
                    <button
                        @click="
                            activeOnly = '1';
                            applyFilters();
                        "
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all w-full sm:w-auto"
                        :class="
                            activeOnly === '1'
                                ? 'bg-white shadow-sm text-emerald-600'
                                : 'text-slate-500 hover:text-slate-700'
                        "
                    >
                        Aktif
                    </button>
                    <button
                        @click="
                            activeOnly = '0';
                            applyFilters();
                        "
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all w-full sm:w-auto"
                        :class="
                            activeOnly === '0'
                                ? 'bg-white shadow-sm text-rose-600'
                                : 'text-slate-500 hover:text-slate-700'
                        "
                    >
                        Suspend
                    </button>
                </div>

                <!-- Live Search -->
                <div class="relative w-full sm:w-64" ref="searchInputRef">
                    <div class="relative flex items-center">
                        <Search class="absolute left-3 h-4 w-4 text-slate-400" />
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
                            class="absolute right-2 p-1 rounded-full hover:bg-slate-200 text-slate-400 transition"
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
                                    class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider"
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
                                    class="flex items-center gap-3 p-2 hover:bg-slate-50 rounded-xl transition-colors cursor-pointer group"
                                    @click="openToggle(row)"
                                >
                                    <div
                                        class="h-8 w-8 rounded-full border flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                        :class="getAvatarColor(row.name)"
                                    >
                                        {{ getInitials(row.name) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-slate-900 truncate">
                                            {{ row.name }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 truncate">
                                            {{ row.email || '-' }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex items-center text-[10px] font-medium px-2 py-1 rounded"
                                        :class="
                                            row.is_active
                                                ? 'bg-emerald-50 text-emerald-600'
                                                : 'bg-rose-50 text-rose-600'
                                        "
                                    >
                                        {{ row.is_active ? 'Aktif' : 'Suspend' }}
                                    </div>
                                </div>
                            </div>
                            <div
                                class="p-2 border-t border-slate-50 text-center"
                                v-if="rows.length > 5"
                            >
                                <span class="text-[10px] text-slate-400"
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
                            <th class="px-4 py-3 pl-5">Profil (Identitas)</th>
                            <th class="px-4 py-3">Kontak & Keamanan</th>
                            <th class="px-4 py-3">Peran & Status</th>
                            <th class="px-4 py-3">Terdaftar Pada</th>
                            <th class="px-4 py-3 text-right pr-5">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50/80">
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="px-4 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center text-slate-400"
                                >
                                    <Search class="h-8 w-8 mb-3 opacity-20" />
                                    <p class="text-sm font-medium text-slate-600">
                                        Belum ada user ditemukan
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
                            :class="!row.is_active ? 'bg-rose-50/30' : ''"
                        >
                            <!-- Profil -->
                            <td class="px-4 py-2.5 pl-5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-9 w-9 rounded-full border flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                        :class="getAvatarColor(row.name)"
                                    >
                                        {{ getInitials(row.name) }}
                                    </div>
                                    <div
                                        class="min-w-0 flex flex-col justify-center group/copyemail"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <p
                                                class="font-bold text-slate-900 truncate"
                                                :class="!row.is_active ? 'text-slate-500' : ''"
                                            >
                                                {{ row.name || 'No Name' }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <Mail class="h-3 w-3 text-slate-400" />
                                            <p class="text-[10px] text-slate-500 truncate">
                                                {{ row.email || '-' }}
                                            </p>
                                            <button
                                                @click="copyToClipboard(row.email)"
                                                class="opacity-0 group-hover/copyemail:opacity-100 transition focus:opacity-100 text-slate-400 hover:text-blue-500"
                                                title="Copy Email"
                                            >
                                                <Check
                                                    v-if="copiedText === row.email"
                                                    class="h-3 w-3 text-emerald-500"
                                                />
                                                <Copy v-else class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kontak & Keamanan -->
                            <td class="px-4 py-2.5">
                                <div class="flex flex-col justify-center group/copyphone">
                                    <div class="flex items-center gap-1.5">
                                        <Phone class="h-3 w-3 text-slate-400" />
                                        <span class="font-medium text-slate-700">{{
                                            row.phone || 'Belum ada nomor'
                                        }}</span>
                                        <button
                                            v-if="row.phone"
                                            @click="copyToClipboard(row.phone)"
                                            class="opacity-0 group-hover/copyphone:opacity-100 transition focus:opacity-100 text-slate-400 hover:text-blue-500"
                                            title="Copy Phone"
                                        >
                                            <Check
                                                v-if="copiedText === row.phone"
                                                class="h-3 w-3 text-emerald-500"
                                            />
                                            <Copy v-else class="h-3 w-3" />
                                        </button>
                                    </div>
                                    <div class="mt-1">
                                        <AppBadge
                                            :variant="row.email_verified_at ? 'success' : 'warning'"
                                            class="scale-90 origin-left"
                                        >
                                            {{ row.email_verified_at ? 'Verified' : 'Unverified' }}
                                        </AppBadge>
                                    </div>
                                </div>
                            </td>

                            <!-- Peran & Status -->
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <AppBadge
                                        :variant="row.role === 'admin' ? 'primary' : 'default'"
                                        class="scale-90 origin-left uppercase tracking-wider"
                                    >
                                        {{ row.role || '-' }}
                                    </AppBadge>

                                    <div
                                        class="flex items-center gap-1.5"
                                        :class="
                                            row.is_active ? 'text-emerald-600' : 'text-rose-600'
                                        "
                                        title="Status Akun"
                                    >
                                        <span class="relative flex h-2 w-2">
                                            <span
                                                v-if="row.is_active"
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                            ></span>
                                            <span
                                                class="relative inline-flex rounded-full h-2 w-2"
                                                :class="
                                                    row.is_active ? 'bg-emerald-500' : 'bg-rose-500'
                                                "
                                            ></span>
                                        </span>
                                        <span class="text-[10px] font-semibold">{{
                                            row.is_active ? 'Aktif' : 'Suspend'
                                        }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Tanggal -->
                            <td class="px-4 py-2.5">
                                <span class="text-slate-500 text-[10px] font-medium">{{
                                    row.joined_at_label || '-'
                                }}</span>
                            </td>

                            <!-- Aksi (Hover & Faintly Visible) -->
                            <td class="px-4 py-2.5 text-right pr-5">
                                <div
                                    class="flex items-center justify-end opacity-30 group-hover:opacity-100 transition-opacity duration-200"
                                >
                                    <!-- Tombol iOS Style Toggle -->
                                    <button
                                        @click="openToggle(row)"
                                        class="h-7 px-3 rounded-lg border flex items-center justify-center transition gap-1.5 font-semibold"
                                        :class="
                                            row.is_active
                                                ? 'bg-white border-rose-200 text-rose-600 hover:bg-rose-50'
                                                : 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-600 hover:text-white'
                                        "
                                    >
                                        <Lock v-if="row.is_active" class="h-3.5 w-3.5" />
                                        <Unlock v-else class="h-3.5 w-3.5" />
                                        <span class="text-[10px]">{{
                                            row.is_active ? 'Suspend' : 'Aktifkan'
                                        }}</span>
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
            :open="openToggleModal"
            :title="selectedUser?.is_active ? 'Suspend Akun User' : 'Aktifkan Akun User'"
            :message="
                selectedUser?.is_active
                    ? 'Yakin ingin melakukan suspend (menonaktifkan) akun ini? User tidak akan bisa login ke dalam sistem.'
                    : 'Yakin ingin membuka gembok dan mengaktifkan akun ini kembali?'
            "
            :confirm-text="selectedUser?.is_active ? 'Ya, Suspend' : 'Ya, Aktifkan'"
            :loading="actionLoading"
            :variant="selectedUser?.is_active ? 'danger' : 'success'"
            @close="openToggleModal = false"
            @confirm="toggleActive"
        />
    </div>
</template>
