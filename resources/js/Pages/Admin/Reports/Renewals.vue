<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    summary: Object,
    renewals: Object,
    packages: Array,
    filters: Object,
});

const filterStartDate = ref(props.filters.start_date || props.summary.start_date);
const filterEndDate = ref(props.filters.end_date || props.summary.end_date);
const filterPackage = ref(props.filters.package_id || '');

const applyFilters = () => {
    router.get(route('admin.reports.renewals'), {
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
        package_id: filterPackage.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Package Renewals Performance Report - Pirgacha Internet" />

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
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-purple-400 border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </span>
                        Renewals Performance Report
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Track paid renewals vs validity-shift renewals and package retention.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 rounded-2xl border border-brand-navy shadow-xl grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
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
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1.5">Package</label>
                    <select
                        v-model="filterPackage"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky px-3 py-2"
                    >
                        <option value="">All Packages</option>
                        <option v-for="p in packages" :key="p.id" :value="p.id">
                            {{ p.name }} ({{ p.speed_mbps }} Mbps)
                        </option>
                    </select>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 sm:p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Renewals</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-white font-mono mt-1.5 sm:mt-2">
                        {{ summary.total_renewals }}
                    </div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 sm:p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Paid Renewals</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-emerald-400 font-mono mt-1.5 sm:mt-2">
                        {{ summary.paid_renewals_count }}
                    </div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 sm:p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Zero-Charge Shifts</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-brand-sky font-mono mt-1.5 sm:mt-2">
                        {{ summary.zero_charge_renewals_count }}
                    </div>
                </div>
                <div class="bg-[#091A2E]/80 backdrop-blur-sm p-4 sm:p-5 rounded-2xl border border-brand-navy shadow-xl">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Renewal Revenue</span>
                    <div class="text-xl sm:text-2xl font-extrabold text-purple-400 font-mono mt-1.5 sm:mt-2">
                        ৳ {{ Number(summary.total_revenue).toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Renewals Table -->
            <div class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-brand-navy flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white tracking-wide">Renewal Logs & Timeline</h3>
                    <span class="text-xs text-slate-400 font-mono">{{ renewals?.total || renewals?.data?.length || 0 }} total entries</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-navy/60 text-left text-sm">
                        <thead class="bg-[#071322]/80 text-slate-400 text-xs uppercase font-semibold whitespace-nowrap">
                            <tr>
                                <th class="px-4 py-3.5">Renewal No</th>
                                <th class="px-4 py-3.5">Date</th>
                                <th class="px-4 py-3.5">Customer</th>
                                <th class="px-4 py-3.5">Package</th>
                                <th class="px-4 py-3.5">Days</th>
                                <th class="px-4 py-3.5">Expiry Shift</th>
                                <th class="px-4 py-3.5">Type / Charge</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-navy/40 whitespace-nowrap">
                            <tr v-if="!renewals.data || renewals.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                    No renewal records found for this period.
                                </td>
                            </tr>
                            <tr v-for="r in renewals.data" :key="r.id" class="hover:bg-brand-navy/30 transition">
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs font-semibold text-brand-sky bg-brand-sky/10 px-2 py-0.5 rounded border border-brand-sky/20">
                                    {{ r.renewal_number }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-400 font-mono text-xs">
                                    {{ formatDate(r.renewed_at) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-white text-xs">{{ r.customer?.name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ r.customer?.customer_code }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-300 text-xs">
                                    {{ r.package?.name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-white font-mono text-xs">
                                    +{{ r.validity_days }} days
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-400 font-mono">
                                    {{ formatDate(r.previous_expiry) }} → <span class="font-bold text-brand-sky">{{ formatDate(r.new_expiry) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span v-if="r.is_zero_charge" class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-brand-sky/10 text-brand-sky border border-brand-sky/30">
                                        Zero-Charge Rule
                                    </span>
                                    <span v-else class="font-bold text-emerald-400 font-mono text-xs">
                                        ৳ {{ Number(r.amount).toLocaleString() }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="renewals.links && renewals.links.length > 3" class="px-4 py-3 bg-[#071322]/80 border-t border-brand-navy flex items-center justify-between">
                    <div class="flex gap-1.5">
                        <template v-for="(link, i) in renewals.links" :key="i">
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
