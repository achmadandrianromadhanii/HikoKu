<script setup>
defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
    emptyTitle: {
        type: String,
        default: 'Data kosong',
    },
    emptyDescription: {
        type: String,
        default: 'Belum ada data untuk ditampilkan.',
    },
    compact: {
        type: Boolean,
        default: true,
    },
})
</script>

<template>
    <div class="flex flex-col min-h-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div v-if="rows.length > 0" class="flex-1 overflow-auto custom-scrollbar">
            <table class="min-w-full">
                <thead class="sticky top-0 z-20 border-b border-slate-200 bg-slate-50 after:absolute after:inset-x-0 after:bottom-0 after:border-b after:border-slate-200/60 shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <tr>
                        <th v-for="column in columns" :key="column.key"
                            class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold text-slate-500">
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="row in rows" :key="row.id || row._key || JSON.stringify(row)"
                        class="border-b border-slate-100 transition hover:bg-slate-50/70">
                        <td v-for="column in columns" :key="column.key" class="px-4 align-middle text-sm text-slate-700"
                            :class="compact ? 'py-3' : 'py-4'">
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                {{ row[column.key] ?? '-' }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="px-6 py-16">
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-200/60 text-slate-400">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7l7 5-7 5Z" />
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">
                    {{ emptyTitle }}
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    {{ emptyDescription }}
                </p>
            </div>
        </div>
    </div>
</template>