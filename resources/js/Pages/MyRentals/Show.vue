<script setup>
    import { computed, ref } from 'vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ChevronLeft } from 'lucide-vue-next';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import ReorderModal from '@/Components/ui/ReorderModal.vue';

    const props = defineProps({
        rental: {
            type: Object,
            required: true,
        },
        reorderSuggestions: {
            type: Array,
            default: () => [],
        },
    });

    const reorderOpen = ref(false);

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    };

    const formatDate = (value) => {
        if (!value) return '-';
        return new Date(value).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    };

    const canReorder = computed(() => {
        return ['returned', 'cancelled'].includes(props.rental.status);
    });

    const canCancel = computed(() => {
        return props.rental.status === 'pending_payment';
    });

    const cancelRental = () => {
        if (!confirm('Batalkan rental ini?')) return;

        router.post(
            route('my-rentals.cancel', props.rental.rental_code),
            {},
            {
                preserveScroll: true,
            }
        );
    };
</script>

<template>
    <Head :title="rental.rental_code" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <!-- [UPDATE]: Container diubah menjadi max-w-md agar kotak tiket lebih ramping (tidak terlalu lebar) -->
            <section class="mx-auto max-w-md px-4 py-8 sm:px-6 lg:px-8">
                <!-- [UPDATE]: Area Header dengan Animasi Fade Up dan Tombol yang disederhanakan -->
                <div
                    class="mb-6 flex items-center justify-between animate-fade-up opacity-0"
                    style="animation-fill-mode: forwards"
                >
                    <Link
                        :href="route('my-rentals.index')"
                        class="inline-flex items-center gap-2 text-sm font-bold text-surface-500 hover:text-surface-900 transition-colors"
                    >
                        <svg
                            class="w-4 h-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M19 12H5"></path>
                            <path d="M12 19l-7-7 7-7"></path>
                        </svg>
                        Kembali
                    </Link>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="canReorder"
                            type="button"
                            class="user-btn-secondary h-9 px-4 text-xs font-bold"
                            @click="reorderOpen = true"
                        >
                            Sewa Ulang
                        </button>
                        <button
                            v-if="canCancel"
                            type="button"
                            class="inline-flex h-9 items-center rounded-xl bg-red-50 px-4 text-xs font-bold text-red-600 transition hover:bg-red-100"
                            @click="cancelRental"
                        >
                            Batalkan
                        </button>
                    </div>
                </div>

                <!-- [UPDATE]: Desain Boarding Pass/Tiket Fisik Utama dengan Shadow Elegan -->
                <div
                    class="relative bg-white rounded-[2rem] shadow-2xl shadow-surface-200/50 ring-1 ring-surface-200 overflow-hidden animate-fade-up opacity-0"
                    style="animation-delay: 100ms; animation-fill-mode: forwards"
                >
                    <!-- [UPDATE]: Padding diperkecil (p-6) agar tinggi kotak tidak terlalu memanjang ke bawah -->
                    <div class="p-6 text-center bg-gradient-to-b from-surface-50/50 to-white">
                        <div
                            class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest mb-3"
                            :class="{
                                'bg-amber-100 text-amber-700': rental.status === 'pending_payment',
                                'bg-blue-100 text-blue-700': rental.status === 'confirmed',
                                'bg-emerald-100 text-emerald-700': rental.status === 'active',
                                'bg-surface-100 text-surface-700': rental.status === 'returned',
                                'bg-red-100 text-red-700': rental.status === 'cancelled',
                                'bg-rose-100 text-rose-700': rental.status === 'overdue',
                            }"
                        >
                            {{ rental.status.replace('_', ' ') }}
                        </div>

                        <!-- [UPDATE]: Font Monospace untuk kode tiket agar terlihat otentik -->
                        <h1
                            class="text-2xl sm:text-3xl font-mono font-extrabold text-surface-900 tracking-tight mb-1"
                        >
                            {{ rental.rental_code }}
                        </h1>
                        <p class="text-xs text-surface-500 font-medium mb-5">
                            {{ formatDate(rental.rental_start) }} &mdash;
                            {{ formatDate(rental.rental_end) }}
                        </p>

                        <!-- [UPDATE]: Sembunyikan QR Code jika belum dibayar (pending_payment) -->
                        <div v-if="rental.status !== 'pending_payment'">
                            <div
                                class="inline-block p-3 bg-white border border-surface-200 rounded-3xl shadow-sm mb-2 group transition-transform hover:scale-105 duration-500"
                            >
                                <img
                                    :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${rental.rental_code}`"
                                    alt="QR Code"
                                    class="w-40 h-40 object-contain mx-auto mix-blend-multiply"
                                />
                            </div>
                            <p class="text-[11px] text-surface-400 mt-1 font-medium">
                                Scan QR Code ini ke Admin untuk proses check-in/out
                            </p>
                        </div>
                        <div
                            v-else
                            class="py-6 px-4 bg-red-50/50 rounded-2xl border border-red-100 mb-2"
                        >
                            <div
                                class="h-16 w-16 mx-auto bg-red-100 rounded-full flex items-center justify-center text-red-500 mb-3"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                                    <line x1="3" x2="21" y1="9" y2="9" />
                                    <path d="M9 13h.01" />
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-red-800 mb-1">Tiket Belum Aktif</p>
                            <p class="text-xs font-medium text-red-600/80">
                                Selesaikan pembayaran Anda agar Barcode / QR Code E-Ticket muncul di
                                sini.
                            </p>
                        </div>
                    </div>

                    <!-- [UPDATE]: Garis Perforasi (Potongan Tiket) menggunakan elemen CSS murni tanpa gambar SVG -->
                    <div
                        class="relative flex items-center w-full h-8 -my-4 z-10 pointer-events-none"
                    >
                        <div
                            class="absolute left-0 w-5 h-10 bg-surface-50 rounded-r-full border-r border-y border-surface-200 -ml-px shadow-[inset_3px_0_5px_rgba(0,0,0,0.03)]"
                        ></div>
                        <div class="w-full border-t-2 border-dashed border-surface-200 mx-5"></div>
                        <div
                            class="absolute right-0 w-5 h-10 bg-surface-50 rounded-l-full border-l border-y border-surface-200 -mr-px shadow-[inset_-3px_0_5px_rgba(0,0,0,0.03)]"
                        ></div>
                    </div>

                    <!-- [UPDATE]: Bagian Bawah Tiket (Ringkasan Pesanan) yang dirapatkan spacingnya (p-6) -->
                    <div class="p-6 bg-white">
                        <h2
                            class="text-xs font-extrabold text-surface-400 mb-4 tracking-widest uppercase text-center"
                        >
                            Ringkasan Pesanan
                        </h2>

                        <div class="space-y-3 mb-6">
                            <div
                                v-for="item in rental.items || []"
                                :key="item.id"
                                class="flex items-center justify-between gap-4 border-b border-surface-100/60 pb-3 last:border-0 last:pb-0"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-surface-800">
                                        {{ item.product_name }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-surface-500 font-medium">
                                        Qty: {{ item.quantity }} •
                                        {{ item.item_type === 'product' ? 'Produk' : 'Paket' }}
                                    </p>
                                </div>
                                <p class="shrink-0 text-sm font-extrabold text-surface-900">
                                    {{ formatCurrency(item.subtotal) }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-3xl bg-surface-50 p-5 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-surface-500 font-medium">Subtotal</span>
                                <span class="font-bold text-surface-800">{{
                                    formatCurrency(rental.subtotal)
                                }}</span>
                            </div>
                            <div
                                v-if="rental.discount_amount > 0"
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-surface-500 font-medium">Diskon</span>
                                <span class="font-bold text-emerald-600"
                                    >-{{ formatCurrency(rental.discount_amount) }}</span
                                >
                            </div>
                            <div
                                v-if="rental.late_fee > 0"
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-surface-500 font-medium">Denda (Late Fee)</span>
                                <span class="font-bold text-red-600"
                                    >+{{ formatCurrency(rental.late_fee) }}</span
                                >
                            </div>
                            <div
                                class="pt-4 mt-3 border-t border-surface-200/60 flex items-center justify-between"
                            >
                                <span
                                    class="font-extrabold text-surface-900 text-sm uppercase tracking-tight"
                                    >Total Lunas</span
                                >
                                <span class="font-black text-primary-600 text-xl">{{
                                    formatCurrency(rental.grand_total)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <ReorderModal
                    :open="reorderOpen"
                    :items="reorderSuggestions"
                    @close="reorderOpen = false"
                />
            </section>
        </div>
        <!-- End of DESKTOP VIEW -->

        <!-- ========================================================= -->
    </DefaultLayout>
</template>

<style scoped>
    /* [UPDATE]: Animasi masuk yang sangat halus untuk memberikan kesan premium / high-end */
    @keyframes fadeUp {
        0% {
            opacity: 0;
            transform: translateY(30px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-up {
        animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
</style>
