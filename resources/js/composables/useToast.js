import { useUiStore } from "@/stores/ui";

export function useToast() {
    const uiStore = useUiStore();

    const success = (message, title = "Berhasil") => {
        uiStore.showToast({
            type: "success",
            title,
            message,
        });
    };

    const error = (message, title = "Terjadi Kesalahan") => {
        uiStore.showToast({
            type: "error",
            title,
            message,
            duration: 4000,
        });
    };

    const info = (message, title = "Informasi") => {
        uiStore.showToast({
            type: "info",
            title,
            message,
        });
    };

    const warning = (message, title = "Perhatian") => {
        uiStore.showToast({
            type: "warning",
            title,
            message,
            duration: 4000,
        });
    };

    return {
        success,
        error,
        info,
        warning,
    };
}
