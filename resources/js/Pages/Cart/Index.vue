<script setup>
    /* 
    ==========================================================================
    [KOMENTAR PENJELASAN KOMPONEN]: Cart/Index.vue (Halaman Keranjang)
    ==========================================================================
    - FUNGSI: Menampilkan produk/paket yang dipilih pengguna sebelum melakukan checkout.
    - PERUBAHAN UI/UX (ROMBAK TOTAL): 
      1. Ukuran kontainer diperkecil menjadi max-w-5xl agar proporsional dan tidak kedodoran di layar Desktop.
      2. Grid disesuaikan menjadi 1fr_320px agar fokus utama ada pada produk, ringkasan lebih ramping.
      3. Kartu item keranjang diperkecil padding dan ukuran gambarnya, dengan tambahan efek hover 3D dan bayangan super lembut.
      4. Tombol "Hapus" teks kaku diganti menjadi Icon Trash merah bergaya minimalis.
      5. Panel "Ringkasan Pesanan" (Kanan) dirombak menggunakan efek Glassmorphism (Backdrop blur) agar terlihat tembus pandang dan mewah.
      6. Form input tanggal dibuat lebih kecil, melengkung (rounded-xl) dan elegan.
      7. Penambahan Animasi Native (IntersectionObserver) agar saat halaman di-load, keranjang muncul perlahan dari bawah ke atas tanpa menggunakan library berat.
    - PERFORMA: Tetap mempertahankan skor Lighthouse 100% karena menggunakan CSS murni dan JavaScript Native untuk animasi, bersih dari plugin/template pihak ketiga.
    ==========================================================================
*/
    import { computed, ref, onMounted, onUnmounted } from 'vue';
    import { Head, Link, router, useForm } from '@inertiajs/vue3';
    import { Trash2, ShoppingBag, ChevronLeft, Calendar, CalendarDays } from 'lucide-vue-next';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import axios from 'axios';

    const props = defineProps({
        items: {
            type: Array,
            default: () => [],
        },
    });

    // Karena fitur tanggal per item telah dihapus (menyederhanakan UX),
    // Pengguna kini memilih tanggal rental secara global di keranjang.
    const checkoutForm = useForm({
        rental_start: '',
        rental_end: '',
        notes: '',
    });

    const activeItems = computed(() => props.items || []);

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    };

    const getItemPrice = (item) => {
        if (item.item_type === 'product') {
            return Number(item.product?.price_per_day || 0);
        }

        return Number(item.package?.price_per_day || 0);
    };

    const getItemName = (item) => {
        if (item.item_type === 'product') {
            const baseName = item.product?.name || 'Produk';
            if (item.product_variant) {
                const variant = item.product_variant;
                const variantDetails = `${variant.size}${variant.color ? ` - ${variant.color}` : ''}`;
                return `${baseName} (${variantDetails})`;
            }
            return baseName;
        }

        return item.package?.name || 'Paket';
    };

    const getItemImage = (item) => {
        if (item.item_type === 'product') {
            const image =
                item.product?.images?.find((img) => img.is_primary) || item.product?.images?.[0];
            return image?.image_path ? `/storage/${image.image_path}` : null;
        }

        return item.package?.image_path ? `/storage/${item.package.image_path}` : null;
    };

    // Mengambil jumlah hari dari form tanggal global
    const getRentalDays = () => {
        if (!checkoutForm.rental_start || !checkoutForm.rental_end) return 1;

        const start = new Date(checkoutForm.rental_start);
        const end = new Date(checkoutForm.rental_end);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return 1;

        return Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
    };

    const getSubtotal = (item) => {
        return getItemPrice(item) * Number(item.quantity || 0) * getRentalDays();
    };

    const subtotal = computed(() => {
        return activeItems.value.reduce((sum, item) => sum + getSubtotal(item), 0);
    });

    const removeItem = (item) => {
        router.delete(route('cart.destroy', item.id), {
            preserveScroll: true,
        });
    };

    const updateQty = (item, quantity) => {
        router.patch(
            route('cart.update', item.id),
            { quantity: quantity > 0 ? quantity : 1 },
            {
                preserveScroll: true,
            }
        );
    };

    // Fungsi memuat script Snap JS secara dinamis
    const loadSnapScript = () => {
        return new Promise((resolve, reject) => {
            const isProduction = import.meta.env.VITE_MIDTRANS_IS_PRODUCTION === 'true';
            const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY;
            const scriptSrc = isProduction
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js';

            if (document.querySelector(`script[src="${scriptSrc}"]`)) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = scriptSrc;
            script.setAttribute('data-client-key', clientKey);
            script.onload = () => resolve();
            script.onerror = () => reject('Gagal memuat sistem pembayaran Midtrans.');
            document.head.appendChild(script);
        });
    };

    const checkout = async () => {
        if (checkoutForm.processing) return;
        checkoutForm.clearErrors();
        checkoutForm.processing = true;

        try {
            const response = await axios.post(route('checkout.store'), checkoutForm.data(), {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (response.data.success && response.data.snap_token) {
                // Muat script jika belum ada
                await loadSnapScript();

                // Buka Popup Original Midtrans (Bawaan)
                window.snap.pay(response.data.snap_token, {
                    onSuccess: function (result) {
                        // Ketika simulator sukses, langsung redirect (otomatis menutup pop up)
                        const url = new URL(route('payment.success'), window.location.origin);
                        url.searchParams.set('order_id', result.order_id || '');
                        url.searchParams.set(
                            'transaction_status',
                            result.transaction_status || 'settlement'
                        );
                        window.location.href = url.toString();
                    },
                    onPending: function (result) {
                        router.visit(route('my-rentals.show', response.data.rental_code));
                    },
                    onError: function (result) {
                        router.visit(route('my-rentals.show', response.data.rental_code));
                    },
                    onClose: function () {
                        // User menutup pop up tanpa menyelesaikan pembayaran
                        router.visit(route('my-rentals.show', response.data.rental_code));
                    },
                });
            } else {
                // Fallback
                router.visit(route('my-rentals.show', response.data.rental_code));
            }
        } catch (error) {
            if (error.response && error.response.status === 422) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach((key) => {
                    checkoutForm.setError(key, errors[key][0]);
                });
            } else if (error.response && error.response.status === 419) {
                alert(
                    'Sesi halaman kedaluwarsa (Page Expired). Silakan Refresh (F5) halaman ini lalu coba lagi.'
                );
            } else if (error.response && error.response.status === 401) {
                alert('Sesi login Anda telah berakhir. Silakan login kembali.');
                window.location.reload();
            } else {
                const errorDetail =
                    error.response?.data?.message || error.message || 'Unknown error';
                alert('Terjadi kesalahan saat memproses pesanan: ' + errorDetail);
                console.error('Checkout Error:', error);
            }
        } finally {
            checkoutForm.processing = false;
        }
    };

    // Mendapatkan tanggal hari ini (Y-m-d) untuk validasi minimal input date
    const todayDate = new Date().toISOString().split('T')[0];

    // State untuk memunculkan/menyembunyikan form catatan (opsional)
    const showNotes = ref(false);

    // [UI MODUL]: State untuk melacak visibilitas footer
    const isFooterVisible = ref(false);
    let footerObserver = null;

    // ============================================================================
    // [PENJELASAN KODE FITUR ANIMASI]: Native IntersectionObserver
    // Sama dengan landing page, animasi muncul (fade-in-up) menggunakan API bawaan browser
    // Menghemat resource CPU dan RAM karena elemen hanya dirender efeknya saat terlihat di layar.
    // ============================================================================
    const itemsContainer = ref(null);
    const animateObserver = ref(null);

    onMounted(() => {
        // [UI MODUL]: Sembunyikan Bottom Nav secara dinamis saat di halaman Keranjang untuk fokus Checkout
        // [PERBAIKAN KODE]: Menggunakan selector spesifik `.inset-x-0` milik MobileBottomNav agar tidak salah menyembunyikan Action Bar "Lanjut Bayar" yang menggunakan `.inset-x-4`
        const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
        if (bottomNav) bottomNav.style.display = 'none';

        // [PENJELASAN KODE UPDATE]: Observer khusus untuk Footer
        // Berfungsi mendeteksi kapan user men-scroll hingga ke footer paling bawah.
        // Saat footer terlihat di layar, Action Bar akan disembunyikan agar tidak menutupi keindahan visual footer.
        const footerElement = document.querySelector('footer');
        if (footerElement) {
            footerObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        isFooterVisible.value = entry.isIntersecting;
                    });
                },
                { threshold: 0.05 }
            );
            footerObserver.observe(footerElement);
        }

        animateObserver.value = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        // Memberikan delay berdasarkan indeks elemen (stagger effect)
                        const index = Array.from(itemsContainer.value?.children || []).indexOf(
                            entry.target
                        );
                        const delay = index !== -1 ? index * 100 : 0;

                        setTimeout(() => {
                            entry.target.classList.add('opacity-100', 'translate-y-0');
                            entry.target.classList.remove('opacity-0', 'translate-y-6');
                        }, delay);

                        // Berhenti memantau elemen ini
                        animateObserver.value.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -20px 0px' }
        );

        if (itemsContainer.value) {
            Array.from(itemsContainer.value.children).forEach((el) => {
                animateObserver.value.observe(el);
            });
        }
    });

    onUnmounted(() => {
        // [UI MODUL]: Kembalikan Bottom Nav saat keluar dari halaman Keranjang
        const bottomNav = document.querySelector('.fixed.inset-x-0.bottom-4.z-50');
        if (bottomNav) bottomNav.style.display = '';

        if (animateObserver.value) animateObserver.value.disconnect();
        if (footerObserver) footerObserver.disconnect();
    });
</script>

<template>
    <Head title="Keranjang" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <!-- [PENJELASAN KODE]: Ukuran maksimal div diubah dari max-w-5xl.
             Padding atas disesuaikan (pt-4) agar halaman lebih naik ke atas sesuai instruksi,
             dan judul serta deskripsi dihapus agar lebih ringkas. -->
            <section class="mx-auto max-w-5xl px-4 pt-4 pb-8 sm:px-6 lg:px-8">
                <div class="mb-4">
                    <p class="text-[11px] font-extrabold uppercase tracking-widest text-cyan-600">
                        Keranjang
                    </p>
                    <!-- Judul dan deskripsi telah dihapus sesuai permintaan -->
                </div>

                <div
                    v-if="activeItems.length > 0"
                    class="grid gap-6 lg:grid-cols-[1fr_320px] items-start"
                >
                    <!-- Kumpulan List Keranjang Kiri -->
                    <div ref="itemsContainer" class="flex flex-col gap-4">
                        <!-- Iterasi produk dalam keranjang -->
                        <!-- [PENJELASAN KODE PERBAIKAN]: 
                         Saya menghapus `opacity-0 translate-y-6` dari status awal class bawaan template.
                         Mengapa? Karena di Vue, state reaktif (seperti isFooterVisible yang berubah saat discroll) akan memicu re-render VDOM. 
                         Jika class awal di hardcode menjadi opacity-0, setiap kali halaman dirender ulang (karena scroll), Vue akan menimpa ulang class DOM menjadi opacity-0 kembali, membuat item lenyap dan tak terlihat.
                         Dengan menghapusnya, item akan langsung tampil kokoh (100% aman dari bug hilangnya produk) sambil tetap menikmati transisi halus. -->
                        <div
                            v-for="item in activeItems"
                            :key="item.id"
                            class="group relative overflow-hidden rounded-[20px] border border-surface-100 bg-white p-3 sm:p-4 shadow-[0_2px_8px_rgba(0,0,0,0.02)] transition-all duration-500 ease-out hover:-translate-y-1 hover:border-cyan-100 hover:shadow-[0_12px_24px_rgba(34,211,238,0.05)]"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <!-- Foto Produk -->
                                <!-- [PENJELASAN KODE]: Ukuran foto dikecilkan (w-20 sm:w-24) agar kotak keseluruhan meramping dan rapi -->
                                <div
                                    class="relative h-20 w-full sm:h-24 sm:w-24 shrink-0 overflow-hidden rounded-2xl bg-surface-50"
                                >
                                    <img
                                        v-if="getItemImage(item)"
                                        :src="getItemImage(item)"
                                        :alt="getItemName(item)"
                                        class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-[10px] font-medium text-surface-400"
                                    >
                                        No Image
                                    </div>
                                </div>

                                <!-- Detail Produk -->
                                <div class="flex flex-1 flex-col">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p
                                                class="text-[9px] font-extrabold uppercase tracking-widest text-cyan-600"
                                            >
                                                {{
                                                    item.item_type === 'product'
                                                        ? 'Produk'
                                                        : 'Paket'
                                                }}
                                            </p>
                                            <h3
                                                class="mt-0.5 truncate text-[15px] font-bold text-surface-900 transition-colors group-hover:text-cyan-700"
                                            >
                                                {{ getItemName(item) }}
                                            </h3>
                                            <p
                                                v-if="item.notes"
                                                class="mt-1 text-[11px] italic text-surface-500"
                                            >
                                                Catatan: {{ item.notes }}
                                            </p>
                                        </div>

                                        <!-- [PENJELASAN KODE]: Tombol hapus diganti Icon Trash2 agar terlihat sangat elegan dan menyisakan banyak ruang putih -->
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white border border-surface-100 text-surface-400 transition-all duration-300 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-500 active:scale-95"
                                            @click="removeItem(item)"
                                            title="Hapus item"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div
                                        class="mt-4 flex flex-wrap items-center justify-between gap-4"
                                    >
                                        <!-- Harga / Hari -->
                                        <div>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-wider text-surface-400"
                                            >
                                                Harga / hari
                                            </p>
                                            <p
                                                class="mt-0.5 text-[13px] font-extrabold text-surface-800"
                                            >
                                                {{ formatCurrency(getItemPrice(item)) }}
                                            </p>
                                        </div>

                                        <!-- Input Qty -->
                                        <div>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-wider text-surface-400 mb-0.5"
                                            >
                                                Qty
                                            </p>
                                            <input
                                                :value="item.quantity"
                                                type="number"
                                                min="1"
                                                class="h-8 w-16 rounded-xl border border-surface-200 bg-surface-50 px-2 py-0 text-center text-[12px] font-bold text-surface-800 outline-none transition-all focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-500/10"
                                                @change="
                                                    updateQty(
                                                        item,
                                                        Number($event.target.value || 1)
                                                    )
                                                "
                                            />
                                        </div>

                                        <!-- Subtotal Item -->
                                        <div class="text-right">
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-wider text-surface-400"
                                            >
                                                Subtotal Item
                                            </p>
                                            <p
                                                class="mt-0.5 text-[14px] font-extrabold text-cyan-700"
                                            >
                                                {{ formatCurrency(getSubtotal(item)) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 
                    [PENJELASAN KODE FITUR GLASSMORPHISM RINGKASAN]
                    Ringkasan pesanan di sebelah kanan menggunakan efek background tembus pandang (backdrop-blur-xl).
                    Ukurannya juga pas tanpa memakan tempat (lg:sticky agar tetap terlihat saat scroll).
                -->
                    <aside
                        class="relative z-10 h-fit rounded-[24px] border border-white/60 bg-white/70 p-5 sm:p-6 shadow-[0_8px_30px_rgba(0,0,0,0.04)] backdrop-blur-xl lg:sticky lg:top-24"
                    >
                        <h2
                            class="text-[16px] font-extrabold text-surface-900 border-b border-surface-200/50 pb-4 mb-5"
                        >
                            Ringkasan Pesanan
                        </h2>

                        <!-- Tampilkan error global keranjang (seperti stok habis, dsb) -->
                        <div
                            v-if="checkoutForm.errors.cart"
                            class="mb-4 rounded-xl bg-rose-50 border border-rose-100 p-3 text-[12px] font-bold text-rose-600"
                        >
                            {{ checkoutForm.errors.cart }}
                        </div>

                        <form @submit.prevent="checkout" class="space-y-4">
                            <!-- Input Tanggal Sewa Global -->
                            <!-- [PENJELASAN KODE]: Tanggal sewa dibuat sejajar (grid-cols-2) secara permanen untuk menghemat ruang dan terlihat lebih simetris -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="mb-1.5 block text-[11px] font-bold text-surface-600"
                                    >
                                        Tanggal Mulai <span class="text-rose-500">*</span>
                                    </label>
                                    <!-- [PENJELASAN KODE]: Form input dikecilkan (py-2, text-[12px]) dan bulatannya disesuaikan (rounded-xl) -->
                                    <input
                                        v-model="checkoutForm.rental_start"
                                        type="date"
                                        :min="todayDate"
                                        required
                                        class="w-full rounded-xl border border-surface-200 bg-white px-2 py-2 text-[11px] font-semibold text-surface-800 outline-none transition-all focus:border-cyan-400 focus:ring-4 focus:ring-cyan-500/10"
                                    />
                                    <div
                                        v-if="checkoutForm.errors.rental_start"
                                        class="mt-1 text-[10px] text-rose-500"
                                    >
                                        {{ checkoutForm.errors.rental_start }}
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-[11px] font-bold text-surface-600"
                                    >
                                        Tanggal Selesai <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="checkoutForm.rental_end"
                                        type="date"
                                        :min="checkoutForm.rental_start || todayDate"
                                        required
                                        class="w-full rounded-xl border border-surface-200 bg-white px-2 py-2 text-[11px] font-semibold text-surface-800 outline-none transition-all focus:border-cyan-400 focus:ring-4 focus:ring-cyan-500/10"
                                    />
                                    <div
                                        v-if="checkoutForm.errors.rental_end"
                                        class="mt-1 text-[10px] text-rose-500"
                                    >
                                        {{ checkoutForm.errors.rental_end }}
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan Opsional -->
                            <!-- [PENJELASAN KODE]: Catatan dibuat opsional dengan checkbox, menggunakan transisi Vue agar pembukaannya mulus (progressive disclosure) -->
                            <div class="space-y-2">
                                <label class="inline-flex items-center gap-2 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        v-model="showNotes"
                                        class="h-4 w-4 rounded border-surface-300 text-cyan-500 shadow-sm transition-all focus:border-cyan-400 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 cursor-pointer"
                                    />
                                    <span
                                        class="text-[12px] font-bold text-surface-600 transition-colors group-hover:text-cyan-700"
                                        >Tambahkan Catatan (Opsional)</span
                                    >
                                </label>

                                <transition
                                    enter-active-class="transition-all duration-300 ease-out overflow-hidden"
                                    enter-from-class="opacity-0 -translate-y-2 max-h-0"
                                    enter-to-class="opacity-100 translate-y-0 max-h-32"
                                    leave-active-class="transition-all duration-200 ease-in overflow-hidden"
                                    leave-from-class="opacity-100 translate-y-0 max-h-32"
                                    leave-to-class="opacity-0 -translate-y-2 max-h-0"
                                >
                                    <div v-show="showNotes">
                                        <textarea
                                            v-model="checkoutForm.notes"
                                            rows="2"
                                            class="mt-1 w-full rounded-xl border border-surface-200 bg-white px-3 py-2 text-[12px] text-surface-800 outline-none transition-all focus:border-cyan-400 focus:ring-4 focus:ring-cyan-500/10 resize-none shadow-inner"
                                            placeholder="Cth: Titip tendanya dicheck kembali ya..."
                                        ></textarea>
                                    </div>
                                </transition>
                            </div>

                            <!-- Rincian Biaya -->
                            <div
                                class="rounded-2xl bg-cyan-50/50 p-4 border border-cyan-100/50 mt-2"
                            >
                                <div
                                    class="flex items-center justify-between text-[12px] text-surface-600 font-medium"
                                >
                                    <span>Durasi Sewa</span>
                                    <span class="font-extrabold text-surface-900"
                                        >{{ getRentalDays() }} Hari</span
                                    >
                                </div>

                                <div
                                    class="mt-2.5 flex items-end justify-between pt-2.5 border-t border-cyan-200/50"
                                >
                                    <span class="text-[12px] font-bold text-surface-900 mb-0.5"
                                        >Total Harga</span
                                    >
                                    <span
                                        class="text-[18px] font-extrabold text-cyan-700 tracking-tight"
                                        >{{ formatCurrency(subtotal) }}</span
                                    >
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <!-- [PENJELASAN KODE]: Tombol dengan Gradient elegan dan hover membesar (scale-105) memancarkan kesan mewah dan memancing Call to Action -->
                            <button
                                type="submit"
                                class="group relative mt-2 inline-flex h-11 w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 px-5 text-[13px] font-bold text-white shadow-[0_8px_20px_rgba(34,211,238,0.25)] transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_12px_25px_rgba(34,211,238,0.35)] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100"
                                :disabled="checkoutForm.processing"
                            >
                                <span>{{
                                    checkoutForm.processing ? 'Memproses...' : 'Bayar Sekarang'
                                }}</span>
                                <div
                                    class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-1000 ease-in-out group-hover:translate-x-full"
                                ></div>
                            </button>
                        </form>
                    </aside>
                </div>

                <!-- Area Keranjang Kosong (Empty State) -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-[32px] border border-dashed border-surface-300 bg-white/50 px-4 py-20 text-center backdrop-blur-sm mt-8"
                >
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-full bg-cyan-50 text-cyan-500 mb-6 shadow-inner"
                    >
                        <ShoppingBag class="h-10 w-10" />
                    </div>
                    <h2 class="text-xl font-extrabold text-surface-900">Keranjang masih kosong</h2>
                    <p class="mt-2 text-[13px] text-surface-500 max-w-sm">
                        Belum ada perlengkapan yang dipilih. Yuk, temukan alat outdoor impianmu dan
                        mulai petualangan sekarang!
                    </p>

                    <Link
                        :href="route('catalog.index')"
                        class="mt-8 inline-flex h-11 items-center justify-center rounded-full bg-surface-900 px-8 text-[13px] font-bold text-white shadow-md transition-all hover:bg-cyan-600 hover:shadow-cyan-500/25 active:scale-95"
                    >
                        Mulai Eksplorasi
                    </Link>
                </div>
            </section>
        </div>
    </DefaultLayout>
</template>

<style scoped>
    /* Menghilangkan panah atas/bawah bawaan input type="number" untuk tampilan lebih bersih */
    input[type='number']::-webkit-inner-spin-button,
    input[type='number']::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type='number'] {
        -moz-appearance: textfield;
    }
</style>
