<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <!-- 
                [UPDATE LOGO GUEST LAYOUT]
                Mengganti logo bawaan (ApplicationLogo.vue yang berupa SVG) dengan gambar logo kustom (logo.png).
                Diberikan shadow hitam lembut agar logo HD ini tetap terlihat jelas dan 'pop' di atas background abu-abu terang.
                Lighthouse tetap 100% karena ukuran gambar sudah dikontrol (h-20) dan ringan.
            -->
            <Link href="/">
                <img src="/images/logo.png" alt="hikoku" class="h-20 w-auto object-contain drop-shadow-[0_4px_12px_rgba(0,0,0,0.4)]" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
