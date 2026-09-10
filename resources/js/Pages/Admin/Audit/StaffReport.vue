<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

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
                class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-700"
            >
                ← Audit Trail Ledger
            </Link>
        </div>

        <!-- Staff Selector & Date Filter -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 mb-6 backdrop-blur-sm">
            <form @submit.prevent="filterReport" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Select Staff Member *</label>
                    <select
                        v-model="selectedStaffId"
                        required
                        class="w-full rounded-xl border-slate-800 bg-slate-950 p-2.5 text-xs text-white"
                    >
                        <option value="">-- Choose Staff --</option>
                        <option v-for="s in staffUsers" :key="s.id" :value="s.id">{{ s.name }} ({{ s.email }})</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">From Date</label>
                    <input v-model="startDate" type="date" class="w-full rounded-xl border-slate-800 bg-slate-950 p-2.5 text-xs text-white font-mono" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">To Date</label>
                    <input v-model="endDate" type="date" class="w-full rounded-xl border-slate-800 bg-slate-950 p-2.5 text-xs text-white font-mono" />
                </div>

                <div>
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-indigo-600 hover:bg-indigo-500 p-2.5 text-xs font-bold text-white shadow-md shadow-indigo-600/30"
                    >
                        Generate Staff Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Accountability KPI Cards -->
        <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Total Collected</div>
                <div class="text-2xl font-black text-emerald-400 mt-1 font-mono">
                    ৳{{ stats.total_collections_amount }}
                </div>
                <div class="text-xs text-slate-500 mt-1">{{ stats.total_collections_count }} Transactions</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Customers Serviced</div>
                <div class="text-2xl font-black text-indigo-400 mt-1 font-mono">
                    {{ stats.unique_customers_count }}
                </div>
                <div class="text-xs text-slate-500 mt-1">Distinct Subscribers</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Audited Actions</div>
                <div class="text-2xl font-black text-amber-400 mt-1 font-mono">
                    {{ stats.total_audited_actions }}
                </div>
                <div class="text-xs text-slate-500 mt-1">Logged in Audit Trail</div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Staff Identity</div>
                <div class="text-base font-bold text-white mt-1">
                    {{ stats.staff_name }}
                </div>
                <div class="text-xs text-emerald-400 mt-1">Active Staff</div>
            </div>
        </div>

        <!-- Individual Collections Ledger -->
        <div v-if="stats" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 backdrop-blur-sm">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider mb-4">
                Individual Transactions by {{ stats.staff_name }}
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="border-b border-slate-800 bg-slate-950/50 uppercase font-semibold text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Receipt #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <tr v-for="c in collections" :key="c.id" class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-mono font-bold text-emerald-400">{{ c.payment_number }}</td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-white">{{ c.customer?.name }}</span>
                                <span class="text-slate-500 font-mono ml-1">({{ c.customer?.customer_code }})</span>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-white">৳{{ c.amount }}</td>
                            <td class="px-4 py-3 capitalize">{{ c.payment_method }}</td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ c.paid_at }}</td>
                        </tr>
                        <tr v-if="collections.length === 0">
                            <td colspan="5" class="text-center py-6 text-slate-500">No collections during this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
