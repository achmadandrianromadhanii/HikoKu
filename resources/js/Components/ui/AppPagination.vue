<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
})
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center gap-2">
        <template v-for="(link, index) in links" :key="index">
            <Link v-if="link.url" :href="link.url"
                class="inline-flex min-w-[40px] items-center justify-center rounded-xl border px-3 py-2 text-sm transition"
                :class="link.active
                    ? 'border-primary-600 bg-primary-600 text-white'
                    : 'border-gray-200 bg-white text-gray-700 hover:border-primary-200 hover:text-primary-600'"
                v-html="link.label" />

            <span v-else
                class="inline-flex min-w-[40px] items-center justify-center rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-400"
                v-html="link.label" />
        </template>
    </div>
</template>
