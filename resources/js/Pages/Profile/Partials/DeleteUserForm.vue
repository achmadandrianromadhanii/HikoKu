<script setup lang="ts">
    import DangerButton from '@/Components/DangerButton.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import Modal from '@/Components/Modal.vue';
    import SecondaryButton from '@/Components/SecondaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import { useForm, usePage } from '@inertiajs/vue3';
    import { nextTick, ref, computed } from 'vue';

    const confirmingUserDeletion = ref(false);
    const passwordInput = ref<HTMLInputElement | null>(null);

    // [KOMENTAR PENJELASAN]: Mengambil data dari middleware untuk mengecek apakah user punya password atau tidak (login via sosmed)
    const page = usePage();
    const hasPassword = computed(() => page.props.auth.user?.has_password);

    const form = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        confirmingUserDeletion.value = true;

        if (hasPassword.value) {
            nextTick(() => passwordInput.value?.focus());
        }
    };

    const deleteUser = () => {
        form.delete(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => {
                if (hasPassword.value) {
                    passwordInput.value?.focus();
                }
            },
            onFinish: () => {
                form.reset();
            },
        });
    };

    const closeModal = () => {
        confirmingUserDeletion.value = false;

        form.clearErrors();
        form.reset();
    };
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">Hapus Akun</h2>

            <p class="mt-1 text-sm text-gray-600">
                Tindakan ini bersifat permanen. Semua data akunmu akan lenyap secara permanen.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Hapus Akun Permanen</DangerButton>

        <!-- [KOMENTAR PENJELASAN]: Modal diubah menjadi ukuran max-width sm agar notifikasinya kecil sesuai permintaan. -->
        <Modal :show="confirmingUserDeletion" @close="closeModal" maxWidth="sm">
            <!-- Animasi kemunculan Modal yang halus bawaan dari komponen Modal.vue Laravel Breeze -->
            <div class="p-6">
                <!-- [KOMENTAR PENJELASAN]: Form konfirmasi password HANYA muncul jika user terdeteksi memiliki password -->
                <template v-if="hasPassword">
                    <h2 class="text-lg font-medium text-gray-900">
                        Apakah anda yakin hapus akun anda saat ini?
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Setelah akun dihapus, semua datanya akan lenyap secara permanen. Masukkan
                        password Anda untuk mengonfirmasi.
                    </p>

                    <div class="mt-6">
                        <InputLabel for="password" value="Password" class="sr-only" />

                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="Ketik Password untuk Mengonfirmasi"
                            @keyup.enter="deleteUser"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <!-- [KOMENTAR PENJELASAN]: Jika user login pakai Google/Sosmed (tidak ada password), hanya tampilkan teks notifikasi simpel -->
                <template v-else>
                    <div class="text-center pt-2 pb-4">
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4"
                        >
                            <svg
                                class="h-6 w-6 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900">Hapus Akun Permanen</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Apakah anda yakin hapus akun anda saat ini? Tindakan ini tidak dapat
                            dibatalkan.
                        </p>
                    </div>
                </template>

                <!-- Tombol aksi (kecil) -->
                <div
                    class="mt-6 flex justify-end gap-3"
                    :class="{ 'justify-center': !hasPassword }"
                >
                    <SecondaryButton @click="closeModal" class="px-4 py-2 text-xs">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="px-4 py-2 text-xs"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Konfirmasi
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
