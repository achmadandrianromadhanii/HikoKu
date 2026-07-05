<script setup>
    import { computed, h, ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import AdminLayout from '@/Layouts/AdminLayout.vue';
    import { Printer, ChevronDown } from 'lucide-vue-next';

    // Definisi layout admin dengan judul halaman yang sesuai.
    defineOptions({
        layout: (h, page) =>
            h(AdminLayout, { title: 'Laporan Finansial', subtitle: 'Dokumen Resmi' }, () => page),
    });

    // Menerima data dari ReportController (Backend Laravel)
    const props = defineProps({
        year: {
            type: Number,
            required: true,
        },
        monthlyReport: {
            type: Array,
            required: true,
        },
        stats: {
            type: Object,
            required: true,
        },
    });

    // Fungsi pemformatan mata uang ke Rupiah dengan rapi
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    // Fungsi pemformatan rata-rata pendapatan per transaksi (AOV)
    const formatAOV = (income, rentals) => {
        if (rentals === 0) return '-';
        return formatCurrency(income / rentals);
    };

    // Menjalankan fitur cetak bawaan browser (Memanggil dialog Print CSS khusus)
    const printReport = () => {
        window.print();
    };

    // Fungsi mengganti tahun laporan. Ini akan melakukan request ulang (XHR) via Inertia
    // tanpa merefresh penuh halaman (Lighthouse Perf 100%).
    const changeYear = (e) => {
        const selectedYear = e.target.value;
        router.get(route('admin.reports.index'), { year: selectedYear }, { preserveState: true });
    };

    // Menghasilkan rentang tahun untuk dropdown (3 tahun ke belakang hingga 1 tahun ke depan)
    const availableYears = computed(() => {
        const currentYear = new Date().getFullYear();
        return Array.from({ length: 5 }, (_, i) => currentYear - 3 + i);
    });
</script>

<template>
    <!-- Komentar: Menambahkan wrapper h-full overflow-y-auto agar halaman laporan memiliki scrollbar sendiri dan tidak menscroll seluruh halaman website. -->
    <div
        class="h-full w-full overflow-y-auto custom-scrollbar pr-2 pb-6 print:overflow-visible print:h-auto"
    >
        <!-- 
          Desain "Paper View": Kontainer utama dibatasi lebarnya (max-w-4xl) 
          dan diletakkan di tengah agar tidak melebar (stretching).
          Memberikan ilusi visual bahwa Admin sedang menatap "lembaran kertas fisik" di layar.
        -->
        <div class="max-w-4xl mx-auto space-y-5 report-container pt-2 print:space-y-4">
            <!-- HEADER DOKUMEN: Berisi judul, kontrol tahun (inline), dan tombol cetak yang halus -->
            <div
                class="flex flex-col sm:flex-row justify-between items-end sm:items-center border-b border-slate-200 pb-3 print-hide"
            >
                <div>
                    <h1
                        class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2"
                    >
                        Laporan Laba Bersih
                        <!-- Filter Tahun yang menyatu dengan judul (Inline Dropdown) agar tidak terlihat seperti form input raksasa -->
                        <div class="relative inline-block ml-1">
                            <select
                                :value="year"
                                @change="changeYear"
                                class="appearance-none bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold py-1 pl-3 pr-8 rounded-lg cursor-pointer outline-none transition-colors border-none focus:ring-0"
                            >
                                <option v-for="y in availableYears" :key="y" :value="y">
                                    {{ y }}
                                </option>
                            </select>
                            <ChevronDown
                                class="h-3 w-3 absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none"
                            />
                        </div>
                    </h1>
                    <p class="text-[12px] text-slate-500 mt-1 font-medium">
                        Dokumen rekapitulasi transaksi resmi dan performa finansial.
                    </p>
                </div>

                <!-- Tombol Cetak yang Ramping dan Minimalis (Subtle) -->
                <button
                    @click="printReport"
                    class="mt-4 sm:mt-0 inline-flex items-center gap-2 px-3 py-1.5 text-[12px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors shadow-[0_1px_2px_rgba(0,0,0,0.02)]"
                >
                    <Printer class="h-3.5 w-3.5" />
                    Cetak Laporan
                </button>
            </div>

            <!-- HEADER KHUSUS CETAK: Hanya muncul saat dokumen di-print (CTRL+P) -->
            <div class="hidden print-block text-center border-b-[3px] pb-5 mb-6 border-slate-900">
                <h1 class="text-2xl font-black uppercase tracking-widest text-slate-900">
                    GearHike - Laporan Laba Bersih
                </h1>
                <p class="text-sm text-slate-600 font-semibold mt-1">
                    Periode Transaksi Laporan: Tahun {{ year }}
                </p>
            </div>

            <!-- 
          RINGKASAN EKSEKUTIF (Executive Summary): Menggantikan 4 kotak grid angka raksasa.
          Menyajikan data dalam format kalimat paragraf elegan (seperti surat resmi korporat).
        -->
            <div
                class="bg-white p-5 rounded-xl border border-slate-200 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] leading-relaxed text-[13px] text-slate-600 print-no-border print-no-padding"
            >
                <p>
                    Berdasarkan rekapitulasi data finansial yang terekam pada sistem, selama periode
                    <strong>Tahun {{ year }}</strong
                    >, perusahaan telah berhasil memproses total
                    <strong>{{ stats.total_rentals_year }} transaksi penyewaan</strong> yang selesai
                    secara penuh. Secara agregat, volume transaksi ini mengkontribusikan pendapatan
                    kotor sebesar
                    <strong
                        class="text-slate-900 tabular-nums font-black bg-slate-100 px-1 rounded"
                        >{{ formatCurrency(stats.total_income_year) }}</strong
                    >. Bila dianalisa lebih lanjut, rata-rata pendapatan yang dihasilkan per
                    transaksi (AOV) pada rentang tahun ini terukur di angka
                    <strong class="text-slate-900 tabular-nums font-bold">{{
                        formatAOV(stats.total_income_year, stats.total_rentals_year)
                    }}</strong
                    >. Untuk performa khusus di bulan berjalan ini, telah terkumpul
                    <strong>{{ stats.current_month_rentals }} transaksi</strong> senilai
                    <strong class="tabular-nums font-bold text-slate-800">{{
                        formatCurrency(stats.current_month_income)
                    }}</strong
                    >.
                </p>
            </div>

            <!-- 
          TABEL RINCIAN: Dibuat sangat padat (High Density), garis halus, dan tipografi khusus.
          Fitur "tabular-nums" digunakan agar struktur angka Rupiah (Titik Ribuan) sejajar lurus vertikal.
        -->
            <div
                class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] print-no-border"
            >
                <table class="w-full text-left text-[12px] whitespace-nowrap">
                    <thead
                        class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-extrabold text-[10px] uppercase tracking-widest"
                    >
                        <tr>
                            <th class="px-5 py-3 w-1/3">Bulan</th>
                            <th class="px-5 py-3 text-center">Volume Transaksi</th>
                            <th class="px-5 py-3 text-right">Rata-rata / TRX</th>
                            <th class="px-5 py-3 text-right">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="(row, index) in monthlyReport"
                            :key="row.month"
                            class="group hover:bg-slate-100/50 even:bg-slate-50/80 transition-colors"
                        >
                            <!-- Nama Bulan -->
                            <td class="px-5 py-2.5 font-bold text-slate-700">
                                {{ row.month_name }}
                            </td>

                            <!-- Volume Transaksi -->
                            <td
                                class="px-5 py-2.5 text-center text-slate-600 tabular-nums font-medium"
                            >
                                {{ row.rentals > 0 ? row.rentals : '-' }}
                            </td>

                            <!-- Rata-rata per Transaksi (AOV) -->
                            <td
                                class="px-5 py-2.5 text-right text-slate-500 tabular-nums font-medium"
                            >
                                {{ row.rentals > 0 ? formatAOV(row.income, row.rentals) : '-' }}
                            </td>

                            <!-- Total Pendapatan Bulan Tersebut -->
                            <td
                                class="px-5 py-2.5 text-right font-black text-slate-900 tabular-nums"
                            >
                                {{ row.income > 0 ? formatCurrency(row.income) : '-' }}
                            </td>
                        </tr>

                        <!-- BARIS TOTAL TAHUNAN: Sebagai kesimpulan dari tabel -->
                        <tr class="bg-slate-800 text-white font-black">
                            <td
                                class="px-5 py-3.5 uppercase text-[10px] tracking-widest text-slate-300"
                            >
                                Total Tahun {{ year }}
                            </td>
                            <td class="px-5 py-3.5 text-center tabular-nums">
                                {{ stats.total_rentals_year }}
                            </td>
                            <td
                                class="px-5 py-3.5 text-right tabular-nums text-slate-300 font-bold"
                            >
                                {{ formatAOV(stats.total_income_year, stats.total_rentals_year) }}
                            </td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-[13px] text-white">
                                {{ formatCurrency(stats.total_income_year) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- AREA TANDA TANGAN: Rapi, elegan, diposisikan ke pojok kanan untuk dicetak -->
            <div class="mt-8 pt-4 flex justify-end print:mt-4 print:pt-2">
                <div class="text-center w-56">
                    <!-- Spasi kosong untuk ruang tanda tangan pena (Diperkecil agar muat 1 kertas) -->
                    <p
                        class="text-[12px] text-slate-600 mb-16 print:mb-12 font-medium uppercase tracking-wider"
                    >
                        Mengetahui,
                    </p>
                    <div class="border-b-2 border-slate-800 mb-1.5"></div>
                    <p class="font-black text-slate-900 text-[13px] tracking-widest uppercase">
                        Administrator
                    </p>
                    <p class="text-[10px] text-slate-400 mt-1">
                        Dicetak pada:
                        {{
                            new Date().toLocaleDateString('id-ID', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })
                        }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    /* 
 * CSS SPESIFIK UNTUK CETAK (MEDIA PRINT)
 * Menjamin hasil cetak sempurna seperti dokumen asli.
 * Menyembunyikan elemen web yang tidak perlu dicetak (seperti sidebar dan tombol cetak).
 */
    @media print {
        body {
            background-color: white !important;
        }

        /* Menyembunyikan elemen bawaan AdminLayout seperti Sidebar & Topbar */
        aside,
        header,
        nav,
        .print-hide {
            display: none !important;
        }

        /* Menetralkan margin & padding yang aslinya dipakai untuk ruang sidebar di desktop */
        .lg\:pl-\[230px\] {
            padding-left: 0 !important;
        }

        .min-h-screen {
            min-height: auto !important;
        }

        /* Menghilangkan bayangan dan border melengkung agar tinta printer tidak boros dan lebih formal */
        .print-no-border {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        .print-no-padding {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        /* Menampilkan header teks besar yang khusus didesain untuk kertas */
        .print-block {
            display: block !important;
        }

        /* Aturan browser standar: memaksa warna background tercetak (untuk Zebra Striping baris tabel) */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Mencegah tabel terpotong separuh di tengah saat berpindah halaman kertas A4 */
        table {
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        /* Memastikan keseluruhan laporan berusaha sebisa mungkin muat di 1 halaman */
        .report-container {
            page-break-inside: avoid;
        }

        /* Setting ukuran margin batas kertas yang lebih kecil agar muat 1 halaman A4/F4 */
        @page {
            size: auto;
            margin: 1cm;
        }

        /* Menyesuaikan padding tabel saat diprint agar lebih hemat ruang vertikal */
        td,
        th {
            padding-top: 6px !important;
            padding-bottom: 6px !important;
        }
    }
</style>
