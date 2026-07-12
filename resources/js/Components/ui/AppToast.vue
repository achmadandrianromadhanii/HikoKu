<script setup>
import { computed } from 'vue'
import { CheckCircle2, Info, AlertTriangle, XCircle, X } from 'lucide-vue-next'
import { useUiStore } from '@/stores/ui'

const uiStore = useUiStore()

const toasts = computed(() => uiStore.toasts)

const typeClassMap = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    error: 'border-red-200 bg-red-50 text-red-600',
    warning: 'border-amber-200 bg-amber-50 text-amber-700',
    info: 'border-blue-200 bg-blue-50 text-blue-700',
}

const iconMap = {
    success: CheckCircle2,
    error: XCircle,
    warning: AlertTriangle,
    info: Info,
}
</script>

<template>
    <div class="pointer-events-none fixed right-4 top-4 z-[95] flex w-full max-w-sm flex-col gap-3">
        <transition-group name="toast" tag="div" class="flex flex-col gap-3">
            <div v-for="toast in toasts" :key="toast.id"
                class="pointer-events-auto overflow-hidden rounded-2xl border shadow-lg"
                :class="typeClassMap[toast.type] || typeClassMap.info">
                <div class="flex items-start gap-3 px-4 py-4">
                    <component :is="iconMap[toast.type] || iconMap.info" class="mt-0.5 h-5 w-5 shrink-0" />

                    <div class="min-w-0 flex-1">
                        <p v-if="toast.title" class="text-sm font-bold">
                            {{ toast.title }}
                        </p>

                        <p class="mt-1 text-sm leading-6">
                            {{ toast.message }}
                        </p>
                    </div>

                    <button type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-current/10 bg-white/40 transition hover:bg-white/70"
                        @click="uiStore.removeToast(toast.id)">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
