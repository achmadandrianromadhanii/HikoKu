<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import {
    User,
    Mail,
    Phone,
    LockKeyhole,
    Eye,
    EyeOff,
    LoaderCircle,
} from 'lucide-vue-next'

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const googleRegisterUrl = computed(() => {
    try {
        return route('auth.social.redirect', { provider: 'google' })
    } catch (error) {
        return null
    }
})

const githubRegisterUrl = computed(() => {
    try {
        return route('auth.social.redirect', { provider: 'github' })
    } catch (error) {
        return null
    }
})

// [UPDATE]: Menambahkan computed property untuk URL register Discord
const discordRegisterUrl = computed(() => {
    try {
        return route('auth.social.redirect', { provider: 'discord' })
    } catch (error) {
        return null
    }
})

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        },
    })
}

// [NEW]: Lifecycle Hook untuk mengatur class di body agar halaman dikunci (tidak bisa scroll)
onMounted(() => {
    document.body.classList.add('no-scrollbar-login');
})

onBeforeUnmount(() => {
    document.body.classList.remove('no-scrollbar-login');
})
</script>

<template>

    <Head title="Register" />

    <AuthLayout title="Form Register">

        
        <!-- [UPDATE]: Background gambar gunung HD yang jernih, tajam, dengan format WebP untuk performa 100%. Ditambah animasi transisi halus (crossfade/zoom). Diposisikan absolute agar rapi. -->
        <div class="fixed inset-0 z-[-1] overflow-hidden bg-slate-900 pointer-events-none">
            <!-- Overlay gelap untuk memastikan teks form tetap terbaca jelas -->
            <div class="absolute inset-0 z-10 bg-black/45"></div>
            
            <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=1920&fm=webp" 
                 alt="Mountain Background 1" 
                 fetchpriority="high" 
                 class="absolute inset-0 h-full w-full object-cover opacity-0 animate-slideshow-1" />
                 
            <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba?q=80&w=1920&fm=webp" 
                 alt="Mountain Background 2" 
                 loading="lazy" 
                 class="absolute inset-0 h-full w-full object-cover opacity-0 animate-slideshow-2" />
                 
            <img src="https://images.unsplash.com/photo-1454496522488-7a8e488e8606?q=80&w=1920&fm=webp" 
                 alt="Mountain Background 3" 
                 loading="lazy" 
                 class="absolute inset-0 h-full w-full object-cover opacity-0 animate-slideshow-3" />
        </div>

        <!-- [PENJELASAN KODE UPDATE]: Memperkecil tinggi minimum menjadi 100dvh agar form sempurna terpusat tanpa scrollbar di mobile -->
        <div class="mx-auto flex min-h-[100dvh] w-full max-w-[420px] items-center justify-center px-4 py-4 sm:px-6 lg:px-8">
            <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding vertikal (py-6 menjadi py-5) agar form lebih padat di layar kecil -->
            <section class="w-full rounded-[32px] border border-white/10 bg-[#0B1120]/90 backdrop-blur-2xl px-5 py-5 shadow-[0_20px_60px_rgba(0,0,0,0.6)] sm:px-8 sm:py-8 relative overflow-hidden">
                <!-- Ornamen Glow (Neon Forest) -->
                <div class="absolute -top-16 -right-16 h-40 w-40 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-16 -left-16 h-40 w-40 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

                <!-- [PENJELASAN KODE UPDATE]: Mengecilkan tinggi logo (h-20 jadi h-16) dan margin bawah (mb-6 jadi mb-4) -->
                <div class="mb-4 flex flex-col items-center justify-center relative z-10">
                    <img src="/images/logo.png" alt="Hiko Logo" class="h-16 w-auto object-contain drop-shadow-[0_0_15px_rgba(34,211,238,0.4)] transition-transform duration-300 hover:scale-105" />
                    <p class="text-[11px] font-bold text-cyan-400 tracking-widest uppercase mt-2">Create Account</p>
                </div>
                
                <!-- [PENJELASAN KODE UPDATE]: Mengurangi jarak antar elemen form dari space-y-4 menjadi space-y-3 -->
                <form class="space-y-3 relative z-10" @submit.prevent="submit">
                        
                        <!-- Grid dua kolom agar ringkas dan tidak memakan banyak ruang vertikal -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Input Nama (Kolom 1) Kapsul -->
                            <div>
                                <div class="group relative z-0 w-full">
                                    <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding y (py-3 jadi py-2.5) -->
                                    <input v-model="form.name" type="text" id="name" autocomplete="name" placeholder=" "
                                        class="peer block w-full appearance-none rounded-[16px] border border-white/10 bg-black/40 px-3 py-2.5 pl-10 text-xs font-bold text-white transition-all focus:border-cyan-400 focus:bg-white/5 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 shadow-inner" />
                                    
                                    <span class="pointer-events-none absolute left-3 top-2.5 text-slate-500 transition-colors peer-focus:text-cyan-400">
                                        <User class="h-4 w-4" />
                                    </span>

                                    <label for="name"
                                        class="absolute left-10 top-2.5 -z-10 origin-[0] -translate-y-5 scale-75 transform text-[11px] font-bold text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-10 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-400">
                                        Nama Lengkap
                                    </label>
                                </div>
                                <p v-if="form.errors.name" class="mt-1 pl-1 text-[10px] font-bold text-rose-400">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Input Nomor HP (Kolom 2) Kapsul -->
                            <div>
                                <div class="group relative z-0 w-full">
                                    <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding y (py-3 jadi py-2.5) -->
                                    <input v-model="form.phone" type="text" id="phone" autocomplete="tel" placeholder=" "
                                        class="peer block w-full appearance-none rounded-[16px] border border-white/10 bg-black/40 px-3 py-2.5 pl-10 text-xs font-bold text-white transition-all focus:border-cyan-400 focus:bg-white/5 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 shadow-inner" />
                                    
                                    <span class="pointer-events-none absolute left-3 top-2.5 text-slate-500 transition-colors peer-focus:text-cyan-400">
                                        <Phone class="h-4 w-4" />
                                    </span>

                                    <label for="phone"
                                        class="absolute left-10 top-2.5 -z-10 origin-[0] -translate-y-5 scale-75 transform text-[11px] font-bold text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-10 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-400">
                                        Nomor HP
                                    </label>
                                </div>
                                <p v-if="form.errors.phone" class="mt-1 pl-1 text-[10px] font-bold text-rose-400">
                                    {{ form.errors.phone }}
                                </p>
                            </div>
                        </div>

                        <!-- Input Email Kapsul (Penuh) -->
                        <div>
                            <div class="group relative z-0 w-full">
                                <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding y (py-3 jadi py-2.5) -->
                                <input v-model="form.email" type="email" id="email" autocomplete="username" placeholder=" "
                                    class="peer block w-full appearance-none rounded-[16px] border border-white/10 bg-black/40 px-3 py-2.5 pl-10 text-xs font-bold text-white transition-all focus:border-cyan-400 focus:bg-white/5 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 shadow-inner" />
                                
                                <span class="pointer-events-none absolute left-3 top-2.5 text-slate-500 transition-colors peer-focus:text-cyan-400">
                                    <Mail class="h-4 w-4" />
                                </span>

                                <label for="email"
                                    class="absolute left-10 top-2.5 -z-10 origin-[0] -translate-y-5 scale-75 transform text-[11px] font-bold text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-10 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-400">
                                    Email Address
                                </label>
                            </div>
                            <p v-if="form.errors.email" class="mt-1 pl-1 text-[10px] font-bold text-rose-400">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Grid dua kolom untuk Password -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Input Password Kapsul -->
                            <div>
                                <div class="group relative z-0 w-full">
                                    <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding y (py-3 jadi py-2.5) -->
                                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password" autocomplete="new-password" placeholder=" "
                                        class="peer block w-full appearance-none rounded-[16px] border border-white/10 bg-black/40 px-3 py-2.5 pl-10 pr-9 text-xs font-bold text-white transition-all focus:border-cyan-400 focus:bg-white/5 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 shadow-inner" />
                                    
                                    <span class="pointer-events-none absolute left-3 top-2.5 text-slate-500 transition-colors peer-focus:text-cyan-400">
                                        <LockKeyhole class="h-4 w-4" />
                                    </span>

                                    <label for="password"
                                        class="absolute left-10 top-2.5 -z-10 origin-[0] -translate-y-5 scale-75 transform text-[11px] font-bold text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-10 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-400">
                                        Password
                                    </label>

                                    <button type="button"
                                        class="absolute bottom-2.5 right-3 text-slate-500 transition-colors hover:text-cyan-400 focus:outline-none active:scale-90"
                                        @click="showPassword = !showPassword">
                                        <EyeOff v-if="showPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 pl-1 text-[10px] font-bold text-rose-400">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Input Konfirmasi Password Kapsul -->
                            <div>
                                <div class="group relative z-0 w-full">
                                    <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding y (py-3 jadi py-2.5) -->
                                    <input v-model="form.password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" id="password_confirmation" autocomplete="new-password" placeholder=" "
                                        class="peer block w-full appearance-none rounded-[16px] border border-white/10 bg-black/40 px-3 py-2.5 pl-10 pr-9 text-xs font-bold text-white transition-all focus:border-cyan-400 focus:bg-white/5 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 shadow-inner" />
                                    
                                    <span class="pointer-events-none absolute left-3 top-2.5 text-slate-500 transition-colors peer-focus:text-cyan-400">
                                        <LockKeyhole class="h-4 w-4" />
                                    </span>

                                    <label for="password_confirmation"
                                        class="absolute left-10 top-2.5 -z-10 origin-[0] -translate-y-5 scale-75 transform text-[11px] font-bold text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-10 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-400">
                                        Ulangi
                                    </label>

                                    <button type="button"
                                        class="absolute bottom-2.5 right-3 text-slate-500 transition-colors hover:text-cyan-400 focus:outline-none active:scale-90"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation">
                                        <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password_confirmation" class="mt-1 pl-1 text-[10px] font-bold text-rose-400">
                                    {{ form.errors.password_confirmation }}
                                </p>
                            </div>
                        </div>

                        <!-- Tombol Submit Kapsul -->
                        <!-- [PENJELASAN KODE UPDATE]: Memperkecil tinggi tombol menjadi h-[44px] dan margin mt-1 -->
                        <button type="submit"
                            class="inline-flex h-[44px] mt-1 w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-cyan-400 to-emerald-400 px-5 text-[14px] font-black text-slate-900 shadow-[0_0_20px_rgba(34,211,238,0.4)] transition-all hover:scale-[1.02] hover:shadow-[0_0_25px_rgba(34,211,238,0.6)] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
                            :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin text-slate-900" />
                            <span>{{ form.processing ? 'Loading...' : 'Daftar Sekarang' }}</span>
                        </button>

                        <!-- Pembatas -->
                        <div class="relative py-2">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-white/10" />
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-[#0B1120] px-3 text-[10px] font-bold tracking-widest uppercase text-slate-500">
                                    Atau Daftar Pakai
                                </span>
                            </div>
                        </div>

                        <!-- Social Logins (Tengah, Glassmorphism, Responsive) -->
                        <div class="flex items-center justify-center gap-4 pt-0">
                            <!-- Google -->
                            <!-- [PENJELASAN KODE UPDATE]: Memperkecil logo social button dari h-11 w-11 menjadi h-10 w-10 -->
                            <a v-if="googleRegisterUrl" :href="googleRegisterUrl"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:border-white/20 hover:bg-white/10 active:scale-95" title="Register with Google">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#EA4335"
                                        d="M12 10.2v3.9h5.5c-.2 1.2-.9 2.3-1.9 3.1l3 2.3c1.8-1.7 2.9-4.2 2.9-7.2 0-.7-.1-1.4-.2-2H12z" />
                                    <path fill="#34A853"
                                        d="M12 22c2.7 0 4.9-.9 6.6-2.5l-3-2.3c-.8.6-1.9 1-3.6 1-2.8 0-5.1-1.9-5.9-4.4l-3.1 2.4C4.7 19.7 8.1 22 12 22z" />
                                    <path fill="#4A90E2"
                                        d="M6.1 13.8c-.2-.6-.3-1.2-.3-1.8s.1-1.2.3-1.8L3 7.8C2.4 9 2 10.5 2 12s.4 3 1 4.2l3.1-2.4z" />
                                    <path fill="#FBBC05"
                                        d="M12 5.8c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 2.9 14.7 2 12 2 8.1 2 4.7 4.3 3 7.8l3.1 2.4c.8-2.5 3.1-4.4 5.9-4.4z" />
                                </svg>
                            </a>
                            
                            <!-- GitHub -->
                            <a v-if="githubRegisterUrl" :href="githubRegisterUrl"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:border-white/20 hover:bg-white/10 active:scale-95" title="Register with GitHub">
                                <svg class="h-5 w-5 shrink-0" fill="#ffffff" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                </svg>
                            </a>

                            <!-- Discord -->
                            <a v-if="discordRegisterUrl" :href="discordRegisterUrl"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:border-[#5865F2]/50 hover:bg-[#5865F2]/20 active:scale-95" title="Register with Discord">
                                <svg class="h-5 w-5 shrink-0" fill="#5865F2" viewBox="0 0 127.14 96.36" aria-hidden="true">
                                    <path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.1,46,96,53,91,65.69,84.69,65.69Z"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Sign In Link -->
                        <div class="mt-2 flex flex-col items-center space-y-1 pt-0">
                            <p class="text-[12px] font-bold text-slate-400">
                                Sudah punya akun?
                                <Link :href="route('login')" class="ml-1 font-black text-cyan-400 transition hover:text-cyan-300 hover:underline">
                                    Login di sini
                                </Link>
                            </p>
                            <p class="text-center text-[10px] font-bold text-slate-600 px-4">
                                Melanjutkan berarti setuju dengan 
                                <Link :href="route('terms')" class="text-slate-400 hover:text-cyan-400 hover:underline">Syarat</Link> & 
                                <Link :href="route('privacy')" class="text-slate-400 hover:text-cyan-400 hover:underline">Privasi</Link>
                            </p>
                        </div>
                    </form>
            </section>
        </div>

    </AuthLayout>
</template>
<!-- [UPDATE]: Memindahkan CSS animasi dari dalam <svg> ke blok 

<!-- [UPDATE]: CSS Keyframes untuk animasi transisi halus (fade & slow zoom) pada 3 background gambar gunung HD -->
<style scoped>
@keyframes fadeZoom1 {
    0%, 33.33% { opacity: 1; transform: scale(1.05); }
    38%, 95% { opacity: 0; transform: scale(1); }
    100% { opacity: 1; transform: scale(1.05); }
}
@keyframes fadeZoom2 {
    0%, 28% { opacity: 0; transform: scale(1); }
    33.33%, 66.66% { opacity: 1; transform: scale(1.05); }
    71%, 100% { opacity: 0; transform: scale(1); }
}
@keyframes fadeZoom3 {
    0%, 61% { opacity: 0; transform: scale(1); }
    66.66%, 100% { opacity: 1; transform: scale(1.05); }
}

.animate-slideshow-1 { animation: fadeZoom1 24s infinite ease-in-out; }
.animate-slideshow-2 { animation: fadeZoom2 24s infinite ease-in-out; }
.animate-slideshow-3 { animation: fadeZoom3 24s infinite ease-in-out; }
</style>

<style>
/* [KOMENTAR PENJELASAN]: Class global khusus untuk mematikan scroll (overflow: hidden) 
   secara paksa di halaman Register agar form termuat pas 1 layar (viewport) sesuai permintaan. */
.no-scrollbar-login {
    overflow: hidden !important;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE/Edge */
}
.no-scrollbar-login::-webkit-scrollbar {
    display: none; /* Chrome/Safari */
}
</style>
