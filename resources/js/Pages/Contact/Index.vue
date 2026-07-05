<script setup>
    import { Head, usePage, router } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import { Phone, Mail, MapPin, ChevronLeft } from 'lucide-vue-next';

    const page = usePage();

    const settings = computed(() => page.props.settings?.public || {});

    const form = ref({
        name: '',
        phone: '',
        message: '',
    });

    const mapLink = computed(() => {
        const lat = settings.value.store_lat;
        const lng = settings.value.store_lng;
        if (!lat || !lng) return '#';
        return `https://maps.google.com/?q=${lat},${lng}`;
    });

    const osmEmbed = computed(() => {
        const lat = settings.value.store_lat;
        const lng = settings.value.store_lng;
        if (!lat || !lng) return '';
        return `https://www.openstreetmap.org/export/embed.html?bbox=${lng}%2C${lat}%2C${lng}%2C${lat}&layer=mapnik&marker=${lat}%2C${lng}`;
    });

    const sendWhatsApp = () => {
        const waNumber = String(settings.value.wa_number || '').replace(/[^\d]/g, '');
        if (!waNumber) return;

        const message = `Halo GearHike,%0A%0ANama: ${encodeURIComponent(form.value.name)}%0ANomor HP: ${encodeURIComponent(form.value.phone)}%0APesan: ${encodeURIComponent(form.value.message)}`;
        window.location.href = `https://wa.me/${waNumber}?text=${message}`;
    };
</script>

<template>
    <Head title="Kontak" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <p class="text-sm font-semibold text-primary-600">Kontak</p>
                    <h1 class="mt-1 text-3xl font-bold text-gray-900">Hubungi GearHike</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Punya pertanyaan tentang alat, stok, atau proses rental? Hubungi kami dengan
                        cepat.
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="space-y-6">
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-bold text-gray-900">Informasi Toko</h2>

                            <div class="mt-5 space-y-4">
                                <div class="flex items-start gap-3">
                                    <Phone class="mt-0.5 h-5 w-5 text-primary-600" />
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Telepon</p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ settings.store_phone || '-' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <Mail class="mt-0.5 h-5 w-5 text-primary-600" />
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Email</p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ settings.store_email || '-' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <MapPin class="mt-0.5 h-5 w-5 text-primary-600" />
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Alamat</p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ settings.store_address || '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm"
                        >
                            <iframe
                                v-if="osmEmbed"
                                :src="osmEmbed"
                                class="h-80 w-full"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="flex h-80 items-center justify-center bg-gray-100 text-sm text-gray-400"
                            >
                                Lokasi belum tersedia
                            </div>
                        </div>

                        <a
                            :href="mapLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-11 items-center rounded-xl border border-gray-200 px-5 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-600"
                        >
                            Buka di Google Maps
                        </a>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-gray-900">Kirim Pesan Cepat</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Form ini akan langsung membuka WhatsApp dengan pesan yang sudah diisi.
                        </p>

                        <div class="mt-6 space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-900"
                                    >Nama</label
                                >
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                                    placeholder="Nama kamu"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-900"
                                    >Nomor HP</label
                                >
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                                    placeholder="08xxxxxxxxxx"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-900"
                                    >Pesan</label
                                >
                                <textarea
                                    v-model="form.message"
                                    rows="6"
                                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                                    placeholder="Tulis pertanyaan kamu di sini..."
                                />
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700"
                                @click="sendWhatsApp"
                            >
                                Kirim via WhatsApp
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- End of DESKTOP VIEW -->

        <!-- ========================================================= -->
    </DefaultLayout>
</template>
