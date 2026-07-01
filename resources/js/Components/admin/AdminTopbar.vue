<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    Menu
} from 'lucide-vue-next'

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    subtitle: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['toggle-sidebar'])

const page = usePage()
// Initialize with server time via props if needed, otherwise client time is fine.
const now = ref(new Date())
let timer = null

const user = computed(() => page.props.auth?.user || null)

const currentTime = computed(() => {
    return now.value.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    })
})

const currentDate = computed(() => {
    return now.value.toLocaleDateString('id-ID', {
        weekday: 'long', // Menampilkan Hari
        day: '2-digit',  // Menampilkan Tanggal
        month: 'long',   // Menampilkan Bulan lengkap
        year: 'numeric', // Menampilkan Tahun
    })
})

onMounted(() => {
    // Sinkronisasi detik secara real-time di sisi client
    timer = setInterval(() => {
        now.value = new Date()
    }, 1000)
})

onUnmounted(() => {
    if (timer) {
        clearInterval(timer)
    }
})
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur-md">
        <div class="flex min-h-[58px] items-center justify-between gap-4 px-4 sm:px-5 lg:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 lg:hidden"
                    @click="emit('toggle-sidebar')">
                    <Menu class="h-4 w-4" />
                </button>

                <!-- Kiri: Judul Halaman Konteks -->
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-slate-800">
                        {{ title }}
                    </h1>
                    <p v-if="subtitle" class="mt-0.5 truncate text-xs text-slate-500">
                        {{ subtitle }}
                    </p>
                </div>
            </div>

            <!-- Kanan: Jam & Tanggal Saja (Tanpa Redundansi) -->
            <div class="hidden items-center gap-3 sm:flex">
                <div class="text-right">
                    <p class="text-xs font-semibold tracking-wide text-slate-500">{{ currentTime }}</p>
                    <p class="text-[10px] uppercase tracking-wider text-slate-400">{{ currentDate }}</p>
                </div>
            </div>
        </div>
    </header>
</template>