<script setup>
// Komentar: Menggunakan Vue 3 Composition API untuk menjaga LCP, CLS, INP 100% Hijau.
// Desain telah dirombak total menjadi versi "Premium Auto-Submit" sesuai instruksi.
import { ref, watch, computed } from 'vue'
import { X, Check, MessageSquarePlus } from 'lucide-vue-next'

const props = defineProps({
    show: Boolean,
    product: Object,
    isPosMode: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'addToCart'])

const isVisible = ref(props.show)
const selectedSize = ref(null)
const selectedColor = ref(null)
const showNotes = ref(false)
const notes = ref('')

// Komentar: Reset state saat pop-up dibuka/ditutup
watch(() => props.show, (val) => {
    isVisible.value = val
    if (val) {
        selectedSize.value = null
        selectedColor.value = null
        showNotes.value = false
        notes.value = ''
        
        // Auto-select jika hanya ada 1 pilihan
        if (availableSizes.value.length === 1) selectedSize.value = availableSizes.value[0]
        if (availableColors.value.length === 1) selectedColor.value = availableColors.value[0]
    }
})

const close = () => {
    isVisible.value = false
    setTimeout(() => {
        emit('close')
    }, 300)
}

// Komentar: Mengambil varian yang valid (bukan dummy)
const realVariants = computed(() => {
    return props.product?.variants?.filter(v => v.size || v.color) || []
})

const hasVariants = computed(() => realVariants.value.length > 0)

const availableSizes = computed(() => {
    const sizes = realVariants.value.map(v => v.size).filter(s => s)
    return [...new Set(sizes)]
})

const availableColors = computed(() => {
    const colors = realVariants.value.map(v => v.color).filter(c => c)
    return [...new Set(colors)]
})

// Komentar: Pemetaan Warna Dinamis (Color Dictionary)
// Mengubah teks inputan admin menjadi warna visual (Hex/CSS)
const COLOR_MAP = {
    'merah': 'bg-rose-500 ring-rose-500',
    'biru': 'bg-blue-500 ring-blue-500',
    'hijau': 'bg-emerald-500 ring-emerald-500',
    'kuning': 'bg-amber-400 ring-amber-400',
    'hitam': 'bg-slate-900 ring-slate-900',
    'putih': 'bg-white border border-slate-200 ring-slate-300 text-slate-900',
    'abu-abu': 'bg-slate-400 ring-slate-400',
    'navy': 'bg-indigo-800 ring-indigo-800',
    'coklat': 'bg-amber-800 ring-amber-800',
    'oren': 'bg-orange-500 ring-orange-500',
    'pink': 'bg-pink-500 ring-pink-500',
    'ungu': 'bg-purple-500 ring-purple-500',
}

const getColorClass = (c) => {
    const key = c?.toLowerCase().trim()
    // Jika tidak ada di map, gunakan fallback warna netral
    return COLOR_MAP[key] || 'bg-slate-200 border border-slate-300 ring-slate-400 text-slate-700'
}

// Komentar: Validasi kombinasi ukuran & warna
const isColorAvailable = (color) => {
    if (!selectedSize.value) return true
    return realVariants.value.some(v => v.size === selectedSize.value && v.color === color)
}
const isSizeAvailable = (size) => {
    if (!selectedColor.value) return true
    return realVariants.value.some(v => v.color === selectedColor.value && v.size === size)
}

const isSelectionComplete = computed(() => {
    if (!hasVariants.value) return true
    const needsSize = availableSizes.value.length > 0
    const needsColor = availableColors.value.length > 0
    
    if (needsSize && needsColor) return selectedSize.value && selectedColor.value
    if (needsSize) return selectedSize.value
    if (needsColor) return selectedColor.value
    return true
})

// Komentar: Logika One-Click Auto Submit
const selectSize = (size) => {
    if (!isSizeAvailable(size)) return
    
    // Toggle off jika diklik lagi
    if (selectedSize.value === size) {
        selectedSize.value = null
        return
    }
    
    selectedSize.value = size
    checkAutoSubmit()
}

const selectColor = (color) => {
    if (!isColorAvailable(color)) return
    
    // Toggle off jika diklik lagi
    if (selectedColor.value === color) {
        selectedColor.value = null
        return
    }
    
    selectedColor.value = color
    checkAutoSubmit()
}

const checkAutoSubmit = () => {
    // Jika user tidak sedang membuka catatan, kita auto-submit
    if (isSelectionComplete.value && !showNotes.value) {
        // Jeda sangat halus (300ms) agar efek animasi klik terlihat sebelum pop-up tertutup
        setTimeout(() => {
            submitFinal()
        }, 300)
    }
}

const submitFinal = () => {
    // Cari ID varian yang sesuai dengan kombinasi ukuran & warna
    let variantId = null
    if (hasVariants.value) {
        const matched = realVariants.value.find(v => {
            const matchSize = availableSizes.value.length === 0 || v.size === selectedSize.value
            const matchColor = availableColors.value.length === 0 || v.color === selectedColor.value
            return matchSize && matchColor
        })
        if (matched) variantId = matched.id
    }

    emit('addToCart', {
        product_id: props.product.id,
        variant_id: variantId,
        notes: notes.value
    })
    close()
}

// Komentar: Jika produk tidak memiliki varian sama sekali, saat pop-up terbuka,
// kita akan langsung auto-submit jika user tidak perlu catatan.
// Namun karena user di awal mengklik produk ini dari luar, pop-up ini sebaiknya tidak terbuka jika tidak ada varian.
// Kondisi fallback di bawah dibuat berjaga-jaga.
</script>

<template>
    <Teleport to="body">
        <div v-if="isVisible" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Komentar: Backdrop blur untuk fokus visual (Glassmorphism) -->
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="close"></div>
            
            <!-- Komentar: Modal Content (Dipersempit menjadi max-w-[340px] agar mungil, elegan, dan premium) -->
            <div class="relative w-full max-w-[340px] overflow-hidden rounded-[24px] bg-white shadow-2xl shadow-cyan-900/10 transition-all animate-in zoom-in-95 duration-200">
                
                <!-- Komentar: Header Minimalis Tanpa Garis Bawah Tegas -->
                <div class="p-5 pb-3 flex items-start justify-between">
                    <div class="pr-4">
                        <h3 class="text-[15px] font-black text-slate-900 leading-tight">{{ product?.name }}</h3>
                        <p class="text-[11px] font-semibold text-slate-400 mt-1">Pilih spesifikasi</p>
                    </div>
                    <!-- Komentar: Tombol Close Modern -->
                    <button @click="close" class="shrink-0 rounded-full p-1.5 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <X class="h-4 w-4" stroke-width="2.5" />
                    </button>
                </div>

                <!-- Komentar: Body Content dengan Jarak (Spacing) Halus -->
                <div class="p-5 pt-2 space-y-6">
                    
                    <template v-if="hasVariants">
                        
                        <!-- Komentar: Bagian Ukuran (Pill Buttons) -->
                        <div v-if="availableSizes.length > 0">
                            <label class="mb-3 block text-[10px] font-bold uppercase tracking-widest text-slate-400">Ukuran</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="size in availableSizes" :key="size" type="button"
                                    @click="selectSize(size)"
                                    :disabled="!isSizeAvailable(size)"
                                    class="relative px-4 py-1.5 rounded-full text-[12px] font-bold transition-all duration-200 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed disabled:active:scale-100"
                                    :class="[
                                        selectedSize === size 
                                            ? 'bg-slate-900 text-white shadow-md' 
                                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                                    ]"
                                >
                                    {{ size }}
                                </button>
                            </div>
                        </div>

                        <!-- Komentar: Bagian Warna (Color Swatches Bulat) -->
                        <div v-if="availableColors.length > 0">
                            <label class="mb-3 block text-[10px] font-bold uppercase tracking-widest text-slate-400">Warna</label>
                            <div class="flex flex-wrap gap-3">
                                <button v-for="color in availableColors" :key="color" type="button"
                                    @click="selectColor(color)"
                                    :disabled="!isColorAvailable(color)"
                                    :title="color"
                                    class="relative h-8 w-8 rounded-full transition-all duration-300 flex items-center justify-center disabled:opacity-20 disabled:cursor-not-allowed"
                                    :class="[
                                        getColorClass(color),
                                        selectedColor === color 
                                            ? 'ring-2 ring-offset-2 scale-110 shadow-md' 
                                            : 'ring-0 hover:scale-110 hover:shadow-sm'
                                    ]"
                                >
                                    <!-- Komentar: Tanda ceklis mungil muncul saat warna dipilih -->
                                    <Check v-if="selectedColor === color" class="h-4 w-4" :class="color.toLowerCase() === 'putih' ? 'text-slate-900' : 'text-white'" stroke-width="3" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Komentar: Fallback jika tidak ada varian -->
                    <div v-else class="text-center py-4">
                        <p class="text-[12px] font-medium text-slate-500">Pilih opsi catatan di bawah jika diperlukan, atau langsung klik Selesai.</p>
                    </div>

                    <!-- Komentar: Progressive Disclosure untuk Catatan Opsional -->
                    <div class="pt-2 border-t border-slate-50">
                        <label class="flex items-center gap-2 cursor-pointer group w-max">
                            <input type="checkbox" v-model="showNotes" class="sr-only" />
                            <div class="relative flex h-4 w-7 items-center rounded-full transition-colors duration-200" :class="showNotes ? 'bg-cyan-500' : 'bg-slate-200'">
                                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform duration-200" :class="showNotes ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                            </div>
                            <span class="text-[11px] font-bold text-slate-500 group-hover:text-slate-700 transition-colors flex items-center gap-1.5">
                                <MessageSquarePlus class="h-3.5 w-3.5" />
                                Tambah catatan (Opsional)
                            </span>
                        </label>

                        <!-- Komentar: Animasi meluncur turun untuk textarea -->
                        <div class="overflow-hidden transition-all duration-300" :class="showNotes ? 'max-h-[150px] opacity-100 mt-3' : 'max-h-0 opacity-0'">
                            <textarea v-model="notes" rows="2"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] font-medium text-slate-700 placeholder:text-slate-400 outline-none transition-all focus:border-cyan-500 focus:bg-white focus:ring-2 focus:ring-cyan-500/20 resize-none"
                                placeholder="Tulis catatan (misal: bungkus rapi)..."></textarea>
                        </div>
                    </div>
                    
                </div>

                <!-- Komentar: Tombol Selesai HANYA MUNCUL jika User membuka Catatan Opsional 
                     atau pop-up ini tidak memiliki varian sama sekali.
                     Sesuai janji: Tombol Batal & Masukkan Keranjang yang besar dihilangkan! -->
                <div v-if="showNotes || !hasVariants" class="p-3 bg-slate-50/50">
                    <button type="button" @click="submitFinal"
                        :disabled="!isSelectionComplete"
                        class="w-full h-10 rounded-[14px] bg-slate-900 text-[12px] font-bold text-white shadow-md hover:bg-slate-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        Selesai & Lanjutkan
                    </button>
                </div>

            </div>
        </div>
    </Teleport>
</template>
