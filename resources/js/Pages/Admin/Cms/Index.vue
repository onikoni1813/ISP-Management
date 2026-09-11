<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    pagesCount: Number,
    bannersCount: Number,
    faqsCount: Number,
    noticesCount: Number,
    publishedNotices: Number,
});
</script>

<template>
    <Head title="Website CMS Management - Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-white">Public Website CMS</h1>
                    <p class="text-xs text-slate-400 mt-1">Manage marketing banners, dynamic content pages, FAQs, notices, and SEO.</p>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('home')"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition"
                    >
                        <svg class="h-4 w-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        View Public Website
                    </a>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    :href="route('admin.cms.pages')"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm hover:border-indigo-500/40 hover:bg-slate-900/90 transition group"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Custom Pages</span>
                        <span class="rounded-xl bg-indigo-500/10 p-2 text-indigo-400 group-hover:scale-110 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-white">{{ pagesCount }}</span>
                        <span class="text-xs text-slate-400">managed</span>
                    </div>
                    <span class="mt-3 block text-xs font-semibold text-indigo-400 group-hover:underline">Manage Pages →</span>
                </Link>

                <Link
                    :href="route('admin.cms.banners')"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm hover:border-cyan-500/40 hover:bg-slate-900/90 transition group"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Hero Banners</span>
                        <span class="rounded-xl bg-cyan-500/10 p-2 text-cyan-400 group-hover:scale-110 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-white">{{ bannersCount }}</span>
                        <span class="text-xs text-slate-400">configured</span>
                    </div>
                    <span class="mt-3 block text-xs font-semibold text-cyan-400 group-hover:underline">Manage Banners →</span>
                </Link>

                <Link
                    :href="route('admin.cms.faqs')"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm hover:border-amber-500/40 hover:bg-slate-900/90 transition group"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">FAQs & Help</span>
                        <span class="rounded-xl bg-amber-500/10 p-2 text-amber-400 group-hover:scale-110 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-white">{{ faqsCount }}</span>
                        <span class="text-xs text-slate-400">questions</span>
                    </div>
                    <span class="mt-3 block text-xs font-semibold text-amber-400 group-hover:underline">Manage FAQs →</span>
                </Link>

                <Link
                    :href="route('admin.cms.notices')"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm hover:border-rose-500/40 hover:bg-slate-900/90 transition group"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">NOC Bulletins</span>
                        <span class="rounded-xl bg-rose-500/10 p-2 text-rose-400 group-hover:scale-110 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-white">{{ publishedNotices }}</span>
                        <span class="text-xs text-slate-400">published of {{ noticesCount }}</span>
                    </div>
                    <span class="mt-3 block text-xs font-semibold text-rose-400 group-hover:underline">Manage Notices →</span>
                </Link>
            </div>

            <!-- CMS Information Box -->
            <div class="rounded-3xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                    Single Source of Truth Architecture (Rule 1 & Section 47)
                </h3>
                <p class="text-xs text-slate-400 leading-relaxed max-w-3xl">
                    All website banners, notices, FAQs, and custom content are stored directly in MySQL and served seamlessly from the shared Laravel/Inertia stack without code deployment. Changes made here take effect immediately on public visitor pages.
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
