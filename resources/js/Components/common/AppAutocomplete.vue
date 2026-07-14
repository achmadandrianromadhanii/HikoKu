<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Search, Loader2 } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps<{
    modelValue: string
    placeholder?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

// Komentar: State untuk menampung hasil pencarian, status loading, dan visibilitas pop-up
const results = ref<any[]>([])
const loading = ref(false)
const showDropdown = ref(false)
const wrapperRef = ref<HTMLElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

// Komentar: Fungsi pencarian pintar yang menembak API /search/autocomplete tanpa refresh
const performSearch = async (term: string) => {
    // Jika kolom kosong, reset semua
    if (term.trim().length < 1) {
        results.value = []
        showDropdown.value = false
        return
    }

    loading.value = true
    showDropdown.value = true
    
    try {
        const response = await axios.get('/search/autocomplete', {
            params: { term: term.trim() }
        })
        results.value = response.data.results || []
    } catch (error) {
        console.error('Search error:', error)
        results.value = []
    } finally {
        loading.value = false
    }
}

// Komentar: Menangkap input pengguna dan memberi jeda (debounce 300ms) agar server tidak di-spam
const handleInput = (event: Event) => {
    const value = (event.target as HTMLInputElement).value
    emit('update:modelValue', value)
    
    if (debounceTimer) clearTimeout(debounceTimer)
    
    debounceTimer = setTimeout(() => {
        performSearch(value)
    }, 300)
}

// Komentar: Menutup pop-up jika pengguna mengklik area di luar kotak pencarian
const closeDropdown = (e: MouseEvent) => {
    if (wrapperRef.value && !wrapperRef.value.contains(e.target as Node)) {
        showDropdown.value = false
    }
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        document.addEventListener('click', closeDropdown)
    }
})

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        document.removeEventListener('click', closeDropdown)
    }
})

// Format harga (Rupiah)
const formatPrice = (price: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price)
}
</script>

<template>
    <!-- Komentar: Lebar search container (w-full) akan dibatasi oleh elemen induk (Navbar) -->
    <div class="relative w-full" ref="wrapperRef">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <Search v-if="!loading" class="w-4 h-4 text-slate-500" />
            <Loader2 v-else class="w-4 h-4 text-cyan-500 animate-spin" />
        </div>
        
        <input 
            type="text" 
            :value="modelValue"
            @input="handleInput"
            @focus="modelValue.length > 0 && (showDropdown = true)"
            :placeholder="placeholder"
            class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all outline-none"
        >

        <!-- Komentar: Dropdown Pop-up dengan animasi transisi CSS murni yang mulus dan sangat ringan -->
        <transition 
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <!-- Dropdown Container -->
            <div v-if="showDropdown && (results.length > 0 || modelValue.trim().length > 0 && !loading)" 
                 class="absolute z-[100] mt-2 w-full min-w-[280px] bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                
                <!-- Jika Ada Hasil -->
                <ul v-if="results.length > 0" class="max-h-[350px] overflow-y-auto custom-scrollbar py-2">
                    <li v-for="product in results" :key="product.id">
                        <!-- Redirect langsung ke halaman produk spesifik -->
                        <Link :href="route('products.show', product.slug)"
                              class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors group"
                              @click="showDropdown = false">
                            <!-- Thumbnail Gambar Kecil -->
                            <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0 border border-slate-100 bg-slate-50 flex items-center justify-center">
                                <img v-if="product.main_image" :src="'/storage/' + product.main_image" :alt="product.name" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />
                                <Search v-else class="w-4 h-4 text-slate-300" />
                            </div>
                            
                            <!-- Detail (Nama dengan Highlight & Harga) -->
                            <div class="min-w-0 flex-1">
                                <!-- v-html digunakan agar tag <mark> (stabilo) dari server dapat dirender dengan benar -->
                                <p class="text-sm font-medium text-slate-700 truncate" v-html="product.highlighted_name || product.name"></p>
                                <p class="text-[11px] text-cyan-700 font-semibold mt-0.5">
                                    {{ formatPrice(product.final_price || product.price) }}
                                </p>
                            </div>
                        </Link>
                    </li>
                </ul>

                <!-- Jika Tidak Ada Hasil -->
                <div v-else-if="!loading" class="p-6 text-center">
                    <Search class="w-8 h-8 text-slate-200 mx-auto mb-2" />
                    <p class="text-sm text-slate-500 font-medium">Tidak ada hasil untuk "<span class="text-slate-800">{{ modelValue }}</span>"</p>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* Scrollbar khusus untuk dropdown */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
/* Komentar: Mengubah gaya highlight (stabilo) <mark> dari bawaan kuning menjadi transparan dengan font cyan-500 */
:deep(mark) {
    background-color: transparent;
    color: #06b6d4;
    font-weight: 700;
}
</style>
