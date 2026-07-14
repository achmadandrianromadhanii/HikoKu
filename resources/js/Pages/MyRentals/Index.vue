<script setup>
    import { computed, ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import { ChevronLeft } from 'lucide-vue-next';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import RentalCard from '@/Components/rental/RentalCard.vue';
    import Modal from '@/Components/Modal.vue';

    const props = defineProps({
        rentals: {
            type: Object,
            required: true,
        },
    });

    const rentalItems = computed(() => props.rentals?.data || []);

    // [UPDATE]: Logika hanya menampilkan tiket yang aktif di halaman utama
    const activeRentals = computed(() => {
        return rentalItems.value.filter((item) =>
            ['confirmed', 'active', 'overdue', 'pending_payment'].includes(item.status)
        );
    });

    // [UPDATE]: Logika untuk menampung riwayat (selesai/batal) yang akan ditampilkan di Modal
    const historyRentals = computed(() => {
        return rentalItems.value.filter((item) => ['returned', 'cancelled'].includes(item.status));
    });

    // [UPDATE]: State untuk mengontrol Toggle Switch dan kemunculan Modal
    const showHistory = ref(false);

    const toggleHistory = () => {
        showHistory.value = !showHistory.value;
    };
</script>

<template>
    <Head title="E-Ticket Saya" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
                <!-- [UPDATE]: Menghapus judul/teks yang dilingkari sesuai permintaan dan mengganti Tab menjadi Toggle Switch -->
                <div
                    class="mb-8 flex items-center justify-between bg-white px-5 py-4 rounded-2xl border border-surface-200 shadow-sm ring-1 ring-surface-100"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-primary-50 flex flex-shrink-0 items-center justify-center"
                        >
                            <svg
                                class="w-5 h-5 text-primary-600"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                ></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-surface-900 tracking-tight">
                                E-Ticket Aktif
                            </h2>
                            <p class="text-[10px] sm:text-xs text-surface-600 font-medium">
                                Tiket yang sedang berjalan
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-1.5">
                        <span
                            class="text-[9px] font-bold uppercase tracking-widest text-surface-600"
                            >Riwayat Selesai</span
                        >
                        <button
                            type="button"
                            @click="toggleHistory"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2"
                            :class="showHistory ? 'bg-primary-600' : 'bg-surface-200'"
                            role="switch"
                            :aria-checked="showHistory"
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="showHistory ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>
                </div>

                <div v-if="activeRentals.length > 0" class="flex flex-col gap-6">
                    <!-- [UPDATE]: Menambahkan animasi staggered cascade untuk setiap tiket -->
                    <RentalCard
                        v-for="(rental, index) in activeRentals"
                        :key="rental.id"
                        :rental="rental"
                        class="animate-fade-up opacity-0"
                        :style="{
                            animationDelay: `${index * 80}ms`,
                            animationFillMode: 'forwards',
                        }"
                    />
                </div>

                <div v-else class="user-empty-state mt-10">
                    <h2 class="text-lg font-bold text-surface-900">Belum ada pesanan aktif</h2>
                    <p class="mt-1 text-xs text-surface-600">
                        Tiket sewa Anda akan muncul di sini secara otomatis.
                    </p>
                </div>

                <!-- [UPDATE]: Modal Pop-Up Halus untuk menampilkan Riwayat Selesai/Batal -->
                <Modal :show="showHistory" @close="toggleHistory" maxWidth="2xl">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-extrabold text-surface-900 tracking-tight">
                                    Riwayat E-Ticket
                                </h3>
                                <p class="text-xs font-medium text-surface-600 mt-0.5">
                                    Daftar pesanan yang telah selesai atau batal
                                </p>
                            </div>
                            <button
                                @click="toggleHistory"
                                class="p-2 rounded-full hover:bg-surface-100 text-surface-600 hover:text-surface-600 transition-colors"
                            >
                                <svg
                                    class="w-5 h-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>

                        <!-- Scrollbar halus untuk riwayat -->
                        <div class="max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                            <div v-if="historyRentals.length > 0" class="flex flex-col gap-4 pb-2">
                                <RentalCard
                                    v-for="(rental, index) in historyRentals"
                                    :key="rental.id"
                                    :rental="rental"
                                    class="animate-fade-up opacity-0 border-surface-200"
                                    :style="{
                                        animationDelay: `${index * 50}ms`,
                                        animationFillMode: 'forwards',
                                    }"
                                />
                            </div>
                            <div
                                v-else
                                class="py-12 text-center bg-surface-50 rounded-2xl border border-dashed border-surface-200"
                            >
                                <p class="text-sm font-bold text-surface-600">Belum ada riwayat</p>
                            </div>
                        </div>
                    </div>
                </Modal>
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
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-up {
        animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
</style>
