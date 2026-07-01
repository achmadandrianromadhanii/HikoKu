<!--
    ==========================================================================
    [KOMPONEN MOBILE]: MobileFilterSheet.vue
    ==========================================================================
    - FUNGSI: Bottom Sheet (laci dari bawah layar) untuk menampilkan opsi filter
      di halaman Katalog. Muncul saat tombol filter diklik.
    - TATA LETAK: Full-width di bagian bawah layar, max-height 75vh,
      dengan overlay gelap di belakangnya.
    - CARA KERJA:
      1. Props `show` mengontrol kapan sheet tampil/tersembunyi.
      2. Props `categories` menerima daftar kategori dari halaman Catalog.
      3. v-model dua arah untuk setiap field filter (category, sort, dll).
      4. Emit `apply` saat tombol Terapkan diklik, `reset` saat Reset diklik.
    - ANIMASI: Slide-up dari bawah (translateY) dengan Vue <transition>.
    ==========================================================================
-->
<script setup>
import { computed } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
    // Komentar: Menampilkan/menyembunyikan sheet
    show: { type: Boolean, default: false },
    // Komentar: Daftar kategori untuk filter chips
    categories: { type: Array, default: () => [] },
    // Komentar: Object form filter (category, sort, condition, dll)
    modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'close', 'apply', 'reset'])

// Komentar: Proxy reaktif untuk v-model dua arah pada form filter
const form = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

// Komentar: Update satu field di form filter
const updateField = (key, value) => {
    emit('update:modelValue', { ...props.modelValue, [key]: value })
}

// Komentar: Terapkan filter dan tutup sheet
const applyFilter = () => {
    emit('apply')
    emit('close')
}

// Komentar: Reset semua filter dan tutup sheet
const resetFilter = () => {
    emit('reset')
    emit('close')
}

// [UPDATE]: Fungsi Toggle untuk Kategori (agar bisa di-klik seperti Checkbox)
const toggleCategory = (catId) => {
    const current = String(props.modelValue.category || '')
    const target = String(catId)
    // Jika diklik lagi, uncheck (jadi string kosong), jika beda maka check
    updateField('category', current === target ? '' : target)
}

// Komentar: Opsi kondisi barang
const conditionOptions = [
    { label: 'Semua', value: '' },
    { label: 'Baru', value: 'new' },
    { label: 'Bekas', value: 'used' },
]

// Komentar: Opsi urutan
const sortOptions = [
    { label: 'Terbaru', value: 'latest' },
    { label: 'Harga Terendah', value: 'price_low' },
    { label: 'Harga Tertinggi', value: 'price_high' },
    { label: 'Terpopuler', value: 'popular' },
]
</script>

<template>
    <!-- Komentar: Overlay gelap (muncul saat sheet terbuka, klik untuk tutup) -->
    <Teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[60] bg-black/40"
                @click="$emit('close')"
            />
        </transition>

        <!-- Komentar: Sheet utama yang slide-up dari bawah -->
        <!-- [UPDATE]: Desain Dark Mode yang menyatu dengan tema aplikasi -->
        <transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="show"
                class="fixed inset-x-0 bottom-0 z-[70] max-h-[80vh] overflow-y-auto rounded-t-[32px] border-t border-white/10 bg-slate-900 shadow-2xl backdrop-blur-3xl"
            >
                <!-- Komentar: Handle bar (garis kecil untuk indikator geser) -->
                <div class="sticky top-0 z-10 flex justify-center bg-slate-900/90 pb-2 pt-3 backdrop-blur-md">
                    <div class="h-1.5 w-12 rounded-full bg-slate-700" />
                </div>

                <div class="px-5 pb-8 pt-2">

                    <!-- Komentar: Header sheet dengan judul dan tombol tutup -->
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="text-base font-extrabold text-white">Filter Produk</h3>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-slate-400 transition hover:text-white active:scale-90"
                            @click="$emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Komentar: Filter Kategori (Custom Checkbox Style) -->
                    <!-- [UPDATE]: Desain Checkbox Custom untuk Kategori sesuai permintaan -->
                    <div class="mb-6">
                        <label class="mb-3 block text-xs font-bold text-slate-400">Kategori Pilihan</label>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Checkbox per kategori -->
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                type="button"
                                class="flex items-center gap-3 rounded-2xl border px-3 py-2.5 transition-all active:scale-95"
                                :class="modelValue.category === String(cat.id)
                                    ? 'border-cyan-500 bg-cyan-500/10'
                                    : 'border-slate-700 bg-slate-800/50 hover:bg-slate-800'"
                                @click="toggleCategory(cat.id)"
                            >
                                <!-- Kotak Checkbox -->
                                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-[6px] border-2 transition-colors"
                                     :class="modelValue.category === String(cat.id) ? 'border-cyan-500 bg-cyan-500' : 'border-slate-600 bg-transparent'">
                                    <svg v-if="modelValue.category === String(cat.id)" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="text-xs font-medium" :class="modelValue.category === String(cat.id) ? 'text-cyan-400' : 'text-slate-300'">
                                    {{ cat.name }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Komentar: Filter Harga (Min - Max) -->
                    <div class="mb-6">
                        <label class="mb-3 block text-xs font-bold text-slate-400">Rentang Harga</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                :value="modelValue.min_price"
                                @input="updateField('min_price', $event.target.value)"
                                placeholder="Min"
                                class="h-11 w-full rounded-2xl border border-slate-700 bg-slate-800 px-4 text-sm text-slate-200 transition focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                            />
                            <span class="text-xs font-bold text-slate-500">—</span>
                            <input
                                type="number"
                                :value="modelValue.max_price"
                                @input="updateField('max_price', $event.target.value)"
                                placeholder="Max"
                                class="h-11 w-full rounded-2xl border border-slate-700 bg-slate-800 px-4 text-sm text-slate-200 transition focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                            />
                        </div>
                    </div>

                    <!-- [UPDATE]: Filter Ketersediaan dengan Toggle Switch bergaya iOS/Android -->
                    <div class="mb-6 flex items-center justify-between rounded-2xl border border-slate-700 bg-slate-800/50 p-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-200">Hanya Stok Tersedia</span>
                            <span class="text-[10px] text-slate-500">Sembunyikan produk yang sedang habis</span>
                        </div>
                        <button 
                            type="button"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="modelValue.in_stock ? 'bg-cyan-500' : 'bg-slate-600'"
                            @click="updateField('in_stock', !modelValue.in_stock)"
                        >
                            <span
                                aria-hidden="true"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="modelValue.in_stock ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>
                    </div>

                    <!-- Komentar: Filter Kondisi (Pill UI untuk dark mode) -->
                    <div class="mb-6">
                        <label class="mb-3 block text-xs font-bold text-slate-400">Kondisi Barang</label>
                        <div class="flex gap-2">
                            <button
                                v-for="opt in conditionOptions"
                                :key="opt.value"
                                type="button"
                                class="rounded-full border px-4 py-2 text-xs font-medium transition active:scale-95"
                                :class="modelValue.condition === opt.value
                                    ? 'border-cyan-500 bg-cyan-500/10 text-cyan-400'
                                    : 'border-slate-700 bg-slate-800 text-slate-400 hover:text-slate-300'"
                                @click="updateField('condition', opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Komentar: Filter Urutkan -->
                    <div class="mb-8">
                        <label class="mb-3 block text-xs font-bold text-slate-400">Urutkan Berdasarkan</label>
                        <select
                            :value="modelValue.sort"
                            @change="updateField('sort', $event.target.value)"
                            class="h-11 w-full rounded-2xl border border-slate-700 bg-slate-800 px-4 text-sm text-slate-200 transition focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500"
                        >
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value" class="bg-slate-800 text-slate-200">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Komentar: Tombol aksi (Terapkan + Reset) -->
                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="flex-1 rounded-2xl bg-cyan-500 py-3.5 text-sm font-extrabold text-slate-900 shadow-[0_0_16px_rgba(6,182,212,0.4)] transition active:scale-[0.97]"
                            @click="applyFilter"
                        >
                            Terapkan Filter
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl border border-slate-700 bg-slate-800 px-6 py-3.5 text-sm font-bold text-slate-300 transition active:scale-[0.97] hover:bg-slate-700"
                            @click="resetFilter"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<style scoped>
/* Komentar: Menyembunyikan scrollbar horizontal pada container chips kategori */
.scrollbar-none::-webkit-scrollbar { display: none; }
.scrollbar-none { scrollbar-width: none; }
</style>
