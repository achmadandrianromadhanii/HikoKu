<script setup>
import { Head, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import { Phone, Mail, MapPin, ChevronLeft } from 'lucide-vue-next'

const page = usePage()

const settings = computed(() => page.props.settings?.public || {})

const form = ref({
    name: '',
    phone: '',
    message: '',
})

const mapLink = computed(() => {
    const lat = settings.value.store_lat
    const lng = settings.value.store_lng
    if (!lat || !lng) return '#'
    return `https://maps.google.com/?q=${lat},${lng}`
})

const osmEmbed = computed(() => {
    const lat = settings.value.store_lat
    const lng = settings.value.store_lng
    if (!lat || !lng) return ''
    return `https://www.openstreetmap.org/export/embed.html?bbox=${lng}%2C${lat}%2C${lng}%2C${lat}&layer=mapnik&marker=${lat}%2C${lng}`
})

const sendWhatsApp = () => {
    const waNumber = String(settings.value.wa_number || '').replace(/[^\d]/g, '')
    if (!waNumber) return

    const message = `Halo GearHike,%0A%0ANama: ${encodeURIComponent(form.value.name)}%0ANomor HP: ${encodeURIComponent(form.value.phone)}%0APesan: ${encodeURIComponent(form.value.message)}`
    window.location.href = `https://wa.me/${waNumber}?text=${message}`
}
</script>

<template>

    <Head title="Kontak" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="hidden lg:block">
        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-primary-600">Kontak</p>
                <h1 class="mt-1 text-3xl font-bold text-gray-900">Hubungi GearHike</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Punya pertanyaan tentang alat, stok, atau proses rental? Hubungi kami dengan cepat.
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
                                    <p class="mt-1 text-sm text-gray-600">{{ settings.store_phone || '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Mail class="mt-0.5 h-5 w-5 text-primary-600" />
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Email</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ settings.store_email || '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <MapPin class="mt-0.5 h-5 w-5 text-primary-600" />
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Alamat</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ settings.store_address || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                        <iframe v-if="osmEmbed" :src="osmEmbed" class="h-80 w-full" loading="lazy" />
                        <div v-else class="flex h-80 items-center justify-center bg-gray-100 text-sm text-gray-400">
                            Lokasi belum tersedia
                        </div>
                    </div>

                    <a :href="mapLink" target="_blank" rel="noopener noreferrer"
                        class="inline-flex h-11 items-center rounded-xl border border-gray-200 px-5 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-600">
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
                            <label class="mb-2 block text-sm font-semibold text-gray-900">Nama</label>
                            <input v-model="form.name" type="text"
                                class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                                placeholder="Nama kamu" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900">Nomor HP</label>
                            <input v-model="form.phone" type="text"
                                class="h-12 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                                placeholder="08xxxxxxxxxx" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900">Pesan</label>
                            <textarea v-model="form.message" rows="6"
                                class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-100"
                                placeholder="Tulis pertanyaan kamu di sini..." />
                        </div>

                        <button type="button"
                            class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700"
                            @click="sendWhatsApp">
                            Kirim via WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </section>
        </div> <!-- End of DESKTOP VIEW -->


        <!-- ========================================================= -->
        <!-- [MOBILE VIEW]: TAMPILAN BARU KHUSUS MOBILE (block lg:hidden)-->
        <!-- ========================================================= -->
        <div class="block lg:hidden bg-slate-50 min-h-screen relative pb-10">
            
            <!-- Mobile Header -->
            <div class="bg-white px-4 py-4 shadow-sm border-b border-slate-100 flex items-center gap-3 sticky top-14 z-20">
                <button type="button" @click="() => window.history.length > 1 ? window.history.back() : router.visit(route('home'))" class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600 active:scale-95">
                    <ChevronLeft class="h-5 w-5" />
                </button>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-800">Hubungi Kami</h1>
                    <p class="text-[10px] text-slate-500 mt-0.5">Layanan bantuan pelanggan</p>
                </div>
            </div>

            <div class="px-4 py-5 space-y-6">
                
                <!-- Info Toko Card -->
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="text-[10px] font-extrabold text-slate-400 tracking-widest uppercase mb-4">Informasi Toko</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-cyan-500">
                                <Phone class="h-3.5 w-3.5" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase">Telepon</p>
                                <p class="text-sm font-bold text-slate-800">{{ settings.store_phone || '-' }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-cyan-500">
                                <Mail class="h-3.5 w-3.5" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase">Email</p>
                                <p class="text-sm font-bold text-slate-800">{{ settings.store_email || '-' }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-cyan-500">
                                <MapPin class="h-3.5 w-3.5" />
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase">Alamat</p>
                                <p class="text-xs font-bold text-slate-800 leading-snug">{{ settings.store_address || '-' }}</p>
                                <a :href="mapLink" target="_blank" rel="noopener noreferrer" class="mt-1.5 inline-block text-[11px] font-bold text-cyan-600 underline">Buka di Peta</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fast Message Form -->
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="text-[10px] font-extrabold text-slate-400 tracking-widest uppercase mb-2">Kirim Pesan WhatsApp</h2>
                    <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">Pesan ini akan otomatis membuka aplikasi WhatsApp dengan pesan Anda.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Nama Lengkap</label>
                            <input v-model="form.name" type="text" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-800 outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-2 focus:ring-cyan-100" placeholder="Contoh: John Doe" />
                        </div>
                        
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Nomor Telepon</label>
                            <input v-model="form.phone" type="tel" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-800 outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-2 focus:ring-cyan-100" placeholder="08..." />
                        </div>
                        
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Isi Pesan</label>
                            <textarea v-model="form.message" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-bold text-slate-800 outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-2 focus:ring-cyan-100" placeholder="Tuliskan pesan..." />
                        </div>

                        <button type="button" @click="sendWhatsApp" class="flex w-full items-center justify-center gap-2 rounded-full bg-[#25D366] py-3.5 text-xs font-bold text-white shadow-md shadow-[#25D366]/20 active:scale-95 transition-transform">
                            Kirim via WhatsApp
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </DefaultLayout>
</template>