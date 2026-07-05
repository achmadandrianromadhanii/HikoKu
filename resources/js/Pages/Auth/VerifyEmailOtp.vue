<script setup>
    import { ref, onMounted, computed, watch } from 'vue';
    import { Head, useForm, router, usePage } from '@inertiajs/vue3';
    import AuthLayout from '@/Layouts/AuthLayout.vue';
    import {
        Mail,
        ArrowRight,
        LoaderCircle,
        ShieldCheck,
        AlertCircle,
        RefreshCw,
    } from 'lucide-vue-next';
    import { useToast } from 'vue-toastification';

    const props = defineProps({
        email: String,
    });

    const page = usePage();
    const toast = useToast();

    // [KOMENTAR PENJELASAN]: Form Inertia untuk memverifikasi OTP (mengandung 6 digit digabung)
    const verifyForm = useForm({
        code: '',
    });

    // [KOMENTAR PENJELASAN]: Form untuk mengirim ulang OTP
    const sendForm = useForm({});

    // [KOMENTAR PENJELASAN]: State untuk 6 kotak input terpisah
    const otpInputs = ref(['', '', '', '', '', '']);
    const inputRefs = ref([]);

    // [KOMENTAR PENJELASAN]: State untuk hitung mundur 5 menit (300 detik)
    const timer = ref(300);
    const timerInterval = ref(null);
    const isTimerRunning = ref(false);
    const otpSent = ref(false);
    const isSuccess = ref(false);

    // [KOMENTAR PENJELASAN]: Format waktu menjadi MM:SS
    const formattedTime = computed(() => {
        const minutes = Math.floor(timer.value / 60);
        const seconds = timer.value % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    });

    // [KOMENTAR PENJELASAN]: Menangani input angka pada tiap kotak OTP. Hanya mengizinkan angka dan memindahkan fokus ke kotak berikutnya.
    const handleInput = (index, event) => {
        const value = event.target.value;
        if (value.match(/^[0-9]$/)) {
            otpInputs.value[index] = value;
            if (index < 5) {
                inputRefs.value[index + 1].focus();
            }
        } else {
            otpInputs.value[index] = ''; // Bersihkan jika bukan angka
        }
        checkAndSubmit();
    };

    // [KOMENTAR PENJELASAN]: Menangani tombol Backspace untuk kembali ke kotak sebelumnya
    const handleKeydown = (index, event) => {
        if (event.key === 'Backspace' && !otpInputs.value[index] && index > 0) {
            inputRefs.value[index - 1].focus();
        }
    };

    // [KOMENTAR PENJELASAN]: Menangani paste kode 6 digit secara langsung
    const handlePaste = (event) => {
        event.preventDefault();
        const pastedData = event.clipboardData.getData('text').slice(0, 6).replace(/\D/g, '');
        if (pastedData.length > 0) {
            for (let i = 0; i < pastedData.length; i++) {
                otpInputs.value[i] = pastedData[i];
            }
            const focusIndex = Math.min(pastedData.length, 5);
            inputRefs.value[focusIndex].focus();
            checkAndSubmit();
        }
    };

    // [KOMENTAR PENJELASAN]: Menggabungkan array 6 kotak menjadi 1 string dan otomatis verifikasi jika penuh
    const checkAndSubmit = () => {
        const code = otpInputs.value.join('');
        if (code.length === 6) {
            verifyForm.code = code;
            submitVerify();
        }
    };

    // [KOMENTAR PENJELASAN]: Fungsi mulai hitung mundur
    const startTimer = () => {
        if (timerInterval.value) clearInterval(timerInterval.value);
        timer.value = 300; // 5 menit
        isTimerRunning.value = true;
        timerInterval.value = setInterval(() => {
            if (timer.value > 0) {
                timer.value--;
            } else {
                clearInterval(timerInterval.value);
                isTimerRunning.value = false;
            }
        }, 1000);
    };

    // [KOMENTAR PENJELASAN]: Menangkap pesan sukses atau error dari flash session Inertia
    watch(
        () => page.props.flash,
        (flash) => {
            if (flash?.success) {
                toast.success(flash.success);
            }
            if (page.props.errors?.otp) {
                toast.error(page.props.errors.otp);
                // Jika diblokir 2 hari atau gagal, stop loading
                sendForm.processing = false;
            }
        },
        { deep: true, immediate: true }
    );

    // [KOMENTAR PENJELASAN]: Kirim OTP
    const sendOtp = () => {
        sendForm.post(route('verify-email-otp.send'), {
            preserveScroll: true,
            onSuccess: () => {
                otpSent.value = true;
                startTimer();
                otpInputs.value = ['', '', '', '', '', ''];
                if (inputRefs.value[0]) inputRefs.value[0].focus();
            },
        });
    };

    // [KOMENTAR PENJELASAN]: Verifikasi OTP
    const submitVerify = () => {
        verifyForm.post(route('verify-email-otp.verify'), {
            preserveScroll: true,
            onSuccess: () => {
                // [UPDATE]: Animasi hijau sukses
                isSuccess.value = true;
                clearInterval(timerInterval.value);
                setTimeout(() => {
                    router.visit(route('profile.edit'));
                }, 1000); // Delay sedikit agar user melihat warna hijau sukses
            },
            onError: () => {
                otpInputs.value = ['', '', '', '', '', ''];
                inputRefs.value[0].focus();
            },
        });
    };

    onMounted(() => {
        // Otomatis kirim OTP saat halaman pertama kali dibuka
        sendOtp();
    });
</script>

<template>
    <Head title="Verifikasi Email" />

    <AuthLayout title="Verifikasi Kode OTP">
        <!-- Background Premium -->
        <div class="fixed inset-0 z-[-1] overflow-hidden bg-slate-900 pointer-events-none">
            <div class="absolute inset-0 z-10 bg-black/50 backdrop-blur-sm"></div>
            <img
                src="https://images.unsplash.com/photo-1506744626753-1fa44df14d28?q=80&w=1920&fm=webp"
                alt="Background"
                class="absolute inset-0 h-full w-full object-cover opacity-30"
            />
        </div>

        <div
            class="mx-auto flex min-h-[calc(100vh-84px)] w-full max-w-[480px] items-center justify-center px-4 py-8"
        >
            <section
                class="w-full rounded-[28px] border border-white/20 bg-white/90 backdrop-blur-xl p-8 shadow-[0_30px_60px_rgba(0,0,0,0.5)] transition-all duration-500"
                :class="{ 'ring-4 ring-emerald-400/50 bg-emerald-50/90': isSuccess }"
            >
                <div class="text-center mb-8">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-600 shadow-inner"
                        :class="{ 'bg-emerald-100 text-emerald-600': isSuccess }"
                    >
                        <ShieldCheck v-if="isSuccess" class="h-8 w-8" />
                        <Mail v-else class="h-8 w-8" />
                    </div>
                    <h2 class="text-2xl font-extrabold text-slate-800">Verifikasi Email Anda</h2>
                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                        Kami telah mengirimkan 6 digit kode OTP rahasia ke alamat email:<br />
                        <strong class="text-slate-800">{{ email }}</strong>
                    </p>
                </div>

                <div
                    v-if="isSuccess"
                    class="py-8 text-center text-emerald-600 font-bold animate-pulse"
                >
                    Verifikasi Berhasil! Mengalihkan...
                </div>

                <div v-else>
                    <!-- Form 6 Kotak OTP -->
                    <div class="mb-6 flex justify-center gap-2 sm:gap-3">
                        <input
                            v-for="(val, index) in otpInputs"
                            :key="index"
                            ref="inputRefs"
                            v-model="otpInputs[index]"
                            @input="handleInput(index, $event)"
                            @keydown="handleKeydown(index, $event)"
                            @paste="handlePaste"
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            class="h-12 w-10 sm:h-14 sm:w-12 rounded-xl border-2 border-slate-200 bg-white text-center text-xl font-bold text-slate-800 shadow-sm transition-all focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20"
                            :class="{
                                'border-red-300 bg-red-50':
                                    page.props.errors.otp || verifyForm.errors.code,
                            }"
                        />
                    </div>

                    <!-- Hitung Mundur & Kirim Ulang -->
                    <div class="flex items-center justify-between text-sm font-medium mt-6">
                        <div
                            class="flex items-center gap-2"
                            :class="isTimerRunning ? 'text-blue-600' : 'text-slate-500'"
                        >
                            <LoaderCircle v-if="isTimerRunning" class="h-4 w-4 animate-spin" />
                            <AlertCircle v-else class="h-4 w-4" />
                            <span>{{ formattedTime }}</span>
                        </div>

                        <button
                            @click="sendOtp"
                            :disabled="isTimerRunning || sendForm.processing"
                            class="flex items-center gap-1.5 text-blue-600 hover:text-blue-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <RefreshCw
                                class="h-3.5 w-3.5"
                                :class="{ 'animate-spin': sendForm.processing }"
                            />
                            <span>Kirim Ulang Kode</span>
                        </button>
                    </div>

                    <div
                        v-if="verifyForm.processing"
                        class="mt-8 text-center text-sm font-semibold text-blue-600 animate-pulse"
                    >
                        Memverifikasi kode...
                    </div>
                </div>
            </section>
        </div>
    </AuthLayout>
</template>
