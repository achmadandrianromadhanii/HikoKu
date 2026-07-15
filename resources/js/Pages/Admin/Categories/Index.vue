<script setup>
    import { computed, ref, watch } from 'vue';
    import { router, useForm, Link } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';

    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                {
                    title: 'Kelola Kategori',
                    subtitle: 'Atur kategori untuk pengelompokan katalog Anda.',
                },
                () => page
            ),
    });
    import AppModal from '@/Components/ui/AppModal.vue';
    import ConfirmModal from '@/Components/ui/ConfirmModal.vue';
    import {
        FolderKanban,
        Plus,
        Pencil,
        Trash2,
        Search,
        ChevronLeft,
        ChevronRight,
        Box,
    } from 'lucide-vue-next';

    const props = defineProps({
        categories: {
            type: Object,
            default: () => ({
                data: [],
                links: [],
                prev_page_url: null,
                next_page_url: null,
                current_page: 1,
                last_page: 1,
                total: 0,
                from: 0,
                to: 0,
            }),
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
                total_products: 0,
            }),
        },
    });

    const search = ref(props.filters.search || '');
    const activeOnly = ref(!!props.filters.active_only);

    const openCreateModal = ref(false);
    const openEditModal = ref(false);
    const openDeleteModal = ref(false);
    const selectedCategory = ref(null);
    const actionLoading = ref(false);

    const rows = computed(() => props.categories?.data || []);

    // Auto Search Debounce
    let searchTimeout;
    watch(
        [search, activeOnly],
        () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                router.get(
                    route('admin.categories.index'),
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
            }, 400);
        },
        { deep: true }
    );

    const createForm = useForm({
        name: '',
        description: '',
        icon: '',
        is_active: true,
        sort_order: 0,
    });

    const editForm = useForm({
        name: '',
        description: '',
        icon: '',
        is_active: true,
        sort_order: 0,
        _method: 'put',
    });

    // Komentar: State untuk fitur Progressive Disclosure (Sembunyi/Tampil animasi halus)
    const createAddDescription = ref(false);
    const editAddDescription = ref(false);

    // Komentar: Logika Auto-clear untuk membersihkan data jika toggle dimatikan pengguna
    watch(createAddDescription, (val) => {
        if (!val) createForm.description = '';
    });
    watch(editAddDescription, (val) => {
        if (!val) editForm.description = '';
    });

    const submitCreate = () => {
        createForm.post(route('admin.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                createForm.is_active = true;
                createForm.sort_order = 0;
                createAddDescription.value = false;
                openCreateModal.value = false;
            },
        });
    };

    const openEdit = (category) => {
        selectedCategory.value = category;
        editForm.name = category.name || '';
        editForm.description = category.description || '';
        editForm.icon = category.icon || '';
        editForm.is_active = !!category.is_active;
        editForm.sort_order = category.sort_order || 0;
        editAddDescription.value = !!category.description;
        openEditModal.value = true;
    };

    const submitEdit = () => {
        if (!selectedCategory.value) return;
        editForm.post(route('admin.categories.update', selectedCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                openEditModal.value = false;
                selectedCategory.value = null;
            },
        });
    };

    const openDelete = (category) => {
        selectedCategory.value = category;
        openDeleteModal.value = true;
    };

    const destroyCategory = () => {
        if (!selectedCategory.value) return;
        actionLoading.value = true;
        router.delete(route('admin.categories.destroy', selectedCategory.value.id), {
            preserveScroll: true,
            onFinish: () => {
                actionLoading.value = false;
                openDeleteModal.value = false;
                selectedCategory.value = null;
            },
        });
    };

    const getInitials = (name) => {
        if (!name) return 'CT';
        const words = name.split(' ');
        if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
        return (words[0][0] + words[1][0]).toUpperCase();
    };

    const getGradient = (id) => {
        const gradients = [
            'bg-gradient-to-br from-indigo-100 to-purple-100 text-indigo-500',
            'bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-500',
            'bg-gradient-to-br from-rose-100 to-orange-100 text-rose-500',
            'bg-gradient-to-br from-sky-100 to-blue-100 text-sky-500',
            'bg-gradient-to-br from-amber-100 to-yellow-100 text-amber-500',
        ];
        return gradients[id % gradients.length];
    };
</script>

<template>
    <!-- Persistent AdminLayout -->

    <!-- Komentar: Mengatur layout menjadi h-full agar scrollbar utama website hilang dan diganti dengan scrollbar pada tabel. -->
    <div class="flex flex-col h-full gap-4">
        <!-- Header & Inline Stats -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 text-sm font-medium text-slate-500">
                <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-100 rounded-full">
                    <FolderKanban class="w-4 h-4 text-slate-500" />
                    <span class="text-slate-700 font-bold">{{ stats.total }}</span> Total Kategori
                </div>
                <span class="text-slate-300">•</span>
                <span class="text-emerald-600 font-semibold">{{ stats.active }} Aktif</span>
            </div>

            <button
                @click="openCreateModal = true"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-cyan-500/30 transition-all hover:bg-cyan-700 hover:shadow-cyan-500/40"
            >
                <Plus class="h-4 w-4" stroke-width="3" />
                Tambah Kategori
            </button>
        </div>

        <!-- Sleek Grid Container -->
        <div
            class="flex-1 min-h-0 flex flex-col rounded-3xl border border-slate-100 bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden"
        >
            <!-- Ultra-Minimalist Search & Filter Bar -->
            <div
                class="shrink-0 border-b border-slate-100/60 p-4 bg-white/50 backdrop-blur-sm relative z-20"
            >
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Seamless Search Bar -->
                    <div class="relative w-full md:max-w-sm">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                        >
                            <Search class="h-4 w-4 text-slate-500" />
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            class="block w-full rounded-xl border-none bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-900 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 focus:outline-none transition-all"
                            placeholder="Cari nama kategori (Otomatis)..."
                        />
                    </div>

                    <!-- iOS Style Toggle for Active Only -->
                    <label
                        class="flex items-center justify-between md:justify-start gap-3 cursor-pointer group px-2 md:px-0"
                    >
                        <span
                            class="text-[12px] font-bold uppercase tracking-widest text-slate-500 group-hover:text-slate-700 transition-colors"
                            >Aktif Saja</span
                        >
                        <div
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                            :class="activeOnly ? 'bg-cyan-700' : 'bg-slate-200'"
                        >
                            <input type="checkbox" v-model="activeOnly" class="sr-only" />
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="activeOnly ? 'translate-x-4' : 'translate-x-1'"
                            ></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Table Area -->
            <div class="flex-1 min-h-0 overflow-auto custom-scrollbar relative z-10">
                <table class="w-full text-left text-sm text-slate-600 whitespace-nowrap">
                    <thead
                        class="sticky top-0 z-20 bg-white text-[10px] font-bold uppercase tracking-widest text-slate-500 shadow-[0_1px_2px_rgba(0,0,0,0.05)] after:absolute after:inset-x-0 after:bottom-0 after:border-b after:border-slate-100/60"
                    >
                        <tr>
                            <th class="px-6 py-4">Kategori</th>
                            <th class="px-6 py-4 text-center">Jumlah Produk</th>
                            <th class="px-6 py-4 text-center">Urutan</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50/50">
                        <!-- Empty State -->
                        <tr v-if="rows.length === 0">
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div
                                        class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center"
                                    >
                                        <FolderKanban class="w-8 h-8 text-slate-300" />
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-700">
                                        Tidak ada kategori
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        Coba sesuaikan kata kunci atau tambah kategori baru.
                                    </p>
                                </div>
                            </td>
                        </tr>

                        <!-- Data Rows (Zebra Striping) -->
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="transition-colors hover:bg-slate-100/50 group even:bg-slate-50/80"
                        >
                            <!-- Col 1: Icon & Name -->
                            <td class="px-6 py-3 min-w-[250px]">
                                <div class="flex items-center gap-4">
                                    <div class="relative shrink-0">
                                        <div
                                            :class="[
                                                'flex h-10 w-10 items-center justify-center rounded-[14px] text-[16px] shadow-sm border border-white/50',
                                                getGradient(row.id),
                                            ]"
                                        >
                                            <span v-if="row.icon && row.icon.length <= 2">{{
                                                row.icon
                                            }}</span>
                                            <span
                                                v-else
                                                class="text-[12px] font-black tracking-tight"
                                                >{{ getInitials(row.name) }}</span
                                            >
                                        </div>
                                        <!-- Green dot indicator if active -->
                                        <div
                                            v-if="row.is_active"
                                            class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-700 border-2 border-white rounded-full"
                                        ></div>
                                    </div>
                                    <div class="flex flex-col overflow-hidden">
                                        <span
                                            class="font-bold text-[13px] text-slate-900 truncate"
                                            >{{ row.name }}</span
                                        >
                                        <span
                                            class="text-[11px] font-medium text-slate-500 truncate mt-0.5"
                                            >{{ row.description || row.slug || '-' }}</span
                                        >
                                    </div>
                                </div>
                            </td>

                            <!-- Col 2: Products Count -->
                            <td class="px-6 py-3 text-center">
                                <span
                                    class="inline-flex items-center text-[12px] font-bold text-slate-600 bg-slate-50 px-3 py-1 rounded-full border border-slate-100"
                                >
                                    <Box class="w-3.5 h-3.5 mr-1.5 text-slate-500" />
                                    {{ row.products_count }}
                                </span>
                            </td>

                            <!-- Col 3: Sort Order -->
                            <td
                                class="px-6 py-3 text-center tabular-nums font-bold text-[13px] text-slate-500"
                            >
                                #{{ row.sort_order }}
                            </td>

                            <!-- Col 4: Status Badges -->
                            <td class="px-6 py-3 text-center">
                                <span
                                    v-if="row.is_active"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-600 border border-emerald-100/50"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-emerald-700 mr-1.5 animate-pulse"
                                    ></span>
                                    Aktif
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500"
                                >
                                    Nonaktif
                                </span>
                            </td>

                            <!-- Col 5: Actions (Hide on hover) -->
                            <td class="px-6 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-1 "
                                >
                                    <button
                                        @click="openEdit(row)"
                                        title="Edit"
                                        class="p-2 text-slate-500 hover:text-cyan-700 hover:bg-cyan-50 rounded-lg transition-colors"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="openDelete(row)"
                                        title="Hapus"
                                        class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Custom Minimalist Pagination -->
            <div
                v-if="categories.total > 0"
                class="shrink-0 border-t border-slate-100/60 p-4 flex items-center justify-between bg-white/30 relative z-20"
            >
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    Hal <span class="text-slate-700">{{ categories.current_page }}</span> /
                    {{ categories.last_page }}
                </div>

                <div class="flex items-center gap-2">
                    <Link prefetch
                        v-if="categories.prev_page_url"
                        :href="categories.prev_page_url"
                        preserve-scroll
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors"
                    >
                        <ChevronLeft class="w-4 h-4" stroke-width="2.5" />
                    </Link>
                    <div
                        v-else
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50/50 text-slate-300 cursor-not-allowed"
                    >
                        <ChevronLeft class="w-4 h-4" stroke-width="2.5" />
                    </div>

                    <Link prefetch
                        v-if="categories.next_page_url"
                        :href="categories.next_page_url"
                        preserve-scroll
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors"
                    >
                        <ChevronRight class="w-4 h-4" stroke-width="2.5" />
                    </Link>
                    <div
                        v-else
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50/50 text-slate-300 cursor-not-allowed"
                    >
                        <ChevronRight class="w-4 h-4" stroke-width="2.5" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Create/Edit/Delete -->
    <AppModal
        :open="openCreateModal"
        title="Tambah Kategori"
        max-width="max-w-md"
        @close="openCreateModal = false"
    >
        <div class="space-y-4 pt-2">
            <!-- Baris 1: Nama (8/12) dan Icon (4/12) -->
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-8">
                    <label
                        class="mb-1.5 block text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                        >Nama Kategori</label
                    >
                    <input
                        v-model="createForm.name"
                        type="text"
                        class="h-9 w-full rounded-lg border-transparent bg-slate-50 px-3 text-[12px] outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                    />
                    <p v-if="createForm.errors.name" class="mt-1 text-[10px] text-rose-500">
                        {{ createForm.errors.name }}
                    </p>
                </div>

                <div class="col-span-4">
                    <label
                        class="mb-1.5 block text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                        >Icon / Emoji</label
                    >
                    <input
                        v-model="createForm.icon"
                        type="text"
                        class="h-9 w-full rounded-lg border-transparent bg-slate-50 px-3 text-center text-[12px] outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                        placeholder="⛺, 🎒"
                    />
                    <p v-if="createForm.errors.icon" class="mt-1 text-[10px] text-rose-500">
                        {{ createForm.errors.icon }}
                    </p>
                </div>
            </div>

            <!-- Baris 2: Progressive Disclosure untuk Deskripsi -->
            <div>
                <label class="group mb-2 flex cursor-pointer items-center gap-2">
                    <div
                        class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors"
                        :class="createAddDescription ? 'bg-cyan-700' : 'bg-slate-200'"
                    >
                        <input type="checkbox" v-model="createAddDescription" class="sr-only" />
                        <span
                            class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                            :class="createAddDescription ? 'translate-x-3.5' : 'translate-x-0.5'"
                        ></span>
                    </div>
                    <span
                        class="text-[11px] font-semibold text-slate-500 transition-colors group-hover:text-slate-700"
                        >Tambahkan Deskripsi (Opsional)</span
                    >
                </label>

                <!-- Komentar: v-show memastikan form disembunyikan/ditampilkan tanpa menghapus dari DOM, memicu transisi jika disupport -->
                <div
                    v-show="createAddDescription"
                    class="overflow-hidden transition-all duration-300"
                >
                    <textarea
                        v-model="createForm.description"
                        rows="2"
                        placeholder="Tulis deskripsi singkat..."
                        class="w-full rounded-lg border-transparent bg-slate-50 px-3 py-2 text-[12px] outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                    ></textarea>
                    <p v-if="createForm.errors.description" class="mt-1 text-[10px] text-rose-500">
                        {{ createForm.errors.description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer: Toggle Status di Kiri, Tombol Aksi di Kanan -->
        <template #footer>
            <div class="mt-2 flex items-center justify-between">
                <label class="group flex cursor-pointer items-center gap-2">
                    <div
                        class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors"
                        :class="createForm.is_active ? 'bg-cyan-700' : 'bg-slate-200'"
                    >
                        <input type="checkbox" v-model="createForm.is_active" class="sr-only" />
                        <span
                            class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                            :class="createForm.is_active ? 'translate-x-3.5' : 'translate-x-0.5'"
                        ></span>
                    </div>
                    <span
                        class="text-[10px] font-bold uppercase tracking-widest text-slate-500 transition-colors group-hover:text-slate-700"
                        >Aktif</span
                    >
                </label>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openCreateModal = false"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-transparent px-3 text-[11px] font-bold text-slate-500 transition-all hover:border-slate-200 hover:bg-slate-50 hover:text-slate-600"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitCreate"
                        :disabled="createForm.processing"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-to-r from-cyan-600 to-blue-600 px-4 text-[12px] font-bold text-white shadow-md shadow-cyan-500/20 transition-all hover:scale-[1.02] hover:shadow-cyan-500/30 disabled:scale-100 disabled:opacity-60"
                    >
                        {{ createForm.processing ? 'Memproses...' : 'Simpan Kategori' }}
                    </button>
                </div>
            </div>
        </template>
    </AppModal>

    <AppModal
        :open="openEditModal"
        title="Edit Kategori"
        max-width="max-w-md"
        @close="openEditModal = false"
    >
        <div class="space-y-4 pt-2">
            <!-- Baris 1: Nama (8/12) dan Icon (4/12) -->
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-8">
                    <label
                        class="mb-1.5 block text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                        >Nama Kategori</label
                    >
                    <input
                        v-model="editForm.name"
                        type="text"
                        class="h-9 w-full rounded-lg border-transparent bg-slate-50 px-3 text-[12px] outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                    />
                    <p v-if="editForm.errors.name" class="mt-1 text-[10px] text-rose-500">
                        {{ editForm.errors.name }}
                    </p>
                </div>

                <div class="col-span-4">
                    <label
                        class="mb-1.5 block text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                        >Icon / Emoji</label
                    >
                    <input
                        v-model="editForm.icon"
                        type="text"
                        class="h-9 w-full rounded-lg border-transparent bg-slate-50 px-3 text-center text-[12px] outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                    />
                    <p v-if="editForm.errors.icon" class="mt-1 text-[10px] text-rose-500">
                        {{ editForm.errors.icon }}
                    </p>
                </div>
            </div>

            <!-- Baris 2: Progressive Disclosure untuk Deskripsi -->
            <div>
                <label class="group mb-2 flex cursor-pointer items-center gap-2">
                    <div
                        class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors"
                        :class="editAddDescription ? 'bg-cyan-700' : 'bg-slate-200'"
                    >
                        <input type="checkbox" v-model="editAddDescription" class="sr-only" />
                        <span
                            class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                            :class="editAddDescription ? 'translate-x-3.5' : 'translate-x-0.5'"
                        ></span>
                    </div>
                    <span
                        class="text-[11px] font-semibold text-slate-500 transition-colors group-hover:text-slate-700"
                        >Tambahkan Deskripsi (Opsional)</span
                    >
                </label>

                <!-- Komentar: v-show dipakai untuk menyembunyikan form text area yang memanjang agar terlihat super ringkas -->
                <div
                    v-show="editAddDescription"
                    class="overflow-hidden transition-all duration-300"
                >
                    <textarea
                        v-model="editForm.description"
                        rows="2"
                        placeholder="Tulis deskripsi singkat..."
                        class="w-full rounded-lg border-transparent bg-slate-50 px-3 py-2 text-[12px] outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20"
                    ></textarea>
                    <p v-if="editForm.errors.description" class="mt-1 text-[10px] text-rose-500">
                        {{ editForm.errors.description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer: Toggle Status di Kiri, Tombol Aksi di Kanan -->
        <template #footer>
            <div class="mt-2 flex items-center justify-between">
                <label class="group flex cursor-pointer items-center gap-2">
                    <div
                        class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors"
                        :class="editForm.is_active ? 'bg-cyan-700' : 'bg-slate-200'"
                    >
                        <input type="checkbox" v-model="editForm.is_active" class="sr-only" />
                        <span
                            class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                            :class="editForm.is_active ? 'translate-x-3.5' : 'translate-x-0.5'"
                        ></span>
                    </div>
                    <span
                        class="text-[10px] font-bold uppercase tracking-widest text-slate-500 transition-colors group-hover:text-slate-700"
                        >Aktif</span
                    >
                </label>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openEditModal = false"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-transparent px-3 text-[11px] font-bold text-slate-500 transition-all hover:border-slate-200 hover:bg-slate-50 hover:text-slate-600"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitEdit"
                        :disabled="editForm.processing"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-to-r from-cyan-600 to-blue-600 px-4 text-[12px] font-bold text-white shadow-md shadow-cyan-500/20 transition-all hover:scale-[1.02] hover:shadow-cyan-500/30 disabled:scale-100 disabled:opacity-60"
                    >
                        {{ editForm.processing ? 'Memproses...' : 'Update Kategori' }}
                    </button>
                </div>
            </div>
        </template>
    </AppModal>

    <ConfirmModal
        :open="openDeleteModal"
        title="Hapus Kategori"
        message="Yakin ingin menghapus kategori ini secara permanen?"
        confirm-text="Ya, Hapus"
        :loading="actionLoading"
        variant="danger"
        @close="openDeleteModal = false"
        @confirm="destroyCategory"
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
