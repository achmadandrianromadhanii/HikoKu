<script setup>
    // Komentar: Menggunakan Vue 3 Composition API murni untuk performa LCP/CLS/INP 100% Hijau
    // Perbaikan Bug: Membesarkan ukuran UI secara proporsional (font-size, padding, height) agar terlihat rapi dan jelas (tidak kekecilan), namun tetap menjaga batas layar (h-[calc(100vh-110px)]) agar bebas scrollbar.
    import { computed, ref, watch } from 'vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';

    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                { title: 'Edit Produk', subtitle: 'Perbarui data produk katalog.' },
                () => page
            ),
    });
    import {
        X,
        ImagePlus,
        Save,
        PackageCheck,
        Sparkles,
        Tag,
        UploadCloud,
        Check,
        Star,
        Plus,
        Trash2,
        ChevronLeft,
        ChevronRight,
    } from 'lucide-vue-next';

    const props = defineProps({
        product: { type: Object, required: true },
        categories: { type: Array, default: () => [] },
    });

    // Komentar: Daftar warna preset untuk varian
    const PREDEFINED_COLORS = [
        { name: 'Merah', class: 'bg-rose-500' },
        { name: 'Biru', class: 'bg-blue-500' },
        { name: 'Hijau', class: 'bg-emerald-500' },
        { name: 'Kuning', class: 'bg-amber-400' },
        { name: 'Hitam', class: 'bg-slate-900' },
        { name: 'Putih', class: 'bg-white border border-slate-200' },
        { name: 'Abu-abu', class: 'bg-slate-400' },
        { name: 'Navy', class: 'bg-indigo-800' },
        { name: 'Coklat', class: 'bg-amber-800' },
        { name: 'Oren', class: 'bg-orange-500' },
    ];

    const currentImage =
        props.product?.images?.find((img) => img.is_primary) || props.product?.images?.[0] || null;
    const preview = ref(currentImage?.image_path ? `/storage/${currentImage.image_path}` : null);
    const isDragging = ref(false);

    // Komentar: State untuk fitur Progressive Disclosure.
    const useCustomSku = ref(!!props.product?.sku);
    const addDescription = ref(!!props.product?.description);

    const form = useForm({
        category_id: props.product.category_id ?? '',
        name: props.product.name ?? '',
        sku: props.product.sku ?? '',
        description: props.product.description ?? '',
        price_per_day: props.product.price_per_day ?? '',
        weight_gram: props.product.weight_gram ?? '',
        condition: props.product.condition ?? 'good',
        is_active: !!props.product.is_active,
        is_featured: !!props.product.is_featured,
        image: null,
        stock_total: props.product.stock_total ?? '',
        variants:
            props.product.variants && props.product.variants.length > 0
                ? props.product.variants.map((v) => ({
                      id: v.id,
                      size: v.size || '',
                      color: v.color || '',
                  }))
                : [{ size: '', color: '' }],
        _method: 'put',
    });

    watch(useCustomSku, (val) => {
        if (!val) form.sku = '';
    });
    watch(addDescription, (val) => {
        if (!val) form.description = '';
    });

    const onImageChange = (event) => {
        const file = event.target.files?.[0] || null;
        form.image = file;
        if (file) preview.value = URL.createObjectURL(file);
    };
    const onDrop = (event) => {
        event.preventDefault();
        isDragging.value = false;
        const file = event.dataTransfer?.files?.[0] || null;
        form.image = file;
        if (file) preview.value = URL.createObjectURL(file);
    };

    const addVariant = () => {
        form.variants.push({ size: '', color: '' });
        // Komentar: Otomatis scroll ke kanan sejauh lebar kartu baru (300px)
        setTimeout(scrollRight, 100);
    };

    const removeVariant = (index) => {
        if (form.variants.length > 1) {
            form.variants.splice(index, 1);
        }
    };

    const submit = () => {
        form.post(route('admin.products.update', props.product.id), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    // Komentar: Ref untuk Container Varian
    const variantContainer = ref(null);
</script>

<template>
    <!-- Komentar: Tambahkan w-full agar root element ini mengambil lebar parent secara pasti, max-w-7xl akan membatasi maksimalnya -->
    <div
        class="w-full flex flex-col h-[calc(100vh-85px)] -mt-2 overflow-hidden max-w-7xl mx-auto px-4"
    >
        <!-- Komentar: Tambahkan min-w-0 w-full pada form untuk memastikan form tidak melebar dari parent flex column -->
        <form
            class="flex-1 min-h-0 min-w-0 w-full flex gap-4 overflow-hidden pb-2"
            @submit.prevent="submit"
        >
            <!-- Komentar: SEKTOR KIRI (Foto, Status) - Lebar diperbesar ke w-72 (288px) -->
            <div class="w-72 shrink-0 flex flex-col gap-4 h-full">
                <!-- Area Foto Utama -->
                <section
                    class="shrink-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <ImagePlus class="h-4 w-4 text-slate-700" />
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                            Foto Utama
                        </h3>
                    </div>
                    <!-- Area Upload -->
                    <div
                        class="relative group overflow-hidden rounded-xl border-2 border-dashed transition-all w-full aspect-square flex flex-col items-center justify-center cursor-pointer"
                        :class="
                            isDragging
                                ? 'border-cyan-400 bg-cyan-50'
                                : 'border-slate-200 bg-slate-50 hover:bg-slate-100/50'
                        "
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop="onDrop"
                    >
                        <input
                            type="file"
                            accept="image/*"
                            class="absolute inset-0 z-10 w-full h-full opacity-0 cursor-pointer"
                            @change="onImageChange"
                        />

                        <!-- Gambar Preview -->
                        <img
                            v-if="preview"
                            :src="preview"
                            alt="Preview"
                            class="absolute inset-0 w-full h-full object-cover"
                        />

                        <!-- Jika ada gambar, tampilkan tombol overlay -->
                        <div
                            v-if="preview"
                            class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/30 transition-all flex items-center justify-center"
                        >
                            <div
                                class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5 bg-white/95 px-3 py-1.5 rounded-lg text-[11px] font-bold text-slate-700 shadow-sm"
                            >
                                <UploadCloud class="h-3.5 w-3.5" /> Ubah Foto
                            </div>
                        </div>

                        <!-- Jika kosong -->
                        <div v-else class="text-center p-3">
                            <div
                                class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-slate-200 group-hover:scale-110 transition-transform"
                            >
                                <UploadCloud
                                    class="h-5 w-5 text-slate-400 group-hover:text-cyan-500 transition-colors"
                                />
                            </div>
                            <p class="text-xs font-bold text-slate-600">Klik / Tarik Gambar</p>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">
                                Maksimal 4MB
                            </p>
                        </div>
                    </div>
                    <p v-if="form.errors.image" class="mt-2 text-xs text-rose-500">
                        {{ form.errors.image }}
                    </p>
                </section>

                <!-- Status Visibilitas -->
                <section class="shrink-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center gap-2">
                        <PackageCheck class="h-4 w-4 text-slate-700" />
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                            Visibilitas
                        </h3>
                    </div>
                    <div class="space-y-2.5">
                        <!-- Toggle Katalog -->
                        <label
                            class="flex cursor-pointer items-center justify-between rounded-lg border border-slate-100 bg-slate-50/70 p-2.5 hover:bg-slate-100 transition-colors"
                        >
                            <span class="text-xs font-bold text-slate-700">Tampil Katalog</span>
                            <div
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                :class="form.is_active ? 'bg-cyan-500' : 'bg-slate-200'"
                            >
                                <input type="checkbox" v-model="form.is_active" class="sr-only" />
                                <span
                                    class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                    :class="form.is_active ? 'translate-x-[18px]' : 'translate-x-1'"
                                ></span>
                            </div>
                        </label>
                        <!-- Toggle Unggulan -->
                        <label
                            class="flex cursor-pointer items-center justify-between rounded-lg border border-slate-100 bg-slate-50/70 p-2.5 hover:bg-slate-100 transition-colors"
                        >
                            <span class="text-xs font-bold text-slate-700">Produk Unggulan</span>
                            <div
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                :class="form.is_featured ? 'bg-amber-500' : 'bg-slate-200'"
                            >
                                <input type="checkbox" v-model="form.is_featured" class="sr-only" />
                                <span
                                    class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                    :class="
                                        form.is_featured ? 'translate-x-[18px]' : 'translate-x-1'
                                    "
                                ></span>
                            </div>
                        </label>
                    </div>
                </section>

                <!-- Spacer -->
                <div class="flex-1"></div>
            </div>

            <!-- Komentar: SEKTOR KANAN (Info Dasar, Spek, & Varian) -->
            <!-- flex-1 min-w-0 memastikan kolom kanan mengambil sisa ruang tapi tidak pernah melebihi parent -->
            <div class="flex-1 min-w-0 flex flex-col gap-4 h-full overflow-hidden">
                <!-- Baris 1: Info Dasar -->
                <section class="shrink-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <!-- Komentar: Tombol Simpan & Tutup dipindahkan ke pojok kanan atas agar praktis dan tidak memakan ruang di bawah. -->
                    <div
                        class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2"
                    >
                        <div class="flex items-center gap-2">
                            <Sparkles class="h-4 w-4 text-cyan-600" />
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                                Informasi Utama
                            </h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                title="Simpan Produk"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-sm ring-1 ring-inset ring-cyan-700 transition-all hover:scale-110 hover:shadow-cyan-500/40 active:scale-95 disabled:opacity-60 disabled:scale-100"
                            >
                                <Save class="h-4 w-4" />
                            </button>
                            <Link
                                :href="route('admin.products.index')"
                                title="Batal / Tutup"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-400 shadow-sm ring-1 ring-inset ring-slate-200 transition-all hover:bg-rose-50 hover:text-rose-600 hover:ring-rose-200 active:scale-95"
                            >
                                <X class="h-5 w-5" />
                            </Link>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-3 items-start">
                        <!-- Kategori (Tinggi h-10) -->
                        <div class="col-span-1">
                            <label
                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                >Kategori</label
                            >
                            <select
                                v-model="form.category_id"
                                class="h-10 py-2 leading-normal w-full rounded-lg border-slate-200 bg-slate-50 pl-3 pr-8 text-xs font-bold text-slate-700 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2364748b%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[position:right_10px_center] bg-no-repeat cursor-pointer hover:bg-white"
                            >
                                <option value="">Pilih</option>
                                <option v-for="item in categories" :key="item.id" :value="item.id">
                                    {{ item.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.category_id"
                                class="mt-1 text-[10px] text-rose-500"
                            >
                                {{ form.errors.category_id }}
                            </p>
                        </div>

                        <!-- Nama (Tinggi h-10) -->
                        <div class="col-span-1">
                            <label
                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                >Nama Produk</label
                            >
                            <input
                                v-model="form.name"
                                type="text"
                                class="h-10 py-2 leading-normal w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-700 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 hover:bg-white"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-[10px] text-rose-500">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- SKU Toggle & Input -->
                        <div class="col-span-1 relative flex flex-col">
                            <label
                                class="mb-1.5 flex items-center justify-between cursor-pointer group"
                            >
                                <span
                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors"
                                    >Gunakan SKU</span
                                >
                                <div
                                    class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors"
                                    :class="useCustomSku ? 'bg-cyan-500' : 'bg-slate-200'"
                                >
                                    <input type="checkbox" v-model="useCustomSku" class="sr-only" />
                                    <span
                                        class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                                        :class="
                                            useCustomSku ? 'translate-x-3.5' : 'translate-x-0.5'
                                        "
                                    ></span>
                                </div>
                            </label>
                            <input
                                v-if="useCustomSku"
                                v-model="form.sku"
                                type="text"
                                class="h-10 py-2 leading-normal w-full rounded-lg border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 shadow-sm outline-none transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"
                                placeholder="Ketik SKU"
                            />
                            <p v-if="form.errors.sku" class="mt-1 text-[10px] text-rose-500">
                                {{ form.errors.sku }}
                            </p>
                        </div>

                        <!-- Deskripsi Toggle -->
                        <div class="col-span-1 relative flex flex-col">
                            <label
                                class="mb-1.5 flex items-center justify-between cursor-pointer group"
                            >
                                <span
                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors"
                                    >Deskripsi Tambahan</span
                                >
                                <div
                                    class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors"
                                    :class="addDescription ? 'bg-cyan-500' : 'bg-slate-200'"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="addDescription"
                                        class="sr-only"
                                    />
                                    <span
                                        class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                                        :class="
                                            addDescription ? 'translate-x-3.5' : 'translate-x-0.5'
                                        "
                                    ></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Area Deskripsi (Muncul Di Bawah Grid jika aktif) -->
                    <div v-show="addDescription" class="mt-3 animate-in fade-in duration-200">
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-xs font-medium shadow-sm outline-none transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 resize-none"
                            placeholder="Tuliskan spesifikasi atau deskripsi detail..."
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-[10px] text-rose-500">
                            {{ form.errors.description }}
                        </p>
                    </div>
                </section>

                <!-- Baris 2 & 3 Gabungan: Spesifikasi, Harga, & Varian -->
                <!-- Komentar: Tambahkan min-w-0 w-full agar flex item ini tidak melebar karena konten anaknya yang panjang (kartu varian) -->
                <section
                    class="flex-1 min-h-0 min-w-0 w-full rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col overflow-hidden"
                >
                    <!-- Header Gabungan -->
                    <div
                        class="shrink-0 p-4 flex items-center justify-between border-b border-slate-100"
                    >
                        <div class="flex items-center gap-2">
                            <Tag class="h-4 w-4 text-cyan-600" />
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                                Spesifikasi, Harga & Varian
                            </h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Komentar: Tombol scroll horizontal dihapus karena varian sekarang menggunakan Grid (turun ke bawah) -->
                            <button
                                type="button"
                                @click="addVariant"
                                class="text-[11px] font-bold text-cyan-600 hover:text-cyan-800 flex items-center gap-1.5 bg-cyan-50 px-3 py-1.5 rounded-lg border border-cyan-100 transition-all hover:bg-cyan-100 active:scale-95"
                            >
                                <Plus class="h-3.5 w-3.5" /> Tambah Varian
                            </button>
                        </div>
                    </div>

                    <!-- Konten Gabungan (Area Scroll Varian di Bawah) -->
                    <!-- Komentar: min-w-0 w-full mencegah ekstensi lebar -->
                    <div
                        class="flex-1 min-h-0 min-w-0 w-full flex flex-col bg-slate-50/50 relative"
                    >
                        <!-- Grid Spesifikasi (Di Atas) -->
                        <div class="shrink-0 p-4 border-b border-slate-100 bg-white">
                            <div class="grid grid-cols-4 gap-3 items-start">
                                <div>
                                    <label
                                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                        >Harga / Hari</label
                                    >
                                    <div class="relative">
                                        <div
                                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                                        >
                                            <span class="text-xs font-bold text-slate-400">Rp</span>
                                        </div>
                                        <input
                                            v-model="form.price_per_day"
                                            type="number"
                                            min="0"
                                            class="h-10 py-2 leading-normal w-full rounded-lg border-slate-200 bg-slate-50 pl-8 pr-3 text-xs font-bold text-slate-700 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 tabular-nums hover:bg-white"
                                        />
                                    </div>
                                    <p
                                        v-if="form.errors.price_per_day"
                                        class="mt-1 text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.price_per_day }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                        >Berat (gram)</label
                                    >
                                    <input
                                        v-model="form.weight_gram"
                                        type="number"
                                        min="0"
                                        class="h-10 py-2 leading-normal w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-700 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 tabular-nums hover:bg-white"
                                    />
                                    <p
                                        v-if="form.errors.weight_gram"
                                        class="mt-1 text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.weight_gram }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                        >Total Stok</label
                                    >
                                    <input
                                        v-model="form.stock_total"
                                        type="number"
                                        min="0"
                                        required
                                        placeholder="0"
                                        class="h-10 py-2 leading-normal w-full rounded-lg border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-700 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 tabular-nums hover:bg-white"
                                    />
                                    <p
                                        v-if="form.errors.stock_total"
                                        class="mt-1 text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.stock_total }}
                                    </p>
                                </div>
                                <!-- Kondisi -->
                                <div>
                                    <label
                                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                        >Kondisi</label
                                    >
                                    <select
                                        v-model="form.condition"
                                        class="h-10 py-2 leading-normal w-full rounded-lg border-slate-200 bg-slate-50 pl-3 pr-8 text-xs font-bold text-slate-700 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2364748b%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[position:right_10px_center] bg-no-repeat cursor-pointer hover:bg-white"
                                    >
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Good</option>
                                        <option value="fair">Fair</option>
                                    </select>
                                    <p
                                        v-if="form.errors.condition"
                                        class="mt-1 text-[10px] text-rose-500"
                                    >
                                        {{ form.errors.condition }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Area Varian (Flex-1) -->
                        <!-- Komentar: Area ini bisa di-scroll ke bawah jika varian banyak. Menggunakan Grid agar menyusun ke bawah, tidak melebar ke samping -->
                        <div
                            class="flex-1 min-h-0 min-w-0 w-full overflow-y-auto custom-scrollbar relative"
                        >
                            <!-- Komentar: Menggunakan Grid (grid-cols-1, md:2, xl:3) agar kartu turun ke baris baru saat penuh -->
                            <div
                                ref="variantContainer"
                                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-4 h-max"
                            >
                                <!-- Komentar: Kotak Card Varian dengan w-full agar mengisi sel grid -->
                                <div
                                    v-for="(variant, index) in form.variants"
                                    :key="index"
                                    class="w-full shrink-0 h-max relative rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-cyan-300 flex flex-col"
                                >
                                    <!-- Label Dummy Varian -->
                                    <div
                                        v-if="!variant.size && !variant.color"
                                        class="absolute -top-2.5 left-3 z-10 px-2 py-0.5 rounded-full bg-slate-100 border border-white text-[9px] font-bold text-slate-400 shadow-sm flex items-center gap-1.5"
                                    >
                                        <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                        Kosong
                                    </div>
                                    <!-- Tombol Hapus -->
                                    <button
                                        v-if="form.variants.length > 1"
                                        type="button"
                                        @click="removeVariant(index)"
                                        class="absolute top-1.5 right-1.5 text-slate-300 hover:text-rose-500 transition-colors p-1.5 z-10 bg-white rounded-full hover:bg-rose-50"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>

                                    <div class="space-y-4 pt-1.5">
                                        <!-- Ukuran -->
                                        <div>
                                            <label
                                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                                >Ukuran</label
                                            >
                                            <select
                                                v-model="variant.size"
                                                class="h-9 py-1.5 leading-normal w-full rounded-lg border-slate-200 bg-slate-50 pl-3 pr-8 text-xs font-bold text-slate-700 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2364748b%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-[length:14px_14px] bg-[position:right_10px_center] bg-no-repeat cursor-pointer hover:bg-white"
                                            >
                                                <option value="">Tidak Ada</option>
                                                <option value="Kecil">Kecil</option>
                                                <option value="Sedang">Sedang</option>
                                                <option value="Besar">Besar</option>
                                            </select>
                                            <p
                                                v-if="form.errors[`variants.${index}.size`]"
                                                class="mt-1 text-[10px] text-rose-500"
                                            >
                                                {{ form.errors[`variants.${index}.size`] }}
                                            </p>
                                        </div>

                                        <!-- Warna (Hanya Icon Bulat) -->
                                        <div>
                                            <label
                                                class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400"
                                                >Warna (Pilih)</label
                                            >
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="c in PREDEFINED_COLORS"
                                                    :key="c.name"
                                                    type="button"
                                                    @click="variant.color = c.name"
                                                    class="w-6 h-6 shrink-0 rounded-full ring-2 ring-offset-2 transition-all shadow-sm"
                                                    :class="[
                                                        c.class,
                                                        variant.color === c.name
                                                            ? 'ring-cyan-500 scale-110'
                                                            : 'ring-transparent hover:scale-125 opacity-70 hover:opacity-100',
                                                    ]"
                                                    :title="c.name"
                                                ></button>
                                            </div>
                                            <p
                                                v-if="form.errors[`variants.${index}.color`]"
                                                class="mt-1 text-[10px] text-rose-500"
                                            >
                                                {{ form.errors[`variants.${index}.color`] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </div>
</template>

<style scoped>
    /* Komentar: Menyembunyikan scrollbar horizontal untuk area scroll varian.
   Navigasi dilakukan via tombol panah < > di header, bukan via scrollbar.
   Class ini dipakai oleh container flex varian agar card tidak mendorong lebar halaman. */
    .variant-scroll-hidden::-webkit-scrollbar {
        display: none;
    }
    .variant-scroll-hidden {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    /* Komentar: Kustomisasi scrollbar vertikal halus (Mac-like) untuk area yang butuh scroll vertikal */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
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
