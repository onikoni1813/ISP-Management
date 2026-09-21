<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    summary: Object,
    transactions: Object,
    accounts: Array,
    filters: Object,
});

const filterStartDate = ref(props.filters.start_date || props.summary.start_date);
const filterEndDate = ref(props.filters.end_date || props.summary.end_date);
const filterAccount = ref(props.filters.account_id || '');
const filterType = ref(props.filters.type || '');

const applyFilters = () => {
    router.get(route('admin.reports.cash-flow'), {
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
        account_id: filterAccount.value || undefined,
        type: filterType.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Cash Flow & Accounts Movement - Pirgacha Internet" />

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
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-cyan-400 border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </span>
                        Cash Flow & Accounts Movement
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Every money movement across Cash, Bank, and Mobile wallets is fully traceable.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">From Date</label>
                    <input
                        type="date"
                        v-model="filterStartDate"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2 font-mono"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">To Date</label>
                    <input
                        type="date"
                        v-model="filterEndDate"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2 font-mono"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Account</label>
                    <select
                        v-model="filterAccount"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Accounts</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">
                            {{ a.name }} ({{ a.type }})
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Transaction Type</label>
                    <select
                        v-model="filterType"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Types</option>
                        <option value="customer_payment">Customer Payment</option>
                        <option value="expense">Operating Expense</option>
                        <option value="salary">Salary Disbursement</option>
                        <option value="transfer_in">Transfer In</option>
                        <option value="transfer_out">Transfer Out</option>
                    </select>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 sm:p-5 rounded-2xl border border-emerald-500/30 shadow-xl">
                    <span class="text-[11px] sm:text-xs font-semibold text-emerald-400 uppercase tracking-wider">Total Cash Inflow</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-emerald-400 font-mono mt-1.5 sm:mt-2">
                        +৳ {{ Number(summary.total_inflow).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 sm:p-5 rounded-2xl border border-rose-500/30 shadow-xl">
                    <span class="text-[11px] sm:text-xs font-semibold text-rose-400 uppercase tracking-wider">Total Cash Outflow</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-rose-400 font-mono mt-1.5 sm:mt-2">
                        -৳ {{ Number(summary.total_outflow).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 sm:p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Net Flow Delta</span>
                    <div
                        :class="[
                            'text-xl sm:text-2xl font-extrabold font-mono mt-1.5 sm:mt-2',
                            summary.net_flow >= 0 ? 'text-cyan-400' : 'text-rose-400'
                        ]"
                    >
                        ৳ {{ Number(summary.net_flow).toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Ledger Transactions Table -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-brand-navy flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white tracking-wide">Detailed Cash Movements</h3>
                    <span class="text-xs text-slate-400 font-mono">{{ transactions?.total || transactions?.data?.length || 0 }} total movements</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-navy/60 text-left text-sm">
                        <thead class="bg-[#071322]/80 text-slate-400 text-xs uppercase font-semibold whitespace-nowrap">
                            <tr>
                                <th class="px-4 py-3.5">Txn Number</th>
                                <th class="px-4 py-3.5">Timestamp</th>
                                <th class="px-4 py-3.5">Account</th>
                                <th class="px-4 py-3.5">Type</th>
                                <th class="px-4 py-3.5">Description</th>
                                <th class="px-4 py-3.5">Debit (In)</th>
                                <th class="px-4 py-3.5">Credit (Out)</th>
                                <th class="px-4 py-3.5">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/40 whitespace-nowrap">
                            <tr v-if="!transactions.data || transactions.data.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                    No ledger movements recorded for this query.
                                </td>
                            </tr>
                            <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-brand-navy/30 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-semibold text-brand-sky bg-brand-sky/10 px-2 py-0.5 rounded border border-brand-sky/20">
                                    {{ t.transaction_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-400 text-xs font-mono">
                                    {{ formatDateTime(t.created_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-white font-medium text-xs">
                                    {{ t.account?.name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#071322] text-slate-300 border border-brand-navy">
                                        {{ t.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-300 max-w-xs truncate">
                                    {{ t.description }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-emerald-400 font-mono text-xs">
                                    {{ Number(t.debit) > 0 ? '৳ ' + Number(t.debit).toLocaleString() : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-rose-400 font-mono text-xs">
                                    {{ Number(t.credit) > 0 ? '৳ ' + Number(t.credit).toLocaleString() : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-semibold text-white">
                                    ৳ {{ Number(t.balance_after).toLocaleString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="px-4 py-3 bg-[#071322]/80 border-t border-brand-navy flex items-center justify-between">
                    <div class="flex gap-1.5">
                        <template v-for="(link, i) in transactions.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-xl border transition font-medium',
                                    link.active ? 'bg-cyan-600 text-white border-cyan-600 font-bold shadow-sm' : 'bg-[#091A2E] text-slate-300 hover:bg-brand-navy/70 border-brand-navy hover:text-white'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span v-else class="px-3 py-1.5 text-xs text-slate-500 border border-transparent" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
