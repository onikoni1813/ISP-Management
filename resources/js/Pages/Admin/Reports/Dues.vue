<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    summary: Object,
    invoices: Object,
    areas: Array,
    filters: Object,
});

const filterArea = ref(props.filters.area_id || '');
const filterSearch = ref(props.filters.search || '');

const applyFilters = () => {
    router.get(route('admin.reports.dues'), {
        area_id: filterArea.value || undefined,
        search: filterSearch.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Due & Outstanding Balances Report - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <a :href="route('admin.reports.index')" class="text-xs text-indigo-600 hover:underline">← Reports Hub</a>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Due & Outstanding Balances</h1>
                    <p class="text-sm text-gray-500">Unpaid invoice ledger, customer credit risk, and area-wise arrears.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Search Customer / Invoice</label>
                    <input
                        type="text"
                        v-model="filterSearch"
                        @keyup.enter="applyFilters"
                        placeholder="Name, Code, or INV-..."
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Filter by Area</label>
                    <select
                        v-model="filterArea"
                        @change="applyFilters"
                        class="text-sm rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                    >
                        <option value="">All Operating Areas</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg shadow-sm"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-xl border border-rose-200 shadow-sm bg-rose-50/20">
                    <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Total Invoice Dues</span>
                    <div class="text-2xl font-extrabold text-rose-600 mt-2">
                        ৳ {{ Number(summary.total_invoice_due).toLocaleString() }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Across {{ summary.total_due_invoices }} pending invoices</div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-amber-200 shadow-sm bg-amber-50/20">
                    <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Total Customer Arrears</span>
                    <div class="text-2xl font-extrabold text-amber-600 mt-2">
                        ৳ {{ Number(summary.total_customer_balance_due).toLocaleString() }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Negative customer balances</div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Customers in Debt</span>
                    <div class="text-2xl font-extrabold text-gray-900 mt-2">
                        {{ summary.total_due_customers }} Subscribers
                    </div>
                    <div class="text-xs text-gray-400 mt-1">Require billing follow-up</div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">Invoice No</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Area</th>
                                <th class="px-4 py-3">Due Date</th>
                                <th class="px-4 py-3">Total Amount</th>
                                <th class="px-4 py-3">Due Balance</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    No overdue invoices found. All clear!
                                </td>
                            </tr>
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-bold text-gray-800">
                                    {{ inv.invoice_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-gray-900">{{ inv.customer?.name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ inv.customer?.customer_code }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-xs">
                                    {{ inv.customer?.area?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700 text-xs">
                                    {{ inv.due_date }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                    ৳ {{ Number(inv.total).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-rose-600">
                                    ৳ {{ Number(inv.due_amount).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium uppercase',
                                            inv.status === 'unpaid' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800'
                                        ]"
                                    >
                                        {{ inv.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links && invoices.links.length > 3" class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex gap-1">
                        <template v-for="(link, i) in invoices.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1 text-xs rounded border transition',
                                    link.active ? 'bg-rose-600 text-white border-rose-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-300'
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
