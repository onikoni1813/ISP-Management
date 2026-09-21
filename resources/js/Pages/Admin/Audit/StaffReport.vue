<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    staffUsers: Array,
    stats: Object,
    collections: Array,
    filters: Object,
});

const selectedStaffId = ref(props.filters.staff_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const filterReport = () => {
    router.get(route('admin.audit.staff-report'), {
        staff_id: selectedStaffId.value,
        start_date: startDate.value,
        end_date: endDate.value,
    });
};
</script>

<template>
    <Head title="Staff Accountability Report - Pirgacha Internet" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Staff Accountability & Performance</h1>
                <p class="text-sm text-slate-400 mt-1">Audit individual staff collection amounts, customer counts, and operations.</p>
            </div>
            <Link
                :href="route('admin.audit.index')"
                class="rounded-xl border border-brand-navy bg-[#091A2E] px-4 py-2.5 text-xs font-semibold text-slate-300 hover:text-white hover:border-brand-sky/50 transition shadow-sm"
            >
                ← Audit Trail Ledger
            </Link>
        </div>

        <!-- Staff Selector & Date Filter -->
        <div class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-5 mb-6 backdrop-blur-sm shadow-xl shadow-black/20">
            <form @submit.prevent="filterReport" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Select Staff Member *</label>
                    <select
                        v-model="selectedStaffId"
                        required
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky transition"
                    >
                        <option value="" class="bg-[#071322] text-slate-400">-- Choose Staff --</option>
                        <option v-for="s in staffUsers" :key="s.id" :value="s.id" class="bg-[#071322] text-white">{{ s.name }} ({{ s.email }})</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">From Date</label>
                    <input
                        v-model="startDate"
                        type="date"
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-white font-mono focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky transition"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">To Date</label>
                    <input
                        v-model="endDate"
                        type="date"
                        class="w-full rounded-xl border border-brand-navy bg-[#071322] px-3 py-2.5 text-xs text-white font-mono focus:border-brand-sky focus:outline-none focus:ring-1 focus:ring-brand-sky transition"
                    />
                </div>

                <div>
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue hover:from-sky-400 hover:to-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-sky-950/40 transition active:scale-[0.98]"
                    >
                        Generate Staff Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Accountability KPI Cards -->
        <div v-if="stats" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-4 sm:p-5 shadow-lg shadow-black/20">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Collected</div>
                <div class="text-xl sm:text-2xl font-black text-emerald-400 mt-1 sm:mt-1.5 font-mono">
                    ৳{{ stats.total_collections_amount }}
                </div>
                <div class="text-xs text-slate-400 mt-1">{{ stats.total_collections_count }} Transactions</div>
            </div>

            <div class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-4 sm:p-5 shadow-lg shadow-black/20">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Customers Serviced</div>
                <div class="text-xl sm:text-2xl font-black text-sky-400 mt-1 sm:mt-1.5 font-mono">
                    {{ stats.unique_customers_count }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Distinct Subscribers</div>
            </div>

            <div class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-4 sm:p-5 shadow-lg shadow-black/20">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Audited Actions</div>
                <div class="text-xl sm:text-2xl font-black text-amber-400 mt-1 sm:mt-1.5 font-mono">
                    {{ stats.total_audited_actions }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Logged in Audit Trail</div>
            </div>

            <div class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-4 sm:p-5 shadow-lg shadow-black/20">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Staff Identity</div>
                <div class="text-sm sm:text-base font-bold text-white mt-1 sm:mt-1.5 truncate">
                    {{ stats.staff_name }}
                </div>
                <div class="text-xs text-emerald-400 mt-1 flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Active Staff
                </div>
            </div>
        </div>

        <!-- Individual Collections Ledger -->
        <div v-if="stats" class="rounded-2xl border border-brand-navy/60 bg-[#091A2E]/80 p-5 backdrop-blur-sm shadow-xl shadow-black/20">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-sky"></span>
                Individual Transactions by {{ stats.staff_name }}
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-brand-navy bg-[#071322]/80 uppercase font-semibold text-slate-400 whitespace-nowrap">
                        <tr>
                            <th class="px-4 py-3">Receipt #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-navy/60 whitespace-nowrap">
                        <tr v-for="c in collections" :key="c.id" class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono font-bold text-emerald-400">{{ c.payment_number }}</td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-white">{{ c.customer?.name }}</span>
                                <span class="text-slate-400 font-mono ml-1">({{ c.customer?.customer_code }})</span>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-white">৳{{ c.amount }}</td>
                            <td class="px-4 py-3 capitalize">{{ c.payment_method }}</td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ formatDateTime(c.paid_at) }}</td>
                        </tr>
                        <tr v-if="collections.length === 0">
                            <td colspan="5" class="text-center py-6 text-slate-400">No collections during this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
