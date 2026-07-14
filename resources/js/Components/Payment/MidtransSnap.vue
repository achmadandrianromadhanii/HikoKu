<script setup lang="ts">
import { onMounted, onUnmounted, ref, nextTick } from 'vue'

const props = defineProps<{
    clientKey: string
    snapToken: string
    onSuccessRedirect: string
    onFailedRedirect: string
}>()

const isLoaded = ref(false)
let observer: MutationObserver | null = null

onUnmounted(() => {
    if (observer) {
        observer.disconnect()
    }
})

onMounted(() => {
    // [UPDATE]: Observasi DOM untuk mencegat penyisipan Iframe oleh Midtrans.
    // Midtrans Snap membuat iframe pembayaran (VA, dll) tanpa menyertakan aturan izin 'clipboard'.
    // Akibatnya, API Clipboard diblokir (Permissions policy violation) dan fitur "Copy Virtual Account" tidak berfungsi optimal di beberapa browser.
    // Kode native ini memperbaiki bug tersebut secara aman dari sisi client.
    const snapContainer = document.getElementById('snap-container')
    if (snapContainer) {
        observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                mutation.addedNodes.forEach((node: any) => {
                    if (node.nodeName === 'IFRAME') {
                        // Injeksi manual akses penuh clipboard ke dalam Iframe Midtrans
                        const currentAllow = node.getAttribute('allow') || ''
                        if (!currentAllow.includes('clipboard-write')) {
                            node.setAttribute('allow', currentAllow ? currentAllow + '; clipboard-read; clipboard-write' : 'clipboard-read; clipboard-write')
                        }
                    }
                })
            }
        })
        observer.observe(snapContainer, { childList: true, subtree: true })
    }

    // [UPDATE]: Mendefinisikan URL script secara dinamis
    const isProduction = import.meta.env.VITE_MIDTRANS_IS_PRODUCTION === 'true'
    const scriptSrc = isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js'

    // [UPDATE]: Mencegah pemuatan ganda (duplicate script) saat navigasi SPA (Inertia).
    // Memuat script berulang kali akan menumpuk event listener `message` di `window`,
    // yang menjadi penyebab utama error "Failed to execute 'postMessage' on 'DOMWindow' target origin mismatch" di console.
    const existingScript = document.querySelector(`script[src="${scriptSrc}"]`)

    if (existingScript) {
        // Script sudah tertanam di head dari render sebelumnya, langsung gunakan
        isLoaded.value = true
        // Beri sedikit jeda agar DOM ter-render utuh sebelum embed
        setTimeout(() => initSnap(), 100)
    } else {
        // Script belum ada, buat dan muat baru
        const script = document.createElement('script')
        script.src = scriptSrc
        script.setAttribute('data-client-key', props.clientKey)
        script.onload = async () => {
            isLoaded.value = true
            // [UPDATE]: Menambahkan Vue nextTick dan delay 500ms untuk menyelesaikan "Iframe Loading Race Condition".
            // Ini memberi waktu bagi browser untuk sepenuhnya me-render DOM container 
            // dan memberi waktu bagi script internal Midtrans untuk benar-benar siap (ready)
            // sebelum snap.embed() dijalankan, sehingga mencegah error 'postMessage' origin mismatch.
            await nextTick()
            setTimeout(() => {
                initSnap()
            }, 500)
        }
        document.head.appendChild(script)
    }
})

const initSnap = () => {
    const snap = (window as any).snap
    if (snap) {
        snap.embed(props.snapToken, {
            embedId: 'snap-container',
            // [UPDATE]: Callback resmi dari Midtrans Snap JS SDK.
            // Setiap callback menerima parameter `result` yang berisi data transaksi
            // langsung dari server Midtrans (order_id, transaction_status, dll).
            // Kita WAJIB meneruskan data ini ke backend agar sinkronisasi status
            // pembayaran berjalan sempurna, terutama di lingkungan localhost
            // di mana webhook Midtrans tidak bisa masuk.
            onSuccess: function (result: any) {
                // [SINKRONISASI]: Kirim order_id & transaction_status ke PaymentController::success()
                // agar backend bisa mengubah status payment dari 'pending' ke 'paid'
                // dan mengonfirmasi rental secara otomatis.
                const url = new URL(props.onSuccessRedirect, window.location.origin)
                url.searchParams.set('order_id', result.order_id || '')
                url.searchParams.set('transaction_status', result.transaction_status || 'settlement')
                window.location.href = url.toString()
            },
            onPending: function () {
                // Status pending — biarkan poller yang menangani polling berkala
            },
            onError: function () {
                window.location.href = props.onFailedRedirect
            },
            onClose: function () {
                // User menutup modal pembayaran tanpa menyelesaikan
            }
        })
    }
}
</script>

<template>
    <div id="snap-container" class="w-full min-h-[400px] flex items-center justify-center relative bg-white rounded-b-[24px]">
        <!-- Loading UI Mewah Khusus Midtrans -->
        <div v-if="!isLoaded" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-10 space-y-5 rounded-b-[24px]">
            <!-- Spinner Ring -->
            <div class="relative flex items-center justify-center h-14 w-14">
                <div class="absolute inset-0 rounded-full border-4 border-slate-100"></div>
                <div class="absolute inset-0 rounded-full border-4 border-cyan-500 border-t-transparent animate-spin"></div>
                <!-- Ikon Kecil di Tengah -->
                <svg class="h-5 w-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <!-- Teks Animasi -->
            <div class="text-center">
                <h3 class="text-sm font-bold text-slate-800 tracking-tight mb-1">Menyiapkan Midtrans</h3>
                <p class="text-[11px] text-slate-500 font-medium animate-pulse">Membuat koneksi terenkripsi...</p>
            </div>
        </div>
    </div>
</template>
