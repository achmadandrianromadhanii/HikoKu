<script setup>
    import { ref } from 'vue';
    import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
    import { User, LockKeyhole, Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';

    const page = usePage();

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const showPassword = ref(false);

    const submit = () => {
        form.post(route('admin.login.store'), {
            onFinish: () => {
                form.reset('password');
            },
        });
    };
</script>

<template>
    <Head title="Admin" />

    <!-- 
        Pembaruan: 
        - Melepas AuthLayout untuk kontrol background penuh.
        - Kombinasi warna sederhana (slate/cyan).
        - Menambahkan background gambar pegunungan gelap (berkaitan dengan GearHike).
    -->
    <div
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950"
    >
        <!-- Tema Gambar Latar Belakang Sederhana -->
        <div class="absolute inset-0 z-0">
            <img
                src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?q=80&w=2070&auto=format&fit=crop"
                class="h-full w-full object-cover opacity-30"
                alt="Mountain Background"
                loading="lazy"
            />
            <div class="absolute inset-0 bg-slate-950/60 mix-blend-multiply"></div>
        </div>

        <!-- Kontainer Form -->
        <div class="relative z-10 w-full max-w-[400px] p-6 animate-[fadeInUp_0.8s_ease-out]">
            <!-- Notifikasi Error (Jika Ada) -->
            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="page.props.flash?.error"
                    class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-center text-sm font-medium text-red-300 backdrop-blur-md"
                >
                    {{ page.props.flash.error }}
                </div>
            </transition>

            <div
                class="rounded-[24px] border border-white/10 bg-slate-900/70 p-8 shadow-2xl backdrop-blur-xl"
            >
                <!-- Judul Sederhana Tanpa Logo -->
                <div class="mb-10 text-center">
                    <h2 class="text-3xl font-extrabold tracking-tight text-white drop-shadow-sm">
                        Admin
                    </h2>
                </div>

                <form class="space-y-8" @submit.prevent="submit">
                    <!-- Username dengan Floating Label & Icon di Luar -->
                    <div class="flex items-end gap-3">
                        <!-- Icon di sebelah kolom -->
                        <div class="pb-2.5 text-slate-400">
                            <User class="h-6 w-6" />
                        </div>
                        <div class="relative w-full">
                            <!-- Input: Placeholder harus berupa spasi ' ' agar CSS peer-placeholder-shown bisa mendeteksi kapan input kosong -->
                            <input
                                v-model="form.email"
                                type="text"
                                id="username"
                                autocomplete="username"
                                placeholder=" "
                                class="peer block w-full appearance-none border-0 border-b-2 border-slate-600 bg-transparent px-0 py-2.5 text-base text-white transition-colors focus:border-cyan-500 focus:outline-none focus:ring-0"
                            />

                            <!-- Floating Label Animasi -->
                            <label
                                for="username"
                                class="absolute top-3 -z-10 origin-[0] -translate-y-6 scale-75 transform text-sm text-slate-400 transition-all duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-0 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-cyan-500"
                            >
                                Username
                            </label>
                        </div>
                    </div>
                    <p v-if="form.errors.email" class="ml-9 mt-1 text-xs text-red-400">
                        {{ form.errors.email }}
                    </p>

                    <!-- Password dengan Floating Label & Icon di Luar -->
                    <div class="flex items-end gap-3">
                        <!-- Icon di sebelah kolom -->
                        <div class="pb-2.5 text-slate-400">
                            <LockKeyhole class="h-6 w-6" />
                        </div>
                        <div class="relative w-full">
                            <!-- Input -->
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                autocomplete="current-password"
                                placeholder=" "
                                class="peer block w-full appearance-none border-0 border-b-2 border-slate-600 bg-transparent px-0 py-2.5 pr-10 text-base text-white transition-colors focus:border-cyan-500 focus:outline-none focus:ring-0"
                            />

                            <!-- Floating Label Animasi -->
                            <label
                                for="password"
                                class="absolute top-3 -z-10 origin-[0] -translate-y-6 scale-75 transform text-sm text-slate-400 transition-all duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-0 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-cyan-500"
                            >
                                Password
                            </label>

                            <!-- Toggle Lihat Sandi -->
                            <button
                                type="button"
                                class="absolute bottom-2.5 right-0 text-slate-400 transition-colors hover:text-cyan-400 focus:outline-none"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                    <p v-if="form.errors.password" class="ml-9 mt-1 text-xs text-red-400">
                        {{ form.errors.password }}
                    </p>

                    <!-- Ingat Sesi -->
                    <div class="flex items-center pt-2">
                        <label
                            class="flex cursor-pointer items-center gap-2 text-sm text-slate-400 transition-colors hover:text-white"
                        >
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded border-slate-600 bg-slate-800/50 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-slate-900"
                            />
                            Ingat Sesi
                        </label>
                    </div>

                    <!-- Tombol Masuk -->
                    <button
                        type="submit"
                        class="relative mt-6 flex h-12 w-full items-center justify-center rounded-xl bg-cyan-600 px-4 text-sm font-semibold tracking-wide text-white transition-all duration-300 hover:bg-cyan-500 hover:shadow-[0_0_15px_rgba(6,182,212,0.4)] focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-slate-900 disabled:opacity-70 disabled:shadow-none"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="mr-2 h-5 w-5 animate-spin" />
                        <span v-else>OTORISASI MASUK</span>
                    </button>
                </form>
            </div>

            <!-- Tautan Kembali -->
            <p class="mt-8 text-center text-sm font-medium text-slate-500">
                <Link :href="route('home')" class="transition-colors hover:text-cyan-400">
                    &larr; Beranda
                </Link>
            </p>
        </div>
    </div>
</template>

<style scoped>
    /* 
    Animasi fadeInUp yang super ringan dan tidak mempengaruhi Cumulative Layout Shift (CLS) 
    karena menggunakan transform dan opacity murni yang dipercepat oleh GPU (GPU-accelerated).
*/
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
