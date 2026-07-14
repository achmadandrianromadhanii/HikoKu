<script setup lang="ts">
import AppModal from '@/Components/ui/AppModal.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps<{
    open: boolean
    items: Array<{
        id: number
        name: string
        price: number
        slug: string
        brand: { name: string } | null
        cover_image: string | null
    }>
}>()

const emit = defineEmits(['close'])
</script>

<template>
    <AppModal :open="open" title="Pesan Ulang Perlengkapan" @close="emit('close')" max-width="max-w-lg">
        <div class="space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar pr-2">
            <p class="text-sm text-slate-500 mb-4">
                Berikut adalah perlengkapan dari rental ini yang bisa kamu pesan ulang.
            </p>
            
            <div v-for="item in items" :key="item.id" class="flex items-center gap-4 p-3 border border-slate-100 rounded-xl hover:bg-slate-50 transition">
                <img v-if="item.cover_image" :src="'/storage/' + item.cover_image" class="w-16 h-16 object-cover rounded-lg" />
                <div v-else class="w-16 h-16 bg-slate-100 rounded-lg flex items-center justify-center">
                    <span class="text-xs text-slate-500">No Image</span>
                </div>
                
                <div class="flex-1 min-w-0">
                    <h4 class="font-bold text-slate-900 truncate">{{ item.name }}</h4>
                    <p class="text-xs text-slate-500 truncate">{{ item.brand?.name || 'Tanpa Merek' }}</p>
                    <p class="text-sm font-semibold text-cyan-700 mt-1">Rp {{ item.price.toLocaleString('id-ID') }}</p>
                </div>
                
                <Link :href="route('catalog.show', item.slug)" class="px-4 py-2 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-cyan-700 transition">
                    Lihat
                </Link>
            </div>
            
            <div v-if="items.length === 0" class="text-center py-8 text-slate-500 text-sm">
                Tidak ada perlengkapan yang tersedia untuk dipesan ulang.
            </div>
        </div>
        
        <template #footer>
            <div class="flex justify-end">
                <button type="button" @click="emit('close')" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                    Tutup
                </button>
            </div>
        </template>
    </AppModal>
</template>
