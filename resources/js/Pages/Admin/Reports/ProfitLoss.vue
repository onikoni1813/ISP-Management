<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
                        <a :href="route('admin.reports.index')" class="text-xs text-indigo-600 hover:underline">← Reports Hub</a>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Profit & Loss Statement</h1>
                    <p class="text-sm text-gray-500">Authoritative statement adhering to Rule 27 (Revenue − Expenses − Salaries = Net Profit).</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        onclick="window.print()"
                        class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg shadow-sm hover:bg-gray-50 transition flex items-center gap-1"
                    >
                        🖨️ Print Statement
                    </button>
                </div>
            </div>

            <!-- Date Range Selector -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Period From</label>
                    <input
                        type="date"
                        v-model="filterStartDate"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Period To</label>
                    <input
                        type="date"
                        v-model="filterEndDate"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>
                <div class="text-xs text-gray-400 pb-2">
                    Evaluating period: {{ period.start_date }} to {{ period.end_date }}
                </div>
            </div>

            <!-- Executive Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Inflow (Revenue) -->
                <div class="bg-white rounded-2xl border border-emerald-200 p-6 shadow-sm bg-gradient-to-b from-white to-emerald-50/20">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Gross Collected Revenue</span>
                    <div class="text-3xl font-black text-emerald-600 mt-2">
                        ৳ {{ Number(financials.total_revenue).toLocaleString() }}
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Total subscriber collections received</p>
                </div>

                <!-- Total Outflow (Expenses + Salary) -->
                <div class="bg-white rounded-2xl border border-rose-200 p-6 shadow-sm bg-gradient-to-b from-white to-rose-50/20">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Total Disbursements</span>
                    <div class="text-3xl font-black text-rose-600 mt-2">
                        ৳ {{ Number(financials.total_expenditure).toLocaleString() }}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                        <span>Ops: ৳{{ Number(financials.total_expenses).toLocaleString() }}</span>
                        <span>•</span>
                        <span>Salaries: ৳{{ Number(financials.total_salaries).toLocaleString() }}</span>
                    </div>
                </div>

                <!-- Net Profit -->
                <div
                    :class="[
                        'rounded-2xl border p-6 shadow-sm text-white bg-gradient-to-br',
                        financials.net_profit >= 0 ? 'from-emerald-600 to-teal-700 border-emerald-500' : 'from-rose-600 to-rose-800 border-rose-500'
                    ]"
                >
                    <span class="text-xs font-bold uppercase tracking-wider text-white/80">Net Operating Profit / Loss</span>
                    <div class="text-3xl font-black mt-2">
                        ৳ {{ Number(financials.net_profit).toLocaleString() }}
                    </div>
                    <p class="text-xs text-white/80 mt-1">
                        {{ financials.net_profit >= 0 ? 'Net positive operating margin' : 'Operating loss recorded in period' }}
                    </p>
                </div>
            </div>

            <!-- Crucial Distinction Banner (Rule 27) -->
            <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-600 text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-indigo-900 uppercase">Accounting Rule 27 Verification</div>
                        <div class="text-xs text-indigo-700">Cash Balance is strictly separated from Period Net Profit.</div>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-indigo-600 font-medium">Total Liquid Account Assets:</span>
                    <span class="text-lg font-extrabold text-indigo-950 ml-2">৳ {{ Number(financials.current_liquid_balance).toLocaleString() }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Expense Categories Breakdown -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Operating Expenses by Category</h3>
                    <div class="divide-y divide-gray-100">
                        <div v-if="!expense_by_category || expense_by_category.length === 0" class="py-4 text-xs text-gray-400 text-center">
                            No operating expenses recorded for this period.
                        </div>
                        <div
                            v-for="item in expense_by_category"
                            :key="item.name"
                            class="py-2.5 flex items-center justify-between text-sm"
                        >
                            <span class="text-gray-700 font-medium">{{ item.name }}</span>
                            <span class="font-bold text-gray-900">৳ {{ Number(item.total).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Active Liquid Accounts Breakdown -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Account Wallets Liquid Balances</h3>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="acc in accounts_breakdown"
                            :key="acc.name"
                            class="py-2.5 flex items-center justify-between text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-gray-800 font-medium">{{ acc.name }}</span>
                                <span class="text-xs text-gray-400 font-mono">({{ acc.type }})</span>
                            </div>
                            <span class="font-bold text-emerald-700 font-mono">৳ {{ Number(acc.balance).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
