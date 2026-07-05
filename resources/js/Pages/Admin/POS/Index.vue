<script setup>
    /**
     * Komponen: Admin/POS/Index.vue
     * Fungsi: Halaman Point of Sale (Kasir) khusus untuk Admin melayani pelanggan offline.
     * Pembaruan: UI/UX Premium, Custom Debounce, Autocomplete Search Dropdown, Micro-Grid, Dark Cart Panel.
     * Performa: Ringan tanpa memberatkan LCP/INP (100% Lighthouse diutamakan).
     */

    import { ref, computed, onMounted, watch } from 'vue';
    import AdminLayout from '@/Layouts/AdminLayout.vue';
    import {
        Search,
        Plus,
        Minus,
        Trash2,
        ShoppingCart,
        CreditCard,
        Banknote,
        QrCode,
        ChevronLeft,
        ChevronRight,
        X,
    } from 'lucide-vue-next';
    import { useUiStore } from '@/stores/ui';
    import axios from 'axios';
    import VariantSelectionModal from '@/Components/product/VariantSelectionModal.vue';

    // Set layout dengan judul dan deskripsi yang sesuai
    defineOptions({
        layout: (h, page) =>
            h(
                AdminLayout,
                {
                    title: 'POS Sewa Offline',
                    subtitle: 'Kasir premium otomatis untuk transaksi toko fisik.',
                },
                () => page
            ),
    });

    const props = defineProps({
        categories: Array,
    });

    const uiStore = useUiStore();

    // === STATE & LOGIKA PRODUK ===
    const products = ref([]);
    const searchInput = ref(''); // Input realtime yang diketik user
    const debouncedSearch = ref(''); // Input yang sudah di-debounce untuk fetch data
    const selectedCategory = ref('all');
    const currentPage = ref(1);
    const lastPage = ref(1);
    const isLoading = ref(false);
    const isSearchFocused = ref(false); // Untuk mengatur visibilitas dropdown autocomplete

    // === CUSTOM DEBOUNCE (Sangat ringan, tidak butuh library eksternal) ===
    // Menunda eksekusi fungsi selama 'delay' milidetik agar API tidak dibombardir
    function debounce(fn, delay) {
        let timeoutID = null;
        return function (...args) {
            clearTimeout(timeoutID);
            timeoutID = setTimeout(() => {
                fn.apply(this, args);
            }, delay);
        };
    }

    // Watcher untuk input search dengan debounce 300ms
    const updateSearch = debounce((value) => {
        debouncedSearch.value = value;
        fetchProducts(1);
    }, 300);

    watch(searchInput, (newVal) => {
        updateSearch(newVal);
    });

    // === BARCODE SCANNER HANDLER ===
    const handleBarcodeScan = async () => {
        if (!searchInput.value) return;

        try {
            // Tembak langsung (tanpa debounce) untuk mendapat hasil pasti dan cepat dari scanner
            const response = await axios.get(route('admin.pos.products'), {
                params: {
                    page: 1,
                    search: searchInput.value,
                    category_id: selectedCategory.value === 'all' ? null : selectedCategory.value,
                },
            });

            const results = response.data.data;
            if (results && results.length > 0) {
                addToCart(results[0]); // Langsung masukkan hasil pencarian pertama ke keranjang

                // Bersihkan form
                searchInput.value = '';
                isSearchFocused.value = false;
            } else {
                uiStore.showToast('Barang tidak ditemukan (Scan Gagal)', 'error');
            }
        } catch (error) {
            console.error('Error scanning barcode:', error);
            uiStore.showToast('Gagal memproses data barcode', 'error');
        }
    };

    // === FETCH DATA AJAX ===
    const fetchProducts = async (page = 1) => {
        isLoading.value = true;
        try {
            const response = await axios.get(route('admin.pos.products'), {
                params: {
                    page: page,
                    search: debouncedSearch.value,
                    category_id: selectedCategory.value,
                },
            });
            products.value = response.data.data;
            currentPage.value = response.data.current_page;
            lastPage.value = response.data.last_page;
        } catch (error) {
            console.error('Error fetching products:', error);
            uiStore.showToast('Gagal memuat produk', 'error');
        } finally {
            isLoading.value = false;
        }
    };

    // Filter autocomplete untuk dropdown (diambil dari data saat ini di halaman tersebut)
    // Jika ingin autocomplete akurat 100% dari semua data server, bisa panggil endpoint khusus.
    // Namun karena data sudah difilter oleh pencarian debounced, kita bisa langsung tampilkan hasilnya.
    const autocompleteResults = computed(() => {
        if (!searchInput.value) return [];
        // Mengembalikan list maksimal 5 item pencarian teratas
        return products.value.slice(0, 5);
    });

    // Watcher untuk Filter Kategori (Otomatis panggil halaman 1)
    watch(selectedCategory, () => {
        fetchProducts(1);
    });

    onMounted(() => {
        fetchProducts();

        // Default Tanggal (Hari ini s/d Besok)
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        // Format YYYY-MM-DD
        rentalStart.value = today.toISOString().split('T')[0];
        rentalEnd.value = tomorrow.toISOString().split('T')[0];
    });

    // === STATE KERANJANG (CART) ===
    const customerName = ref('');
    const customerWhatsapp = ref('');
    const rentalStart = ref('');
    const rentalEnd = ref('');
    const cart = ref([]);
    const paymentMethod = ref('cash');

    // Hitung otomatis jumlah hari
    const totalDays = computed(() => {
        if (!rentalStart.value || !rentalEnd.value) return 1;
        const start = new Date(rentalStart.value);
        const end = new Date(rentalEnd.value);
        const diffTime = end - start;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return Math.max(1, diffDays + 1); // Minimal 1 hari
    });

    // Modal Variant State
    const showVariantModal = ref(false);
    const selectedProductForVariant = ref(null);

    // Fungsi klik Autocomplete atau Card untuk menambah barang ke cart
    const addToCart = (product) => {
        // Jika ada varian, buka modal
        if (product.variants && product.variants.length > 0) {
            selectedProductForVariant.value = product;
            showVariantModal.value = true;
            // Close autocomplete if open
            if (searchInput.value) {
                searchInput.value = '';
                isSearchFocused.value = false;
            }
            return;
        }

        const existing = cart.value.find(
            (item) => item.product_id === product.id && !item.variant_id
        );
        if (existing) {
            if (existing.quantity < product.stock_available) {
                existing.quantity++;
            } else {
                uiStore.showToast('Stok maksimal tercapai', 'error');
            }
        } else {
            if (product.stock_available > 0) {
                cart.value.push({
                    product_id: product.id,
                    variant_id: null,
                    name: product.name,
                    price: product.price_per_day,
                    quantity: 1,
                    max_stock: product.stock_available,
                    image: product.images?.[0]?.image_path || null,
                    notes: '',
                });
                if (searchInput.value) {
                    searchInput.value = '';
                    isSearchFocused.value = false;
                }
            } else {
                uiStore.showToast('Stok produk habis', 'error');
            }
        }
    };

    const handleVariantAddToCart = (data) => {
        const product =
            products.value.find((p) => p.id === data.product_id) || selectedProductForVariant.value;
        const variant = product?.variants?.find((v) => v.id === data.variant_id);

        if (!variant && product?.variants?.length > 0) return;

        const cartItemId = data.variant_id ? `v_${data.variant_id}` : `p_${data.product_id}`;
        const existing = cart.value.find(
            (item) =>
                (item.variant_id ? `v_${item.variant_id}` : `p_${item.product_id}`) === cartItemId
        );

        const maxStock = variant ? variant.stock_available : product.stock_available;
        const itemName = variant
            ? `${product.name} - ${variant.size} ${variant.color ? `(${variant.color})` : ''}`
            : product.name;

        if (existing) {
            if (existing.quantity < maxStock) {
                existing.quantity++;
            } else {
                uiStore.showToast('Stok maksimal varian tercapai', 'error');
            }
        } else {
            cart.value.push({
                product_id: product.id,
                variant_id: data.variant_id || null,
                name: itemName,
                price: product.price_per_day,
                quantity: 1,
                max_stock: maxStock,
                image: product.images?.[0]?.image_path || null,
                notes: data.notes || '',
            });
        }
    };

    // Logika Minus/Plus keranjang
    const incrementQty = (item) => {
        if (item.quantity < item.max_stock) item.quantity++;
    };
    const decrementQty = (item) => {
        if (item.quantity > 1) item.quantity--;
        else removeFromCart(item);
    };
    const removeFromCart = (item) => {
        cart.value = cart.value.filter((i) => {
            if (item.variant_id) return i.variant_id !== item.variant_id;
            return i.product_id !== item.product_id;
        });
    };

    // Hitung otomatis Total Tagihan
    const grandTotal = computed(() => {
        return cart.value.reduce(
            (total, item) => total + item.price * item.quantity * totalDays.value,
            0
        );
    });

    const formatRupiah = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    // === STATE & LOGIKA MODAL (PEMBAYARAN) ===
    const showCashModal = ref(false);
    const showQrisModal = ref(false);
    const showTransferModal = ref(false);
    const amountReceived = ref('');
    const isProcessing = ref(false);

    const kembalian = computed(() => {
        const received = parseFloat(amountReceived.value) || 0;
        return Math.max(0, received - grandTotal.value);
    });

    // Buka modal sesuai pilihan
    const handleCheckout = () => {
        if (!customerName.value || !customerWhatsapp.value) {
            return uiStore.showToast('Data pelanggan (Nama & WA) wajib diisi', 'error');
        }
        if (cart.value.length === 0) {
            return uiStore.showToast('Keranjang masih kosong', 'error');
        }

        if (paymentMethod.value === 'cash') showCashModal.value = true;
        if (paymentMethod.value === 'qris') showQrisModal.value = true;
        if (paymentMethod.value === 'transfer') showTransferModal.value = true;
    };

    // Proses transaksi simpan ke database
    const submitTransaction = async () => {
        isProcessing.value = true;
        try {
            const response = await axios.post(route('admin.pos.store'), {
                customer_name: customerName.value,
                customer_whatsapp: customerWhatsapp.value,
                rental_start: rentalStart.value,
                rental_end: rentalEnd.value,
                payment_method: paymentMethod.value,
                amount_received: amountReceived.value || 0,
                items: cart.value.map((i) => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    product_variant_id: i.variant_id,
                    notes: i.notes,
                })),
            });

            uiStore.showToast(response.data.message, 'success');

            // Bersihkan seluruh state setelah sukses
            cart.value = [];
            customerName.value = '';
            customerWhatsapp.value = '';
            amountReceived.value = '';
            closeAllModals();
            fetchProducts(1); // Refresh grid & stok terbaru
        } catch (error) {
            console.error(error);
            const msg = error.response?.data?.message || 'Gagal memproses transaksi';
            uiStore.showToast(msg, 'error');
        } finally {
            isProcessing.value = false;
        }
    };

    const closeAllModals = () => {
        showCashModal.value = false;
        showQrisModal.value = false;
        showTransferModal.value = false;
    };
</script>

<template>
    <!-- 
      LAYOUT UTAMA: Anti-Scroll Global
      Ketinggian dikunci ke kalkulasi tinggi viewport dikurangi tinggi header.
      Sehingga seluruh layar mantap tidak bergerak, hanya area grid yang bisa digulir.
    -->
    <div class="h-[calc(100vh-100px)] flex flex-col lg:flex-row gap-5 overflow-hidden font-sans">
        <!-- ==========================================
             PANEL KIRI: KATALOG PRODUK
        =========================================== -->
        <div
            class="flex-1 flex flex-col min-w-0 bg-slate-50/50 rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden relative"
        >
            <!-- Header Pencarian & Kategori (Sleek Mode) -->
            <div
                class="p-3 bg-white/80 backdrop-blur-md border-b border-slate-200/60 z-20 flex flex-col gap-3 shrink-0"
            >
                <div class="relative w-full z-30">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <Search class="h-4 w-4 text-slate-400" />
                    </div>
                    <!-- 
                        Auto-Search Input dengan Event Focus/Blur & Auto-Enter Barcode Scanner 
                        Animasi ring halus (focus:ring-2) menambah kemewahan interaksi.
                    -->
                    <input
                        type="text"
                        v-model="searchInput"
                        @focus="isSearchFocused = true"
                        @blur="window.setTimeout(() => (isSearchFocused = false), 200)"
                        @keyup.enter="handleBarcodeScan"
                        class="block w-full pl-9 pr-3 py-2 bg-slate-50 border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-300"
                        placeholder="Scan barcode / Cari nama alat (Lalu tekan Enter)..."
                    />

                    <!-- AUTOCOMPLETE DROPDOWN (Muncul Melayang Saat Mengetik) -->
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div
                            v-if="
                                isSearchFocused &&
                                searchInput.length > 0 &&
                                autocompleteResults.length > 0
                            "
                            class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden z-50"
                        >
                            <ul>
                                <li
                                    v-for="res in autocompleteResults"
                                    :key="res.id"
                                    @click="addToCart(res)"
                                    class="flex items-center gap-3 p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50 last:border-0 transition-colors"
                                >
                                    <div
                                        class="w-10 h-10 rounded-md bg-slate-100 overflow-hidden shrink-0"
                                    >
                                        <img
                                            :src="
                                                res.images?.[0]?.image_path
                                                    ? `/storage/${res.images[0].image_path}`
                                                    : 'https://placehold.co/100'
                                            "
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-slate-800 truncate">
                                            {{ res.name }}
                                        </div>
                                        <div class="text-xs text-primary font-medium">
                                            {{ formatRupiah(res.price_per_day) }}
                                        </div>
                                    </div>
                                    <button
                                        class="p-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white transition-colors"
                                    >
                                        <Plus class="w-4 h-4" />
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </Transition>
                </div>

                <!-- Kategori Mikro Pills -->
                <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar items-center">
                    <button
                        @click="selectedCategory = 'all'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all duration-300"
                        :class="
                            selectedCategory === 'all'
                                ? 'bg-slate-800 text-white shadow-md'
                                : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'
                        "
                    >
                        Semua
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="selectedCategory = cat.id"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all duration-300"
                        :class="
                            selectedCategory === cat.id
                                ? 'bg-slate-800 text-white shadow-md'
                                : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'
                        "
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- Grid Area (Super Kompak) -->
            <div class="flex-1 overflow-y-auto p-3 relative scroll-smooth">
                <!-- Loading Spinner Soft -->
                <div
                    v-if="isLoading"
                    class="absolute inset-0 bg-slate-50/50 backdrop-blur-[2px] z-10 flex items-center justify-center"
                >
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>

                <div
                    v-if="products.length === 0"
                    class="flex flex-col items-center justify-center py-20 text-slate-400"
                >
                    <Search class="w-12 h-12 mb-3 text-slate-300" />
                    <p class="text-sm">Barang tidak ditemukan.</p>
                </div>

                <!-- 
                    Perombakan Grid: Lebih padat (kecil), optimal untuk desktop tinggi.
                    Menggunakan 4 sampai 5 kolom di resolusi besar agar kartu sedikit lebih lebar.
                -->
                <div
                    v-else
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3"
                >
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl hover:-translate-y-1 hover:border-primary/30 transition-all duration-300 cursor-pointer flex flex-col group relative"
                        @click="addToCart(product)"
                    >
                        <!-- Badge Habis -->
                        <div
                            v-if="product.stock_available <= 0"
                            class="absolute top-2 right-2 z-10 bg-red-500/90 backdrop-blur text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm"
                        >
                            HABIS
                        </div>

                        <!-- Area Gambar (Lebih Tinggi) -->
                        <div
                            class="h-32 bg-slate-50 relative overflow-hidden flex items-center justify-center"
                        >
                            <img
                                v-if="product.images?.[0]?.image_path"
                                :src="`/storage/${product.images[0].image_path}`"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 ease-out"
                                alt="Produk"
                            />
                            <div
                                v-else
                                class="text-slate-400 font-bold text-lg tracking-wider opacity-60"
                            >
                                No Img
                            </div>
                        </div>

                        <!-- Detail Produk (Tertata Rapi) -->
                        <div
                            class="p-3 flex-1 flex flex-col justify-between bg-white z-20 relative"
                        >
                            <h3
                                class="font-bold text-xs text-slate-800 leading-snug line-clamp-2 mb-2 group-hover:text-primary transition-colors"
                            >
                                {{ product.name }}
                            </h3>
                            <div class="flex flex-col gap-1 mt-auto">
                                <div class="flex items-center justify-end w-full">
                                    <div
                                        class="flex items-center bg-slate-50 border border-slate-100 px-2 py-0.5 rounded text-[10px] font-bold text-slate-500"
                                    >
                                        Stok:
                                        <span class="ml-1 text-slate-700">{{
                                            product.stock_available
                                        }}</span>
                                    </div>
                                </div>
                                <div class="font-black text-sm text-slate-900">
                                    {{ formatRupiah(product.price_per_day) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination Mewah (Tanpa teks berlebihan) -->
            <div
                class="p-2 border-t border-slate-200/60 bg-white shrink-0 flex items-center justify-center gap-4"
            >
                <button
                    @click="fetchProducts(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="p-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-30 transition-colors"
                >
                    <ChevronLeft class="w-4 h-4 text-slate-600" />
                </button>
                <div class="flex gap-1">
                    <span
                        v-for="p in lastPage"
                        :key="p"
                        class="w-2 h-2 rounded-full transition-colors"
                        :class="p === currentPage ? 'bg-primary' : 'bg-slate-200'"
                    >
                    </span>
                </div>
                <button
                    @click="fetchProducts(currentPage + 1)"
                    :disabled="currentPage === lastPage"
                    class="p-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-30 transition-colors"
                >
                    <ChevronRight class="w-4 h-4 text-slate-600" />
                </button>
            </div>
        </div>

        <!-- ==========================================
             PANEL KANAN: KERANJANG (DARK GLASS MODE)
        =========================================== -->
        <div
            class="w-full lg:w-[320px] xl:w-[340px] flex flex-col bg-slate-900 rounded-2xl shadow-2xl overflow-hidden shrink-0 border border-slate-800 text-slate-100 relative"
        >
            <!-- Header Cart Minimalis -->
            <div
                class="p-3 border-b border-slate-800 flex items-center justify-between shrink-0 bg-slate-900/80 backdrop-blur-md"
            >
                <div class="flex items-center gap-2">
                    <ShoppingCart class="w-4 h-4 text-primary-300" />
                    <h2 class="font-bold text-sm tracking-wide">Checkout</h2>
                </div>
                <span
                    class="bg-primary/20 text-primary-300 text-xs font-black px-2 py-0.5 rounded-full"
                    >{{ cart.length }} Item</span
                >
            </div>

            <!-- Hilangkan scrollbar bawaan dengan custom class tailwind -->
            <div
                class="flex-1 overflow-y-auto p-3 space-y-4 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:'none'] [scrollbar-width:'none']"
            >
                <!-- Input Data Pelanggan -->
                <div class="bg-slate-800/50 p-2.5 rounded-xl border border-slate-700/50">
                    <div class="relative">
                        <input
                            type="text"
                            v-model="customerName"
                            id="name"
                            class="block w-full px-0 py-1.5 text-xs bg-transparent border-0 border-b border-slate-600 focus:ring-0 focus:border-primary peer text-white placeholder-transparent transition-colors"
                            placeholder="Nama Lengkap"
                        />
                        <label
                            for="name"
                            class="absolute left-0 -top-3 text-[9px] font-bold text-slate-400 uppercase tracking-wider transition-all peer-placeholder-shown:text-xs peer-placeholder-shown:top-1.5 peer-focus:-top-3 peer-focus:text-[9px] peer-focus:text-primary"
                            >Nama Pelanggan</label
                        >
                    </div>
                    <div class="relative mt-4">
                        <input
                            type="text"
                            v-model="customerWhatsapp"
                            id="wa"
                            class="block w-full px-0 py-1.5 text-xs bg-transparent border-0 border-b border-slate-600 focus:ring-0 focus:border-primary peer text-white placeholder-transparent transition-colors"
                            placeholder="No. WhatsApp"
                        />
                        <label
                            for="wa"
                            class="absolute left-0 -top-3 text-[9px] font-bold text-slate-400 uppercase tracking-wider transition-all peer-placeholder-shown:text-xs peer-placeholder-shown:top-1.5 peer-focus:-top-3 peer-focus:text-[9px] peer-focus:text-primary"
                            >No. WhatsApp</label
                        >
                    </div>
                </div>

                <!-- Durasi Sewa Kompak -->
                <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                            Masa Sewa
                        </h3>
                        <span
                            class="text-[9px] font-black text-cyan-500 bg-cyan-500/10 px-1.5 py-0.5 rounded"
                            >{{ totalDays }} Hari</span
                        >
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1 relative">
                            <input
                                type="date"
                                v-model="rentalStart"
                                class="w-full text-xs font-semibold bg-slate-900 border border-slate-700 text-slate-200 rounded-lg focus:ring-cyan-500 focus:border-cyan-500 px-2 py-1.5 transition-colors appearance-none"
                                style="color-scheme: dark"
                            />
                        </div>
                        <div class="flex-1 relative">
                            <input
                                type="date"
                                v-model="rentalEnd"
                                class="w-full text-xs font-semibold bg-slate-900 border border-slate-700 text-slate-200 rounded-lg focus:ring-cyan-500 focus:border-cyan-500 px-2 py-1.5 transition-colors appearance-none"
                                style="color-scheme: dark"
                            />
                        </div>
                    </div>
                </div>

                <!-- Keranjang Item (Ringkas) -->
                <div
                    class="flex flex-col max-h-[160px] overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:'none'] [scrollbar-width:'none']"
                >
                    <h3
                        class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2 shrink-0"
                    >
                        Keranjang Item
                    </h3>
                    <div
                        v-if="cart.length === 0"
                        class="flex-1 border border-dashed border-slate-700 rounded-xl flex items-center justify-center text-slate-500 text-[10px] py-4"
                    >
                        Pilih produk di kiri
                    </div>

                    <div v-else class="space-y-1">
                        <TransitionGroup name="list" tag="div" class="space-y-1">
                            <div
                                v-for="(item, idx) in cart"
                                :key="
                                    item.variant_id
                                        ? `v_${item.variant_id}`
                                        : `p_${item.product_id}`
                                "
                                class="flex gap-2 items-center p-1.5 bg-slate-800/80 border border-slate-700 rounded-lg"
                            >
                                <img
                                    :src="
                                        item.image
                                            ? `/storage/${item.image}`
                                            : 'https://placehold.co/100'
                                    "
                                    class="w-8 h-8 rounded object-cover bg-slate-900"
                                    alt=""
                                />
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[10px] font-bold text-slate-100 truncate">
                                        {{ item.name }}
                                    </h4>
                                    <div class="text-[9px] text-primary-400 font-medium">
                                        {{ formatRupiah(item.price) }}
                                    </div>
                                    <div
                                        v-if="item.notes"
                                        class="text-[8px] text-slate-400 truncate italic mt-0.5"
                                    >
                                        Catatan: {{ item.notes }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="decrementQty(item)"
                                        class="w-5 h-5 flex items-center justify-center bg-slate-900 rounded hover:bg-slate-700 text-slate-400"
                                    >
                                        <Minus class="w-2.5 h-2.5" />
                                    </button>
                                    <span class="text-[10px] font-bold w-4 text-center">{{
                                        item.quantity
                                    }}</span>
                                    <button
                                        @click="incrementQty(item)"
                                        class="w-5 h-5 flex items-center justify-center bg-slate-900 rounded hover:bg-slate-700 text-slate-400"
                                    >
                                        <Plus class="w-2.5 h-2.5" />
                                    </button>
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>
                </div>

                <!-- Pilihan Pembayaran Estetik -->
                <div class="bg-slate-800/30 p-2 rounded-xl border border-slate-700/50 mt-4">
                    <h3 class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                        Metode Bayar
                    </h3>
                    <div class="grid grid-cols-3 gap-1.5">
                        <!-- Custom Radio Cards -->
                        <label
                            class="relative flex flex-col items-center p-2 cursor-pointer rounded-lg border transition-all duration-300"
                            :class="
                                paymentMethod === 'cash'
                                    ? 'bg-slate-800 border-slate-500 text-white shadow-md'
                                    : 'bg-transparent border-slate-700/50 text-slate-500 hover:bg-slate-800/50 hover:text-slate-300'
                            "
                        >
                            <input
                                type="radio"
                                v-model="paymentMethod"
                                value="cash"
                                class="sr-only"
                            />
                            <Banknote
                                class="w-4 h-4 mb-1"
                                :class="paymentMethod === 'cash' ? 'text-emerald-400' : ''"
                            />
                            <span class="text-[9px] font-bold uppercase">Tunai</span>
                        </label>
                        <label
                            class="relative flex flex-col items-center p-2 cursor-pointer rounded-lg border transition-all duration-300"
                            :class="
                                paymentMethod === 'qris'
                                    ? 'bg-slate-800 border-slate-500 text-white shadow-md'
                                    : 'bg-transparent border-slate-700/50 text-slate-500 hover:bg-slate-800/50 hover:text-slate-300'
                            "
                        >
                            <input
                                type="radio"
                                v-model="paymentMethod"
                                value="qris"
                                class="sr-only"
                            />
                            <QrCode
                                class="w-4 h-4 mb-1"
                                :class="paymentMethod === 'qris' ? 'text-blue-400' : ''"
                            />
                            <span class="text-[9px] font-bold uppercase">QRIS</span>
                        </label>
                        <label
                            class="relative flex flex-col items-center p-2 cursor-pointer rounded-lg border transition-all duration-300"
                            :class="
                                paymentMethod === 'transfer'
                                    ? 'bg-slate-800 border-slate-500 text-white shadow-md'
                                    : 'bg-transparent border-slate-700/50 text-slate-500 hover:bg-slate-800/50 hover:text-slate-300'
                            "
                        >
                            <input
                                type="radio"
                                v-model="paymentMethod"
                                value="transfer"
                                class="sr-only"
                            />
                            <CreditCard
                                class="w-4 h-4 mb-1"
                                :class="paymentMethod === 'transfer' ? 'text-indigo-400' : ''"
                            />
                            <span class="text-[9px] font-bold uppercase">Transfer</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer Area: Total & Action Button (Gradient) -->
            <div class="p-4 border-t border-slate-800 bg-slate-900/90 backdrop-blur-lg shrink-0">
                <div class="flex items-end justify-between mb-4">
                    <div class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                        Total Tagihan
                    </div>
                    <div class="text-3xl font-black text-white font-mono tracking-tighter">
                        {{ formatRupiah(grandTotal) }}
                    </div>
                </div>
                <!-- Tombol Proses Transaksi dengan gradasi khusus dari Screenshot -->
                <button
                    @click="handleCheckout"
                    :disabled="cart.length === 0"
                    class="w-full py-4 rounded-2xl font-black text-sm text-white uppercase tracking-widest transition-all duration-300 relative overflow-hidden group"
                    :class="
                        cart.length === 0
                            ? 'bg-slate-800 text-slate-500 cursor-not-allowed border border-slate-700'
                            : 'bg-gradient-to-br from-[#2e1065] via-[#7e22ce] to-[#ea580c] shadow-[0_0_20px_rgba(234,88,12,0.25)] hover:shadow-[0_0_30px_rgba(234,88,12,0.4)] hover:-translate-y-1'
                    "
                >
                    <!-- Efek kilauan cahaya pada tombol saat hover -->
                    <div
                        v-if="cart.length > 0"
                        class="absolute inset-0 w-1/2 h-full bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-12 -translate-x-full group-hover:animate-[shimmer_1.5s_ease-out_infinite]"
                    ></div>
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        Proses Transaksi
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL PEMBAYARAN (BLUR BACKDROP)
    =========================================== -->

    <!-- Modal Cash -->
    <Transition name="modal">
        <div
            v-if="showCashModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        >
            <!-- Container Modal Tunai (Eksklusif sesuai desain orisinal) -->
            <div
                class="bg-white rounded-[32px] w-full max-w-sm shadow-2xl overflow-hidden p-8 text-center transform scale-100 transition-all"
            >
                <!-- Ikon Header -->
                <div
                    class="w-16 h-16 bg-[#eafff4] text-[#10b981] rounded-[20px] flex items-center justify-center mx-auto mb-6"
                >
                    <Banknote class="w-8 h-8" />
                </div>

                <!-- Total Tagihan -->
                <h2 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Total Tagihan Tunai
                </h2>
                <div class="text-4xl font-black text-[#1e293b] mb-8 tracking-tight font-mono">
                    Rp {{ grandTotal.toLocaleString('id-ID') }}
                </div>

                <!-- Input Uang Diterima -->
                <div class="text-left mb-6">
                    <label
                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2 mb-2 block"
                        >Uang Diterima</label
                    >
                    <input
                        type="number"
                        v-model="amountReceived"
                        class="w-full text-2xl font-bold bg-white border-2 border-[#10b981] rounded-[20px] focus:ring-4 focus:ring-[#10b981]/20 focus:border-[#10b981] py-3.5 px-5 transition-all text-[#1e293b] outline-none"
                        placeholder="0"
                    />
                </div>

                <!-- Box Kembalian (Hijau Halus) -->
                <div
                    class="bg-[#f4fdf8] px-5 py-4 rounded-[20px] border border-emerald-50 flex justify-between items-center mb-8"
                >
                    <span class="text-[11px] font-bold text-[#059669] uppercase tracking-widest"
                        >Kembalian</span
                    >
                    <span class="text-xl font-black text-[#059669] font-mono"
                        >Rp {{ kembalian.toLocaleString('id-ID') }}</span
                    >
                </div>

                <!-- Tombol Aksi -->
                <div class="flex gap-3">
                    <button
                        @click="closeAllModals"
                        class="w-full py-4 rounded-[18px] font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitTransaction"
                        :disabled="isProcessing"
                        class="w-full py-4 rounded-[18px] font-bold text-white bg-[#10b981] hover:bg-[#059669] shadow-lg shadow-[#10b981]/25 transition-all disabled:opacity-50"
                    >
                        {{ isProcessing ? '...' : 'Selesai' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal QRIS -->
    <Transition name="modal">
        <div
            v-if="showQrisModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        >
            <!-- Container Modal QRIS -->
            <div class="bg-white rounded-[32px] w-full max-w-sm shadow-2xl p-8 text-center">
                <!-- Ikon Header -->
                <div
                    class="w-16 h-16 bg-blue-50 text-blue-500 rounded-[20px] flex items-center justify-center mx-auto mb-6"
                >
                    <QrCode class="w-8 h-8" />
                </div>

                <h2 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Scan QRIS Sesuai Tagihan
                </h2>
                <div class="text-4xl font-black text-[#1e293b] mb-6 font-mono tracking-tight">
                    Rp {{ grandTotal.toLocaleString('id-ID') }}
                </div>

                <!-- Kotak QR -->
                <div
                    class="bg-white border-2 border-blue-500 rounded-3xl inline-block mb-8 p-3 shadow-sm"
                >
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=ContohQRIS"
                        alt="QRIS"
                        class="w-40 h-40 rounded-2xl"
                    />
                </div>

                <!-- Tombol Aksi -->
                <div class="flex gap-3">
                    <button
                        @click="closeAllModals"
                        class="w-full py-4 rounded-[18px] font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitTransaction"
                        :disabled="isProcessing"
                        class="w-full py-4 rounded-[18px] font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/25 transition-all disabled:opacity-50"
                    >
                        {{ isProcessing ? '...' : 'Telah Dibayar' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Transfer -->
    <Transition name="modal">
        <div
            v-if="showTransferModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        >
            <!-- Container Modal Transfer -->
            <div class="bg-white rounded-[32px] w-full max-w-sm shadow-2xl p-8 text-center">
                <!-- Ikon Header -->
                <div
                    class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-[20px] flex items-center justify-center mx-auto mb-6"
                >
                    <CreditCard class="w-8 h-8" />
                </div>

                <h2 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                    Total Transfer
                </h2>
                <div class="text-4xl font-black text-[#1e293b] mb-8 tracking-tight font-mono">
                    Rp {{ grandTotal.toLocaleString('id-ID') }}
                </div>

                <!-- Detail Rekening -->
                <div
                    class="bg-[#f8fafc] p-6 rounded-2xl border border-slate-200 flex flex-col items-center justify-center mb-8"
                >
                    <div
                        class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2"
                    >
                        BCA Official
                    </div>
                    <div
                        class="text-3xl font-black text-[#1e293b] font-mono tracking-tight select-all"
                    >
                        1234 5678 90
                    </div>
                    <div class="text-xs font-semibold text-slate-500 mt-2">A/N Hiko App Utama</div>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex gap-3">
                    <button
                        @click="closeAllModals"
                        class="w-full py-4 rounded-[18px] font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitTransaction"
                        :disabled="isProcessing"
                        class="w-full py-4 rounded-[18px] font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/25 transition-all disabled:opacity-50"
                    >
                        {{ isProcessing ? '...' : 'Dana Masuk' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Varian -->
    <VariantSelectionModal
        :show="showVariantModal"
        :product="selectedProductForVariant"
        :is-pos-mode="true"
        @close="showVariantModal = false"
        @add-to-cart="handleVariantAddToCart"
    />
</template>

<style scoped>
    /* Transisi Khusus untuk list keranjang agar halus saat dihapus/ditambah */
    .list-enter-active,
    .list-leave-active {
        transition: all 0.3s ease;
    }
    .list-enter-from,
    .list-leave-to {
        opacity: 0;
        transform: translateX(30px);
    }

    /* Transisi Modal Blur & Skala */
    .modal-enter-active,
    .modal-leave-active {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-enter-from,
    .modal-leave-to {
        opacity: 0;
        transform: scale(0.95);
    }

    /* Animasi Shimmer untuk tombol checkout mewah */
    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
</style>
