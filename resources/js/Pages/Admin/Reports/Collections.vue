<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    summary: Object,
    daily_totals: Object,
    by_method: Array,
    payments: Object,
    collectors: Array,
    accounts: Array,
    areas: Array,
    filters: Object,
});

const filterStartDate = ref(props.filters.start_date || props.summary.start_date);
const filterEndDate = ref(props.filters.end_date || props.summary.end_date);
const filterMethod = ref(props.filters.payment_method || '');
const filterCollector = ref(props.filters.collector_id || '');
const filterAccount = ref(props.filters.account_id || '');
const filterArea = ref(props.filters.area_id || '');

const applyFilters = () => {
    router.get(route('admin.reports.collections'), {
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
        payment_method: filterMethod.value || undefined,
        collector_id: filterCollector.value || undefined,
        account_id: filterAccount.value || undefined,
        area_id: filterArea.value || undefined,
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
                        <Link :href="route('admin.reports.index')" class="text-xs text-brand-sky hover:underline flex items-center gap-1 font-medium">
                            ← Reports Hub
                        </Link>
                    </div>
                    <h1 class="text-2xl font-bold text-white tracking-tight mt-1 flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-brand-sky border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        Collection & Revenue Report
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Breakdown of customer bill collections, payment methods, and daily totals.</p>
                </div>
                <div class="text-xs text-slate-300 bg-[#091A2E]/80 border border-brand-navy px-3.5 py-2 rounded-xl shadow-md font-mono">
                    Range: <span class="font-bold text-brand-sky">{{ summary.start_date }} ~ {{ summary.end_date }}</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">From Date</label>
                    <input
                        type="date"
                        v-model="filterStartDate"
                        @change="applyFilters"
                        class="w-full text-xs sm:text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">To Date</label>
                    <input
                        type="date"
                        v-model="filterEndDate"
                        @change="applyFilters"
                        class="w-full text-xs sm:text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Method</label>
                    <select
                        v-model="filterMethod"
                        @change="applyFilters"
                        class="w-full text-xs sm:text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Channels</option>
                        <option value="Cash">Cash</option>
                        <option value="bKash">bKash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Bank">Bank Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Collected By</label>
                    <select
                        v-model="filterCollector"
                        @change="applyFilters"
                        class="w-full text-xs sm:text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Staff</option>
                        <option v-for="c in collectors" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Coverage Area</label>
                    <select
                        v-model="filterArea"
                        @change="applyFilters"
                        class="w-full text-xs sm:text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Areas (সমগ্র নেটওয়ার্ক)</option>
                        <option v-for="ar in areas" :key="ar.id" :value="ar.id">{{ ar.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Credited Account</label>
                    <select
                        v-model="filterAccount"
                        @change="applyFilters"
                        class="w-full text-xs sm:text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Accounts</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Collection</span>
                    <div class="text-2xl font-extrabold text-brand-sky font-mono mt-2">
                        ৳ {{ Number(summary.total_amount).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Transactions Processed</span>
                    <div class="text-2xl font-extrabold text-white font-mono mt-2">
                        {{ summary.total_count }} Receipts
                    </div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Average Per Ticket</span>
                    <div class="text-2xl font-extrabold text-emerald-400 font-mono mt-2">
                        ৳ {{ summary.total_count ? Math.round(summary.total_amount / summary.total_count).toLocaleString() : 0 }}
                    </div>
                </div>
            </div>

            <!-- Channel Breakdown Cards -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-5 rounded-2xl border border-brand-navy shadow-xl">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3.5">Collection by Channel</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div v-for="m in by_method" :key="m.payment_method" class="p-3.5 bg-[#071322] rounded-xl border border-brand-navy">
                        <div class="text-xs font-semibold text-slate-300">{{ m.payment_method }}</div>
                        <div class="text-lg font-bold text-brand-sky font-mono mt-1">৳ {{ Number(m.total).toLocaleString() }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">{{ m.count }} transactions</div>
                    </div>
                </div>
            </div>

            <!-- Detailed Payments Table -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-brand-navy flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white tracking-wide">Payment Receipts Breakdown</h3>
                    <span class="text-xs text-slate-400 font-mono">{{ payments?.total || payments?.data?.length || 0 }} total entries</span>
                </div>
                <!-- Mobile Card Layout (block md:hidden) -->
                <div class="block md:hidden divide-y divide-brand-navy/60">
                    <div v-if="!payments.data || payments.data.length === 0" class="p-8 text-center text-slate-500 text-xs">
                        No collection records match current filter criteria.
                    </div>
                    <div
                        v-for="p in payments.data"
                        :key="'mobile-' + p.id"
                        class="p-4 space-y-3 hover:bg-brand-navy/20 transition"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-brand-sky bg-brand-sky/10 px-2 py-0.5 rounded border border-brand-sky/20">
                                {{ p.payment_number }}
                            </span>
                            <div class="font-extrabold text-emerald-400 font-mono text-sm">
                                ৳ {{ Number(p.amount).toLocaleString() }}
                            </div>
                        </div>

                        <div>
                            <div class="font-semibold text-white text-xs">{{ p.customer?.name }}</div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] text-slate-400 font-mono">{{ p.customer?.customer_code }}</span>
                                <span v-if="p.customer?.area" class="text-[9px] px-1.5 py-0.2 rounded bg-brand-sky/10 text-brand-sky font-semibold border border-brand-sky/20">
                                    {{ p.customer.area.name }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1 border-t border-brand-navy/50">
                            <div class="text-slate-300">
                                <span class="px-2 py-0.5 rounded text-[11px] bg-[#071322] border border-brand-navy font-medium">
                                    {{ p.payment_method }}
                                </span>
                                <span class="text-[11px] text-slate-400 ml-1">({{ p.account?.name }})</span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono">
                                {{ formatDateTime(p.paid_at) }}
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-400 flex items-center justify-between">
                            <span>Collected By: <strong class="text-white">{{ p.collector?.name || 'System' }}</strong></span>
                            <span v-if="Number(p.discount) > 0" class="text-amber-400 font-mono">
                                ছাড়: ৳{{ Number(p.discount).toLocaleString() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table Layout (hidden md:block) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-navy/60 text-left text-sm">
                        <thead class="bg-[#071322]/80 text-slate-400 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3.5">Receipt No</th>
                                <th class="px-4 py-3.5">Date</th>
                                <th class="px-4 py-3.5">Customer</th>
                                <th class="px-4 py-3.5">Channel / Account</th>
                                <th class="px-4 py-3.5">Amount</th>
                                <th class="px-4 py-3.5">Collected By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/40">
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                                    No collection records match current filter criteria.
                                </td>
                            </tr>
                            <tr v-for="p in payments.data" :key="p.id" class="hover:bg-brand-navy/30 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-semibold text-brand-sky bg-brand-sky/10 px-2 py-0.5 rounded border border-brand-sky/20">
                                    {{ p.payment_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-400 font-mono text-xs">
                                    {{ formatDateTime(p.paid_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-white text-xs">{{ p.customer?.name }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] text-slate-400 font-mono">{{ p.customer?.customer_code }}</span>
                                        <span v-if="p.customer?.area" class="text-[9px] px-1.5 py-0.2 rounded bg-brand-sky/10 text-brand-sky font-semibold border border-brand-sky/20">
                                            {{ p.customer.area.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-300">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-[#071322] text-slate-200 border border-brand-navy">
                                        {{ p.payment_method }}
                                    </span>
                                    <span class="text-xs text-slate-400 ml-1.5">({{ p.account?.name }})</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-extrabold text-emerald-400 font-mono text-sm">৳ {{ Number(p.amount).toLocaleString() }}</div>
                                    <div v-if="Number(p.discount) > 0" class="text-[10px] text-amber-400 font-mono">
                                        ছাড়: ৳{{ Number(p.discount).toLocaleString() }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-300">
                                    {{ p.collector?.name || 'System' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="px-4 py-3 bg-[#071322]/80 border-t border-brand-navy flex items-center justify-between">
                    <div class="flex gap-1.5">
                        <template v-for="(link, i) in payments.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-xl border transition font-medium',
                                    link.active ? 'bg-gradient-to-r from-brand-sky to-brand-blue text-white border-brand-sky shadow-sm' : 'bg-[#091A2E] text-slate-300 hover:bg-brand-navy/70 border-brand-navy hover:text-white'
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
