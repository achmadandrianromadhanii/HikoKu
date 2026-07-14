<script setup>
import { computed } from 'vue'
import { Heart } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'

const props = defineProps({
    productId: {
        type: [Number, String],
        required: true,
    },
    size: {
        type: String,
        default: 'md',
    },
    iconOnly: {
        type: Boolean,
        default: true,
    },
})

const authStore = useAuthStore()
const wishlistStore = useWishlistStore()

const isActive = computed(() => wishlistStore.isWishlisted(props.productId))

const sizeClass = computed(() => {
    if (props.size === 'sm') return 'h-8 w-8'
    if (props.size === 'lg') return 'h-11 w-11'
    return 'h-9 w-9'
})

const iconClass = computed(() => {
    if (props.size === 'sm') return 'h-4 w-4'
    if (props.size === 'lg') return 'h-5 w-5'
    return 'h-[18px] w-[18px]'
})

// [KOMENTAR PENJELASAN]: Mengubah pewarnaan tombol Love. Jika sudah diklik, warnanya menjadi Merah Terang.
const buttonClass = computed(() => {
    if (isActive.value) {
        return 'border-red-200 bg-red-50 text-red-500 shadow-sm'
    }

    return 'border-surface-200 bg-white text-surface-600 hover:border-red-200 hover:bg-red-50/60 hover:text-red-500'
})

// [KOMENTAR PENJELASAN]: Menerapkan "Optimistic UI", tidak ada loading state.
// Saat diklik, tombol langsung bereaksi tanpa delay sambil server bekerja di latar belakang.
const toggleWishlist = () => {
    if (!authStore.isLoggedIn) {
        window.location.href = route('login')
        return
    }

    wishlistStore.toggle(props.productId)
}
</script>

<template>
    <button type="button" :aria-label="isActive ? 'Hapus dari wishlist' : 'Tambah ke wishlist'" :class="[
        sizeClass,
        buttonClass,
        'inline-flex items-center justify-center rounded-full border transition duration-200 disabled:cursor-not-allowed disabled:opacity-70',
    ]" @click.prevent.stop="toggleWishlist">
        <Heart :class="[iconClass, isActive ? 'fill-current' : '']" />
    </button>
</template>
