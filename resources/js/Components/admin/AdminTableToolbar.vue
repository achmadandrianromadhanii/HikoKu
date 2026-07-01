<script setup>
import { Search, Filter } from 'lucide-vue-next'

defineProps({
    search: {
        type: String,
        default: '',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari data...',
    },
})

const emit = defineEmits(['update:search', 'apply', 'reset'])
</script>

<template>
    <section class="mb-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="grid flex-1 gap-3">
                <div class="grid gap-3 lg:grid-cols-[minmax(280px,420px)_1fr]">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                        <input :value="search" type="text"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                            :placeholder="searchPlaceholder" @input="emit('update:search', $event.target.value)"
                            @keyup.enter="emit('apply')" />
                    </div>

                    <div class="grid gap-3">
                        <slot name="filters" />
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <slot name="left-actions" />

                <button type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    @click="emit('reset')">
                    Reset
                </button>

                <button type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    @click="emit('apply')">
                    <Filter class="h-4 w-4" />
                    Filter
                </button>

                <slot name="right-actions" />
            </div>
        </div>
    </section>
</template>