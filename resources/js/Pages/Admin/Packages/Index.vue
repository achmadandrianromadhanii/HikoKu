<script setup>
    import { computed, ref, watch, h } from 'vue';
    import { Link, router } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';

    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                { title: 'Packages', subtitle: 'Kelola paket bundling rental.' },
                () => page
            ),
    });
    import AppPagination from '@/Components/ui/AppPagination.vue';
    import ConfirmModal from '@/Components/ui/ConfirmModal.vue';
    import { Plus, Pencil, Trash2, Image as ImageIcon, Search, Star } from 'lucide-vue-next';

    const props = defineProps({
        packages: {
            type: Object,
            default: () => ({
                data: [],
                links: [],
            }),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const search = ref(props.filters.search || '');
    const activeOnly = ref(!!props.filters.active_only);

    const openDeleteModal = ref(false);
    const deleteLoading = ref(false);
    const selectedPackage = ref(null);

    const rows = computed(() => props.packages?.data || []);
    const paginationLinks = computed(() => props.packages?.links || []);

    // Auto-search dengan debounce murni (300ms) tanpa library eksternal agar super ringan
    const debounce = (fn, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn(...args), delay);
        };
    };

    const applyFilters = debounce(() => {
        router.get(
            route('admin.packages.index'),
            {
                search: search.value,
                active_only: activeOnly.value ? 1 : '',
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    }, 300);

    // Watcher untuk memicu auto-search jika input berubah
    watch([search, activeOnly], () => {
        applyFilters();
    });

    const openDelete = (pkg) => {
        selectedPackage.value = pkg;
        openDeleteModal.value = true;
    };

    const destroyPackage = () => {
        if (!selectedPackage.value) return;

        deleteLoading.value = true;

        router.delete(route('admin.packages.destroy', selectedPackage.value.id), {
            preserveScroll: true,
            onFinish: () => {
                deleteLoading.value = false;
                openDeleteModal.value = false;
                selectedPackage.value = null;
            },
        });
    };
</script>

<template>
    <!-- AdminLayout diubah menjadi persistent layout via defineOptions -->
    <!-- Komentar: Mengatur layout menjadi h-full agar scrollbar utama website hilang dan diganti dengan scrollbar pada tabel. -->
    <div class="flex flex-col h-full gap-4">
        <!-- Header Level Dewa -->
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Packages</h1>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    Koleksi paket bundling premium untuk user.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Link prefetch
                    :href="route('admin.packages.create')"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-cyan-600 to-blue-600 px-4 text-[12px] font-bold text-white shadow-md shadow-cyan-500/20 transition-all hover:scale-[1.02] hover:shadow-cyan-500/30"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Paket
                </Link>
            </div>
        </div>

        <!-- Toolbar Super Kompak -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100"
        >
            <!-- Search Input Mungil -->
            <div class="relative w-full max-w-xs">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <Search class="h-4 w-4 text-slate-500" />
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Ketik 1 huruf..."
                    class="h-9 w-full rounded-lg border-transparent bg-slate-50 pl-9 pr-3 text-[12px] outline-none transition-all placeholder:text-slate-500 focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                />
            </div>

            <!-- Toggle Aktif Saja -->
            <label class="group flex cursor-pointer items-center gap-2">
                <div
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                    :class="activeOnly ? 'bg-cyan-700' : 'bg-slate-200'"
                >
                    <input type="checkbox" v-model="activeOnly" class="sr-only" />
                    <span
                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                        :class="activeOnly ? 'translate-x-4' : 'translate-x-1'"
                    ></span>
                </div>
                <span
                    class="text-[11px] font-bold uppercase tracking-wider text-slate-500 transition-colors group-hover:text-slate-700"
                    >Aktif Saja</span
                >
            </label>
        </div>

        <!-- Tabel Data Level Dewa -->
        <div
            class="flex-1 min-h-0 flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100"
        >
            <div class="flex-1 min-h-0 overflow-auto custom-scrollbar relative z-10">
                <table class="w-full text-left text-[12px] text-slate-600 whitespace-nowrap">
                    <thead
                        class="sticky top-0 z-20 border-b border-slate-100 bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-500 shadow-[0_1px_2px_rgba(0,0,0,0.05)] after:absolute after:inset-x-0 after:bottom-0 after:border-b after:border-slate-100/60"
                    >
                        <tr>
                            <th class="px-4 py-3 font-semibold">Paket Bundling</th>
                            <th class="px-4 py-3 font-semibold">Isi Paket (Items)</th>
                            <th class="px-4 py-3 font-semibold text-right">Harga Sewa</th>
                            <th class="px-4 py-3 font-semibold text-center">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                Belum ada data paket bundling.
                            </td>
                        </tr>
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="group transition-colors hover:bg-slate-100/50 even:bg-slate-50/80"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3.5">
                                    <!-- 
                                          [BUG FIX FOTO SQUISHED/BLUR] 
                                          Menggunakan absolute inset-0 untuk mengatasi bug Flexbox di beberapa browser yang membuat gambar gepeng/distorsi.
                                          Ditambahkan shadow halus, ukuran h-12 w-12 agar lebih detail, dan animasi zoom saat baris di-hover.
                                        -->
                                    <div
                                        class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_2px_8px_-3px_rgba(0,0,0,0.1)]"
                                    >
                                        <img
                                            v-if="row.primary_image_url"
                                            :src="row.primary_image_url"
                                            :alt="row.name"
                                            class="absolute inset-0 h-full w-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-110"
                                        />
                                        <ImageIcon v-else class="h-5 w-5 text-slate-300" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="font-bold text-slate-900 transition-colors group-hover:text-cyan-700"
                                                >{{ row.name }}</span
                                            >
                                            <Star
                                                v-if="row.is_featured"
                                                class="h-3 w-3 fill-amber-400 text-amber-400"
                                            />
                                        </div>
                                        <span class="mt-0.5 block text-[10px] text-slate-500">{{
                                            row.slug
                                        }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex max-w-xs flex-wrap gap-1.5">
                                    <span
                                        v-for="item in row.items"
                                        :key="item.product_id"
                                        class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600"
                                    >
                                        {{ item.product?.name }}
                                        <span class="font-bold text-slate-500"
                                            >x{{ item.quantity }}</span
                                        >
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <span class="block font-bold text-cyan-700">{{
                                    row.price_label
                                }}</span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="
                                        row.is_active
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                >
                                    {{ row.is_active ? 'Aktif' : 'Draft' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <Link prefetch
                                        :href="route('admin.packages.edit', row.id)"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                    <button
                                        type="button"
                                        @click="openDelete(row)"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-600"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Super Rapi -->
        <div class="shrink-0 relative z-20">
            <AppPagination :links="paginationLinks" />
        </div>
    </div>

    <ConfirmModal
        :open="openDeleteModal"
        title="Hapus Paket"
        message="Yakin ingin menghapus paket bundling ini? Data tidak bisa dikembalikan."
        confirm-text="Ya, Hapus"
        :loading="deleteLoading"
        variant="danger"
        @close="openDeleteModal = false"
        @confirm="destroyPackage"
    />
</template>

<style scoped>
    /* Komentar: Kustomisasi scrollbar halus di dalam tabel agar terlihat elegan (Mac-like) */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
