import { defineStore } from "pinia";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

export const useWishlistStore = defineStore("wishlist", () => {
    const productIds = ref([]);

    // [KOMENTAR PENJELASAN]: State untuk melacak apakah ada item baru yang belum dilihat.
    // Menyimpan status ke localStorage agar tidak reset saat halaman di-refresh.
    const hasUnread = ref(localStorage.getItem('wishlist_has_unread') === 'true');

    const markAsRead = () => {
        hasUnread.value = false;
        localStorage.setItem('wishlist_has_unread', 'false');
    };

    const setWishlist = (ids = []) => {
        productIds.value = Array.isArray(ids)
            ? ids.map((id) => Number(id))
            : [];
    };

    const isWishlisted = (productId) => {
        return productIds.value.includes(Number(productId));
    };

    const toggle = (productId) => {
        return new Promise((resolve, reject) => {
            const currentId = Number(productId);
            const wasWishlisted = isWishlisted(currentId);

            // [KOMENTAR PENJELASAN]: Update UI secara Optimistik (Sangat Instan).
            // Vue langsung mengubah state di browser detik itu juga saat tombol diklik.
            // Tidak perlu menunggu server membalas, jadinya 100% realtime dan ringan.
            if (wasWishlisted) {
                productIds.value = productIds.value.filter((id) => id !== currentId);
                // Sembunyikan badge otomatis jika wishlist sudah benar-benar kosong
                if (productIds.value.length === 0) {
                    hasUnread.value = false;
                    localStorage.setItem('wishlist_has_unread', 'false');
                }
            } else {
                productIds.value = [...productIds.value, currentId];
                // [KOMENTAR PENJELASAN]: Munculkan badge kembali ketika ada produk BARU ditambahkan
                hasUnread.value = true;
                localStorage.setItem('wishlist_has_unread', 'true');
            }

            router.post(
                route("wishlist.toggle"),
                { product_id: currentId },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        resolve({
                            wishlisted: !wasWishlisted
                        });
                    },
                    onError: (errors) => {
                        // [KOMENTAR PENJELASAN]: Rollback State.
                        // Apabila internet terputus atau server gagal merespon, maka kembalikan UI ke state awal.
                        if (wasWishlisted) {
                            productIds.value = [...productIds.value, currentId];
                        } else {
                            productIds.value = productIds.value.filter((id) => id !== currentId);
                        }
                        reject(errors);
                    },
                },
            );
        });
    };

    return {
        productIds,
        hasUnread,
        setWishlist,
        isWishlisted,
        toggle,
        markAsRead,
    };
});
