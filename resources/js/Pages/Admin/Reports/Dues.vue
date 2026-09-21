<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDate } from '@/Utils/date';

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
                        <Link :href="route('admin.reports.index')" class="text-xs text-brand-sky hover:underline flex items-center gap-1 font-medium">
                            ← Reports Hub
                        </Link>
                    </div>
                    <h1 class="text-2xl font-bold text-white tracking-tight mt-1 flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-rose-400 border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        Due & Outstanding Balances
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Unpaid invoice ledger, customer credit risk, and area-wise arrears.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Search Customer / Invoice</label>
                    <input
                        type="text"
                        v-model="filterSearch"
                        @keyup.enter="applyFilters"
                        placeholder="Name, Code, or INV-..."
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Filter by Area</label>
                    <select
                        v-model="filterArea"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Operating Areas</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                <div>
                    <button
                        @click="applyFilters"
                        class="w-full sm:w-auto px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold rounded-xl shadow-md transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-5 rounded-2xl border border-rose-500/30 shadow-xl">
                    <span class="text-xs font-semibold text-rose-400 uppercase tracking-wider">Total Invoice Dues</span>
                    <div class="text-2xl font-extrabold text-rose-400 font-mono mt-2">
                        ৳ {{ Number(summary.total_invoice_due).toLocaleString() }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Across {{ summary.total_due_invoices }} pending invoices</div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-5 rounded-2xl border border-amber-500/30 shadow-xl">
                    <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Total Customer Arrears</span>
                    <div class="text-2xl font-extrabold text-amber-400 font-mono mt-2">
                        ৳ {{ Number(summary.total_customer_balance_due).toLocaleString() }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Negative customer balances</div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Customers in Debt</span>
                    <div class="text-2xl font-extrabold text-white font-mono mt-2">
                        {{ summary.total_due_customers }} Subscribers
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Require billing follow-up</div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-brand-navy flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white tracking-wide">Outstanding Invoices Ledger</h3>
                    <span class="text-xs text-slate-400 font-mono">{{ invoices?.total || invoices?.data?.length || 0 }} total entries</span>
                </div>
                <!-- Mobile Card Layout (block md:hidden) -->
                <div class="block md:hidden divide-y divide-brand-navy/60">
                    <div v-if="!invoices.data || invoices.data.length === 0" class="p-8 text-center text-slate-500 text-xs">
                        No overdue invoices found. All clear!
                    </div>
                    <div
                        v-for="inv in invoices.data"
                        :key="'mobile-' + inv.id"
                        class="p-4 space-y-3 hover:bg-brand-navy/20 transition"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-brand-sky bg-brand-sky/10 px-2 py-0.5 rounded border border-brand-sky/20">
                                {{ inv.invoice_number }}
                            </span>
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase',
                                    inv.status === 'unpaid' ? 'bg-rose-950/60 text-rose-400 border border-rose-800/40' : 'bg-amber-950/60 text-amber-400 border border-amber-800/40'
                                ]"
                            >
                                {{ inv.status }}
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-semibold text-white text-xs">{{ inv.customer?.name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ inv.customer?.customer_code }}</div>
                                <div v-if="inv.customer?.area" class="text-[10px] text-brand-sky mt-0.5">
                                    Area: {{ inv.customer.area.name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400">Due Balance</div>
                                <div class="font-extrabold text-rose-400 font-mono text-sm">
                                    ৳ {{ Number(inv.due_amount).toLocaleString() }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-brand-navy/50 text-slate-400 font-mono text-[11px]">
                            <span>Total: ৳{{ Number(inv.total).toLocaleString() }}</span>
                            <span>Due Date: {{ formatDate(inv.due_date) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table Layout (hidden md:block) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-navy/60 text-left text-sm">
                        <thead class="bg-[#071322]/80 text-slate-400 text-xs uppercase font-semibold whitespace-nowrap">
                            <tr>
                                <th class="px-4 py-3.5">Invoice No</th>
                                <th class="px-4 py-3.5">Customer</th>
                                <th class="px-4 py-3.5">Area</th>
                                <th class="px-4 py-3.5">Due Date</th>
                                <th class="px-4 py-3.5">Total Amount</th>
                                <th class="px-4 py-3.5">Due Balance</th>
                                <th class="px-4 py-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/40 whitespace-nowrap">
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                    No overdue invoices found. All clear!
                                </td>
                            </tr>
                            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-brand-navy/30 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-semibold text-brand-sky bg-brand-sky/10 px-2 py-0.5 rounded border border-brand-sky/20">
                                    {{ inv.invoice_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-white text-xs">{{ inv.customer?.name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ inv.customer?.customer_code }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-400 text-xs">
                                    {{ inv.customer?.area?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-300 font-mono text-xs">
                                    {{ formatDate(inv.due_date) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-400 font-mono text-xs">
                                    ৳ {{ Number(inv.total).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-extrabold text-rose-400 font-mono text-sm">
                                    ৳ {{ Number(inv.due_amount).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase',
                                            inv.status === 'unpaid' ? 'bg-rose-950/60 text-rose-400 border border-rose-800/40' : 'bg-amber-950/60 text-amber-400 border border-amber-800/40'
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
                <div v-if="invoices.links && invoices.links.length > 3" class="px-4 py-3 bg-[#071322]/80 border-t border-brand-navy flex items-center justify-between">
                    <div class="flex gap-1.5">
                        <template v-for="(link, i) in invoices.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-xl border transition font-medium',
                                    link.active ? 'bg-rose-600 text-white border-rose-600 font-bold shadow-sm' : 'bg-[#091A2E] text-slate-300 hover:bg-brand-navy/70 border-brand-navy hover:text-white'
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
