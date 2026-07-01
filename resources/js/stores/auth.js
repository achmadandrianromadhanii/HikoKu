import { defineStore } from "pinia";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export const useAuthStore = defineStore("auth", () => {
    const page = usePage();

    const user = computed(() => page.props.auth?.user || null);

    const isLoggedIn = computed(() => !!user.value);

    const isAdmin = computed(() => {
        return user.value?.role === "admin";
    });

    const isVerified = computed(() => {
        return !!user.value?.email_verified_at;
    });

    return {
        user,
        isLoggedIn,
        isAdmin,
        isVerified,
    };
});
