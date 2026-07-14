<script setup>
    import { Head, Link, router, useForm } from '@inertiajs/vue3';
    import { computed, ref, onMounted, watch, nextTick } from 'vue';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import ProductCard from '@/Components/product/ProductCard.vue';
    import AppFooter from '@/Components/common/AppFooter.vue';
    import AppAutocomplete from '@/Components/common/AppAutocomplete.vue';
    import { PackageSearch, Filter } from 'lucide-vue-next';

    const props = defineProps({
        products: {
            type: Object,
            required: true,
        },
        categories: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        category: props.filters.category || '',
        search: props.filters.search || '',
        available_only: !!props.filters.available_only,
        condition: props.filters.condition || '',
        min_price: props.filters.min_price || '',
        max_price: props.filters.max_price || '',
        sort: props.filters.sort || 'latest',
    });

    const submitFilter = () => {
        router.get(route('catalog.index'), form.data(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const resetFilter = () => {
        form.category = '';
        form.search = '';
        form.available_only = false;
        form.condition = '';
        form.min_price = '';
        form.max_price = '';
        form.sort = 'latest';
        submitFilter();
    };

    const productData = computed(() => props.products?.data || []);

    const showMobileFilter = ref(false);
</script>

<template>
    <Head title="Katalog" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: DASHBOARD STYLE (hidden lg:flex)          -->
        <!-- ========================================================= -->
        <div
            class="flex flex-col lg:flex-row h-[calc(100vh-84px)] mx-auto w-full max-w-[1400px] px-4 py-4 sm:px-6 lg:px-8 gap-6 items-start"
        >
            <!-- [FITUR PREMIUM]: FIXED SIDEBAR 
                 Sidebar dikunci tinggi penuh dan tidak memiliki scrollbar mandiri (diam) -->
            <aside class="w-full lg:w-[280px] shrink-0 h-auto lg:h-full">
                <div
                    class="user-panel h-full overflow-hidden p-5 shadow-[0_4px_24px_rgba(0,0,0,0.02)] border-surface-100/50 bg-white/80 backdrop-blur-md"
                >
                    <div class="space-y-4">
                        <!-- [UPDATE]: Fitur pencarian "Cari Produk" dipindah ke Navbar, jadi dihapus dari sidebar ini. -->
                        <div>
                            <label
                                for="category_filter"
                                class="mb-1.5 block text-[13px] font-extrabold text-surface-900 tracking-wide uppercase"
                            >
                                Kategori
                            </label>
                            <select
                                id="category_filter"
                                v-model="form.category"
                                class="w-full rounded-xl border-transparent bg-surface-50 px-4 py-3 text-[14px] text-surface-700 outline-none transition focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                            >
                                <option value="">Semua kategori</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.slug"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <!-- [UPDATE]: Filter Kondisi dihapus agar tampilan lebih bersih. -->

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div>
                                <label
                                    for="min_price_filter"
                                    class="mb-1.5 block text-[13px] font-extrabold text-surface-900 tracking-wide uppercase"
                                >
                                    Harga Min
                                </label>
                                <input
                                    id="min_price_filter"
                                    v-model="form.min_price"
                                    type="number"
                                    placeholder="Rp 0"
                                    class="w-full rounded-xl border-transparent bg-surface-50 px-4 py-3 text-[14px] text-surface-700 outline-none transition focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                />
                            </div>

                            <div>
                                <label
                                    for="max_price_filter"
                                    class="mb-1.5 block text-[13px] font-extrabold text-surface-900 tracking-wide uppercase"
                                >
                                    Harga Max
                                </label>
                                <input
                                    id="max_price_filter"
                                    v-model="form.max_price"
                                    type="number"
                                    placeholder="Rp Max"
                                    class="w-full rounded-xl border-transparent bg-surface-50 px-4 py-3 text-[14px] text-surface-700 outline-none transition focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                />
                            </div>
                        </div>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-transparent bg-cyan-50/50 px-4 py-3 text-[13px] font-bold text-cyan-800 transition-colors hover:bg-cyan-50"
                        >
                            <input
                                v-model="form.available_only"
                                type="checkbox"
                                class="h-4 w-4 rounded border-cyan-300 text-cyan-700 focus:ring-cyan-500"
                            />
                            Tampilkan stok tersedia saja
                        </label>

                        <div class="grid gap-2.5 pt-2">
                            <!-- [ROMBAK TOMBOL]: Tinggi, lonjong, mewah -->
                            <button
                                type="button"
                                class="inline-flex h-11 w-full items-center justify-center rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 text-[13px] font-extrabold text-white shadow-lg shadow-cyan-500/20 transition-all hover:scale-105 hover:shadow-cyan-500/40"
                                @click="submitFilter"
                            >
                                Terapkan Filter
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-11 w-full items-center justify-center rounded-full border border-surface-200 bg-white text-[13px] font-extrabold text-surface-600 transition-all hover:border-surface-300 hover:bg-surface-50"
                                @click="resetFilter"
                            >
                                Reset Filter
                            </button>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- KONTEN KANAN: Berisi Top Filter (Diam) dan Grid Produk (Scroll) -->
            <div class="flex-1 flex flex-col h-auto lg:h-full w-full overflow-visible lg:overflow-hidden">
                <!-- TOP FILTER STATIS -->
                <div
                    class="shrink-0 mb-6 user-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between border-transparent shadow-sm bg-white/80 backdrop-blur-md"
                >
                    <p class="text-[13px] text-surface-600">
                        Menampilkan
                        <span class="font-extrabold text-surface-900">{{
                            products.from || 0
                        }}</span>
                        -
                        <span class="font-extrabold text-surface-900">{{ products.to || 0 }}</span>
                        dari
                        <span class="font-extrabold text-surface-900">{{
                            products.total || 0
                        }}</span>
                        produk
                    </p>

                    <div class="flex items-center gap-2">
                        <label for="sort_filter" class="text-[13px] font-extrabold text-surface-700">Urutkan</label>

                        <select
                            id="sort_filter"
                            v-model="form.sort"
                            class="h-10 cursor-pointer rounded-full border border-transparent bg-surface-50 px-4 text-[13px] font-medium text-surface-800 outline-none transition hover:bg-surface-100 focus:border-cyan-300 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                            @change="submitFilter"
                        >
                            <option value="latest">Terbaru</option>
                            <option value="price_low">Harga Terendah</option>
                            <option value="price_high">Harga Tertinggi</option>
                            <option value="rating">Rating Tertinggi</option>
                        </select>
                    </div>
                </div>

                <!-- AREA SCROLLABLE KHUSUS PRODUK DAN FOOTER -->
                <div class="flex-1 overflow-y-auto pr-2 pb-6 custom-scrollbar">
                    <div v-if="productData.length > 0">
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <ProductCard
                                v-for="product in productData"
                                :key="product.id"
                                :product="product"
                            />
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center rounded-[32px] border border-dashed border-surface-200 bg-surface-50 py-20 text-center shadow-sm"
                    >
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm mb-4"
                        >
                            <PackageSearch class="h-8 w-8 text-surface-300" />
                        </div>
                        <h3 class="text-lg font-extrabold text-surface-900">
                            Tidak ada produk ditemukan
                        </h3>
                        <p class="mt-2 text-[13px] text-surface-600 max-w-sm leading-relaxed">
                            Maaf, produk dengan kriteria filter yang Anda cari sedang kosong. Coba
                            hapus beberapa filter pencarian Anda.
                        </p>
                        <button
                            type="button"
                            class="mt-6 inline-flex h-11 items-center justify-center rounded-full bg-surface-900 px-6 text-[13px] font-extrabold text-white transition-all hover:bg-surface-800 hover:scale-105"
                            @click="resetFilter"
                        >
                            Reset Semua Filter
                        </button>
                    </div>

                    <!-- Paginasi jika ada (diletakkan di dalam area scroll) -->
                    <div
                        v-if="products.links?.length > 3"
                        class="flex flex-wrap items-center gap-2 pt-4"
                    >
                        <template v-for="(link, index) in products.links" :key="index">
                            <Link prefetch
                                v-if="link.url"
                                :href="link.url"
                                class="inline-flex min-w-[40px] items-center justify-center rounded-full border border-transparent px-3 py-2 text-[13px] font-bold transition-all hover:scale-105"
                                :class="
                                    link.active
                                        ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-md shadow-cyan-500/20'
                                        : 'bg-surface-100 text-surface-700 hover:bg-surface-200'
                                "
                                v-html="link.label"
                            />

                            <span
                                v-else
                                class="inline-flex min-w-[40px] items-center justify-center rounded-full border border-surface-100 bg-surface-50 px-3 py-2 text-[13px] text-surface-600"
                                v-html="link.label"
                            />
                        </template>
                    </div>

                    <!-- [INJEKSI FOOTER]: Footer diletakkan di paling bawah setelah produk -->
                    <div class="mt-12 -mx-2 -mb-6">
                        <!-- Margin negatif digunakan agar footer bisa merentang sedikit lebih lebar 
                             atau Anda bisa menggunakan mt-12 saja untuk tampilan boxed yang eksklusif -->
                        <div class="rounded-[2.5rem] overflow-hidden shadow-2xl">
                            <AppFooter />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>
