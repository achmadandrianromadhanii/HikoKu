import { defineStore } from "pinia";
import { ref } from "vue";

let toastId = 1;

export const useUiStore = defineStore("ui", () => {
    const loading = ref(false);
    const modals = ref({});
    const toasts = ref([]);

    const setLoading = (value) => {
        loading.value = !!value;
    };

    const openModal = (name) => {
        modals.value = {
            ...modals.value,
            [name]: true,
        };
    };

    const closeModal = (name) => {
        modals.value = {
            ...modals.value,
            [name]: false,
        };
    };

    const isModalOpen = (name) => {
        return !!modals.value[name];
    };

    const removeToast = (id) => {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    };

    const showToast = ({
        title = "",
        message = "",
        type = "info",
        duration = 3000,
    } = {}) => {
        const id = toastId++;

        toasts.value.push({
            id,
            title,
            message,
            type,
        });

        if (duration > 0) {
            window.setTimeout(() => {
                removeToast(id);
            }, duration);
        }

        return id;
    };

    const clearToasts = () => {
        toasts.value = [];
    };

    return {
        loading,
        modals,
        toasts,
        setLoading,
        openModal,
        closeModal,
        isModalOpen,
        showToast,
        removeToast,
        clearToasts,
    };
});
