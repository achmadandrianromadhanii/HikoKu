<script setup>
    import { computed, ref, h } from 'vue';
    import { router, useForm } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';

    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                {
                    title: 'FAQs',
                    subtitle:
                        'Kelola pertanyaan umum untuk halaman user dengan tampilan rapi dan compact.',
                },
                () => page
            ),
    });
    import AdminPageHeader from '@/Components/admin/AdminPageHeader.vue';
    import AdminStatCard from '@/Components/admin/AdminStatCard.vue';
    import AdminTableToolbar from '@/Components/admin/AdminTableToolbar.vue';
    import DataTable from '@/Components/admin/DataTable.vue';
    import AdminIconButton from '@/Components/admin/AdminIconButton.vue';
    import AppBadge from '@/Components/ui/AppBadge.vue';
    import AppPagination from '@/Components/ui/AppPagination.vue';
    import AppModal from '@/Components/ui/AppModal.vue';
    import ConfirmModal from '@/Components/ui/ConfirmModal.vue';
    import {
        CircleHelp,
        CheckCircle2,
        XCircle,
        ListOrdered,
        Plus,
        Pencil,
        Trash2,
        Filter,
    } from 'lucide-vue-next';

    const props = defineProps({
        faqs: {
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
        stats: {
            type: Object,
            default: () => ({
                total: 0,
                active: 0,
                inactive: 0,
                top5: 0,
            }),
        },
    });

    const search = ref(props.filters.search || '');
    const activeOnly = ref(!!props.filters.active_only);

    const openCreateModal = ref(false);
    const openEditModal = ref(false);
    const openDeleteModal = ref(false);
    const selectedFaq = ref(null);
    const actionLoading = ref(false);

    const rows = computed(() => props.faqs?.data || []);
    const paginationLinks = computed(() => props.faqs?.links || []);

    const statCards = computed(() => [
        {
            label: 'Total FAQ',
            value: props.stats.total,
            icon: CircleHelp,
            variant: 'emerald',
        },
        {
            label: 'Aktif',
            value: props.stats.active,
            icon: CheckCircle2,
            variant: 'blue',
        },
        {
            label: 'Nonaktif',
            value: props.stats.inactive,
            icon: XCircle,
            variant: 'red',
        },
        {
            label: 'Top 5 Landing',
            value: props.stats.top5,
            icon: ListOrdered,
            variant: 'amber',
        },
    ]);

    const columns = [
        { key: 'question', label: 'Pertanyaan' },
        { key: 'answer', label: 'Jawaban' },
        { key: 'sort_order', label: 'Sort' },
        { key: 'status', label: 'Status' },
        { key: 'actions', label: 'Aksi' },
    ];

    const createForm = useForm({
        question: '',
        answer: '',
        is_active: true,
        sort_order: 0,
    });

    const editForm = useForm({
        question: '',
        answer: '',
        is_active: true,
        sort_order: 0,
        _method: 'put',
    });

    const applyFilters = () => {
        router.get(
            route('admin.faqs.index'),
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
    };

    const resetFilters = () => {
        search.value = '';
        activeOnly.value = false;
        applyFilters();
    };

    const submitCreate = () => {
        createForm.post(route('admin.faqs.store'), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                createForm.is_active = true;
                createForm.sort_order = 0;
                openCreateModal.value = false;
            },
        });
    };

    const openEdit = (faq) => {
        selectedFaq.value = faq;

        editForm.question = faq.question || '';
        editForm.answer = faq.answer || '';
        editForm.is_active = !!faq.is_active;
        editForm.sort_order = faq.sort_order || 0;

        openEditModal.value = true;
    };

    const submitEdit = () => {
        if (!selectedFaq.value) return;

        editForm.post(route('admin.faqs.update', selectedFaq.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                openEditModal.value = false;
                selectedFaq.value = null;
            },
        });
    };

    const openDelete = (faq) => {
        selectedFaq.value = faq;
        openDeleteModal.value = true;
    };

    const destroyFaq = () => {
        if (!selectedFaq.value) return;

        actionLoading.value = true;

        router.delete(route('admin.faqs.destroy', selectedFaq.value.id), {
            preserveScroll: true,
            onFinish: () => {
                actionLoading.value = false;
                openDeleteModal.value = false;
                selectedFaq.value = null;
            },
        });
    };

    const statusVariant = (row) => {
        return row.is_active ? 'success' : 'danger';
    };
</script>

<template>
    <!-- Persistent AdminLayout -->
    <div class="h-full flex flex-col space-y-4 sm:space-y-6">
        <AdminPageHeader
            title="Kelola FAQs"
            subtitle="Atur pertanyaan umum, jawaban, urutan tampil, dan status aktif."
        >
            <template #actions>
                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-2xl bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    @click="openCreateModal = true"
                >
                    <Plus class="h-4 w-4" />
                    Tambah
                </button>
            </template>
        </AdminPageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 shrink-0">
            <AdminStatCard
                v-for="card in statCards"
                :key="card.label"
                :label="card.label"
                :value="card.value"
                :icon="card.icon"
                :variant="card.variant"
            />
        </section>

        <AdminTableToolbar
            v-model:search="search"
            search-placeholder="Cari pertanyaan atau jawaban..."
            @apply="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
                <label
                    class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700"
                >
                    <input
                        v-model="activeOnly"
                        type="checkbox"
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        @change="applyFilters"
                    />
                    Aktif saja
                </label>
            </template>

            <template #right-actions>
                <button
                    type="button"
                    class="inline-flex h-11 items-center gap-2 rounded-2xl bg-emerald-700 px-5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    @click="applyFilters"
                >
                    <Filter class="h-4 w-4" />
                    Terapkan
                </button>
            </template>
        </AdminTableToolbar>

        <section class="flex-1 flex flex-col min-h-0 space-y-4">
            <DataTable
                :columns="columns"
                :rows="rows"
                empty-title="Belum ada FAQ"
                empty-description="FAQ publik akan tampil di halaman ini."
            >
                <template #cell-question="{ row }">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ row.question }}</p>
                    </div>
                </template>

                <template #cell-answer="{ row }">
                    <p class="line-clamp-2 max-w-[420px] text-sm text-slate-700">
                        {{ row.answer || '-' }}
                    </p>
                </template>

                <template #cell-status="{ row }">
                    <AppBadge :variant="statusVariant(row)">
                        {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                    </AppBadge>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex flex-wrap gap-2">
                        <AdminIconButton title="Edit FAQ" variant="blue" @click="openEdit(row)">
                            <Pencil class="h-4 w-4" />
                        </AdminIconButton>

                        <AdminIconButton title="Hapus FAQ" variant="red" @click="openDelete(row)">
                            <Trash2 class="h-4 w-4" />
                        </AdminIconButton>
                    </div>
                </template>
            </DataTable>

            <div class="shrink-0">
                <AppPagination :links="paginationLinks" />
            </div>
        </section>
    </div>

    <AppModal
        :open="openCreateModal"
        title="Tambah FAQ"
        max-width="max-w-3xl"
        @close="openCreateModal = false"
    >
        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-900">Pertanyaan</label>
                <input
                    v-model="createForm.question"
                    type="text"
                    class="h-11 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-100"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-900">Jawaban</label>
                <textarea
                    v-model="createForm.answer"
                    rows="6"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-100"
                />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900"
                        >Sort Order</label
                    >
                    <input
                        v-model="createForm.sort_order"
                        type="number"
                        min="0"
                        class="h-11 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-100"
                    />
                </div>

                <label
                    class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700 sm:mt-7"
                >
                    <input
                        v-model="createForm.is_active"
                        type="checkbox"
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    />
                    Aktif
                </label>
            </div>
        </div>

        <template #footer>
            <div class="flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex h-10 items-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:text-emerald-700"
                    @click="openCreateModal = false"
                >
                    Batal
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 items-center rounded-2xl bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    :disabled="createForm.processing"
                    @click="submitCreate"
                >
                    {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </template>
    </AppModal>

    <AppModal
        :open="openEditModal"
        title="Edit FAQ"
        max-width="max-w-3xl"
        @close="openEditModal = false"
    >
        <div class="space-y-5">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-900">Pertanyaan</label>
                <input
                    v-model="editForm.question"
                    type="text"
                    class="h-11 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-100"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-900">Jawaban</label>
                <textarea
                    v-model="editForm.answer"
                    rows="6"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-100"
                />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900"
                        >Sort Order</label
                    >
                    <input
                        v-model="editForm.sort_order"
                        type="number"
                        min="0"
                        class="h-11 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-100"
                    />
                </div>

                <label
                    class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700 sm:mt-7"
                >
                    <input
                        v-model="editForm.is_active"
                        type="checkbox"
                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    />
                    Aktif
                </label>
            </div>
        </div>

        <template #footer>
            <div class="flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex h-10 items-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:text-emerald-700"
                    @click="openEditModal = false"
                >
                    Batal
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 items-center rounded-2xl bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    :disabled="editForm.processing"
                    @click="submitEdit"
                >
                    {{ editForm.processing ? 'Menyimpan...' : 'Update' }}
                </button>
            </div>
        </template>
    </AppModal>

    <ConfirmModal
        :open="openDeleteModal"
        title="Hapus FAQ"
        message="Yakin ingin menghapus FAQ ini?"
        confirm-text="Ya, Hapus"
        :loading="actionLoading"
        variant="danger"
        @close="openDeleteModal = false"
        @confirm="destroyFaq"
    />
</template>
