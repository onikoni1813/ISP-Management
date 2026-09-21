<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    metrics: Object,
});

const reportModules = [
    {
        title: 'Collection & Revenue',
        desc: 'Daily payment collection trends, channel breakdowns (Cash, bKash, Bank), and collector attribution.',
        href: route('admin.reports.collections'),
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        gradient: 'from-blue-600/20 to-sky-600/20 border-brand-sky/30 text-brand-sky',
        iconBg: 'from-blue-600 to-sky-500',
    },
    {
        title: 'Due & Outstanding Balances',
        desc: 'Unpaid invoice aging, overdue subscriber lists, and area-wise outstanding amounts.',
        href: route('admin.reports.dues'),
        icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        gradient: 'from-amber-600/20 to-rose-600/20 border-rose-500/30 text-rose-400',
        iconBg: 'from-amber-500 to-rose-600',
    },
    {
        title: 'Profit & Loss Financial Statement',
        desc: 'Rule 27 executive statement: Revenue minus Operating Expenses & Staff Salaries with liquid balance distinction.',
        href: route('admin.reports.profit-loss'),
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        gradient: 'from-emerald-600/20 to-teal-600/20 border-emerald-500/30 text-emerald-400',
        iconBg: 'from-emerald-600 to-teal-500',
    },
    {
        title: 'Package Renewals Performance',
        desc: 'Subscriber extension volumes, paid renewals vs validity-shift zero charge renewals, and package popularity.',
        href: route('admin.reports.renewals'),
        icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        gradient: 'from-purple-600/20 to-indigo-600/20 border-purple-500/30 text-purple-400',
        iconBg: 'from-purple-600 to-indigo-600',
    },
    {
        title: 'Cash Flow & Accounts Movement',
        desc: 'Traceable money flow across Cash, Bank, and Mobile wallets with full audit trail entries.',
        href: route('admin.reports.cash-flow'),
        icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        gradient: 'from-cyan-600/20 to-blue-600/20 border-cyan-500/30 text-cyan-400',
        iconBg: 'from-cyan-600 to-blue-600',
    },
    {
        title: 'Staff Accountability & Performance',
        desc: 'Individual field staff collection volume, receipt counts, and logged actions summary.',
        href: route('admin.audit.staff-report'),
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        gradient: 'from-brand-orange/20 to-amber-600/20 border-brand-orange/30 text-brand-orange',
        iconBg: 'from-brand-orange to-amber-600',
    },
];
</script>

<template>
    <Head title="Business Reports & Analytics - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-brand-sky border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                        Executive Reports & Analytics
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Audit-grade financial statements, collection metrics, and operational performance.</p>
                </div>
                <div class="text-xs text-slate-400">
                    Fiscal Period: <span class="text-brand-sky font-bold font-mono">{{ new Date().toLocaleString('en-US', { month: 'long', year: 'numeric' }) }}</span>
                </div>
            </div>

            <!-- Executive KPI Summary Cards -->
            <div v-if="metrics" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">This Month Revenue</div>
                        <div class="text-xl font-bold text-emerald-400 font-mono mt-0.5">
                            ৳{{ Number(metrics.monthly_revenue).toLocaleString() }}
                        </div>
                    </div>
                </div>

                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Unpaid Dues</div>
                        <div class="text-xl font-bold text-rose-400 font-mono mt-0.5">
                            ৳{{ Number(metrics.total_due).toLocaleString() }}
                        </div>
                    </div>
                </div>

                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Month Expenses & Salary</div>
                        <div class="text-xl font-bold text-amber-400 font-mono mt-0.5">
                            ৳{{ Number(metrics.monthly_expenses + metrics.monthly_salaries).toLocaleString() }}
                        </div>
                    </div>
                </div>

                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-brand-sky/10 border border-brand-sky/30 flex items-center justify-center text-brand-sky">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Liquid Accounts Fund</div>
                        <div class="text-xl font-bold text-white font-mono mt-0.5">
                            ৳{{ Number(metrics.active_accounts_balance).toLocaleString() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="rep in reportModules"
                    :key="rep.title"
                    :href="rep.href"
                    class="group relative bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy p-6 shadow-xl hover:border-brand-sky/50 hover:shadow-brand-sky/10 transition-all duration-300 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div
                                :class="[
                                    'w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-lg bg-gradient-to-tr',
                                    rep.iconBg
                                ]"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="rep.icon" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-brand-sky group-hover:translate-x-1 transition-transform flex items-center gap-1">
                                View Report &rarr;
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-white group-hover:text-brand-sky transition-colors">
                            {{ rep.title }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            {{ rep.desc }}
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-brand-navy flex items-center justify-between text-[11px] text-slate-400 font-medium">
                        <span>Real-time Data</span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-400 font-semibold font-mono text-[11px]">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Verified
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
