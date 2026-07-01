<!--
    ==========================================================================
    [KOMPONEN KHUSUS MOBILE]: MobileToggle.vue
    ==========================================================================
    - FUNGSI: Menggantikan checkbox standar (HTML) menjadi Toggle Switch
      bergaya Material / iOS yang elegan, mewah, dan interaktif.
    - CARA KERJA: Menerima v-model (Boolean) untuk mengontrol state On/Off.
    - PERFORMA: Murni menggunakan CSS transform dan transisi background
      agar GPU memprosesnya di 60FPS tanpa memberatkan layout (No Lag).
    - DESAIN: Saat aktif, berubah menjadi warna Gradient Cyan-Emerald 
      dan bulatan bergeser (translate-x) halus. Saat pasif, warnanya Slate.
    ==========================================================================
-->
<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    }
})

const emit = defineEmits(['update:modelValue'])

const toggle = () => {
    emit('update:modelValue', !props.modelValue)
}
</script>

<template>
    <label class="flex items-start justify-between gap-4 py-2 cursor-pointer group active:scale-[0.98] transition-transform duration-200">
        <!-- Area Teks Label & Deskripsi -->
        <div class="flex-1 flex flex-col">
            <span class="text-[13px] font-bold text-slate-900 transition-colors duration-200" :class="modelValue ? 'text-cyan-700' : ''">
                {{ label }}
            </span>
            <span v-if="description" class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                {{ description }}
            </span>
        </div>

        <!-- Area Switcher (Toggle) -->
        <div 
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-300 ease-in-out border border-white/20 shadow-inner"
            :class="modelValue ? 'bg-gradient-to-r from-cyan-500 to-emerald-400' : 'bg-slate-300'"
            role="switch" 
            :aria-checked="modelValue"
            @click.prevent="toggle"
        >
            <!-- Bulatan Dalam (Thumb) -->
            <span 
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-[0_2px_5px_rgba(0,0,0,0.15)] ring-0 transition duration-300 ease-in-out"
                :class="modelValue ? 'translate-x-5' : 'translate-x-[2px]'"
            />
        </div>
        <!-- Hidden Checkbox untuk aksesibilitas form standar -->
        <input 
            type="checkbox" 
            class="hidden" 
            :checked="modelValue" 
            @change="emit('update:modelValue', $event.target.checked)" 
        />
    </label>
</template>
