<script setup>
import { computed } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'max-w-2xl',
    },
    closeOnOverlay: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['close'])

const close = () => emit('close')

const panelClass = computed(() => {
    return `w-full ${props.maxWidth} rounded-3xl bg-white shadow-2xl`
})

const onOverlayClick = () => {
    if (props.closeOnOverlay) {
        close()
    }
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[90] flex items-center justify-center bg-gray-900/50 px-4 py-6"
            @click.self="onOverlayClick">
            <div :class="panelClass">
                <div v-if="title || $slots.header"
                    class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                    <div>
                        <slot name="header">
                            <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
                        </slot>
                    </div>

                    <button type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:border-primary-200 hover:text-primary-600"
                        @click="close">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="px-6 py-5">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="border-t border-gray-100 px-6 py-4">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>