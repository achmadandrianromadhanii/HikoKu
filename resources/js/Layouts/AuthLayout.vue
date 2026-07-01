<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { watch, onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import AppToast from '@/Components/ui/AppToast.vue'

defineProps({
    title: {
        type: String,
        default: 'Auth',
    },
})

const page = usePage()
const uiStore = useUiStore()

// [KOMENTAR PENJELASAN]: Fungsi ini bertugas memantau (watch) pesan flash dari server (misal sukses/gagal login sosmed)
// dan langsung memunculkan animasi notifikasi halus menggunakan AppToast.
const checkFlashMessages = () => {
    if (page.props.flash?.success) {
        uiStore.showToast({
            title: 'Berhasil',
            message: page.props.flash.success,
            type: 'success',
        })
    }
    if (page.props.flash?.error) {
        uiStore.showToast({
            title: 'Gagal',
            message: page.props.flash.error,
            type: 'error',
        })
    }
}

onMounted(() => {
    checkFlashMessages()
})

watch(() => page.props.flash, () => {
    checkFlashMessages()
}, { deep: true })

</script>

<template>

    <Head :title="title" />

    <AppToast />

    <div class="relative min-h-screen overflow-hidden bg-[#041b39]">
        <div class="absolute inset-0 bg-[linear-gradient(90deg,_#031632_0%,_#06314a_28%,_#0a4a61_62%,_#0e7481_100%)]" />
        <div class="absolute inset-0 opacity-[0.06]">
            <div class="auth-grid h-full w-full" />
        </div>
        <div class="pointer-events-none absolute -left-24 top-8 h-72 w-72 rounded-full bg-cyan-400/12 blur-3xl" />
        <div class="pointer-events-none absolute right-0 top-0 h-80 w-80 rounded-full bg-emerald-300/10 blur-3xl" />
        <div class="pointer-events-none absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-sky-500/10 blur-3xl" />

        <div class="relative z-10 mx-auto flex min-h-screen max-w-[1280px] items-center justify-center px-6 py-6">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.auth-grid {
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.8) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.8) 1px, transparent 1px);
    background-size: 26px 26px;
    mask-image: radial-gradient(circle at center, black, transparent 82%);
}
</style>