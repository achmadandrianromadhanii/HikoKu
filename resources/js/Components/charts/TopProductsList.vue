<script setup>
import { computed } from 'vue'
import { Package } from 'lucide-vue-next'

const props = defineProps({
    labels: {
        type: Array,
        default: () => [],
    },
    values: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: 'Unit Disewa',
    },
})

// Gabungkan label dan value menjadi object
const chartItems = computed(() => {
    return props.labels.map((label, index) => ({
        label,
        value: Number(props.values[index] || 0),
    })).sort((a, b) => b.value - a.value) // Sort descending
})

// Cari nilai tertinggi untuk menghitung persentase lebar progress bar
const maxValue = computed(() => {
    return Math.max(...chartItems.value.map(item => item.value), 1)
})

const hasData = computed(() => chartItems.value.length > 0)

// Helper untuk inisial nama jika ingin dipakai (opsional, saat ini pakai icon)
const getInitials = (name) => {
    if (!name) return 'P'
    return name.substring(0, 2).toUpperCase()
}
</script>

<template>
    <div class="h-full w-full flex flex-col">
        <ul v-if="hasData" class="flex-1 flex flex-col gap-3">
            <!-- Render List Item dengan efek hover premium -->
            <li v-for="(item, index) in chartItems" :key="index"
                class="group flex items-center gap-4 rounded-2xl p-2.5 transition-all duration-300 hover:bg-slate-50">
                
                <!-- Avatar / Icon -->
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-cyan-700 group-hover:shadow-sm transition-all duration-300">
                    <Package class="h-5 w-5" />
                </div>

                <!-- Informasi Produk & Progress Bar -->
                <div class="flex flex-1 flex-col justify-center min-w-0">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="truncate text-[13px] font-bold text-slate-700 group-hover:text-cyan-800 transition-colors">
                            {{ item.label }}
                        </span>
                        <span class="text-[13px] font-bold text-slate-900 ml-3">
                            {{ item.value }}
                        </span>
                    </div>
                    
                    <!-- Thin Progress Bar Background -->
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <!-- Progress Bar Fill (Gradient) -->
                        <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 transition-all duration-1000 ease-out"
                            :style="{ width: `${(item.value / maxValue) * 100}%` }">
                        </div>
                    </div>
                </div>
            </li>
        </ul>

        <!-- Empty State -->
        <div v-else class="flex h-full min-h-[200px] items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
            <p class="text-sm font-medium text-slate-500">Belum ada produk terjual.</p>
        </div>
    </div>
</template>
