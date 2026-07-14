<script setup>
    import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
    import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
    import AuthLayout from '@/Layouts/AuthLayout.vue';
    import { Mail, LockKeyhole, Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';

    const page = usePage();

    const form = useForm({
        email: '',
        password: '',
        remember: false,
        'cf-turnstile-response': '', // [NEW]: Disiapkan untuk dikirim ke backend
    });

    const showPassword = ref(false);

    // State untuk Captcha dan Notifikasi Sukses
    const captchaVerified = ref(false);
    const showSuccessToast = ref(false);
    const successMessage = computed(() => page.props.flash?.success);

    // [NEW]: Gunakan ref() DOM element untuk menjamin kita tidak menargetkan ID nyasar saat transisi halaman SPA
    const turnstileContainer = ref(null);

    // [NEW]: Simpan ID Widget di root scope agar terbaca di lifecycle unmount
    let turnstileWidgetId = null;

    onMounted(() => {
        // [KOMENTAR PENJELASAN]: Menambahkan class ke body untuk menghilangkan scrollbar secara visual di halaman login,
        // hal ini dilakukan agar tampilan form dan captcha tetap rapi (100% tinggi) tanpa scrollbar yang mengganggu.
        document.body.classList.add('no-scrollbar-login');

        if (successMessage.value) {
            showSuccessToast.value = true;
            setTimeout(() => {
                showSuccessToast.value = false;
            }, 5000);
        }

        // [UPDATE ANTI-ERROR]: Deteksi apakah web berjalan di Localhost tanpa HTTPS
        const isLocalhost =
            window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        const isHttp = window.location.protocol !== 'https:';

        if (isLocalhost && isHttp) {
            // BYPASS: Jika di localhost HTTP, matikan Turnstile sepenuhnya agar tidak ada error merah di console browser.
            // Backend (AuthenticatedSessionController) juga sudah dikonfigurasi untuk mem-bypass token di localhost.
            captchaVerified.value = true;
            form['cf-turnstile-response'] = 'local-dummy-bypass';
            // Beri sedikit penanda visual agar developer tahu Turnstile sedang mode bypass
            if (turnstileContainer.value) {
                turnstileContainer.value.innerHTML =
                    '<div class="text-[10px] text-slate-500 text-center py-2 italic font-mono">Turnstile Bypassed (Local Mode)</div>';
            }
            return; // Hentikan fungsi agar API Cloudflare tidak didownload sama sekali
        }

        // [UPDATE]: Logika render Turnstile yang aman untuk SPA (Single Page Application)
        // Supaya captcha tetap muncul meskipun user bolak-balik halaman tanpa reload
        const renderTurnstile = () => {
            if (window.turnstile && turnstileContainer.value) {
                // Jika ada widget sebelumnya yang ter-cache di memori, hapus (remove) terlebih dahulu
                if (turnstileWidgetId !== null) {
                    window.turnstile.remove(turnstileWidgetId);
                }

                // Bersihkan sisa DOM iframe dari Turnstile jika ada
                turnstileContainer.value.innerHTML = '';

                // [VITAL]: Render langsung ke objek DOM (turnstileContainer.value)
                // Jangan gunakan '#cf-turnstile-widget' karena rentan duplikat saat Vue transisi
                turnstileWidgetId = window.turnstile.render(turnstileContainer.value, {
                    // [UPDATE ANTI-ERROR]: Jika berjalan di HTTP (seperti localhost biasa), paksa gunakan Dummy Key Cloudflare.
                    // Hal ini MENCEGAH munculnya error merah 'postMessage DOMWindow' dan 'preload warning' di Console
                    // karena script Turnstile asli membutuhkan HTTPS untuk berkomunikasi dengan origin frame-nya.
                    sitekey:
                        window.location.protocol !== 'https:'
                            ? '1x00000000000000000000AA'
                            : page.props.turnstile_site_key || '1x00000000000000000000AA',
                    callback: function (token) {
                        form['cf-turnstile-response'] = token;
                        captchaVerified.value = true;
                    },
                    'error-callback': function () {
                        form['cf-turnstile-response'] = '';
                        captchaVerified.value = false;
                    },
                });
            }
        };

        if (window.turnstile) {
            // Jika script sudah pernah di-load sebelumnya (kasus kembali dari halaman Register)
            renderTurnstile();
        } else {
            // Jika ini adalah loading pertama kali
            window.onloadTurnstileCallback = () => {
                renderTurnstile();
            };

            if (!document.getElementById('turnstile-script')) {
                const script = document.createElement('script');
                script.id = 'turnstile-script';
                script.src =
                    'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback&render=explicit';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        }
    });

    // [NEW]: Siklus hidup BeforeUnmount (Saat user pindah ke halaman Register)
    // Wajib menghapus Turnstile dari sistem SEBELUM DOM hilang agar tidak menumpuk dan menyebabkan Error 110200
    onBeforeUnmount(() => {
        if (window.turnstile && turnstileWidgetId !== null) {
            window.turnstile.remove(turnstileWidgetId);
            turnstileWidgetId = null;
        }
        // [KOMENTAR PENJELASAN]: Menghapus class saat keluar dari halaman login agar scrollbar halaman lain kembali normal
        document.body.classList.remove('no-scrollbar-login');
    });

    const googleLoginUrl = computed(() => {
        try {
            return route('auth.social.redirect', { provider: 'google' });
        } catch (error) {
            return null;
        }
    });

    const githubLoginUrl = computed(() => {
        try {
            return route('auth.social.redirect', { provider: 'github' });
        } catch (error) {
            return null;
        }
    });

    // [UPDATE]: Menambahkan computed property untuk URL login Discord
    const discordLoginUrl = computed(() => {
        try {
            return route('auth.social.redirect', { provider: 'discord' });
        } catch (error) {
            return null;
        }
    });

    const submit = () => {
        form.post(route('login'), {
            onFinish: () => {
                form.reset('password');
            },
        });
    };
</script>

<template>
    <Head title="Login Admin" />

    <AuthLayout title="Form Login">
        <transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="translate-x-12 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-12 opacity-0"
        >
            <div
                v-if="showSuccessToast"
                class="fixed right-6 top-6 z-50 flex items-center gap-3 rounded-2xl border border-emerald-400/20 bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-4 text-white shadow-[0_10px_40px_rgba(16,185,129,0.3)] backdrop-blur-md"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>
                <div class="flex flex-col">
                    <p class="text-sm font-bold tracking-wide">Sukses!</p>
                    <p class="text-xs text-emerald-50">{{ successMessage }}</p>
                </div>
            </div>
        </transition>
        <!-- [UPDATE]: Background gambar gunung HD yang jernih, tajam, dengan format WebP untuk performa 100%. Ditambah animasi transisi halus (crossfade/zoom). Diposisikan absolute agar rapi. -->
        <div class="fixed inset-0 z-[-1] overflow-hidden bg-slate-900 pointer-events-none">
            <!-- Overlay gelap untuk memastikan teks form tetap terbaca jelas -->
            <div class="absolute inset-0 z-10 bg-black/45"></div>

            <img
                src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=1920&fm=webp"
                alt="Mountain Background 1"
                fetchpriority="high"
                class="absolute inset-0 h-full w-full object-cover opacity-0 animate-slideshow-1"
            />

            <img
                src="https://images.unsplash.com/photo-1519681393784-d120267933ba?q=80&w=1920&fm=webp"
                alt="Mountain Background 2"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover opacity-0 animate-slideshow-2"
            />

            <img
                src="https://images.unsplash.com/photo-1454496522488-7a8e488e8606?q=80&w=1920&fm=webp"
                alt="Mountain Background 3"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover opacity-0 animate-slideshow-3"
            />
        </div>

        <!-- [PENJELASAN KODE UPDATE]: Memperkecil tinggi minimum menjadi 100dvh agar form sempurna terpusat tanpa scrollbar di mobile -->
        <div
            class="mx-auto flex min-h-[100dvh] w-full max-w-[420px] items-center justify-center px-4 py-2 sm:px-6 lg:px-8"
        >
            <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding vertikal (py-6 menjadi py-5) agar form lebih padat di layar kecil -->
            <section
                class="w-full rounded-[32px] border border-white/10 bg-[#0B1120]/90 backdrop-blur-md md:backdrop-blur-2xl px-5 py-5 shadow-[0_20px_60px_rgba(0,0,0,0.6)] sm:px-8 sm:py-8 relative overflow-hidden"
            >
                <!-- Ornamen Glow (Neon Forest) -->
                <!-- [OPTIMASI GPU]: Hapus blur-3xl, ganti dengan radial-gradient murni -->
                <div
                    class="absolute -top-16 -left-16 h-40 w-40 rounded-full pointer-events-none"
                    style="
                        background: radial-gradient(
                            circle,
                            rgba(6, 182, 212, 0.2) 0%,
                            transparent 70%
                        );
                    "
                ></div>
                <div
                    class="absolute -bottom-16 -right-16 h-40 w-40 rounded-full pointer-events-none"
                    style="
                        background: radial-gradient(
                            circle,
                            rgba(16, 185, 129, 0.1) 0%,
                            transparent 70%
                        );
                    "
                ></div>

                <!-- [PENJELASAN KODE UPDATE]: Mengecilkan tinggi logo (h-24 jadi h-16) dan margin bawah (mb-8 jadi mb-4) -->
                <div class="mb-4 flex flex-col items-center justify-center relative z-10">
                    <img
                        src="/images/logo.webp"
                        alt="Hiko Logo"
                        class="h-16 w-auto object-contain drop-shadow-[0_0_15px_rgba(34,211,238,0.4)] transition-transform duration-300 hover:scale-105"
                    />
                    <p class="text-[11px] font-bold text-cyan-400 tracking-widest uppercase mt-2">
                        Welcome Back
                    </p>
                </div>

                <!-- [PENJELASAN KODE UPDATE]: Mengurangi jarak antar input (space-y-4 jadi space-y-3) -->
                <form class="space-y-3 relative z-10" @submit.prevent="submit">
                    <!-- Input Email Kapsul -->
                    <div>
                        <div class="group relative z-0 w-full">
                            <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding dalam (py-3.5 jadi py-2.5) agar lebih ringkas -->
                            <input
                                v-model="form.email"
                                type="email"
                                id="email"
                                autocomplete="username"
                                placeholder=" "
                                class="peer block w-full appearance-none rounded-[16px] border border-white/10 bg-black/40 px-4 py-2.5 pl-11 text-sm font-bold text-white transition-all focus:border-cyan-400 focus:bg-white/5 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 shadow-inner"
                            />

                            <span
                                class="pointer-events-none absolute left-3.5 top-2.5 text-slate-500 transition-colors peer-focus:text-cyan-400"
                            >
                                <Mail class="h-5 w-5" />
                            </span>

                            <label
                                for="email"
                                class="absolute left-11 top-2.5 -z-10 origin-[0] -translate-y-6 scale-75 transform text-[13px] font-bold text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-11 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-cyan-400"
                            >
                                Email Address
                            </label>
                        </div>
                        <p
                            v-if="form.errors.email"
                            class="mt-1 pl-1 text-[11px] font-bold text-rose-400"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Input Password Kapsul -->
                    <div>
                        <div class="group relative z-0 w-full">
                            <!-- [PENJELASAN KODE UPDATE]: Mengurangi padding dalam (py-3.5 jadi py-2.5) agar lebih ringkas -->
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                autocomplete="current-password"
                                placeholder=" "
                                class="peer block w-full appearance-none rounded-[16px] border border-white/10 bg-black/40 px-4 py-2.5 pl-11 pr-11 text-sm font-bold text-white transition-all focus:border-cyan-400 focus:bg-white/5 focus:outline-none focus:ring-4 focus:ring-cyan-500/20 shadow-inner"
                            />

                            <span
                                class="pointer-events-none absolute left-3.5 top-2.5 text-slate-500 transition-colors peer-focus:text-cyan-400"
                            >
                                <LockKeyhole class="h-5 w-5" />
                            </span>

                            <label
                                for="password"
                                class="absolute left-11 top-2.5 -z-10 origin-[0] -translate-y-6 scale-75 transform text-[13px] font-bold text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-11 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-cyan-400"
                            >
                                Password
                            </label>

                            <button
                                type="button"
                                class="absolute bottom-2.5 right-3.5 text-slate-500 transition-colors hover:text-cyan-400 focus:outline-none active:scale-90"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="mt-1.5 pl-1 text-[11px] font-bold text-rose-400"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Opsi Ingat Saya & Loader (Glassmorphism Pill) -->
                    <div
                        class="flex items-center justify-between gap-3 rounded-full bg-white/5 border border-white/5 px-4 py-2 w-full sm:w-2/3 max-w-[240px]"
                    >
                        <label
                            class="flex cursor-pointer items-center gap-2 text-[12px] font-bold text-slate-300 transition hover:text-cyan-400"
                        >
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded bg-black/50 border-white/20 text-cyan-500 transition focus:ring-cyan-500/50"
                            />
                            Ingat Saya
                        </label>

                        <div
                            class="inline-flex items-center gap-1.5 text-[11px] font-bold text-cyan-400"
                            v-if="form.processing"
                        >
                            <LoaderCircle class="h-3.5 w-3.5 animate-spin" />
                            <span>Loading...</span>
                        </div>
                    </div>

                    <!-- Turnstile Cloudflare -->
                    <!-- [PENJELASAN KODE UPDATE]: Mengubah padding y menjadi sangat tipis (py-1) agar turnstile tidak memakan banyak ruang vertikal -->
                    <div class="flex flex-col items-center justify-center py-1">
                        <div
                            ref="turnstileContainer"
                            class="transform scale-90 origin-center bg-white/5 rounded-xl border border-white/10 p-1 overflow-hidden shadow-inner"
                        ></div>
                        <p
                            v-if="form.errors['cf-turnstile-response']"
                            class="mt-1 text-center text-[11px] font-bold text-rose-400"
                        >
                            {{ form.errors['cf-turnstile-response'] }}
                        </p>
                    </div>

                    <!-- Tombol Submit (Lebar penuh, besar, mewah) -->
                    <!-- [PENJELASAN KODE UPDATE]: Memperkecil tinggi tombol (h-[52px] jadi h-[46px]) untuk menghemat tinggi total form -->
                    <button
                        type="submit"
                        class="inline-flex h-[46px] w-full items-center justify-center rounded-full bg-gradient-to-r from-cyan-400 to-emerald-400 px-5 text-[14px] font-black text-slate-900 shadow-[0_0_20px_rgba(34,211,238,0.4)] transition-all hover:scale-[1.02] hover:shadow-[0_0_25px_rgba(34,211,238,0.6)] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
                        :disabled="form.processing || !captchaVerified"
                    >
                        Masuk Sekarang
                    </button>

                    <!-- Pembatas -->
                    <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-white/10" />
                        </div>
                        <div class="relative flex justify-center">
                            <span
                                class="bg-[#0B1120] px-3 text-[10px] font-bold tracking-widest uppercase text-slate-500"
                            >
                                Atau Lanjut Pakai
                            </span>
                        </div>
                    </div>

                    <!-- Social Logins (Tengah, Glassmorphism, Responsive) -->
                    <div class="flex items-center justify-center gap-4 pt-0">
                        <!-- Google -->
                        <!-- [PENJELASAN KODE UPDATE]: Memperkecil logo social button dari h-12 w-12 menjadi h-10 w-10 -->
                        <a
                            v-if="googleLoginUrl"
                            :href="googleLoginUrl"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:border-white/20 hover:bg-white/10 active:scale-95"
                            title="Login with Google"
                        >
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    fill="#EA4335"
                                    d="M12 10.2v3.9h5.5c-.2 1.2-.9 2.3-1.9 3.1l3 2.3c1.8-1.7 2.9-4.2 2.9-7.2 0-.7-.1-1.4-.2-2H12z"
                                />
                                <path
                                    fill="#34A853"
                                    d="M12 22c2.7 0 4.9-.9 6.6-2.5l-3-2.3c-.8.6-1.9 1-3.6 1-2.8 0-5.1-1.9-5.9-4.4l-3.1 2.4C4.7 19.7 8.1 22 12 22z"
                                />
                                <path
                                    fill="#4A90E2"
                                    d="M6.1 13.8c-.2-.6-.3-1.2-.3-1.8s.1-1.2.3-1.8L3 7.8C2.4 9 2 10.5 2 12s.4 3 1 4.2l3.1-2.4z"
                                />
                                <path
                                    fill="#FBBC05"
                                    d="M12 5.8c1.5 0 2.8.5 3.8 1.5l2.8-2.8C16.9 2.9 14.7 2 12 2 8.1 2 4.7 4.3 3 7.8l3.1 2.4c.8-2.5 3.1-4.4 5.9-4.4z"
                                />
                            </svg>
                        </a>

                        <!-- GitHub -->
                        <a
                            v-if="githubLoginUrl"
                            :href="githubLoginUrl"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:border-white/20 hover:bg-white/10 active:scale-95"
                            title="Login with GitHub"
                        >
                            <svg
                                class="h-6 w-6 shrink-0"
                                fill="#ffffff"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </a>

                        <!-- Discord -->
                        <a
                            v-if="discordLoginUrl"
                            :href="discordLoginUrl"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:border-[#5865F2]/50 hover:bg-[#5865F2]/20 active:scale-95"
                            title="Login with Discord"
                        >
                            <svg
                                class="h-6 w-6 shrink-0"
                                fill="#5865F2"
                                viewBox="0 0 127.14 96.36"
                                aria-hidden="true"
                            >
                                <path
                                    d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.1,46,96,53,91,65.69,84.69,65.69Z"
                                />
                            </svg>
                        </a>
                    </div>

                    <!-- Sign Up Link -->
                    <!-- [PENJELASAN KODE UPDATE]: Mengurangi jarak sign up link (mt-6 jadi mt-4) -->
                    <div class="mt-4 flex flex-col items-center space-y-1 pt-1">
                        <p class="text-[12px] font-bold text-slate-500">
                            Belum ada akun?
                            <Link prefetch
                                :href="route('register')"
                                class="ml-1 font-black text-cyan-400 transition hover:text-cyan-300 hover:underline"
                            >
                                Daftar Sekarang
                            </Link>
                        </p>
                        <p class="text-center text-[10px] font-bold text-slate-600 px-6">
                            Melanjutkan berarti setuju dengan
                            <Link prefetch
                                :href="route('terms')"
                                class="text-slate-500 hover:text-cyan-400 hover:underline"
                                >Syarat</Link
                            >
                            &
                            <Link prefetch
                                :href="route('privacy')"
                                class="text-slate-500 hover:text-cyan-400 hover:underline"
                                >Privasi</Link
                            >
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
        0%,
        33.33% {
            opacity: 1;
            transform: scale(1.05);
        }
        38%,
        95% {
            opacity: 0;
            transform: scale(1);
        }
        100% {
            opacity: 1;
            transform: scale(1.05);
        }
    }
    @keyframes fadeZoom2 {
        0%,
        28% {
            opacity: 0;
            transform: scale(1);
        }
        33.33%,
        66.66% {
            opacity: 1;
            transform: scale(1.05);
        }
        71%,
        100% {
            opacity: 0;
            transform: scale(1);
        }
    }
    @keyframes fadeZoom3 {
        0%,
        61% {
            opacity: 0;
            transform: scale(1);
        }
        66.66%,
        100% {
            opacity: 1;
            transform: scale(1.05);
        }
    }

    .animate-slideshow-1 {
        animation: fadeZoom1 24s infinite ease-in-out;
    }
    .animate-slideshow-2 {
        animation: fadeZoom2 24s infinite ease-in-out;
    }
    .animate-slideshow-3 {
        animation: fadeZoom3 24s infinite ease-in-out;
    }
</style>

<style>
    /* [KOMENTAR PENJELASAN]: Class global khusus untuk halaman Login agar scrollbar hilang 
   sepenuhnya sesuai permintaan. `overflow: hidden !important` memastikan halaman terkunci 
   tidak bisa digulir, sehingga memaksakan UI agar 100% muat di satu layar (viewport). */
    .no-scrollbar-login {
        overflow: hidden !important;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
    }
    .no-scrollbar-login::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }
</style>
