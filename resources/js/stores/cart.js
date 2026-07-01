import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useCartStore = defineStore("cart", () => {
    const items = ref([]);

    const setItems = (payload = []) => {
        items.value = Array.isArray(payload) ? payload : [];
    };

    const activeItems = computed(() => {
        const now = new Date();

        return items.value.filter((item) => {
            if (!item.expires_at) return true;
            return new Date(item.expires_at) > now;
        });
    });

    const expiredItems = computed(() => {
        const now = new Date();

        return items.value.filter((item) => {
            if (!item.expires_at) return false;
            return new Date(item.expires_at) <= now;
        });
    });

    const count = computed(() => {
        return activeItems.value.reduce(
            (total, item) => total + Number(item.quantity || 0),
            0,
        );
    });

    const removeItem = (id) => {
        items.value = items.value.filter((item) => item.id !== id);
    };

    const updateQty = (id, quantity) => {
        items.value = items.value.map((item) =>
            item.id === id ? { ...item, quantity } : item,
        );
    };

    const clearCart = () => {
        items.value = [];
    };

    return {
        items,
        activeItems,
        expiredItems,
        count,
        setItems,
        removeItem,
        updateQty,
        clearCart,
    };
});
