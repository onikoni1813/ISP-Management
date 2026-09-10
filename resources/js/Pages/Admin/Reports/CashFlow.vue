<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

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
                        <a :href="route('admin.reports.index')" class="text-xs text-indigo-600 hover:underline">← Reports Hub</a>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Cash Flow & Accounts Movement</h1>
                    <p class="text-sm text-gray-500">Every money movement across Cash, Bank, and Mobile wallets is fully traceable.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">From Date</label>
                    <input
                        type="date"
                        v-model="filterStartDate"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">To Date</label>
                    <input
                        type="date"
                        v-model="filterEndDate"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Account</label>
                    <select
                        v-model="filterAccount"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
                    >
                        <option value="">All Accounts</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">
                            {{ a.name }} ({{ a.type }})
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Transaction Type</label>
                    <select
                        v-model="filterType"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-cyan-500 focus:ring-cyan-500"
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
                <div class="bg-white p-5 rounded-xl border border-emerald-200 shadow-sm">
                    <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Total Cash Inflow</span>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-2">
                        +৳ {{ Number(summary.total_inflow).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-rose-200 shadow-sm">
                    <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Total Cash Outflow</span>
                    <div class="text-2xl font-extrabold text-rose-600 mt-2">
                        -৳ {{ Number(summary.total_outflow).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Net Flow Delta</span>
                    <div
                        :class="[
                            'text-2xl font-extrabold mt-2',
                            summary.net_flow >= 0 ? 'text-cyan-600' : 'text-rose-600'
                        ]"
                    >
                        ৳ {{ Number(summary.net_flow).toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Ledger Transactions Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Txn Number</th>
                                <th class="px-4 py-3">Timestamp</th>
                                <th class="px-4 py-3">Account</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Debit (In)</th>
                                <th class="px-4 py-3">Credit (Out)</th>
                                <th class="px-4 py-3">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!transactions.data || transactions.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                    No ledger movements recorded for this query.
                                </td>
                            </tr>
                            <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-bold text-gray-900">
                                    {{ t.transaction_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">
                                    {{ new Date(t.created_at).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-800 font-medium">
                                    {{ t.account?.name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-800">
                                        {{ t.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate">
                                    {{ t.description }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-emerald-600">
                                    {{ Number(t.debit) > 0 ? '৳ ' + Number(t.debit).toLocaleString() : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-rose-600">
                                    {{ Number(t.credit) > 0 ? '৳ ' + Number(t.credit).toLocaleString() : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-semibold text-gray-900">
                                    ৳ {{ Number(t.balance_after).toLocaleString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-1">
                        <template v-for="(link, i) in transactions.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1 text-xs rounded border transition',
                                    link.active ? 'bg-cyan-600 text-white border-cyan-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-300'
                                ]"
                                v-html="link.label"
                            ></button>
                            <span v-else class="px-3 py-1 text-xs text-gray-400 border border-transparent" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
