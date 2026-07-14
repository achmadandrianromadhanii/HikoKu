<script setup>
import { Head, router } from '@inertiajs/vue3'
import { onMounted, onUnmounted, ref } from 'vue'
import AdminSidebar from '@/Components/admin/AdminSidebar.vue'
import AdminTopBar from '@/Components/admin/AdminTopbar.vue'
import AppToast from '@/Components/ui/AppToast.vue'
import { useToast } from 'vue-toastification'

const props = defineProps({
    title: {
        type: String,
        default: 'Admin Panel',
    },
    subtitle: {
        type: String,
        default: '',
    },
})

const sidebarOpen = ref(false)
// Komentar: State reaktif untuk menyimpan status Sidebar Desktop (Mini Sidebar / Collapsed)
const sidebarCollapsed = ref(false)
const toast = useToast()

const openSidebar = () => {
    sidebarOpen.value = true
}

// Komentar: Fungsi untuk memicu perubahan antara mode lebar (230px) dan sempit (80px) di Desktop, dan menyimpannya di browser agar ingat saat refresh
const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
    if (typeof window !== 'undefined') {
        localStorage.setItem('hiko_sidebar_collapsed', sidebarCollapsed.value.toString())
    }
}

const closeSidebar = () => {
    sidebarOpen.value = false
}

const handleResize = () => {
    if (window.innerWidth >= 1024) {
        sidebarOpen.value = false
    }
}

onMounted(() => {
    // Komentar: Membaca status terakhir sidebar saat memuat halaman (Persistensi LocalStorage)
    if (typeof window !== 'undefined') {
        const savedState = localStorage.getItem('hiko_sidebar_collapsed')
        if (savedState) {
            sidebarCollapsed.value = savedState === 'true'
        }
    }

    handleResize()
    window.addEventListener('resize', handleResize)

    // Inisialisasi Listener Laravel Echo untuk Notifikasi Real-Time
    if (typeof window !== 'undefined' && window.Echo) {
        window.Echo.channel('admin-notifications')
            .listen('.NewRentalCreated', (e) => {
                if (toast) toast.success(e.message || 'Pesanan Rental Baru!', { timeout: 5000 })
                // Refresh data tanpa refresh halaman jika ada props terkait di halaman tersebut
                router.reload({ only: ['rentals', 'stats', 'rows', 'payments', 'chartData'], preserveScroll: true, preserveState: true })
            })
            .listen('.PaymentSuccess', (e) => {
                if (toast) toast.info(e.message || 'Pembayaran berhasil dikonfirmasi!', { timeout: 5000 })
                router.reload({ only: ['rentals', 'stats', 'rows', 'payments', 'chartData'], preserveScroll: true, preserveState: true })
            })
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    
    // Cleanup listener untuk mencegah memori leak / double firing
    if (typeof window !== 'undefined' && window.Echo) {
        window.Echo.leaveChannel('admin-notifications')
    }
})
</script>

<template>

    <Head :title="title" />

    <div class="h-screen overflow-hidden bg-slate-50 text-slate-800 flex flex-col">
        <!-- Komentar: Mengirimkan prop collapsed dan mendengarkan event toggle-collapse dari sidebar -->
        <AdminSidebar :open="sidebarOpen" :collapsed="sidebarCollapsed" @close="closeSidebar" @toggle-collapse="toggleCollapse" />

        <!-- Komentar: Transisi padding CSS murni, tidak menggunakan JS animasi agar LCP/CLS aman 100% -->
        <div class="flex-1 flex flex-col min-h-0 transition-all duration-300 ease-in-out"
             :class="sidebarCollapsed ? 'lg:pl-[80px]' : 'lg:pl-[230px]'">
            <AdminTopBar :title="title" :subtitle="subtitle" @toggle-sidebar="openSidebar" class="shrink-0 z-20" />

            <!-- Main container FIXED, konten di dalamnya yang mengatur scroll sendiri -->
            <main class="flex-1 flex flex-col min-h-0 relative">
                <div class="px-4 py-4 sm:px-5 lg:px-6 mx-auto w-full max-w-[1600px] h-full flex flex-col min-h-0 overflow-y-auto overflow-x-hidden">
                    <slot />
                </div>
            </main>
        </div>

        <AppToast />
    </div>
</template>
