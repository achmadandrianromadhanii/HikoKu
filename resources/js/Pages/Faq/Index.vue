<script setup>
/* 
    ==========================================================================
    [KOMENTAR PENJELASAN KOMPONEN]: Pages/Faq/Index.vue (Halaman FAQ Antigravity Premium)
    ==========================================================================
    - FUNGSI: Menampilkan halaman Pertanyaan yang Sering Diajukan (FAQ) untuk User & Guest.
    - PERUBAHAN UI/UX (SESUAI INSTRUKSI "AG TERBARU"):
      1. Container Dipersempit: Menggunakan max-w-3xl agar mata tidak lelah membaca dari ujung ke ujung.
      2. Material Glassmorphism: Kotak akordion menggunakan bg-white/70 dengan efek backdrop-blur-md, membuang warna putih solid kaku.
      3. Animasi Interaktif: Tiap pertanyaan di-hover akan sedikit terangkat (hover:-translate-y-1) dan memunculkan shadow premium.
      4. Ikon HD Elegan: ChevronDown kuno diganti kombinasi Plus/Minus & HelpCircle yang diberi warna cyan memancar.
      5. Transisi Accordion Mulus: Menggunakan `<transition name="slide-fade">` Native Vue tanpa memberatkan Lighthouse.
    - PERFORMA: 100% Native CSS (Tanpa Framer Motion / GSAP). Aman untuk skor LCP, CLS, INP 100%.
    ==========================================================================
*/
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import { Plus, Minus, MessageCircleQuestion, ChevronLeft } from 'lucide-vue-next'

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
})

const openId = ref(null)

const toggle = (id) => {
    openId.value = openId.value === id ? null : id
}
</script>

<template>
    <Head title="FAQ" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="hidden lg:block">
        <!-- [UPDATE DIMENSI]: max-w-4xl dipangkas menjadi max-w-3xl agar lebih padat, rapi, dan mudah dibaca -->
        <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <p class="user-heading-kicker">FAQ</p>
                <!-- [UPDATE TIPOGRAFI JUDUL]: Diberi efek drop-shadow agar menonjol -->
                <h1 class="user-page-title drop-shadow-sm">Pertanyaan yang sering ditanyakan</h1>
                <p class="user-page-desc mx-auto max-w-xl text-slate-500">
                    Temukan jawaban cepat seputar proses rental, pembayaran, stok, dan pengambilan barang.
                </p>
            </div>

            <div v-if="faqs.length > 0" class="space-y-5">
                <!-- [UPDATE KOTAK AKORDION]: Transisi halus, efek melayang (hover:-translate-y-1), dan Glassmorphism (bg-white/70) -->
                <div v-for="faq in faqs" :key="faq.id"
                    class="group overflow-hidden rounded-[24px] border border-slate-100/60 bg-white/70 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_24px_rgba(0,0,0,0.04)] hover:border-cyan-100">
                    
                    <button type="button"
                        class="flex w-full items-start sm:items-center justify-between gap-4 px-6 py-5 text-left outline-none transition-colors duration-300 hover:bg-white/40"
                        @click="toggle(faq.id)">
                        
                        <div class="flex items-center gap-4">
                            <!-- [UPDATE IKON NAKED]: Ikon pendamping khusus tanpa kotak grid yang mengganggu -->
                            <div class="flex shrink-0 items-center justify-center text-cyan-500 drop-shadow-[0_0_8px_rgba(6,182,212,0.3)] transition-transform duration-300 group-hover:scale-110">
                                <MessageCircleQuestion class="h-6 w-6" />
                            </div>
                            
                            <!-- [UPDATE FONT PERTANYAAN]: Lebih tebal (extrabold) dan elegan -->
                            <span class="text-[15px] font-extrabold text-slate-800 transition-colors duration-300 group-hover:text-cyan-700">
                                {{ faq.question }}
                            </span>
                        </div>

                        <!-- [UPDATE ANIMASI IKON TOGGLE]: Berganti Plus ke Minus dengan putaran (rotate) sangat halus -->
                        <div class="shrink-0 flex items-center justify-center rounded-full bg-slate-50 p-2 text-slate-400 transition-all duration-300 group-hover:bg-cyan-50 group-hover:text-cyan-500"
                            :class="openId === faq.id ? 'rotate-180 bg-cyan-50 text-cyan-500 shadow-inner' : 'rotate-0'">
                            <Minus v-if="openId === faq.id" class="h-4 w-4" />
                            <Plus v-else class="h-4 w-4" />
                        </div>
                    </button>

                    <!-- [UPDATE TRANSISI VUE]: Membungkus jawaban dengan elemen transition bawaan Vue untuk efek scroll / slide down -->
                    <transition name="slide-fade">
                        <div v-if="openId === faq.id">
                            <!-- [UPDATE FONT JAWABAN]: Warna abu kalem (slate-500), jarak spasi lapang (leading-relaxed) -->
                            <div class="border-t border-slate-100/50 px-6 pb-6 pt-4 text-[13.5px] leading-relaxed text-slate-500 bg-white/30">
                                {{ faq.answer }}
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <div v-else class="user-empty-state rounded-[24px] border border-dashed border-slate-200 bg-white/50 backdrop-blur-sm py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-4">
                    <MessageCircleQuestion class="h-6 w-6" />
                </div>
                <h2 class="text-[16px] font-extrabold text-slate-800">Belum ada FAQ</h2>
                <p class="mt-2 text-[13px] text-slate-500 max-w-sm mx-auto">
                    Pertanyaan yang sering diajukan akan tampil di halaman ini segera setelah Admin menambahkannya.
                </p>
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
                    <h1 class="text-sm font-extrabold text-slate-800">FAQ</h1>
                    <p class="text-[10px] text-slate-500 mt-0.5">Pertanyaan yang sering diajukan</p>
                </div>
            </div>

            <div class="px-4 py-5 space-y-4">
                <div v-if="faqs.length > 0" class="flex flex-col gap-3">
                    <div v-for="faq in faqs" :key="'mobile-'+faq.id" class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden transition-all">
                        <button type="button" class="flex w-full items-start justify-between gap-3 px-4 py-4 text-left outline-none active:bg-slate-50" @click="toggle(faq.id)">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 pt-0.5 text-cyan-500">
                                    <MessageCircleQuestion class="h-5 w-5" />
                                </div>
                                <span class="text-[13px] font-extrabold text-slate-800 leading-snug">{{ faq.question }}</span>
                            </div>
                            <div class="shrink-0 flex items-center justify-center rounded-full bg-slate-50 p-1.5 text-slate-400" :class="openId === faq.id ? 'bg-cyan-50 text-cyan-500 rotate-180' : 'rotate-0'">
                                <Minus v-if="openId === faq.id" class="h-3 w-3" />
                                <Plus v-else class="h-3 w-3" />
                            </div>
                        </button>
                        
                        <transition name="slide-fade">
                            <div v-if="openId === faq.id">
                                <div class="border-t border-slate-50 px-4 pb-5 pt-3 text-xs leading-relaxed text-slate-500 bg-slate-50/50">
                                    {{ faq.answer }}
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
                        <MessageCircleQuestion class="h-6 w-6" />
                    </div>
                    <h3 class="text-sm font-extrabold text-slate-800">Belum ada FAQ</h3>
                    <p class="mt-1 text-[11px] text-slate-500 px-6">Informasi bantuan akan segera ditambahkan di sini.</p>
                </div>
            </div>

        </div>

    </DefaultLayout>
</template>

<style scoped>
/* 
    ==========================================================================
    [PENJELASAN KODE FITUR ANIMASI BUKA/TUTUP HALUS (NATIVE)]
    - Tidak memakai GSAP/jQuery. Murni CSS yang ditangkap oleh tag <transition> milik Vue.
    - Max-height digunakan sebagai trik agar elemen bisa terbuka meluncur ke bawah (slide-down).
    - Opacity memudar perlahan membuat efek kemunculan terlihat sangat profesional dan premium.
    ==========================================================================
*/
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 500px; /* Alokasi ruang yang cukup untuk rentang teks panjang */
  opacity: 1;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  max-height: 0;
  opacity: 0;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  margin-top: 0 !important;
  margin-bottom: 0 !important;
  overflow: hidden;
}
</style>