<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: 0 },
    hint: { type: String, default: '' },
    icon: { type: [Object, Function], required: true },
    variant: { type: String, default: 'blue' },
})

// Komentar: [FUNGSI ANIMASI ANGKA] Variabel state reaktif untuk menyimpan angka yang sedang melaju (bergerak) saat animasi berlangsung. Dimulai dari angka 0.
const displayValue = ref(0)

// Komentar: [DETEKSI MATA UANG] Mendeteksi secara cerdas apakah nilai asli mengandung "Rp" atau simbol mata uang, agar nanti format animasinya mengikuti tipe uang.
const isCurrency = computed(() => {
    return String(props.value).toLowerCase().includes('rp')
})

// Komentar: [EKSTRAKSI ANGKA MURNI] Mengambil angka asli (menghapus Rp, koma, titik) untuk diubah menjadi integer murni sehingga bisa dikalkulasi animasinya.
const targetNumber = computed(() => {
    const rawString = String(props.value)
    // Hapus semua karakter huruf dan simbol, sisakan hanya angka
    const cleanNumber = rawString.replace(/[^0-9]/g, "")
    return Number(cleanNumber) || 0
})

// Komentar: [LOGIKA CORE ANIMASI SANGAT RINGAN] Fungsi ini menganimasi angka murni secara halus.
// Sangat penting: Kita HANYA menggunakan requestAnimationFrame bawaan browser (Native JS).
// Ini 100% optimal dan hijau untuk metrik Lighthouse (LCP, CLS, INP) karena tidak menggunakan setInterval/setTimeout yang sering menyebabkan lagging. 
// Rendering animasi bergantung murni pada GPU dan refresh rate monitor (sangat smooth).
const animateValue = (start, end, duration) => {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        // Hitung progres waktu yang berjalan (dari 0.0 hingga 1.0)
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        
        // Komentar: [RUMUS PERLAMBATAN/EASING] Menggunakan 'easeOutCubic' (Matematika: 1 - (1-x)^3).
        // Fungsi ini membuat pergerakan angka ngebut di awal, lalu "sangat pelan" dan "berjalan mulus" 
        // ketika akan menyentuh angka target (tidak terburu-buru berhenti secara kaku).
        const easeOut = 1 - Math.pow(1 - progress, 3);
        
        // Memperbarui nilai angka yang tampil di layar
        displayValue.value = Math.floor(start + easeOut * (end - start));
        
        // Jika belum selesai, lanjutkan ke frame (detik) berikutnya
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            // Memastikan frame terakhir berhenti lurus di angka akurat tanpa kesalahan pecahan (anti-bug)
            displayValue.value = end; 
        }
    };
    window.requestAnimationFrame(step);
}

// Komentar: [TRIGGER AWAL] Eksekusi animasi seketika saat kartu ini berhasil dibuat/di-mount di dalam layar web Anda
onMounted(() => {
    // Memberi durasi 2000 milidetik (2 Detik penuh). Durasi pelan agar terasa premium dan mewah.
    animateValue(0, targetNumber.value, 2000); 
})

// Komentar: [REAKTIF REALTIME] Jika data value berubah dari sisi server / websocket, 
// jangan melompat patah-patah! Angka lama akan berlanjut berlari mengejar angka yang baru secara mulus.
watch(targetNumber, (newVal) => {
    animateValue(displayValue.value, newVal, 1500);
})

// Komentar: [FORMAT ULANG] Mengubah kembali angka yang sedang bergerak (integer murni) menjadi format aslinya yang enak dibaca pengguna
const formattedValue = computed(() => {
    // Jika ia terdeteksi sebagai pendapatan / uang (ada huruf Rp)
    if (isCurrency.value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(displayValue.value)
    }
    // Jika angka biasa (misal jumlah produk: 2500 -> 2.500)
    return new Intl.NumberFormat('id-ID').format(displayValue.value)
})

const variantMap = {
    blue: {
        iconWrap: 'bg-blue-500/10 text-blue-600',
        glow: 'shadow-[0_0_15px_rgba(59,130,246,0.4)]',
    },
    emerald: {
        iconWrap: 'bg-emerald-500/10 text-emerald-600',
        glow: 'shadow-[0_0_15px_rgba(16,185,129,0.4)]',
    },
    amber: {
        iconWrap: 'bg-amber-500/10 text-amber-600',
        glow: 'shadow-[0_0_15px_rgba(245,158,11,0.4)]',
    },
    red: {
        iconWrap: 'bg-red-500/10 text-red-600',
        glow: 'shadow-[0_0_15px_rgba(239,68,68,0.4)]',
    },
    slate: {
        iconWrap: 'bg-slate-500/10 text-slate-600',
        glow: 'shadow-[0_0_15px_rgba(100,116,139,0.4)]',
    },
}

const theme = variantMap[props.variant] || variantMap.blue
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-300 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1">
        <div class="flex items-start justify-between gap-4 relative z-10">
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-extrabold uppercase tracking-widest text-slate-500/80">
                    {{ label }}
                </p>

                <p class="mt-2 text-[32px] font-black tracking-tight text-slate-900">
                    <!-- Komentar: Menampilkan formattedValue (hasil animasi native reaktif) agar UI terupdate tanpa merusak layout CSS -->
                    {{ formattedValue }}
                </p>

                <p v-if="hint" class="mt-1 text-xs font-medium text-slate-400">
                    {{ hint }}
                </p>
            </div>

            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl transition-all" :class="[theme.iconWrap]">
                <component :is="icon" class="h-6 w-6" :class="theme.glow" />
            </div>
        </div>
        
        <!-- Subtle decorative corner gradient based on variant color -->
        <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full opacity-[0.03] blur-2xl" 
            :class="'bg-' + props.variant + '-500'">
        </div>
    </div>
</template>