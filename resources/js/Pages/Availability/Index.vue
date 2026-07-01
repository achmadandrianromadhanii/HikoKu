<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import ProductCard from '@/Components/product/ProductCard.vue'

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const search = ref(props.filters.search || '')

const submitSearch = () => {
    router.get(
        route('availability.index'),
        { search: search.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}
</script>

<template>

    <Head title="Cek Ketersediaan" />

    <DefaultLayout>
        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-primary-600">Cek Ketersediaan</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900">Lihat stok tanpa harus login</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Cari produk dan cek apakah stoknya masih tersedia untuk disewa.
                </p>
            </div>

            <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input v-model="search" type="text"
                        class="h-12 flex-1 rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                        placeholder="Cari nama produk..." @keyup.enter="submitSearch" />

                    <button type="button"
                        class="inline-flex h-12 items-center justify-center rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700"
                        @click="submitSearch">
                        Cari
                    </button>
                </div>
            </div>

            <div v-if="products.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <ProductCard v-for="product in products" :key="product.id" :product="product" />
            </div>

            <div v-else
                class="rounded-3xl border border-dashed border-gray-300 bg-white px-6 py-20 text-center shadow-sm">
                <h2 class="text-xl font-bold text-gray-900">Tidak ada produk ditemukan</h2>
                <p class="mt-2 text-sm text-gray-500">
                    Coba ubah kata kunci pencarian untuk melihat stok produk lain.
                </p>
            </div>
        </section>
    </DefaultLayout>
</template>