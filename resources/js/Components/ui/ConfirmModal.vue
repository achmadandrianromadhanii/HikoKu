<script setup>
import AppModal from '@/Components/ui/AppModal.vue'
import AppButton from '@/Components/ui/AppButton.vue'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi',
    },
    message: {
        type: String,
        default: 'Apakah kamu yakin ingin melanjutkan?',
    },
    confirmText: {
        type: String,
        default: 'Ya, Lanjutkan',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    variant: {
        type: String,
        default: 'danger',
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
    <AppModal :open="open" :title="title" max-width="max-w-lg" :close-on-overlay="!loading" @close="emit('close')">
        <p class="text-sm leading-7 text-gray-600">
            {{ message }}
        </p>

        <template #footer>
            <div class="flex flex-wrap justify-end gap-3">
                <AppButton variant="ghost" :disabled="loading" @click="emit('close')">
                    {{ cancelText }}
                </AppButton>

                <AppButton :variant="variant" :loading="loading" @click="emit('confirm')">
                    {{ confirmText }}
                </AppButton>
            </div>
        </template>
    </AppModal>
</template>
