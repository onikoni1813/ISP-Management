<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

defineProps({
    page: Object,
});
</script>

<template>
    <Head :title="`${page.seo_title || page.title} - Pirgacha Internet`">
        <meta v-if="page.seo_description" name="description" :content="page.seo_description" />
    </Head>

    <WebsiteLayout>
        <div class="py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="space-y-2 border-b border-slate-800 pb-6">
                <div class="flex items-center gap-2 text-xs text-cyan-400 font-bold uppercase tracking-widest">
                    <span>Document</span>
                    <span>•</span>
                    <span>{{ new Date(page.updated_at).toLocaleDateString() }}</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-white">{{ page.title }}</h1>
            </div>

            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-8 backdrop-blur-sm prose prose-invert max-w-none text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                {{ page.content || 'Content for this page is being updated.' }}
            </div>
        </div>
    </WebsiteLayout>
</template>
