<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    summary: Object,
    daily_totals: Object,
    by_method: Array,
    payments: Object,
    collectors: Array,
    accounts: Array,
    filters: Object,
});

const filterStartDate = ref(props.filters.start_date || props.summary.start_date);
const filterEndDate = ref(props.filters.end_date || props.summary.end_date);
const filterMethod = ref(props.filters.payment_method || '');
const filterCollector = ref(props.filters.collector_id || '');
const filterAccount = ref(props.filters.account_id || '');

const applyFilters = () => {
    router.get(route('admin.reports.collections'), {
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
        payment_method: filterMethod.value || undefined,
        collector_id: filterCollector.value || undefined,
        account_id: filterAccount.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Collection & Revenue Report - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <a :href="route('admin.reports.index')" class="text-xs text-indigo-600 hover:underline">← Reports Hub</a>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Collection & Revenue Report</h1>
                    <p class="text-sm text-gray-500">Breakdown of customer bill collections, payment methods, and daily totals.</p>
                </div>
                <div class="text-xs text-gray-500 bg-white border border-gray-200 px-3 py-2 rounded-xl shadow-sm">
                    Reporting Range: <span class="font-bold text-gray-800">{{ summary.start_date }} ~ {{ summary.end_date }}</span>
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
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">To Date</label>
                    <input
                        type="date"
                        v-model="filterEndDate"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Method</label>
                    <select
                        v-model="filterMethod"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Channels</option>
                        <option value="Cash">Cash</option>
                        <option value="bKash">bKash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Bank">Bank Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Collected By</label>
                    <select
                        v-model="filterCollector"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Staff</option>
                        <option v-for="c in collectors" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Credited Account</label>
                    <select
                        v-model="filterAccount"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Accounts</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Collection</span>
                    <div class="text-2xl font-extrabold text-indigo-600 mt-2">
                        ৳ {{ Number(summary.total_amount).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Transactions Processed</span>
                    <div class="text-2xl font-extrabold text-gray-900 mt-2">
                        {{ summary.total_count }} Receipts
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Average Per Ticket</span>
                    <div class="text-2xl font-extrabold text-gray-700 mt-2">
                        ৳ {{ summary.total_count ? Math.round(summary.total_amount / summary.total_count) : 0 }}
                    </div>
                </div>
            </div>

            <!-- Channel Breakdown Cards -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Collection by Channel</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div v-for="m in by_method" :key="m.payment_method" class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="text-xs font-semibold text-gray-600">{{ m.payment_method }}</div>
                        <div class="text-lg font-bold text-gray-900 mt-1">৳ {{ Number(m.total).toLocaleString() }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ m.count }} transactions</div>
                    </div>
                </div>
            </div>

            <!-- Detailed Payments Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 font-semibold text-sm text-gray-800">
                    Payment Receipts Breakdown
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Receipt No</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Channel / Account</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Collected By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                    No collection records match current filter criteria.
                                </td>
                            </tr>
                            <tr v-for="p in payments.data" :key="p.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-bold text-indigo-600">
                                    {{ p.payment_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700 text-xs">
                                    {{ new Date(p.paid_at).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">{{ p.customer?.name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ p.customer?.customer_code }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ p.payment_method }}
                                    </span>
                                    <span class="text-xs text-gray-500 ml-1">({{ p.account?.name }})</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-extrabold text-emerald-600">
                                    ৳ {{ Number(p.amount).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                    {{ p.collector?.name || 'System' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-1">
                        <template v-for="(link, i) in payments.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1 text-xs rounded border transition',
                                    link.active ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-300'
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
