<script setup>
import { computed, ref, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard,
    Database,
    ShoppingCart,
    Settings,
    LogOut,
    X,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Circle,
    User,
    BarChart
} from 'lucide-vue-next'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    // Komentar: Prop untuk menerima status desktop sidebar (menyusut atau lebar) dari Layout
    collapsed: {
        type: Boolean,
        default: false,
    }
})

// Komentar: Emit tambahan untuk mengizinkan komponen ini menyuruh Layout mengubah status collapse
const emit = defineEmits(['close', 'toggle-collapse'])

const page = usePage()

const user = computed(() => page.props.auth?.user || null)

const menuGroups = [
    {
        label: null,
        items: [
            { label: 'Dashboard', icon: LayoutDashboard, route: 'admin.dashboard', type: 'link' },
        ],
    },
    {
        label: 'MENU UTAMA',
        items: [
            {
                label: 'Master Data',
                icon: Database,
                type: 'dropdown',
                id: 'master',
                subItems: [
                    { label: 'Products', route: 'admin.products.index' },
                    { label: 'Categories', route: 'admin.categories.index' },
                    { label: 'Packages', route: 'admin.packages.index' },
                ]
            },
            {
                label: 'Transaksi',
                icon: ShoppingCart,
                type: 'dropdown',
                id: 'transaksi',
                subItems: [
                    { label: 'Semua Pesanan', route: 'admin.rentals.index' },
                    { label: 'Sewa Manual (POS)', route: 'admin.pos.index' },
                    { label: 'Pembayaran', route: 'admin.payments.index' },
                ]
            },
        ],
    },
    {
        label: 'LAPORAN',
        items: [
            { label: 'Ringkasan Laporan', icon: BarChart, route: 'admin.reports.index', type: 'link' },
        ],
    },
    {
        label: 'PENGATURAN',
        items: [
            {
                label: 'Sistem',
                icon: Settings,
                type: 'dropdown',
                id: 'system',
                subItems: [
                    { label: 'Users', route: 'admin.users.index' },
                ]
            }
        ]
    }
]

const openDropdowns = ref({})
const profileMenuOpen = ref(false)

const isActiveRoute = (routeName) => {
    try {
        return route().current(routeName) || route().current(`${routeName}.*`)
    } catch (error) {
        return false
    }
}

const isDropdownActive = (subItems) => {
    return subItems.some(item => isActiveRoute(item.route))
}

const toggleDropdown = (id) => {
    // Komentar: Jika sidebar dalam mode menyusut (mini), klik dropdown akan melebarkan sidebar secara otomatis
    if (props.collapsed) {
        emit('toggle-collapse')
    }
    openDropdowns.value[id] = !openDropdowns.value[id]
    
    // Komentar: Menyimpan status terbuka/tertutupnya submenu ke localStorage agar bertahan saat di-refresh
    if (typeof window !== 'undefined') {
        localStorage.setItem('hiko_sidebar_dropdowns', JSON.stringify(openDropdowns.value))
    }
}

const toggleProfileMenu = () => {
    profileMenuOpen.value = !profileMenuOpen.value
}

onMounted(() => {
    // Komentar: Membaca memori submenu dari browser saat halaman dimuat (Persistensi)
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('hiko_sidebar_dropdowns')
        if (saved) {
            try {
                openDropdowns.value = JSON.parse(saved)
            } catch(e){}
        }
    }

    // Keep dropdown open if active route is inside (hanya jika belum terbuka oleh localStorage)
    menuGroups.forEach(group => {
        group.items.forEach(item => {
            if (item.type === 'dropdown') {
                if (isDropdownActive(item.subItems)) {
                    openDropdowns.value[item.id] = true
                }
            }
        })
    })
})

const logout = () => {
    router.post(route('admin.logout'))
}

const closeIfMobile = () => {
    emit('close')
}
</script>

<template>
    <div>
        <div v-if="open" class="fixed inset-0 z-[88] bg-black/60 backdrop-blur-[1px] lg:hidden"
            @click="emit('close')" />

        <!-- Komentar: Lebar sidebar menyesuaikan prop `collapsed`. 80px saat menyempit, 230px saat normal. Transisi lebar menggunakan CSS murni (ease-in-out) -->
        <aside
            class="fixed inset-y-0 left-0 z-[89] flex flex-col border-r border-slate-800 bg-[#071226] text-slate-200 shadow-2xl transition-all duration-300 ease-in-out"
            :class="[
                open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                collapsed ? 'lg:w-[80px] w-[230px]' : 'w-[230px]'
            ]">
            
            <!-- Komentar: Tombol Expand/Collapse Desktop dengan ikon < >. Posisinya absolute persis di perbatasan kanan sidebar (right: -16px) -->
            <button type="button" @click="emit('toggle-collapse')"
                class="hidden lg:flex absolute -right-4 top-[22px] z-[100] h-8 w-8 items-center justify-center rounded-full border border-slate-700 bg-[#071226] text-slate-300 hover:text-white hover:bg-slate-800 hover:scale-110 transition-all duration-300 shadow-[0_0_15px_rgba(0,0,0,0.5)]">
                <ChevronRight v-if="collapsed" class="h-4 w-4" />
                <ChevronLeft v-else class="h-4 w-4" />
            </button>
            
            <!-- Header -->
            <div class="border-b border-slate-800 px-5 py-5 transition-all duration-300" :class="collapsed ? 'px-4' : 'px-5'">
                <div class="flex items-start justify-between gap-3">
                    <Link :href="route('admin.dashboard')" class="flex min-w-0 flex-1 items-center gap-3">
                        <!-- 
                            [UPDATE LOGO ADMIN]
                            Logo di Admin Panel tetap menggunakan logo.png.
                            Diberikan efek drop-shadow hitam tajam agar kontras dan sangat jelas di atas background gelap.
                            Tidak menggunakan filter image-rendering agar gambar tetap mulus anti-aliased (tidak pecah/pixelated).
                        -->
                        <div class="flex h-12 w-auto shrink-0 items-center justify-center overflow-visible">
                            <img src="/images/logo.png" alt="hikoku" class="h-12 w-auto object-contain drop-shadow-[0_2px_8px_rgba(0,0,0,0.6)]" />
                        </div>

                        <!-- Komentar: Sembunyikan teks nama dan admin panel saat sidebar menyempit agar rapi -->
                        <div class="min-w-0 transition-opacity duration-300" :class="collapsed ? 'lg:hidden opacity-0' : 'opacity-100'">
                            <p class="truncate text-[22px] bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent drop-shadow-sm leading-none" style="font-family: 'Pacifico', cursive;">hikoku</p>
                            <p class="truncate text-[11px] font-medium text-slate-400">Admin Panel</p>
                        </div>
                    </Link>

                    <button type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 text-slate-300 transition hover:bg-white/5 lg:hidden"
                        @click="emit('close')">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Menus -->
            <div class="sidebar-scroll flex-1 overflow-y-auto px-4 py-5" :class="collapsed ? 'px-2' : 'px-4'">
                <div class="space-y-6">
                    <section v-for="(group, groupIndex) in menuGroups" :key="groupIndex">
                        <!-- Komentar: Sembunyikan judul grup (MENU UTAMA) jika menyempit -->
                        <p v-if="group.label"
                            class="mb-3 px-2 text-[10px] font-extrabold uppercase tracking-widest text-slate-500/80 transition-all duration-300"
                            :class="collapsed ? 'lg:hidden opacity-0' : 'opacity-100'">
                            {{ group.label }}
                        </p>

                        <div class="space-y-1.5">
                            <template v-for="item in group.items" :key="item.id || item.route">
                                
                                <!-- Link Type -->
                                <Link v-if="item.type === 'link'" :href="route(item.route)"
                                    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-all duration-300" 
                                    :class="[
                                        isActiveRoute(item.route) ? 'bg-gradient-to-r from-cyan-500/10 to-blue-500/5 text-cyan-400 shadow-[inset_3px_0_0_0_#06b6d4]' : 'text-slate-400 hover:bg-white/5 hover:text-white',
                                        collapsed ? 'lg:justify-center lg:px-0' : ''
                                    ]" 
                                    @click="closeIfMobile">
                                    <div class="flex items-center justify-center transition-colors duration-300 shrink-0" 
                                        :class="isActiveRoute(item.route) ? 'text-cyan-400' : 'text-slate-500 group-hover:text-white'">
                                        <component :is="item.icon" class="h-5 w-5" />
                                    </div>
                                    <!-- Komentar: Teks menu dihilangkan saat collapsed -->
                                    <span class="truncate font-medium transition-all duration-300" :class="collapsed ? 'lg:hidden opacity-0' : 'opacity-100'">{{ item.label }}</span>
                                </Link>

                                <!-- Dropdown Type -->
                                <div v-else-if="item.type === 'dropdown'" class="flex flex-col">
                                    <button type="button" @click="toggleDropdown(item.id)"
                                        class="group flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm transition-all duration-300"
                                        :class="[
                                            (isDropdownActive(item.subItems) || openDropdowns[item.id]) ? 'text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white',
                                            collapsed ? 'lg:justify-center lg:px-0' : ''
                                        ]">
                                        
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center transition-colors duration-300 shrink-0"
                                                :class="(isDropdownActive(item.subItems) || openDropdowns[item.id]) ? 'text-cyan-400' : 'text-slate-500 group-hover:text-white'">
                                                <component :is="item.icon" class="h-5 w-5" />
                                            </div>
                                            <span class="truncate font-medium transition-all duration-300" :class="collapsed ? 'lg:hidden opacity-0' : 'opacity-100'">{{ item.label }}</span>
                                        </div>

                                        <div class="transition-transform duration-300" :class="[openDropdowns[item.id] && !collapsed ? 'rotate-180 text-cyan-400' : 'text-slate-500', collapsed ? 'lg:hidden opacity-0' : 'opacity-100']">
                                            <ChevronDown class="h-4 w-4" />
                                        </div>
                                    </button>

                                    <!-- Dropdown Items (Smooth Grid Transition) -->
                                    <!-- Komentar: Jika sedang collapsed, paksakan sembunyikan dropdown agar tampilan mini icon tidak jelek berantakan -->
                                    <div class="grid transition-all duration-300 ease-in-out"
                                        :class="(openDropdowns[item.id] && !collapsed) ? 'grid-rows-[1fr] opacity-100 mt-1' : 'grid-rows-[0fr] opacity-0'">
                                        <div class="overflow-hidden">
                                            <div class="space-y-1 pl-3 pr-2 pb-2 pt-1">
                                                
                                                <!-- Komentar: Garis vertikal di sini sudah dihapus mutlak. Sesuai pesanan hanya menggunakan icon O -->
                                                <Link v-for="subItem in item.subItems" :key="subItem.route" :href="route(subItem.route)"
                                                    class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-300"
                                                    :class="isActiveRoute(subItem.route) ? 'text-cyan-400 bg-cyan-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                                                    @click="closeIfMobile">
                                                    
                                                    <!-- Circle Icon (Icon O bulat) inside dropdown -->
                                                    <Circle class="h-2 w-2 shrink-0 transition-all duration-300" 
                                                        :class="isActiveRoute(subItem.route) ? 'fill-cyan-400 text-cyan-400 shadow-[0_0_8px_rgba(6,182,212,0.8)]' : 'text-slate-600 fill-slate-800 group-hover:fill-slate-400 group-hover:text-slate-400'" />
                                                    
                                                    <span class="truncate font-medium">{{ subItem.label }}</span>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </template>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Footer: Simple Logout Toggle Switch -->
            <div class="mt-auto border-t border-white/5 bg-[#071226] p-4 relative flex justify-center transition-all duration-300" :class="collapsed ? 'p-3' : 'p-4'">
                
                <!-- Popover Logout Menu -->
                <div class="absolute bottom-full left-4 right-4 mb-2 overflow-hidden transition-all duration-300 origin-bottom"
                    :class="profileMenuOpen ? 'scale-100 opacity-100 pointer-events-auto' : 'scale-95 opacity-0 pointer-events-none'">
                    <div class="rounded-2xl border border-white/10 bg-slate-900 p-2 shadow-2xl backdrop-blur-xl">
                        <button type="button" @click="logout"
                            class="flex w-full items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-red-400 transition-colors duration-300 hover:bg-red-500/10">
                            <LogOut class="h-4 w-4 shrink-0" />
                            <span :class="collapsed ? 'lg:hidden' : ''">Akhiri Sesi (Logout)</span>
                        </button>
                    </div>
                </div>

                <!-- Minimal Toggle Switch Button -->
                <button type="button" @click="toggleProfileMenu"
                    class="group flex h-10 w-full items-center justify-center gap-2.5 rounded-xl border border-white/5 bg-white/[0.02] text-slate-400 transition-all duration-300 hover:bg-white/10 hover:text-white hover:shadow-inner"
                    :class="[
                        profileMenuOpen ? 'bg-white/10 text-white border-white/10 shadow-inner' : '',
                        collapsed ? 'px-0' : 'px-3'
                    ]">
                    <User class="h-4 w-4 shrink-0" />
                    <span class="text-[13px] font-bold tracking-wide transition-all duration-300" :class="collapsed ? 'lg:hidden opacity-0' : 'opacity-100'">Opsi Akun</span>
                    <ChevronDown class="h-4 w-4 transition-transform duration-300 shrink-0" :class="[profileMenuOpen ? 'rotate-180' : '', collapsed ? 'lg:hidden' : '']" />
                </button>
            </div>
        </aside>
    </div>
</template>

<style scoped>
.sidebar-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.16) transparent;
}

.sidebar-scroll::-webkit-scrollbar {
    width: 6px;
}

.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.10);
    border-radius: 9999px;
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.20);
}
</style>