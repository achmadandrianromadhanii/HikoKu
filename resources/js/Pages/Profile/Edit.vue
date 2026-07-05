<script setup>
    import { computed, onBeforeUnmount, ref } from 'vue';
    import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
    import DefaultLayout from '@/Layouts/DefaultLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import {
        Camera,
        BadgeCheck,
        User,
        Mail,
        Phone,
        ShieldAlert,
        KeyRound,
        LoaderCircle,
        UserCog,
        ChevronLeft,
        ChevronRight,
        LogOut,
        Ticket,
        X,
        Info,
    } from 'lucide-vue-next';
    import RentalCard from '@/Components/rental/RentalCard.vue';

    const page = usePage();
    const user = computed(() => page.props.auth?.user || null);

    // [KOMENTAR PENJELASAN]: Fungsi helper untuk memproses URL gambar. Jika diawali http (seperti dari sosmed), ubah ke ukuran HD jika dari Google. Jika lokal, ambil dari storage.
    const getAvatarUrl = (path) => {
        if (!path) return null;
        if (path.startsWith('http')) {
            // Paksa resolusi tinggi untuk Google
            if (path.includes('googleusercontent.com') && path.includes('=s')) {
                return path.split('=s')[0] + '=s800-c';
            }
            // Paksa resolusi tinggi untuk Github
            if (path.includes('githubusercontent.com') && !path.includes('s=')) {
                return path + (path.includes('?') ? '&s=800' : '?s=800');
            }
            return path;
        }
        return `/storage/${path}`;
    };

    // [KOMENTAR PENJELASAN]: State (variabel) untuk menyimpan gambar pratinjau avatar saat di-upload.
    const preview = ref(getAvatarUrl(user.value?.avatar));
    const objectUrl = ref(null);

    // [KOMENTAR PENJELASAN]: Mengontrol tab mana yang aktif ('personal' untuk Info Pribadi, 'security' untuk Keamanan) untuk Desktop
    const activeTab = ref('personal');

    // [KOMENTAR PENJELASAN]: State untuk Hub & Spoke Navigation (Mobile)
    const activeSection = ref('hub'); // 'hub' | 'edit' | 'eticket' | 'security' | 'delete'
    const slideDirection = ref('right');
    const showLogoutConfirm = ref(false);

    const navigateTo = (section) => {
        slideDirection.value = 'right';
        activeSection.value = section;
    };

    const goBackToHub = () => {
        slideDirection.value = 'left';
        activeSection.value = 'hub';
    };

    // [KOMENTAR PENJELASAN]: Data rental (E-Ticket) diambil langsung dari data asli tanpa paginasi (sinkron 100% Desktop & Mobile)
    const rentalItems = computed(() => page.props.rentals || []);
    const activeRentals = computed(() =>
        rentalItems.value.filter((i) =>
            ['confirmed', 'active', 'overdue', 'pending_payment'].includes(i.status)
        )
    );
    const historyRentals = computed(() =>
        rentalItems.value.filter((i) => ['returned', 'cancelled'].includes(i.status))
    );
    const showHistory = ref(false);

    // [KOMENTAR PENJELASAN]: State untuk Checkbox Konfirmasi Hapus Akun (Sinkron Desktop & Mobile)
    const confirmDelete = ref(false);

    // [KOMENTAR PENJELASAN]: Form untuk mengupdate profil utama (Nama, Email, HP, Avatar)
    const profileForm = useForm({
        _method: 'patch',
        name: user.value?.name || '',
        email: user.value?.email || '',
        phone: user.value?.phone || '',
        avatar: null,
    });

    // [KOMENTAR PENJELASAN]: Form untuk mengubah password
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    // [KOMENTAR PENJELASAN]: Form untuk konfirmasi hapus akun (membutuhkan password)
    const deleteForm = useForm({
        password: '',
    });

    // [KOMENTAR PENJELASAN]: State untuk form hapus akun dikendalikan langsung oleh checkbox tanpa modal terpisah (Sinkron Desktop & Mobile)
    const confirmingUserDeletion = ref(false);

    // [KOMENTAR PENJELASAN]: Fungsi yang dipanggil ketika file avatar dipilih, mengubahnya jadi pratinjau (preview) foto instan
    const onAvatarChange = (event) => {
        const file = event.target.files?.[0] || null;
        profileForm.avatar = file;

        if (objectUrl.value) {
            URL.revokeObjectURL(objectUrl.value);
            objectUrl.value = null;
        }

        if (file) {
            objectUrl.value = URL.createObjectURL(file);
            preview.value = objectUrl.value;
            return;
        }

        preview.value = getAvatarUrl(user.value?.avatar);
    };

    // [KOMENTAR PENJELASAN]: Mengirim data Profil ke server menggunakan Inertia.js secara mulus tanpa reload
    const submitProfile = () => {
        profileForm.post(route('profile.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                profileForm.clearErrors();
                profileForm.reset('avatar');
            },
        });
    };

    // [KOMENTAR PENJELASAN]: Mengirim data Password baru ke server
    const submitPassword = () => {
        passwordForm.put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => {
                passwordForm.reset();
            },
        });
    };

    // [KOMENTAR PENJELASAN]: Mengirim permintaan hapus akun permanen (Tindakan ini diproteksi oleh checkbox verifikasi di frontend)
    const submitDelete = () => {
        deleteForm.delete(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => {},
            onError: () => deleteForm.reset('password'),
        });
    };

    // [KOMENTAR PENJELASAN]: Membersihkan memori pratinjau gambar ketika komponen ditutup agar tidak membebani browser
    onBeforeUnmount(() => {
        if (objectUrl.value) {
            URL.revokeObjectURL(objectUrl.value);
        }
    });
</script>

<template>
    <Head title="Profil Akun" />

    <DefaultLayout>
        <!-- ========================================================= -->
        <!-- [DESKTOP VIEW]: 100% TIDAK DIUBAH (hidden lg:block)       -->
        <!-- ========================================================= -->
        <div class="block">
            <!-- [KOMENTAR PENJELASAN]: Membatasi lebar tampilan profil menjadi max-w-xl agar lebih kompak, lebih ke atas (py-6), dan tidak terlalu melebar (sesuai permintaan). -->
            <section class="mx-auto max-w-xl px-4 py-6 sm:px-6 lg:px-8">
                <!-- 1. HEADER IDENTITAS SENTRAL (HERO PROFILE) -->
                <!-- [KOMENTAR PENJELASAN]: Bagian ini meniru gaya profil premium (seperti gambar 4) dengan avatar terpusat, glowing, dan badge verified. -->
                <div class="mb-8 flex flex-col items-center text-center">
                    <!-- Avatar Bundar dengan efek Glow & Hover Kamera -->
                    <div class="group relative mb-5 h-32 w-32">
                        <div
                            class="absolute inset-0 rounded-full bg-gradient-to-tr from-cyan-400 to-blue-600 opacity-20 blur-xl transition duration-500 group-hover:opacity-40"
                        ></div>

                        <label
                            for="avatar_upload"
                            class="relative flex h-full w-full cursor-pointer items-center justify-center overflow-hidden rounded-full border-4 border-white bg-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.08)] ring-4 ring-cyan-500/20 transition-all duration-300 group-hover:ring-cyan-500/40"
                        >
                            <!-- Gambar Pratinjau -->
                            <img
                                v-if="preview"
                                :src="preview"
                                alt="Avatar User"
                                class="h-full w-full object-cover"
                            />

                            <!-- Jika Belum Ada Foto (Inisial) -->
                            <span v-else class="text-4xl font-extrabold text-slate-300">
                                {{ user?.name?.charAt(0).toUpperCase() || 'U' }}
                            </span>

                            <!-- Lapisan Overlay Kamera saat di-Hover -->
                            <div
                                class="absolute inset-0 flex flex-col items-center justify-center bg-black/40 opacity-0 backdrop-blur-sm transition-opacity duration-300 group-hover:opacity-100"
                            >
                                <Camera class="h-8 w-8 text-white" stroke-width="1.5" />
                            </div>
                        </label>

                        <!-- Input File Tersembunyi (Disambungkan dengan label di atas) -->
                        <input
                            id="avatar_upload"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onAvatarChange"
                        />
                    </div>

                    <!-- Nama & Badge Verified (Centang Biru Asli jika login Sosmed) -->
                    <h1
                        class="flex items-center justify-center gap-2 text-2xl font-extrabold tracking-tight text-slate-800"
                    >
                        {{ user?.name || 'User' }}
                        <svg
                            v-if="user?.is_social_login"
                            class="h-6 w-6 text-blue-500 animate-pop-in"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M11.609 2.012a.56.56 0 0 1 .782 0l2.257 2.128c.154.145.367.202.576.155l3.056-.689a.56.56 0 0 1 .665.485l.348 3.11c.024.21.162.386.357.457l2.946 1.071a.56.56 0 0 1 .326.75l-1.306 2.846a.546.546 0 0 0 0 .422l1.306 2.845a.56.56 0 0 1-.326.75l-2.946 1.072a.548.548 0 0 0-.357.456l-.348 3.111a.56.56 0 0 1-.665.485l-3.056-.69a.555.555 0 0 0-.576.156l-2.257 2.127a.56.56 0 0 1-.782 0l-2.257-2.127a.555.555 0 0 0-.576-.156l-3.056.69a.56.56 0 0 1-.665-.485l-.348-3.111a.548.548 0 0 0-.357-.456l-2.946-1.072a.56.56 0 0 1-.326-.75l1.306-2.845a.546.546 0 0 0 0-.422l-1.306-2.846a.56.56 0 0 1 .326-.75l2.946-1.071a.548.548 0 0 0 .357-.457l.348-3.11a.56.56 0 0 1 .665-.485l3.056.689c.209.047.422-.01.576-.155l2.257-2.128Z"
                                fill="currentColor"
                            />
                            <path
                                d="M10.16 15.617a.644.644 0 0 1-.462-.195l-2.73-2.825a.64.64 0 0 1 .92-.89l2.26 2.338 4.457-4.805a.64.64 0 0 1 .937.87l-4.912 5.295a.64.64 0 0 1-.47.212Z"
                                fill="#ffffff"
                            />
                        </svg>
                    </h1>

                    <!-- Email dengan ikon -->
                    <p
                        class="mt-1 flex items-center justify-center gap-1.5 text-sm font-medium text-slate-500"
                    >
                        <Mail class="h-4 w-4" />
                        {{ user?.email }}
                    </p>
                </div>

                <!-- 2. SISTEM NAVIGASI TABS -->
                <!-- [KOMENTAR PENJELASAN]: Memperbaiki relasi dan sinkronisasi agar Desktop memiliki menu tab yang persis sama fungsionalnya dengan Mobile (Info Pribadi, E-Ticket, dan Keamanan) -->
                <div
                    class="mb-6 flex justify-center gap-2 rounded-2xl bg-white p-1.5 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-slate-100"
                >
                    <button
                        @click="activeTab = 'personal'"
                        type="button"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-300"
                        :class="
                            activeTab === 'personal'
                                ? 'bg-cyan-50 text-cyan-700 shadow-sm'
                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                        "
                    >
                        <UserCog class="h-4 w-4" />
                        <span class="hidden sm:inline">Info Pribadi</span>
                        <span class="sm:hidden">Info</span>
                    </button>
                    <button
                        @click="activeTab = 'eticket'"
                        type="button"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-300"
                        :class="
                            activeTab === 'eticket'
                                ? 'bg-amber-50 text-amber-700 shadow-sm'
                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                        "
                    >
                        <Ticket class="h-4 w-4" />
                        <span class="hidden sm:inline">E-Ticket</span>
                        <span class="sm:hidden">Tiket</span>
                    </button>
                    <button
                        @click="activeTab = 'security'"
                        type="button"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-300"
                        :class="
                            activeTab === 'security'
                                ? 'bg-emerald-50 text-emerald-700 shadow-sm'
                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                        "
                    >
                        <ShieldAlert class="h-4 w-4" />
                        <span class="hidden sm:inline">Keamanan</span>
                        <span class="sm:hidden">Keamanan</span>
                    </button>
                </div>

                <!-- 3. KONTEN TABS DENGAN ANIMASI TRANSISI HALUS -->
                <div class="relative min-h-[400px]">
                    <Transition
                        enter-active-class="transition-all duration-500 ease-out absolute w-full"
                        enter-from-class="opacity-0 translate-y-4 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-300 ease-in absolute w-full"
                        leave-from-class="opacity-100 translate-y-0 scale-100"
                        leave-to-class="opacity-0 -translate-y-4 scale-95"
                    >
                        <!-- TAB 1: INFORMASI PRIBADI -->
                        <div v-if="activeTab === 'personal'" class="w-full">
                            <!-- [KOMENTAR PENJELASAN]: Mengurangi padding p-6 menjadi p-5 agar lebih padat dan tidak terlalu lebar -->
                            <div
                                class="rounded-3xl border border-slate-100 bg-white/70 p-5 backdrop-blur-lg shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:p-6"
                            >
                                <h2 class="text-lg font-bold text-slate-800">Detail Informasi</h2>
                                <p class="mt-1 text-sm text-slate-500 mb-5">
                                    Perbarui identitas diri kamu agar tetap valid.
                                </p>

                                <form @submit.prevent="submitProfile" class="space-y-4">
                                    <!-- Error Avatar ditampilkan di sini jika ada -->
                                    <p
                                        v-if="profileForm.errors.avatar"
                                        class="text-xs font-medium text-red-500 text-center bg-red-50 p-2 rounded-lg"
                                    >
                                        Error Foto: {{ profileForm.errors.avatar }}
                                    </p>

                                    <!-- Baris Nama & HP (Grid 2 Kolom untuk menghemat ruang) -->
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <!-- Input Nama -->
                                        <div class="group relative z-0 w-full">
                                            <input
                                                v-model="profileForm.name"
                                                type="text"
                                                id="name"
                                                placeholder=" "
                                                class="peer block w-full appearance-none rounded-none border-0 border-b-2 border-slate-200 bg-transparent px-0 py-2.5 pl-8 text-sm text-slate-900 transition-colors focus:border-cyan-600 focus:outline-none focus:ring-0"
                                            />
                                            <span
                                                class="pointer-events-none absolute left-0 top-3 text-slate-400 transition-colors peer-focus:text-cyan-600"
                                            >
                                                <User class="h-4.5 w-4.5" stroke-width="2" />
                                            </span>
                                            <label
                                                for="name"
                                                class="absolute left-8 top-3 -z-10 origin-[0] -translate-y-5 scale-75 transform text-sm text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-8 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-600"
                                            >
                                                Nama Lengkap
                                            </label>
                                            <p
                                                v-if="profileForm.errors.name"
                                                class="mt-1 text-xs font-medium text-red-500"
                                            >
                                                {{ profileForm.errors.name }}
                                            </p>
                                        </div>

                                        <!-- Input Nomor HP -->
                                        <div class="group relative z-0 w-full">
                                            <input
                                                v-model="profileForm.phone"
                                                type="text"
                                                id="phone"
                                                placeholder=" "
                                                class="peer block w-full appearance-none rounded-none border-0 border-b-2 border-slate-200 bg-transparent px-0 py-2.5 pl-8 text-sm text-slate-900 transition-colors focus:border-cyan-600 focus:outline-none focus:ring-0"
                                            />
                                            <span
                                                class="pointer-events-none absolute left-0 top-3 text-slate-400 transition-colors peer-focus:text-cyan-600"
                                            >
                                                <Phone class="h-4.5 w-4.5" stroke-width="2" />
                                            </span>
                                            <label
                                                for="phone"
                                                class="absolute left-8 top-3 -z-10 origin-[0] -translate-y-5 scale-75 transform text-sm text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-8 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-600"
                                            >
                                                Nomor HP
                                            </label>
                                            <p
                                                v-if="profileForm.errors.phone"
                                                class="mt-1 text-xs font-medium text-red-500"
                                            >
                                                {{ profileForm.errors.phone }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Input Email (Full width) -->
                                    <!-- [KOMENTAR PENJELASAN]: Email di-disable (read-only) jika user login menggunakan Sosial Media agar datanya tidak bisa diubah sembarangan -->
                                    <div class="group relative z-0 w-full mt-2">
                                        <input
                                            v-model="profileForm.email"
                                            type="email"
                                            id="email"
                                            placeholder=" "
                                            :disabled="user?.is_social_login"
                                            class="peer block w-full appearance-none rounded-none border-0 border-b-2 border-slate-200 bg-transparent px-0 py-2.5 pl-8 pr-24 text-sm text-slate-900 transition-colors focus:border-cyan-600 focus:outline-none focus:ring-0 disabled:text-slate-400 disabled:border-slate-100 disabled:cursor-not-allowed"
                                        />

                                        <span
                                            class="pointer-events-none absolute left-0 top-3 text-slate-400 transition-colors peer-focus:text-cyan-600"
                                        >
                                            <Mail class="h-4.5 w-4.5" stroke-width="2" />
                                        </span>

                                        <label
                                            for="email"
                                            class="absolute left-8 top-3 -z-10 origin-[0] -translate-y-5 scale-75 transform text-sm text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-8 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-cyan-600"
                                        >
                                            Alamat Email
                                            <span
                                                v-if="user?.is_social_login"
                                                class="text-[10px] text-blue-500 font-bold ml-1"
                                                >(Terkunci dari Sosmed)</span
                                            >
                                        </label>

                                        <!-- [KOMENTAR PENJELASAN]: Indikator Verifikasi Email & Tombol OTP -->
                                        <div class="absolute right-0 top-2 flex items-center">
                                            <div
                                                v-if="user?.email_verified_at"
                                                class="flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full border border-emerald-100"
                                            >
                                                <BadgeCheck class="h-3.5 w-3.5" />
                                                <span>Terverifikasi</span>
                                            </div>
                                            <div v-else-if="!user?.is_social_login">
                                                <Link
                                                    :href="route('verify-email-otp')"
                                                    class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full border border-blue-100 transition-colors shadow-sm"
                                                >
                                                    Verifikasi
                                                </Link>
                                            </div>
                                        </div>

                                        <p
                                            v-if="profileForm.errors.email"
                                            class="mt-1 text-xs font-medium text-red-500"
                                        >
                                            {{ profileForm.errors.email }}
                                        </p>
                                    </div>

                                    <!-- Tombol Simpan Profil (Premium Button) -->
                                    <div class="mt-6 pt-2">
                                        <button
                                            type="submit"
                                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-6 text-sm font-bold text-white shadow-[0_8px_20px_rgba(8,145,178,0.25)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_12px_25px_rgba(8,145,178,0.35)] hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0"
                                            :disabled="profileForm.processing"
                                        >
                                            <LoaderCircle
                                                v-if="profileForm.processing"
                                                class="h-5 w-5 animate-spin"
                                            />
                                            <span>{{
                                                profileForm.processing
                                                    ? 'Menyimpan...'
                                                    : 'Simpan Profil'
                                            }}</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- TAB 2: E-TICKET SEWA (BARU UNTUK DESKTOP, SINKRON DENGAN MOBILE) -->
                        <!-- [KOMENTAR PENJELASAN]: Menambahkan logic dan fitur E-Ticket ke Desktop agar fungsionalitas dan relasi backend 100% sama dengan Mobile -->
                        <div v-else-if="activeTab === 'eticket'" class="w-full space-y-6">
                            <div
                                class="rounded-3xl border border-slate-100 bg-white/70 p-5 backdrop-blur-lg shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:p-6"
                            >
                                <!-- Header & Toggle E-Ticket -->
                                <div
                                    class="flex items-center justify-between mb-6 border-b border-slate-100 pb-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="rounded-full bg-amber-100 p-2 text-amber-600">
                                            <Ticket class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-bold text-slate-800">
                                                {{
                                                    showHistory ? 'Riwayat Selesai' : 'Tiket Aktif'
                                                }}
                                            </h2>
                                            <p class="text-sm text-slate-500">
                                                {{
                                                    showHistory
                                                        ? historyRentals.length
                                                        : activeRentals.length
                                                }}
                                                pesanan ditemukan
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        @click="showHistory = !showHistory"
                                        type="button"
                                        class="relative h-7 w-12 rounded-full transition-colors duration-300 border-2 border-transparent focus:outline-none focus:ring-2 focus:ring-amber-500/50"
                                        :class="showHistory ? 'bg-amber-500' : 'bg-slate-300'"
                                    >
                                        <span
                                            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-300"
                                            :class="showHistory ? 'translate-x-5' : 'translate-x-0'"
                                        ></span>
                                    </button>
                                </div>

                                <!-- List Tiket Aktif -->
                                <div v-if="!showHistory" class="space-y-4">
                                    <template v-if="activeRentals.length > 0">
                                        <RentalCard
                                            v-for="rental in activeRentals"
                                            :key="'desktop-active-' + rental.id"
                                            :rental="rental"
                                            class="shadow-sm hover:shadow-md transition-shadow"
                                        />
                                    </template>
                                    <div
                                        v-else
                                        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-amber-100 bg-amber-50/50 py-12 text-center transition-all"
                                    >
                                        <Ticket class="h-8 w-8 text-amber-300 mb-3 animate-pulse" />
                                        <h3 class="text-sm font-bold text-slate-700">
                                            Belum ada tiket aktif
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-1 max-w-[250px]">
                                            Lakukan penyewaan alat dan tiket Anda akan otomatis
                                            muncul di sini.
                                        </p>
                                    </div>
                                </div>

                                <!-- List Riwayat Selesai/Batal -->
                                <div v-else class="space-y-4">
                                    <template v-if="historyRentals.length > 0">
                                        <RentalCard
                                            v-for="rental in historyRentals"
                                            :key="'desktop-history-' + rental.id"
                                            :rental="rental"
                                            class="shadow-sm opacity-70 grayscale-[30%] hover:grayscale-0 hover:opacity-100 transition-all"
                                        />
                                    </template>
                                    <div
                                        v-else
                                        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-12 text-center transition-all"
                                    >
                                        <Info class="h-8 w-8 text-slate-400 mb-3" />
                                        <h3 class="text-sm font-bold text-slate-700">
                                            Belum ada riwayat
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-1 max-w-[250px]">
                                            Pesanan yang telah selesai atau dibatalkan akan masuk ke
                                            riwayat ini.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: KEAMANAN AKUN (Password & Hapus Akun) -->
                        <div v-else-if="activeTab === 'security'" class="w-full space-y-6">
                            <!-- Toggle Email Notification -->
                            <!-- [KOMENTAR PENJELASAN]: Sinkronisasi logic Desktop dan Mobile untuk fitur Notifikasi Email via Inertia POST -->
                            <div
                                class="flex items-center justify-between rounded-3xl border border-emerald-100 bg-emerald-50/50 p-6 sm:p-8 backdrop-blur-lg shadow-[0_8px_30px_rgb(0,0,0,0.04)]"
                            >
                                <div>
                                    <h2
                                        class="text-lg font-bold text-slate-800 flex items-center gap-2"
                                    >
                                        Notifikasi Email
                                    </h2>
                                    <p class="text-sm text-slate-500 mt-1">
                                        Terima email update untuk pesanan sewa Anda.
                                    </p>
                                </div>
                                <Link
                                    :href="route('profile.toggle-notification')"
                                    method="post"
                                    as="button"
                                    preserve-scroll
                                    class="relative h-7 w-12 shrink-0 rounded-full transition-colors duration-300 border-2 border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                                    :class="
                                        user?.notification_email ? 'bg-emerald-500' : 'bg-slate-300'
                                    "
                                >
                                    <span
                                        class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-md transition-transform duration-300"
                                        :class="
                                            user?.notification_email
                                                ? 'translate-x-5'
                                                : 'translate-x-0'
                                        "
                                    ></span>
                                </Link>
                            </div>

                            <!-- Ganti Password Card -->
                            <!-- [KOMENTAR PENJELASAN]: Form Ubah Password Dihilangkan/Disembunyikan jika user menggunakan akun Sosmed (Google, dll) karena password mereka dikendalikan oleh provider Sosmed -->
                            <div
                                v-if="!user?.is_social_login"
                                class="rounded-3xl border border-slate-100 bg-white/70 p-6 backdrop-blur-lg shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:p-8"
                            >
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="rounded-full bg-slate-100 p-2 text-slate-600">
                                        <KeyRound class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-slate-800">
                                            Ubah Password
                                        </h2>
                                        <p class="text-sm text-slate-500">
                                            Kunci keamanan ganda untuk akunmu.
                                        </p>
                                    </div>
                                </div>

                                <form @submit.prevent="submitPassword" class="space-y-5">
                                    <!-- Password Saat Ini -->
                                    <div class="group relative z-0 w-full">
                                        <input
                                            v-model="passwordForm.current_password"
                                            type="password"
                                            id="current_password"
                                            placeholder=" "
                                            class="peer block w-full appearance-none rounded-none border-0 border-b-2 border-slate-200 bg-transparent px-0 py-2.5 text-sm text-slate-900 transition-colors focus:border-slate-800 focus:outline-none focus:ring-0"
                                        />
                                        <label
                                            for="current_password"
                                            class="absolute left-0 top-3 -z-10 origin-[0] -translate-y-5 scale-75 transform text-sm text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-0 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-slate-800"
                                        >
                                            Password Saat Ini
                                        </label>
                                        <p
                                            v-if="passwordForm.errors.current_password"
                                            class="mt-1.5 text-xs font-medium text-red-500"
                                        >
                                            {{ passwordForm.errors.current_password }}
                                        </p>
                                    </div>

                                    <!-- Password Baru & Konfirmasi (Grid 2 Kolom) -->
                                    <div class="grid gap-5 sm:grid-cols-2 pt-2">
                                        <div class="group relative z-0 w-full">
                                            <input
                                                v-model="passwordForm.password"
                                                type="password"
                                                id="new_password"
                                                placeholder=" "
                                                class="peer block w-full appearance-none rounded-none border-0 border-b-2 border-slate-200 bg-transparent px-0 py-2.5 text-sm text-slate-900 transition-colors focus:border-slate-800 focus:outline-none focus:ring-0"
                                            />
                                            <label
                                                for="new_password"
                                                class="absolute left-0 top-3 -z-10 origin-[0] -translate-y-5 scale-75 transform text-sm text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-0 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-slate-800"
                                            >
                                                Password Baru
                                            </label>
                                            <p
                                                v-if="passwordForm.errors.password"
                                                class="mt-1.5 text-xs font-medium text-red-500"
                                            >
                                                {{ passwordForm.errors.password }}
                                            </p>
                                        </div>

                                        <div class="group relative z-0 w-full">
                                            <input
                                                v-model="passwordForm.password_confirmation"
                                                type="password"
                                                id="confirm_password"
                                                placeholder=" "
                                                class="peer block w-full appearance-none rounded-none border-0 border-b-2 border-slate-200 bg-transparent px-0 py-2.5 text-sm text-slate-900 transition-colors focus:border-slate-800 focus:outline-none focus:ring-0"
                                            />
                                            <label
                                                for="confirm_password"
                                                class="absolute left-0 top-3 -z-10 origin-[0] -translate-y-5 scale-75 transform text-sm text-slate-500 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-0 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-slate-800"
                                            >
                                                Konfirmasi Password
                                            </label>
                                            <p
                                                v-if="passwordForm.errors.password_confirmation"
                                                class="mt-1.5 text-xs font-medium text-red-500"
                                            >
                                                {{ passwordForm.errors.password_confirmation }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="pt-3">
                                        <button
                                            type="submit"
                                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-slate-800 px-6 text-sm font-bold text-white transition hover:bg-slate-900 hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-70"
                                            :disabled="passwordForm.processing"
                                        >
                                            <LoaderCircle
                                                v-if="passwordForm.processing"
                                                class="h-4 w-4 animate-spin"
                                            />
                                            <span>{{
                                                passwordForm.processing
                                                    ? 'Menyimpan...'
                                                    : 'Perbarui Password'
                                            }}</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Hapus Akun Card (Danger Zone) -->
                            <div
                                class="rounded-3xl border border-red-100 bg-red-50/50 p-6 sm:p-8 relative overflow-hidden"
                            >
                                <div
                                    class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-red-100 blur-2xl"
                                ></div>

                                <h2 class="text-lg font-bold text-red-600 relative z-10">
                                    Zona Berbahaya
                                </h2>
                                <p class="mt-1 text-sm text-red-500/80 mb-5 relative z-10">
                                    Tindakan ini bersifat permanen. Semua data akunmu akan lenyap
                                    secara permanen.
                                </p>

                                <!-- [KOMENTAR PENJELASAN]: Logika form hapus disinkronkan sepenuhnya dengan Mobile (menggunakan Checkbox konfirmasi tanpa modal tambahan) -->
                                <form
                                    @submit.prevent="submitDelete"
                                    class="relative z-10 space-y-5"
                                >
                                    <div
                                        v-if="!user?.is_social_login"
                                        class="group relative z-0 w-full mb-2"
                                    >
                                        <input
                                            v-model="deleteForm.password"
                                            type="password"
                                            id="delete_password"
                                            placeholder=" "
                                            class="peer block w-full appearance-none rounded-none border-0 border-b-2 border-red-200 bg-transparent px-0 py-2.5 text-sm text-slate-900 transition-colors focus:border-red-500 focus:outline-none focus:ring-0"
                                        />
                                        <label
                                            for="delete_password"
                                            class="absolute left-0 top-3 -z-10 origin-[0] -translate-y-5 scale-75 transform text-sm text-red-400 duration-300 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:left-0 peer-focus:-translate-y-5 peer-focus:scale-75 peer-focus:text-red-500"
                                        >
                                            Ketik Password untuk Mengonfirmasi
                                        </label>
                                        <p
                                            v-if="deleteForm.errors.password"
                                            class="mt-1.5 text-xs font-medium text-red-500"
                                        >
                                            {{ deleteForm.errors.password }}
                                        </p>
                                    </div>

                                    <!-- Custom Checkbox Desktop (Sama dengan Mobile) -->
                                    <label
                                        class="flex items-start gap-3 cursor-pointer select-none group bg-red-50/50 p-3 rounded-xl border border-red-100 transition-all hover:bg-red-50"
                                    >
                                        <div class="relative mt-0.5 shrink-0">
                                            <input
                                                v-model="confirmDelete"
                                                type="checkbox"
                                                class="peer sr-only"
                                            />
                                            <div
                                                class="h-5 w-5 rounded-md border-2 border-red-300 bg-white transition-all duration-200 peer-checked:border-red-500 peer-checked:bg-red-500"
                                            ></div>
                                            <svg
                                                class="absolute top-0.5 left-0.5 h-4 w-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                            >
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <span class="text-sm text-slate-700 leading-relaxed">
                                            Saya memahami bahwa tindakan ini
                                            <strong class="text-red-600 font-bold"
                                                >tidak dapat dibatalkan</strong
                                            >
                                            dan semua data akan dihapus.
                                        </span>
                                    </label>

                                    <button
                                        type="submit"
                                        class="inline-flex h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-2xl bg-red-600 px-6 text-sm font-bold text-white transition hover:bg-red-700 hover:shadow-lg hover:shadow-red-500/20 disabled:cursor-not-allowed disabled:opacity-30"
                                        :disabled="!confirmDelete || deleteForm.processing"
                                    >
                                        <LoaderCircle
                                            v-if="deleteForm.processing"
                                            class="h-4 w-4 animate-spin"
                                        />
                                        <span>Hapus Akun Permanen</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </section>
        </div>
        <!-- End of DESKTOP VIEW -->

        <!-- ========================================================= -->
    </DefaultLayout>
</template>

<style scoped>
    /* [KOMENTAR PENJELASAN]: Menyesuaikan font input form dan autofill agar sinkron dengan gaya aplikasi */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
        transition: background-color 5000s ease-in-out 0s;
        -webkit-text-fill-color: #0f172a;
    }

    /* [KOMENTAR PENJELASAN]: Animasi Pop-in Halus untuk Ikon Verifikasi */
    @keyframes pop-in {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        70% {
            transform: scale(1.2);
            opacity: 1;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    .animate-pop-in {
        animation: pop-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    /* [KOMENTAR PENJELASAN]: Transisi Slide Kanan-Kiri (Native App Feel) */
    .slide-right-enter-active,
    .slide-right-leave-active,
    .slide-left-enter-active,
    .slide-left-leave-active {
        transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    }

    .slide-right-enter-from {
        transform: translateX(100%);
        opacity: 0;
    }
    .slide-right-leave-to {
        transform: translateX(-50%);
        opacity: 0;
    }

    .slide-left-enter-from {
        transform: translateX(-50%);
        opacity: 0;
    }
    .slide-left-leave-to {
        transform: translateX(100%);
        opacity: 0;
    }

    /* [KOMENTAR PENJELASAN]: Transisi Fade untuk Popup */
    .fade-enter-active,
    .fade-leave-active {
        transition: opacity 0.3s ease;
    }
    .fade-enter-from,
    .fade-leave-to {
        opacity: 0;
    }
</style>
