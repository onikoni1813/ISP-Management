<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    period: Object,
    financials: Object,
    expense_by_category: Array,
    accounts_breakdown: Array,
    filters: Object,
});

const filterStartDate = ref(props.filters.start_date || props.period.start_date);
const filterEndDate = ref(props.filters.end_date || props.period.end_date);

const applyFilters = () => {
    router.get(route('admin.reports.profit-loss'), {
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Profit & Loss Financial Statement - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.reports.index')" class="text-xs text-brand-sky hover:underline flex items-center gap-1 font-medium">
                            ← Reports Hub
                        </Link>
                    </div>
                    <h1 class="text-2xl font-bold text-white tracking-tight mt-1 flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-emerald-400 border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </span>
                        Profit & Loss Statement
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Authoritative statement adhering to Rule 27 (Revenue − Expenses − Salaries = Net Profit).</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        onclick="window.print()"
                        class="px-3.5 py-2 bg-[#0B1E36] hover:bg-[#102B4D] border border-brand-navy text-slate-200 hover:text-white text-xs font-semibold rounded-xl shadow-sm transition flex items-center gap-1.5"
                    >
                        🖨️ Print Statement
                    </button>
                </div>
            </div>

            <!-- Date Range Selector -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Period From</label>
                    <input
                        type="date"
                        v-model="filterStartDate"
                        @change="applyFilters"
                        class="text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Period To</label>
                    <input
                        type="date"
                        v-model="filterEndDate"
                        @change="applyFilters"
                        class="text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    />
                </div>
                <div class="text-xs text-slate-400 pb-2.5 font-mono">
                    Evaluating period: <span class="text-white">{{ period.start_date }}</span> to <span class="text-white">{{ period.end_date }}</span>
                </div>
            </div>

            <!-- Executive Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Inflow (Revenue) -->
                <div class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-emerald-500/30 p-6 shadow-xl">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Gross Collected Revenue</span>
                    <div class="text-3xl font-black text-emerald-400 font-mono mt-2">
                        ৳ {{ Number(financials.total_revenue).toLocaleString() }}
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Total subscriber collections received</p>
                </div>

                <!-- Total Outflow (Expenses + Salary) -->
                <div class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-rose-500/30 p-6 shadow-xl">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-400">Total Disbursements</span>
                    <div class="text-3xl font-black text-rose-400 font-mono mt-2">
                        ৳ {{ Number(financials.total_expenditure).toLocaleString() }}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 mt-1">
                        <span>Ops: ৳{{ Number(financials.total_expenses).toLocaleString() }}</span>
                        <span>•</span>
                        <span>Salaries: ৳{{ Number(financials.total_salaries).toLocaleString() }}</span>
                    </div>
                </div>

                <!-- Net Profit -->
                <div
                    :class="[
                        'rounded-2xl border p-6 shadow-xl text-white backdrop-blur-sm',
                        financials.net_profit >= 0 ? 'bg-emerald-950/40 border-emerald-500/50 text-emerald-300' : 'bg-rose-950/40 border-rose-500/50 text-rose-300'
                    ]"
                >
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Net Operating Profit / Loss</span>
                    <div class="text-3xl font-black font-mono mt-2" :class="financials.net_profit >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                        ৳ {{ Number(financials.net_profit).toLocaleString() }}
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ financials.net_profit >= 0 ? 'Net positive operating margin' : 'Operating loss recorded in period' }}
                    </p>
                </div>
            </div>

            <!-- Crucial Distinction Banner (Rule 27) -->
            <div class="p-4 bg-[#091A2E]/90 border border-brand-sky/40 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-brand-sky/10 border border-brand-sky/30 text-brand-sky rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white uppercase tracking-wider">Accounting Rule 27 Verification</div>
                        <div class="text-xs text-slate-400">Cash Balance is strictly separated from Period Net Profit.</div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 font-medium">Total Liquid Account Assets:</span>
                    <span class="text-xl font-extrabold text-brand-sky font-mono ml-2">৳ {{ Number(financials.current_liquid_balance).toLocaleString() }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Expense Categories Breakdown -->
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-6 rounded-2xl border border-brand-navy shadow-xl space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Operating Expenses by Category</h3>
                    <div class="divide-y divide-brand-navy/60">
                        <div v-if="!expense_by_category || expense_by_category.length === 0" class="py-4 text-xs text-slate-500 text-center">
                            No operating expenses recorded for this period.
                        </div>
                        <div
                            v-for="item in expense_by_category"
                            :key="item.name"
                            class="py-3 flex items-center justify-between text-sm"
                        >
                            <span class="text-slate-300 font-medium">{{ item.name }}</span>
                            <span class="font-bold text-rose-400 font-mono">৳ {{ Number(item.total).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Active Liquid Accounts Breakdown -->
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-6 rounded-2xl border border-brand-navy shadow-xl space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Account Wallets Liquid Balances</h3>
                    <div class="divide-y divide-brand-navy/60">
                        <div
                            v-for="acc in accounts_breakdown"
                            :key="acc.name"
                            class="py-3 flex items-center justify-between text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-slate-200 font-medium">{{ acc.name }}</span>
                                <span class="text-xs text-slate-500 font-mono">({{ acc.type }})</span>
                            </div>
                            <span class="font-bold text-emerald-400 font-mono">৳ {{ Number(acc.balance).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
