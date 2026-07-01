<!--
    ==========================================================================
    [KOMPONEN KHUSUS MOBILE]: MobileCheckbox.vue
    ==========================================================================
    - FUNGSI: Menggantikan checkbox standar (HTML) menjadi Checkbox modern 
      dengan animasi memantul (bounce/scale). Sangat cocok untuk daftar 
      keranjang atau filter.
    - CARA KERJA: Menerima v-model (Boolean) untuk mengontrol centang.
    - PERFORMA: Menggunakan CSS Scale dan Opacity untuk performa 60FPS.
    ==========================================================================
-->
<script setup>
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        required: true,
    },
    label: {
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
    <label :for="id" class="flex items-center gap-3 cursor-pointer group active:scale-[0.96] transition-transform duration-200">
        <!-- Kotak Checkbox Asli (Disembunyikan) -->
        <input 
            :id="id"
            type="checkbox" 
            class="hidden" 
            :checked="modelValue"
            @change="emit('update:modelValue', $event.target.checked)"
        />

        <!-- Custom Checkbox Box -->
        <div 
            class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-[6px] border-[1.5px] transition-all duration-300 ease-[cubic-bezier(0.175,0.885,0.32,1.275)] shadow-sm"
            :class="modelValue 
                ? 'border-cyan-500 bg-gradient-to-br from-cyan-400 to-blue-500' 
                : 'border-slate-300 bg-white'"
        >
            <!-- Ikon Centang (Checkmark) SVG -->
            <svg 
                class="pointer-events-none h-3 w-3 text-white transition-all duration-300 ease-out"
                :class="modelValue ? 'opacity-100 scale-100' : 'opacity-0 scale-50'"
                xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 24 24" 
                fill="none" 
                stroke="currentColor" 
                stroke-width="3" 
                stroke-linecap="round" 
                stroke-linejoin="round"
            >
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <!-- Label Teks (Jika ada) -->
        <span v-if="label" class="text-[13px] font-medium text-slate-800" :class="modelValue ? 'font-bold text-slate-900' : ''">
            {{ label }}
        </span>
    </label>
</template>
